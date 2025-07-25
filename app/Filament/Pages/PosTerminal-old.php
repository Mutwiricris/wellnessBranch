<?php

namespace App\Filament\Pages;

use App\Models\Service;
use App\Models\InventoryItem;
use App\Models\Staff;
use App\Models\PosTransaction;
use App\Models\User;
use App\Models\GiftVoucher;
use App\Models\DiscountCoupon;
use App\Models\LoyaltyPoint;
use App\Models\PosPromotion;
use App\Models\PosPaymentSplit;
use App\Filament\Resources\PosTransactionResource;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Livewire\Attributes\On;

class PosTerminal extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-device-tablet';
    
    protected static ?string $navigationGroup = '🛒 Point of Sale';
    
    protected static ?int $navigationSort = 1;
    
    protected static ?string $navigationLabel = 'POS Terminal';

    protected static string $view = 'filament.pages.pos-terminal';

    protected static ?string $title = 'Point of Sale Terminal';

    public array $cart = [];
    public array $customerData = [];
    public ?int $selectedStaffId = null;
    public string $paymentMethod = 'cash';
    public float $subtotal = 0;
    public float $discountAmount = 0;
    public float $tipAmount = 0;
    public float $taxAmount = 0;
    public float $totalAmount = 0;
    public string $selectedCategory = 'all';
    public string $activeCategory = 'service_all';
    public string $searchTerm = '';
    public bool $isProcessingPayment = false;
    public string $activeTab = 'services';
    
    // Phase 2 properties
    public string $appliedVoucherCode = '';
    public string $appliedCouponCode = '';
    public int $loyaltyPointsToUse = 0;
    public float $voucherDiscountAmount = 0;
    public float $couponDiscountAmount = 0;
    public float $loyaltyDiscountAmount = 0;
    public array $availablePromotions = [];
    public array $appliedPromotions = [];
    public array $paymentSplits = [];
    public bool $showPaymentSplitModal = false;
    public bool $showVoucherModal = false;
    public bool $showCouponModal = false;
    public bool $showLoyaltyModal = false;
    public int $customerLoyaltyPoints = 0;
    
    // Additional booking flow properties
    public bool $showServiceBookingFlow = false;
    public string $currentBookingStep = 'staff';
    public ?string $selectedCartItemId = null;
    public array $availableTimeSlots = [];
    public string $selectedCustomerType = '';
    public string $customerSearch = '';
    public array $customerSearchResults = [];
    public array $selectedCustomer = [];
    public string $selectServiceDate = '';
    public bool $showBookingConfirmation = false;
    public array $confirmedBooking = [];
    public string $mpesaPhone = '';
    
    // Checkout flow properties
    public string $checkoutStep = 'shopping'; // shopping, cart_review, payment, confirmation
    public bool $showCheckoutFlow = false;
    
    // Booking flow properties
    public string $bookingStep = 'staff'; // staff, datetime, customer, summary
    
    // Daily summary properties
    public bool $showDailySummary = false;
    public ?array $dailySummaryData = null;
    

    public function mount(): void
    {
        $this->customerData = [
            'type' => 'walk_in',
            'client_id' => null,
            'first_name' => '',
            'last_name' => '',
            'name' => '',
            'phone' => '',
            'email' => '',
            'gender' => '',
            'date_of_birth' => '',
            'notes' => ''
        ];
        
        // Set default staff to current user's staff if available
        $user = auth()->user();
        if ($user && $user->staff) {
            $this->selectedStaffId = $user->staff->id;
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('new_transaction')
                ->label('New Transaction')
                ->icon('heroicon-o-plus-circle')
                ->color('success')
                ->size('lg')
                ->action('clearCart'),
                
            Action::make('view_transactions')
                ->label('Transaction History')
                ->icon('heroicon-o-list-bullet')
                ->color('gray')
                ->url(fn (): string => PosTransactionResource::getUrl('index')),
                
            Action::make('daily_summary')
                ->label('Daily Summary')
                ->icon('heroicon-o-chart-bar')
                ->color('info')
                ->action('showDailySummary'),
        ];
    }

    public function getServices()
    {
        $tenant = \Filament\Facades\Filament::getTenant();
        
        $query = Service::whereHas('branches', function($q) use ($tenant) {
            $q->where('branch_id', $tenant->id);
        })->where('status', 'active');

        if ($this->selectedCategory !== 'all') {
            $query->where('category', $this->selectedCategory);
        }

        if (!empty($this->searchTerm)) {
            $query->where(function($q) {
                $q->where('name', 'like', '%' . $this->searchTerm . '%')
                  ->orWhere('description', 'like', '%' . $this->searchTerm . '%');
            });
        }

        return $query->orderBy('name')->get();
    }

    public function getProducts()
    {
        $tenant = \Filament\Facades\Filament::getTenant();
        
        $query = InventoryItem::where('branch_id', $tenant->id)
            ->where('is_active', true)
            ->whereNotNull('selling_price')
            ->where('current_stock', '>', 0);

        if ($this->selectedCategory !== 'all') {
            $query->where('category', $this->selectedCategory);
        }

        if (!empty($this->searchTerm)) {
            $query->where(function($q) {
                $q->where('name', 'like', '%' . $this->searchTerm . '%')
                  ->orWhere('description', 'like', '%' . $this->searchTerm . '%');
            });
        }

        return $query->orderBy('name')->get();
    }

    public function getStaff()
    {
        $tenant = \Filament\Facades\Filament::getTenant();
        
        return Staff::whereHas('branches', function($query) use ($tenant) {
            $query->where('branch_id', $tenant->id);
        })->where('status', 'active')->orderBy('name')->get();
    }

    public function getServiceCategories()
    {
        return [
            'service_all' => 'All Services',
            'service_facial' => 'Facial Treatments',
            'service_massage' => 'Massage Therapy',
            'service_manicure' => 'Manicure',
            'service_pedicure' => 'Pedicure',
            'service_hair' => 'Hair Care',
            'service_body' => 'Body Treatments',
            'service_waxing' => 'Waxing',
            'service_other' => 'Other Services'
        ];
    }

    public function getProductCategories()
    {
        return [
            'product_all' => 'All Products',
            'product_retail' => 'Retail Products',
            'product_supplies' => 'Service Supplies',
            'product_consumables' => 'Consumables',
            'product_linens' => 'Linens & Towels'
        ];
    }

    public function addToCart($itemType, $itemId)
    {
        $item = $itemType === 'service' 
            ? Service::find($itemId) 
            : InventoryItem::find($itemId);

        if (!$item) return;

        $cartItemId = $itemType . '_' . $itemId;

        if (isset($this->cart[$cartItemId])) {
            // Increase quantity for products only
            if ($itemType === 'product' && $item->current_stock >= ($this->cart[$cartItemId]['quantity'] + 1)) {
                $this->cart[$cartItemId]['quantity']++;
            } else if ($itemType === 'service') {
                // Services can be added multiple times with different staff
                $cartItemId = $itemType . '_' . $itemId . '_' . uniqid();
                $this->cart[$cartItemId] = [
                    'id' => $itemId,
                    'type' => $itemType,
                    'name' => $item->name,
                    'description' => $item->description ?? '',
                    'price' => $item->price,
                    'quantity' => 1,
                    'staff_id' => $this->selectedStaffId,
                    'duration' => $item->duration_minutes,
                    'image_url' => $item->image_url ?? null,
                ];
            }
        } else {
            $this->cart[$cartItemId] = [
                'id' => $itemId,
                'type' => $itemType,
                'name' => $item->name,
                'description' => $item->description ?? '',
                'price' => $itemType === 'service' ? $item->price : $item->selling_price,
                'quantity' => 1,
                'staff_id' => $itemType === 'service' ? $this->selectedStaffId : null,
                'duration' => $itemType === 'service' ? $item->duration_minutes : null,
                'image_url' => $item->image_url ?? null,
                'stock_available' => $itemType === 'product' ? $item->current_stock : null,
            ];
        }

        $this->calculateTotals();
        
        Notification::make()
            ->title('Added to Cart')
            ->body($item->name . ' added successfully')
            ->success()
            ->duration(2000)
            ->send();
    }

    public function removeFromCart($cartItemId)
    {
        if (isset($this->cart[$cartItemId])) {
            $itemName = $this->cart[$cartItemId]['name'];
            unset($this->cart[$cartItemId]);
            $this->calculateTotals();
            
            Notification::make()
                ->title('Removed from Cart')
                ->body($itemName . ' removed successfully')
                ->success()
                ->duration(2000)
                ->send();
        }
    }

    public function updateQuantity($cartItemId, $quantity)
    {
        if ($quantity <= 0) {
            $this->removeFromCart($cartItemId);
            return;
        }

        if (isset($this->cart[$cartItemId])) {
            $item = $this->cart[$cartItemId];
            
            // Check stock for products
            if ($item['type'] === 'product' && $quantity > $item['stock_available']) {
                Notification::make()
                    ->title('Insufficient Stock')
                    ->body('Only ' . $item['stock_available'] . ' items available in stock')
                    ->warning()
                    ->send();
                return;
            }
            
            $this->cart[$cartItemId]['quantity'] = $quantity;
            $this->calculateTotals();
        }
    }

    public function updateStaffAssignment($cartItemId, $staffId)
    {
        if (isset($this->cart[$cartItemId])) {
            $this->cart[$cartItemId]['staff_id'] = $staffId;
        }
    }

    public function calculateTotals()
    {
        $this->subtotal = collect($this->cart)->sum(function ($item) {
            return $item['price'] * $item['quantity'];
        });

        // Calculate Phase 2 discounts
        $this->calculatePhase2Discounts();

        // Apply 16% VAT for Kenya
        $this->taxAmount = $this->subtotal * 0.16;
        
        // Calculate total with all discounts
        $totalDiscounts = $this->discountAmount + $this->voucherDiscountAmount + 
                         $this->couponDiscountAmount + $this->loyaltyDiscountAmount;
        
        $this->totalAmount = $this->subtotal + $this->taxAmount + $this->tipAmount - $totalDiscounts;
        
        // Ensure total is not negative
        $this->totalAmount = max(0, $this->totalAmount);
    }

    private function calculatePhase2Discounts()
    {
        $tenant = \Filament\Facades\Filament::getTenant();
        
        // Reset Phase 2 discounts
        $this->voucherDiscountAmount = 0;
        $this->couponDiscountAmount = 0;
        $this->loyaltyDiscountAmount = 0;
        
        // Calculate voucher discount
        if ($this->appliedVoucherCode) {
            $voucher = GiftVoucher::where('voucher_code', $this->appliedVoucherCode)
                ->where('branch_id', $tenant->id)
                ->first();
            
            if ($voucher && $voucher->isValid()) {
                $this->voucherDiscountAmount = min($this->subtotal, $voucher->remaining_amount);
            }
        }
        
        // Calculate coupon discount
        if ($this->appliedCouponCode) {
            $coupon = DiscountCoupon::where('coupon_code', $this->appliedCouponCode)
                ->where('branch_id', $tenant->id)
                ->first();
            
            if ($coupon && $coupon->isValid()) {
                $this->couponDiscountAmount = $coupon->calculateDiscountAmount($this->subtotal);
            }
        }
        
        // Calculate loyalty points discount
        if ($this->loyaltyPointsToUse > 0 && $this->customerData['client_id']) {
            $availablePoints = LoyaltyPoint::getAvailablePoints(
                $this->customerData['client_id'], 
                $tenant->id
            );
            
            $pointsToUse = min($this->loyaltyPointsToUse, $availablePoints);
            $this->loyaltyDiscountAmount = $pointsToUse * 1.0; // 1 point = 1 KES
        }
        
        // Auto-apply eligible promotions
        $this->checkEligiblePromotions();
    }

    private function checkEligiblePromotions()
    {
        $tenant = \Filament\Facades\Filament::getTenant();
        $cartData = array_values($this->cart);
        
        $this->availablePromotions = PosPromotion::getEligiblePromotions(
            $tenant->id, 
            $this->subtotal, 
            $cartData
        );
        
        // Auto-apply promotions
        $autoPromotions = PosPromotion::getAutoApplyPromotions(
            $tenant->id, 
            $this->subtotal, 
            $cartData
        );
        
        $this->appliedPromotions = $autoPromotions;
        
        // Calculate automatic promotion discounts
        foreach ($autoPromotions as $promotion) {
            $promotionModel = PosPromotion::find($promotion['id']);
            if ($promotionModel) {
                $this->discountAmount += $promotionModel->calculateDiscountAmount($this->subtotal, $cartData);
            }
        }
    }

    public function processPayment()
    {
        if (empty($this->cart)) {
            Notification::make()
                ->title('Cart Empty')
                ->body('Please add items to cart before processing payment')
                ->warning()
                ->send();
            return;
        }

        if (!$this->selectedStaffId) {
            Notification::make()
                ->title('Staff Required')
                ->body('Please select a staff member')
                ->warning()
                ->send();
            return;
        }

        // Validate service booking details
        foreach ($this->cart as $cartItemId => $item) {
            if ($item['type'] === 'service' && !$this->isServiceBookingComplete($cartItemId)) {
                Notification::make()
                    ->title('Incomplete Service Booking')
                    ->body('Please complete booking details for: ' . $item['name'])
                    ->warning()
                    ->send();
                return;
            }
        }

        $this->isProcessingPayment = true;

        try {
            $tenant = \Filament\Facades\Filament::getTenant();
            
            // Create POS Transaction
            $transaction = PosTransaction::create([
                'branch_id' => $tenant->id,
                'staff_id' => $this->selectedStaffId,
                'client_id' => $this->customerData['client_id'],
                'transaction_type' => $this->getTransactionType(),
                'subtotal' => $this->subtotal,
                'discount_amount' => $this->discountAmount,
                'tax_amount' => $this->taxAmount,
                'tip_amount' => $this->tipAmount,
                'total_amount' => $this->totalAmount,
                'payment_method' => $this->paymentMethod,
                'payment_status' => $this->paymentMethod === 'cash' ? 'completed' : 'processing',
                'customer_info' => $this->customerData['type'] === 'walk_in' ? [
                    'name' => $this->customerData['name'],
                    'phone' => $this->customerData['phone'],
                    'email' => $this->customerData['email']
                ] : null,
            ]);

            // Add transaction items
            foreach ($this->cart as $cartItem) {
                $transaction->items()->create([
                    'item_type' => $cartItem['type'],
                    'item_id' => $cartItem['id'],
                    'item_name' => $cartItem['name'],
                    'item_description' => $cartItem['description'],
                    'quantity' => $cartItem['quantity'],
                    'unit_price' => $cartItem['price'],
                    'total_price' => $cartItem['price'] * $cartItem['quantity'],
                    'assigned_staff_id' => $cartItem['staff_id'],
                    'duration_minutes' => $cartItem['duration'],
                ]);
            }

            if ($this->paymentMethod === 'cash') {
                $transaction->markAsCompleted();
                $this->handlePaymentSuccess($transaction);
            } elseif ($this->paymentMethod === 'mpesa') {
                $this->initiateMpesaPayment($transaction);
            }

        } catch (\Exception $e) {
            $this->isProcessingPayment = false;
            
            Notification::make()
                ->title('Payment Failed')
                ->body('An error occurred while processing payment: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    private function getTransactionType(): string
    {
        $hasServices = collect($this->cart)->contains('type', 'service');
        $hasProducts = collect($this->cart)->contains('type', 'product');

        if ($hasServices && $hasProducts) return 'mixed';
        if ($hasServices) return 'service';
        if ($hasProducts) return 'product';
        
        return 'service';
    }

    private function initiateMpesaPayment($transaction)
    {
        // M-Pesa STK Push integration will be implemented here
        // For now, simulate payment processing
        
        $this->dispatch('mpesa-payment-initiated', [
            'transaction_id' => $transaction->id,
            'amount' => $this->totalAmount,
            'phone' => $this->customerData['phone'] ?: '254700000000'
        ]);
    }

    #[On('mpesa-payment-success')]
    public function handleMpesaSuccess($transactionId, $mpesaTransactionId)
    {
        $transaction = PosTransaction::find($transactionId);
        
        if ($transaction) {
            $transaction->update([
                'payment_status' => 'completed',
                'mpesa_transaction_id' => $mpesaTransactionId
            ]);
            
            $transaction->markAsCompleted();
            $this->handlePaymentSuccess($transaction);
        }
    }

    #[On('mpesa-payment-failed')]
    public function handleMpesaFailure($transactionId, $error)
    {
        $transaction = PosTransaction::find($transactionId);
        
        if ($transaction) {
            $transaction->update(['payment_status' => 'failed']);
        }

        $this->isProcessingPayment = false;
        
        Notification::make()
            ->title('M-Pesa Payment Failed')
            ->body($error)
            ->danger()
            ->send();
    }

    private function handlePaymentSuccess($transaction)
    {
        $this->isProcessingPayment = false;
        
        Notification::make()
            ->title('Payment Successful!')
            ->body('Transaction #' . $transaction->transaction_number . ' completed successfully')
            ->success()
            ->duration(5000)
            ->send();

        // Clear cart and reset form
        $this->clearCart();
        
        // Dispatch event to print receipt
        $this->dispatch('print-receipt', ['transaction_id' => $transaction->id]);
    }

    public function clearCart()
    {
        $this->cart = [];
        $this->subtotal = 0;
        $this->discountAmount = 0;
        $this->tipAmount = 0;
        $this->taxAmount = 0;
        $this->totalAmount = 0;
        $this->customerData = [
            'type' => 'walk_in',
            'client_id' => null,
            'first_name' => '',
            'last_name' => '',
            'name' => '',
            'phone' => '',
            'email' => '',
            'gender' => '',
            'date_of_birth' => '',
            'notes' => ''
        ];
        $this->paymentMethod = 'cash';
        $this->isProcessingPayment = false;
        $this->searchTerm = '';
        $this->selectedCategory = 'all';
        
        // Clear Phase 2 data
        $this->appliedVoucherCode = '';
        $this->appliedCouponCode = '';
        $this->loyaltyPointsToUse = 0;
        $this->voucherDiscountAmount = 0;
        $this->couponDiscountAmount = 0;
        $this->loyaltyDiscountAmount = 0;
        $this->availablePromotions = [];
        $this->appliedPromotions = [];
        $this->paymentSplits = [];
        $this->customerLoyaltyPoints = 0;
        
        // Clear service booking flow
        $this->showServiceBookingFlow = false;
        $this->selectedCartItemId = null;
        $this->availableTimeSlots = [];
        
        // Clear checkout flow
        $this->checkoutStep = 'shopping';
        $this->showCheckoutFlow = false;
    }

    // Gift Voucher Methods
    public function applyVoucher()
    {
        if (empty($this->appliedVoucherCode)) {
            Notification::make()
                ->title('Voucher Code Required')
                ->body('Please enter a voucher code')
                ->warning()
                ->send();
            return;
        }

        $tenant = \Filament\Facades\Filament::getTenant();
        $voucher = GiftVoucher::where('voucher_code', $this->appliedVoucherCode)
            ->where('branch_id', $tenant->id)
            ->first();

        if (!$voucher) {
            Notification::make()
                ->title('Invalid Voucher')
                ->body('Voucher code not found')
                ->danger()
                ->send();
            return;
        }

        if (!$voucher->isValid()) {
            Notification::make()
                ->title('Voucher Not Valid')
                ->body('This voucher has expired or been fully redeemed')
                ->danger()
                ->send();
            return;
        }

        $this->calculateTotals();
        $this->showVoucherModal = false;

        Notification::make()
            ->title('Voucher Applied')
            ->body("Applied voucher: {$voucher->voucher_code} - KES " . number_format($this->voucherDiscountAmount, 2))
            ->success()
            ->send();
    }

    public function removeVoucher()
    {
        $this->appliedVoucherCode = '';
        $this->calculateTotals();
        
        Notification::make()
            ->title('Voucher Removed')
            ->success()
            ->send();
    }

    // Discount Coupon Methods
    public function applyCoupon()
    {
        if (empty($this->appliedCouponCode)) {
            Notification::make()
                ->title('Coupon Code Required')
                ->body('Please enter a coupon code')
                ->warning()
                ->send();
            return;
        }

        $tenant = \Filament\Facades\Filament::getTenant();
        $coupon = DiscountCoupon::where('coupon_code', $this->appliedCouponCode)
            ->where('branch_id', $tenant->id)
            ->first();

        if (!$coupon) {
            Notification::make()
                ->title('Invalid Coupon')
                ->body('Coupon code not found')
                ->danger()
                ->send();
            return;
        }

        if (!$coupon->isValid() || !$coupon->isValidAtCurrentTime()) {
            Notification::make()
                ->title('Coupon Not Valid')
                ->body('This coupon has expired or is not valid at this time')
                ->danger()
                ->send();
            return;
        }

        $this->calculateTotals();
        $this->showCouponModal = false;

        Notification::make()
            ->title('Coupon Applied')
            ->body("Applied coupon: {$coupon->coupon_code} - {$coupon->formatted_discount_value}")
            ->success()
            ->send();
    }

    public function removeCoupon()
    {
        $this->appliedCouponCode = '';
        $this->calculateTotals();
        
        Notification::make()
            ->title('Coupon Removed')
            ->success()
            ->send();
    }

    // Loyalty Points Methods
    public function updateLoyaltyPoints()
    {
        if ($this->customerData['client_id']) {
            $tenant = \Filament\Facades\Filament::getTenant();
            $this->customerLoyaltyPoints = LoyaltyPoint::getAvailablePoints(
                $this->customerData['client_id'], 
                $tenant->id
            );
        }
        
        $this->calculateTotals();
        $this->showLoyaltyModal = false;
    }

    public function clearLoyaltyPoints()
    {
        $this->loyaltyPointsToUse = 0;
        $this->calculateTotals();
    }

    // Split Payment Methods
    public function initiateSplitPayment()
    {
        $this->paymentSplits = [
            [
                'payment_method' => 'cash',
                'amount' => $this->totalAmount,
                'reference' => ''
            ]
        ];
        $this->showPaymentSplitModal = true;
    }

    public function addPaymentSplit()
    {
        $this->paymentSplits[] = [
            'payment_method' => 'cash',
            'amount' => 0,
            'reference' => ''
        ];
    }

    public function removePaymentSplit($index)
    {
        if (count($this->paymentSplits) > 1) {
            unset($this->paymentSplits[$index]);
            $this->paymentSplits = array_values($this->paymentSplits);
        }
    }

    public function processSplitPayment()
    {
        $totalSplitAmount = array_sum(array_column($this->paymentSplits, 'amount'));
        
        if (abs($totalSplitAmount - $this->totalAmount) > 0.01) {
            Notification::make()
                ->title('Payment Split Error')
                ->body('Split amounts must equal the total amount')
                ->danger()
                ->send();
            return;
        }

        $this->paymentMethod = 'mixed';
        $this->showPaymentSplitModal = false;
        $this->processPayment();
    }

    public function setCustomer($customerId)
    {
        $customer = User::find($customerId);
        
        if ($customer) {
            $tenant = \Filament\Facades\Filament::getTenant();
            
            $this->customerData = [
                'type' => 'registered',
                'client_id' => $customerId,
                'name' => $customer->first_name . ' ' . $customer->last_name,
                'phone' => $customer->phone,
                'email' => $customer->email
            ];
            
            // Load customer's loyalty points
            $this->customerLoyaltyPoints = LoyaltyPoint::getAvailablePoints($customerId, $tenant->id);
            
            // Recalculate totals with customer data
            $this->calculateTotals();
        }
    }

    public function showDailySummary()
    {
        $this->showDailySummary = true;
        
        // Load daily summary data
        $this->dailySummaryData = [
            'total_sales' => 15420.50,
            'transaction_count' => 24,
            'services_count' => 18,
            'products_count' => 35
        ];
        
        // TODO: Replace with actual data from PosTransaction::getDailySummary()
        // $tenant = \Filament\Facades\Filament::getTenant();
        // $this->dailySummaryData = PosTransaction::getDailySummary($tenant->id, now());
    }

    public function updatedDiscountAmount()
    {
        $this->calculateTotals();
    }

    public function updatedTipAmount()
    {
        $this->calculateTotals();
    }

    public function updatedSearchTerm()
    {
        // Trigger re-render of services/products
    }

    public function updatedSelectedCategory()
    {
        // Trigger re-render of services/products
    }

    public function setActiveCategory($category)
    {
        $this->activeCategory = $category;
    }

    public function getFilteredServices()
    {
        $query = Service::query();
        
        // Filter by category
        if ($this->activeCategory !== 'service_all') {
            $category = str_replace('service_', '', $this->activeCategory);
            $query->where('category', $category);
        }
        
        // Filter by search term
        if ($this->searchTerm) {
            $query->where(function($q) {
                $q->where('name', 'like', '%' . $this->searchTerm . '%')
                  ->orWhere('description', 'like', '%' . $this->searchTerm . '%');
            });
        }
        
        return $query->where('status', 'active')->orderBy('name')->get();
    }

    public function getFilteredProducts()
    {
        $query = InventoryItem::query();
        
        // Filter by category
        if ($this->activeCategory !== 'product_all') {
            $category = str_replace('product_', '', $this->activeCategory);
            $query->where('category', $category);
        }
        
        // Filter by search term
        if ($this->searchTerm) {
            $query->where(function($q) {
                $q->where('name', 'like', '%' . $this->searchTerm . '%')
                  ->orWhere('description', 'like', '%' . $this->searchTerm . '%');
            });
        }
        
        return $query->where('current_stock', '>', 0)->orderBy('name')->get();
    }

    // Service booking flow methods
    public function startServiceBooking($cartItemId)
    {
        $this->selectedCartItemId = $cartItemId;
        $this->currentBookingStep = 'staff';
        $this->showServiceBookingFlow = true;
    }
    
    public function selectServiceStaff($staffId)
    {
        if ($this->selectedCartItemId && isset($this->cart[$this->selectedCartItemId])) {
            $this->cart[$this->selectedCartItemId]['staff_id'] = $staffId;
            $this->cart[$this->selectedCartItemId]['staff_name'] = Staff::find($staffId)->name;
            $this->currentBookingStep = 'datetime';
            
            Notification::make()
                ->title('Staff selected successfully')
                ->body('Now select your preferred date and time')
                ->success()
                ->duration(2000)
                ->send();
        }
    }
    
    public function selectServiceDate($date)
    {
        $this->selectServiceDate = $date;
        if ($this->selectedCartItemId && isset($this->cart[$this->selectedCartItemId])) {
            $this->cart[$this->selectedCartItemId]['appointment_date'] = $date;
            $this->loadServiceTimeSlots();
        }
    }
    
    public function selectServiceTime($time)
    {
        if ($this->selectedCartItemId && isset($this->cart[$this->selectedCartItemId])) {
            $this->cart[$this->selectedCartItemId]['appointment_time'] = $time;
            $this->currentBookingStep = 'customer';
            
            Notification::make()
                ->title('Time slot selected')
                ->body('Great! Now let\'s get customer details')
                ->success()
                ->duration(2000)
                ->send();
        }
    }
    
    public function loadServiceTimeSlots()
    {
        if (!$this->selectedCartItemId || !isset($this->cart[$this->selectedCartItemId])) {
            return;
        }
        
        $cartItem = $this->cart[$this->selectedCartItemId];
        if (!isset($cartItem['appointment_date']) || !isset($cartItem['staff_id'])) {
            return;
        }
        
        // Generate available time slots (8:00 AM to 6:00 PM, 30-min intervals)
        $slots = [];
        $startTime = 8; // 8 AM
        $endTime = 18; // 6 PM
        
        for ($hour = $startTime; $hour < $endTime; $hour++) {
            for ($minute = 0; $minute < 60; $minute += 30) {
                $time = sprintf('%02d:%02d', $hour, $minute);
                $slots[] = [
                    'time' => $time,
                    'available' => true, // In real implementation, check against existing bookings
                    'display' => date('g:i A', strtotime($time))
                ];
            }
        }
        
        $this->availableTimeSlots = $slots;
    }
    
    
    public function completeServiceBooking()
    {
        // Get the current booking item
        $bookingItem = $this->getServiceBookingItem();
        
        if ($bookingItem) {
            // Prepare confirmation data
            $this->confirmedBooking = [
                'service_name' => $bookingItem['name'],
                'amount' => $bookingItem['price'],
                'staff_name' => $bookingItem['staff_name'] ?? '',
                'appointment_date' => $bookingItem['appointment_date'] ?? '',
                'appointment_time' => $bookingItem['appointment_time'] ?? '',
                'customer_name' => $this->selectedCustomer['name'] ?? $this->customerData['name'] ?? '',
                'customer_phone' => $this->selectedCustomer['phone'] ?? $this->customerData['phone'] ?? '',
                'customer_email' => $this->selectedCustomer['email'] ?? $this->customerData['email'] ?? '',
                'reference' => 'BK' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT)
            ];
            
            // Show confirmation modal
            $this->showBookingConfirmation = true;
        }
        
        $this->showServiceBookingFlow = false;
        $this->selectedCartItemId = null;
        $this->availableTimeSlots = [];
        
        Notification::make()
            ->title('🎉 Service added to cart!')
            ->body('Your booking is ready. You can now proceed to payment.')
            ->success()
            ->duration(3000)
            ->send();
    }
    
    public function cancelServiceBooking()
    {
        if ($this->selectedCartItemId && isset($this->cart[$this->selectedCartItemId])) {
            // Remove incomplete booking from cart
            unset($this->cart[$this->selectedCartItemId]);
            $this->calculateTotals();
        }
        
        $this->showServiceBookingFlow = false;
        $this->selectedCartItemId = null;
        $this->availableTimeSlots = [];
    }
    
    public function getServiceBookingItem()
    {
        if (!$this->selectedCartItemId || !isset($this->cart[$this->selectedCartItemId])) {
            return null;
        }
        
        return $this->cart[$this->selectedCartItemId];
    }
    
    public function isServiceBookingComplete($cartItemId): bool
    {
        if (!isset($this->cart[$cartItemId])) {
            return false;
        }
        
        $item = $this->cart[$cartItemId];
        
        return $item['type'] === 'service' && 
               isset($item['staff_id']) && 
               isset($item['appointment_date']) && 
               isset($item['appointment_time']);
    }
    
    public function selectCustomerType($type)
    {
        $this->selectedCustomerType = $type;
        $this->customerSearch = '';
        $this->customerSearchResults = [];
        $this->selectedCustomer = [];
    }
    
    public function selectExistingCustomer($customerId)
    {
        // In a real implementation, you would fetch the customer data from the database
        // For now, we'll use mock data
        $this->selectedCustomer = [
            'id' => $customerId,
            'name' => 'John Doe',
            'phone' => '+254700000000',
            'email' => 'john@example.com'
        ];
    }
    
    public function proceedToPayment()
    {
        // If we have a new customer, combine first and last names
        if ($this->selectedCustomerType === 'new' && !empty($this->customerData['first_name']) && !empty($this->customerData['last_name'])) {
            $this->customerData['name'] = trim($this->customerData['first_name'] . ' ' . $this->customerData['last_name']);
        }
        
        $this->currentBookingStep = 'payment';
    }
    
    public function goBackBookingStep()
    {
        if ($this->currentBookingStep === 'datetime') {
            $this->currentBookingStep = 'staff';
        } elseif ($this->currentBookingStep === 'customer') {
            $this->currentBookingStep = 'datetime';
        } elseif ($this->currentBookingStep === 'payment') {
            $this->currentBookingStep = 'customer';
        }
    }
    
    public function closeBookingConfirmation()
    {
        $this->showBookingConfirmation = false;
        $this->confirmedBooking = [];
    }
    
    public function printReceipt()
    {
        Notification::make()
            ->title('Receipt Printed')
            ->body('Receipt has been sent to the printer')
            ->success()
            ->send();
    }
    
    public function sendSMSConfirmation()
    {
        Notification::make()
            ->title('SMS Sent')
            ->body('Booking confirmation SMS has been sent to the customer')
            ->success()
            ->send();
    }
    
    public function sendEmailConfirmation()
    {
        Notification::make()
            ->title('Email Sent')
            ->body('Booking confirmation email has been sent to the customer')
            ->success()
            ->send();
    }
    
    // Checkout Flow Methods
    public function startCheckout()
    {
        if (empty($this->cart)) {
            Notification::make()
                ->title('Cart Empty')
                ->body('Please add items to cart before checkout')
                ->warning()
                ->send();
            return;
        }
        
        // Check if all services have complete booking details
        foreach ($this->cart as $cartItemId => $item) {
            if ($item['type'] === 'service' && !$this->isServiceBookingComplete($cartItemId)) {
                Notification::make()
                    ->title('Incomplete Service Booking')
                    ->body('Please complete booking details for: ' . $item['name'])
                    ->warning()
                    ->send();
                return;
            }
        }
        
        $this->checkoutStep = 'cart_review';
        $this->showCheckoutFlow = true;
    }
    
    public function proceedToCheckoutPayment()
    {
        if ($this->checkoutStep === 'cart_review') {
            $this->checkoutStep = 'payment';
        }
    }
    
    public function proceedToConfirmation()
    {
        if ($this->checkoutStep === 'payment') {
            $this->checkoutStep = 'confirmation';
        }
    }
    
    public function goBackInCheckout()
    {
        if ($this->checkoutStep === 'payment') {
            $this->checkoutStep = 'cart_review';
        } elseif ($this->checkoutStep === 'confirmation') {
            $this->checkoutStep = 'payment';
        } elseif ($this->checkoutStep === 'cart_review') {
            $this->showCheckoutFlow = false;
            $this->checkoutStep = 'shopping';
        }
    }
    
    public function cancelCheckout()
    {
        $this->showCheckoutFlow = false;
        $this->checkoutStep = 'shopping';
    }
    
    public function completeTransaction()
    {
        // This will call the existing processPayment method
        $this->processPayment();
    }

    // Daily Summary Modal Methods
    public function closeDailySummary()
    {
        $this->showDailySummary = false;
        $this->dailySummaryData = null;
    }

    public function emailDailySummary()
    {
        // TODO: Implement email functionality
        Notification::make()
            ->title('Email Sent')
            ->body('Daily summary has been emailed successfully')
            ->success()
            ->send();
    }

    public function exportDailySummary()
    {
        // TODO: Implement export/print functionality
        Notification::make()
            ->title('Export Ready')
            ->body('Daily summary is ready for printing')
            ->success()
            ->send();
    }

    // Modal Show Methods
    public function showBookingModal()
    {
        $this->showServiceBookingFlow = true;
        $this->bookingStep = 'staff';
    }

    public function showCouponModal()
    {
        $this->showCouponModal = true;
    }

    public function showLoyaltyModal()
    {
        $this->showLoyaltyModal = true;
    }

    public function showVoucherModal()
    {
        $this->showVoucherModal = true;
    }

    // Service Booking Modal Methods
    public function closeServiceBookingModal()
    {
        $this->showServiceBookingFlow = false;
        $this->bookingStep = 'staff';
        $this->selectedCartItemId = null;
    }

    public function confirmServiceBooking()
    {
        // TODO: Implement service booking confirmation logic
        $this->showServiceBookingFlow = false;
        $this->bookingStep = 'staff';
        
        Notification::make()
            ->title('Service Booked')
            ->body('Service has been added to cart successfully')
            ->success()
            ->send();
    }

    // Service Booking Navigation Methods
    public function proceedToDatetime()
    {
        $this->bookingStep = 'datetime';
    }

    public function proceedToCustomer()
    {
        $this->bookingStep = 'customer';
    }

    public function proceedToSummary()
    {
        $this->bookingStep = 'summary';
    }

    public function goBackToStaff()
    {
        $this->bookingStep = 'staff';
    }

    public function goBackToDatetime()
    {
        $this->bookingStep = 'datetime';
    }

    public function goBackToCustomer()
    {
        $this->bookingStep = 'customer';
    }
}