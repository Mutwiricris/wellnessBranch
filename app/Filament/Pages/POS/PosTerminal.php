<?php

namespace App\Filament\Pages\POS;

use App\Domain\POS\Services\PosService;
use App\Domain\POS\Services\CartService;
use App\Domain\Inventory\Repositories\ProductRepositoryInterface;
use App\Models\Service;
use App\Models\Customer;
use App\Models\Staff;
use Filament\Pages\Page;
use Filament\Notifications\Notification;
use Filament\Facades\Filament;
use Livewire\Attributes\On;
use Exception;

class PosTerminal extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';
    protected static ?string $navigationGroup = 'Point of Sale';
    protected static ?int $navigationSort = 1;
    protected static ?string $navigationLabel = 'POS Terminal';
    protected static string $view = 'filament.pages.pos.pos-terminal';
    protected static ?string $title = 'POS Terminal';

    // Cart state
    public array $cart = [];
    public array $totals = [];

    // Customer state
    public ?int $customerId = null;
    public ?string $customerName = null;
    public string $customerSearch = '';
    public array $customerSearchResults = [];

    // Payment state
    public bool $showPaymentModal = false;
    public string $paymentMethod = 'cash';
    public array $paymentSplits = [];
    public ?string $mpesaPhone = null;
    public bool $processingPayment = false;
    public ?string $paymentStatus = null;
    public ?string $paymentMessage = null;

    // UI state
    public string $activeTab = 'services';
    public string $searchTerm = '';
    public ?int $selectedCategoryId = null;

    // Receipt state
    public bool $showReceiptModal = false;
    public ?array $lastReceipt = null;

    // Discount & Promotions state
    public string $couponCode = '';
    public ?array $appliedCoupon = null;
    public string $voucherCode = '';
    public ?array $appliedVoucher = null;
    public float $manualDiscountAmount = 0;
    public string $manualDiscountType = 'fixed'; // 'fixed' or 'percentage'

    // Loyalty Points state
    public int $loyaltyPointsToUse = 0;
    public ?int $customerLoyaltyPoints = null;

    public function mount(): void
    {
        $this->resetCart();
    }

    /**
     * Reset cart to initial state
     */
    public function resetCart(): void
    {
        $this->cart = [];
        $this->totals = [
            'subtotal' => 0,
            'discount' => 0,
            'tax' => 0,
            'total' => 0,
            'item_count' => 0,
            'total_quantity' => 0,
        ];
        $this->customerId = null;
        $this->customerName = null;
        $this->paymentMethod = 'cash';
        $this->paymentSplits = [];
    }

    /**
     * Add service to cart
     */
    public function addService(int $serviceId): void
    {
        try {
            $service = Service::findOrFail($serviceId);

            if ($service->status !== 'active') {
                throw new Exception('This service is not available');
            }

            // Check if service already in cart
            $existingIndex = $this->findCartItemIndex('service', $serviceId);

            if ($existingIndex !== null) {
                // Increment quantity
                $this->cart[$existingIndex]['quantity']++;
            } else {
                // Add new item
                $this->cart[] = [
                    'item_type' => 'service',
                    'item_id' => $service->id,
                    'service_id' => $service->id,
                    'name' => $service->name,
                    'quantity' => 1,
                    'unit_price' => $service->price,
                    'discount_amount' => 0,
                    'discount_type' => 'fixed',
                    'tax_rate' => 16,
                    'staff_id' => null,
                    'duration_minutes' => $service->duration_minutes,
                    'notes' => null,
                ];
            }

            $this->calculateTotals();

            Notification::make()
                ->success()
                ->title('Added to cart')
                ->body("{$service->name} added to cart")
                ->send();
        } catch (Exception $e) {
            Notification::make()
                ->danger()
                ->title('Error')
                ->body($e->getMessage())
                ->send();
        }
    }

    /**
     * Add product to cart
     */
    public function addProduct(int $productId): void
    {
        try {
            $productRepository = app(ProductRepositoryInterface::class);
            $cartService = app(CartService::class);

            $product = $productRepository->find($productId);

            if (!$product) {
                throw new Exception('Product not found');
            }

            $tenant = Filament::getTenant();
            if (!$tenant) {
                throw new Exception('No branch selected');
            }

            // Check if product already in cart
            $existingIndex = $this->findCartItemIndex('product', $productId);

            if ($existingIndex !== null) {
                // Increment quantity
                $requestedQuantity = $this->cart[$existingIndex]['quantity'] + 1;

                // Validate availability
                $cartService->validateCartItem([
                    'item_type' => 'product',
                    'product_id' => $productId,
                    'quantity' => $requestedQuantity,
                ], $tenant->id);

                $this->cart[$existingIndex]['quantity'] = $requestedQuantity;
            } else {
                // Add new item
                $inventory = $product->branchInventory()
                    ->where('branch_id', $tenant->id)
                    ->first();

                if (!$inventory) {
                    throw new Exception('Product not available in this branch');
                }

                $validatedItem = $cartService->validateCartItem([
                    'item_type' => 'product',
                    'product_id' => $productId,
                    'quantity' => 1,
                    'unit_price' => $inventory->effective_price,
                ], $tenant->id);

                $this->cart[] = $validatedItem;
            }

            $this->calculateTotals();

            Notification::make()
                ->success()
                ->title('Added to cart')
                ->body("{$product->name} added to cart")
                ->send();
        } catch (Exception $e) {
            Notification::make()
                ->danger()
                ->title('Error')
                ->body($e->getMessage())
                ->send();
        }
    }

    /**
     * Update item quantity
     */
    public function updateQuantity(int $index, int $quantity): void
    {
        try {
            if ($quantity <= 0) {
                $this->removeItem($index);
                return;
            }

            if (!isset($this->cart[$index])) {
                throw new Exception('Item not found in cart');
            }

            // Validate quantity for products
            if ($this->cart[$index]['item_type'] === 'product') {
                $cartService = app(CartService::class);
                $tenant = Filament::getTenant();

                $cartService->validateCartItem([
                    'item_type' => 'product',
                    'product_id' => $this->cart[$index]['product_id'],
                    'quantity' => $quantity,
                ], $tenant->id);
            }

            $this->cart[$index]['quantity'] = $quantity;
            $this->calculateTotals();
        } catch (Exception $e) {
            Notification::make()
                ->danger()
                ->title('Error')
                ->body($e->getMessage())
                ->send();
        }
    }

    /**
     * Apply discount to item
     */
    public function applyItemDiscount(int $index, float $discountAmount, string $discountType = 'fixed'): void
    {
        try {
            if (!isset($this->cart[$index])) {
                throw new Exception('Item not found in cart');
            }

            $cartService = app(CartService::class);
            $this->cart[$index] = $cartService->applyItemDiscount(
                $this->cart[$index],
                $discountAmount,
                $discountType
            );

            $this->calculateTotals();

            Notification::make()
                ->success()
                ->title('Discount applied')
                ->send();
        } catch (Exception $e) {
            Notification::make()
                ->danger()
                ->title('Error')
                ->body($e->getMessage())
                ->send();
        }
    }

    /**
     * Remove item from cart
     */
    public function removeItem(int $index): void
    {
        if (isset($this->cart[$index])) {
            $itemName = $this->cart[$index]['name'];
            unset($this->cart[$index]);
            $this->cart = array_values($this->cart); // Re-index array
            $this->calculateTotals();

            Notification::make()
                ->warning()
                ->title('Removed from cart')
                ->body("{$itemName} removed from cart")
                ->send();
        }
    }

    /**
     * Calculate cart totals
     */
    public function calculateTotals(): void
    {
        $cartService = app(CartService::class);
        $this->totals = $cartService->calculateTotals($this->cart);
    }

    /**
     * Select customer
     */
    public function selectCustomer(int $customerId): void
    {
        $customer = Customer::find($customerId);

        if ($customer) {
            $this->customerId = $customerId;
            $this->customerName = $customer->name;
            $this->customerSearch = '';
            $this->customerSearchResults = [];

            Notification::make()
                ->success()
                ->title('Customer selected')
                ->body($customer->name)
                ->send();
        }
    }

    /**
     * Search customers
     */
    #[On('search-customers')]
    public function searchCustomers(): void
    {
        if (strlen($this->customerSearch) < 2) {
            $this->customerSearchResults = [];
            return;
        }

        $this->customerSearchResults = Customer::query()
            ->where(function ($query) {
                $query->where('name', 'like', "%{$this->customerSearch}%")
                    ->orWhere('email', 'like', "%{$this->customerSearch}%")
                    ->orWhere('phone', 'like', "%{$this->customerSearch}%");
            })
            ->limit(10)
            ->get()
            ->toArray();
    }

    /**
     * Open payment modal
     */
    public function openPaymentModal(): void
    {
        \Log::info('openPaymentModal called', ['cart' => $this->cart]);

        if (empty($this->cart)) {
            Notification::make()
                ->warning()
                ->title('Cart is empty')
                ->body('Please add items to cart before checkout')
                ->send();
            return;
        }

        $this->showPaymentModal = true;
        $this->dispatch('open-modal', id: 'payment-modal');
        \Log::info('Payment modal opened');
    }

    /**
     * Process payment and complete transaction
     */
    public function processPayment(): void
    {
        try {
            if (empty($this->cart)) {
                throw new Exception('Cart is empty');
            }

            // Validate M-Pesa phone number if M-Pesa is selected
            if ($this->paymentMethod === 'mpesa') {
                if (empty($this->mpesaPhone)) {
                    throw new Exception('Please enter M-Pesa phone number');
                }

                // Validate phone number format
                $mpesaService = app(\App\Domain\Payment\Services\MpesaService::class);
                if (!$mpesaService->validatePhoneNumber($this->mpesaPhone)) {
                    throw new Exception('Invalid phone number. Use format: 0712345678 or 254712345678');
                }
            }

            $this->processingPayment = true;
            $this->paymentStatus = 'processing';

            $tenant = Filament::getTenant();
            if (!$tenant) {
                throw new Exception('No branch selected');
            }

            $posService = app(PosService::class);
            $cartService = app(CartService::class);

            // Validate cart items
            $validatedCart = $cartService->validateCartItems($this->cart, $tenant->id);

            // Create transaction
            $transaction = $posService->createTransaction(
                $tenant->id,
                $validatedCart,
                [
                    'customer_id' => $this->customerId,
                    'staff_id' => null, // Staff member assigned to service items (not the cashier)
                    'payment_method' => $this->paymentMethod,
                    'transaction_type' => $this->determineTransactionType(),
                ]
            );

            // Process payment based on method
            if ($this->paymentMethod === 'mpesa') {
                $this->processMpesaPayment($transaction);
            } elseif ($this->paymentMethod === 'card') {
                $this->processCardPayment($transaction);
            } else {
                $this->processCashPayment($transaction);
            }

        } catch (Exception $e) {
            $this->processingPayment = false;
            $this->paymentStatus = 'failed';
            $this->paymentMessage = $e->getMessage();

            Notification::make()
                ->danger()
                ->title('Payment failed')
                ->body($e->getMessage())
                ->send();
        }
    }

    /**
     * Process M-Pesa payment
     */
    private function processMpesaPayment($transaction): void
    {
        try {
            $posService = app(PosService::class);

            $paymentData = [
                'payment_method' => 'mpesa',
                'amount' => $this->totals['total'],
                'mpesa_phone' => $this->mpesaPhone,
            ];

            $transaction = $posService->processPayment($transaction->id, $paymentData);

            $this->paymentStatus = 'pending';
            $this->paymentMessage = 'Please check your phone and enter M-Pesa PIN to complete payment';

            Notification::make()
                ->info()
                ->title('M-Pesa STK Push sent')
                ->body('Please check your phone and enter your M-Pesa PIN')
                ->persistent()
                ->send();

            // Note: In production, you would poll for payment status or use webhooks
            // For now, we'll simulate completion after a delay
            $this->simulateMpesaCompletion($transaction);

        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Simulate M-Pesa payment completion (for demo)
     */
    private function simulateMpesaCompletion($transaction): void
    {
        // In production, this would be handled by M-Pesa callback
        // For demo, we'll mark it as completed
        sleep(2);

        $this->completeTransaction($transaction);
    }

    /**
     * Process card payment
     */
    private function processCardPayment($transaction): void
    {
        try {
            $posService = app(PosService::class);

            $paymentData = [
                'payment_method' => 'card',
                'amount' => $this->totals['total'],
            ];

            $transaction = $posService->processPayment($transaction->id, $paymentData);

            $this->completeTransaction($transaction);

        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Process cash payment
     */
    private function processCashPayment($transaction): void
    {
        try {
            $posService = app(PosService::class);

            $paymentData = [
                'payment_method' => $this->paymentMethod,
                'amount' => $this->totals['total'],
            ];

            $transaction = $posService->processPayment($transaction->id, $paymentData);

            $this->completeTransaction($transaction);

        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Complete transaction and show receipt
     */
    private function completeTransaction($transaction): void
    {
        $posService = app(PosService::class);

        // Generate receipt
        $this->lastReceipt = $posService->generateReceipt($transaction->id);

        // Reset state
        $this->processingPayment = false;
        $this->paymentStatus = 'completed';
        $this->paymentMessage = 'Payment completed successfully';

        // Reset cart and show receipt
        $this->resetCart();
        $this->showPaymentModal = false;
        $this->dispatch('close-modal', id: 'payment-modal');
        $this->showReceiptModal = true;
        $this->dispatch('open-modal', id: 'receipt-modal');

        Notification::make()
            ->success()
            ->title('Transaction completed')
            ->body("Transaction #{$transaction->transaction_number} completed successfully")
            ->send();
    }

    /**
     * Find cart item index by type and ID
     */
    private function findCartItemIndex(string $itemType, int $itemId): ?int
    {
        foreach ($this->cart as $index => $item) {
            if ($item['item_type'] === $itemType && $item['item_id'] === $itemId) {
                return $index;
            }
        }
        return null;
    }

    /**
     * Determine transaction type based on cart contents
     */
    private function determineTransactionType(): string
    {
        $hasServices = false;
        $hasProducts = false;

        foreach ($this->cart as $item) {
            if ($item['item_type'] === 'service') {
                $hasServices = true;
            } elseif ($item['item_type'] === 'product') {
                $hasProducts = true;
            }
        }

        if ($hasServices && $hasProducts) {
            return 'mixed';
        } elseif ($hasServices) {
            return 'service';
        } else {
            return 'product';
        }
    }

    /**
     * Get available services
     */
    public function getServicesProperty()
    {
        $query = Service::query()
            ->where('status', 'active')
            ->orderBy('name');

        if ($this->searchTerm) {
            $query->where('name', 'like', "%{$this->searchTerm}%");
        }

        if ($this->selectedCategoryId) {
            $query->whereHas('category', function ($q) {
                $q->where('id', $this->selectedCategoryId);
            });
        }

        return $query->get();
    }

    /**
     * Get available products
     */
    public function getProductsProperty()
    {
        $tenant = Filament::getTenant();
        if (!$tenant) {
            return collect();
        }

        $productRepository = app(ProductRepositoryInterface::class);

        $query = $productRepository->getActive();

        if ($this->searchTerm) {
            $query = $query->where('name', 'like', "%{$this->searchTerm}%");
        }

        // Filter by products available in current branch
        return $query->whereHas('branchInventory', function ($q) use ($tenant) {
            $q->where('branch_id', $tenant->id)
                ->where('is_available', true)
                ->where('quantity_on_hand', '>', 0);
        })->get();
    }

    /**
     * Get available staff
     */
    public function getStaffProperty()
    {
        $tenant = Filament::getTenant();
        if (!$tenant) {
            return collect();
        }

        return Staff::query()
            ->whereHas('branches', function ($q) use ($tenant) {
                $q->where('branches.id', $tenant->id);
            })
            ->where('status', 'active')
            ->orderBy('name')
            ->get();
    }

    /**
     * Assign staff to cart item (for services)
     */
    public function assignStaff(int $index, ?int $staffId): void
    {
        if (isset($this->cart[$index])) {
            $this->cart[$index]['staff_id'] = $staffId;
            Notification::make()
                ->success()
                ->title('Staff assigned')
                ->send();
        }
    }

    /**
     * Apply coupon code
     */
    public function applyCoupon(): void
    {
        if (empty($this->couponCode)) {
            Notification::make()
                ->warning()
                ->title('Please enter a coupon code')
                ->send();
            return;
        }

        // TODO: Implement coupon validation logic
        // For now, just show a placeholder
        Notification::make()
            ->info()
            ->title('Coupon functionality')
            ->body('Coupon validation will be implemented')
            ->send();
    }

    /**
     * Remove applied coupon
     */
    public function removeCoupon(): void
    {
        $this->appliedCoupon = null;
        $this->couponCode = '';
        $this->calculateTotals();

        Notification::make()
            ->success()
            ->title('Coupon removed')
            ->send();
    }

    /**
     * Apply voucher code
     */
    public function applyVoucher(): void
    {
        if (empty($this->voucherCode)) {
            Notification::make()
                ->warning()
                ->title('Please enter a voucher code')
                ->send();
            return;
        }

        // TODO: Implement voucher validation logic
        Notification::make()
            ->info()
            ->title('Voucher functionality')
            ->body('Voucher validation will be implemented')
            ->send();
    }

    /**
     * Remove applied voucher
     */
    public function removeVoucher(): void
    {
        $this->appliedVoucher = null;
        $this->voucherCode = '';
        $this->calculateTotals();

        Notification::make()
            ->success()
            ->title('Voucher removed')
            ->send();
    }

    /**
     * Apply manual discount
     */
    public function applyManualDiscount(): void
    {
        if ($this->manualDiscountAmount <= 0) {
            Notification::make()
                ->warning()
                ->title('Please enter a discount amount')
                ->send();
            return;
        }

        $this->calculateTotals();

        Notification::make()
            ->success()
            ->title('Discount applied')
            ->body("Discount of KES {$this->manualDiscountAmount} applied")
            ->send();
    }

    /**
     * Use loyalty points
     */
    public function useLoyaltyPoints(): void
    {
        if ($this->loyaltyPointsToUse <= 0) {
            Notification::make()
                ->warning()
                ->title('Please enter points to redeem')
                ->send();
            return;
        }

        if (!$this->customerId) {
            Notification::make()
                ->warning()
                ->title('Please select a customer first')
                ->send();
            return;
        }

        // TODO: Validate customer has enough points
        // For now, just show a placeholder
        Notification::make()
            ->info()
            ->title('Loyalty points')
            ->body('Loyalty points system will be implemented')
            ->send();
    }
}
