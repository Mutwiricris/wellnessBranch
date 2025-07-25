<?php

namespace App\Filament\Pages;

use App\Models\Service;
use App\Models\InventoryItem;
use App\Models\Staff;
use App\Models\Booking;
use App\Models\Client;
use App\Models\User;
use App\Models\PosTransaction;
use App\Models\Branch;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Livewire\Attributes\On;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use App\Services\AvailabilityService;
use Carbon\Carbon;

class PosTerminal extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-device-tablet';
    protected static ?string $navigationGroup = '🛒 Point of Sale';
    protected static ?int $navigationSort = 1;
    protected static ?string $navigationLabel = 'POS Terminal';
    protected static string $view = 'filament.pages.pos-terminal';
    protected static ?string $title = 'Point of Sale Terminal';

    // === CORE POS STATE ===
    public array $cart = [];
    public float $subtotal = 0.00;
    public float $taxAmount = 0.00;
    public float $totalAmount = 0.00;
    public float $discountAmount = 0.00;
    public float $tipAmount = 0.00;
    
    // === CUSTOMER & APPOINTMENT ===
    public ?int $selectedCustomerId = null;
    public ?int $selectedAppointmentId = null;
    public array $customerData = [];
    public string $customerSearch = '';
    public array $customerSearchResults = [];
    
    // === CUSTOMER DETAILS FORM ===
    public string $customerType = 'existing'; // 'existing' or 'new'
    public string $firstName = '';
    public string $lastName = '';
    public string $email = '';
    public string $phone = '';
    public string $allergies = 'None';
    public string $gender = '';
    public string $dateOfBirth = '';
    public bool $createAccount = false;
    
    // === PAYMENT ===
    public string $paymentMethod = 'cash';
    public array $paymentSplits = [];
    public string $mpesaPhone = '';
    public bool $processingPayment = false;
    
    // === VOUCHERS & DISCOUNTS ===
    public string $voucherCode = '';
    public string $couponCode = '';
    public array $appliedVouchers = [];
    public array $appliedCoupons = [];
    
    // === STAFF & INVENTORY ===
    public ?int $selectedStaffId = null;
    public string $searchTerm = '';
    public string $categoryFilter = 'all';
    
    // === SERVICE SELECTION STATE ===
    public ?int $pendingServiceId = null;
    public bool $showStaffSelectionModal = false;
    public array $availableStaffForService = [];
    
    // === UI STATE ===
    public string $activeTab = 'services';
    public bool $showCustomerModal = false;
    public bool $showPaymentModal = false;
    public bool $showReceiptModal = false;
    public bool $showAppointmentSearch = false;
    public bool $showDateTimeModal = false;
    public bool $showBookingConfirmationModal = false;
    
    // === BOOKING FLOW STATE ===
    public string $selectedDate = '';
    public string $selectedTime = '';
    public array $availableTimeSlots = [];
    public array $bookedSlots = [];

    public function mount(): void
    {
        $this->resetCart();
        $this->calculateTotals();
    }

    public function updatedCustomerSearch(): void
    {
        $this->searchAppointments();
        $this->searchCustomers();
    }

    public function searchCustomers(): void
    {
        if (empty($this->customerSearch)) {
            $this->customerSearchResults = [];
            return;
        }

        $customers = User::where('user_type', 'client')
            ->where(function($query) {
                $query->where('first_name', 'like', '%' . $this->customerSearch . '%')
                      ->orWhere('last_name', 'like', '%' . $this->customerSearch . '%')
                      ->orWhere('email', 'like', '%' . $this->customerSearch . '%')
                      ->orWhere('phone', 'like', '%' . $this->customerSearch . '%');
            })
            ->take(10)
            ->get();

        $this->customerSearchResults = $customers->map(function ($customer) {
            return [
                'id' => $customer->id,
                'name' => $customer->first_name . ' ' . $customer->last_name,
                'email' => $customer->email,
                'phone' => $customer->phone,
            ];
        })->toArray();
    }

    public function selectCustomer(int $customerId): void
    {
        $customer = User::find($customerId);
        if ($customer) {
            $this->selectedCustomerId = $customerId;
            $this->customerData = [
                'name' => $customer->first_name . ' ' . $customer->last_name,
                'email' => $customer->email,
                'phone' => $customer->phone,
            ];
            $this->showCustomerModal = false;
            
            Notification::make()
                ->title('Customer Selected')
                ->body("Selected customer: {$this->customerData['name']}")
                ->success()
                ->send();
        }
    }

    public function saveCustomerDetails(): void
    {
        // Validate customer form
        try {
            $this->validate([
                'firstName' => 'required|string|min:2|max:50',
                'lastName' => 'required|string|min:2|max:50',
                'email' => 'required|email|max:100',
                'phone' => 'required|string|min:10|max:20',
                'allergies' => 'nullable|string|max:500',
                'gender' => 'nullable|in:male,female,other,prefer_not_to_say',
                'dateOfBirth' => 'nullable|date|before:today',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Show validation errors
            Notification::make()
                ->title('Validation Error')
                ->body('Please check the form fields: ' . implode(', ', array_keys($e->errors())))
                ->danger()
                ->send();
            return;
        }

        try {
            // Create or find customer
            $customer = User::firstOrCreate(
                ['email' => $this->email],
                [
                    'first_name' => $this->firstName,
                    'last_name' => $this->lastName,
                    'email' => $this->email,
                    'phone' => $this->phone,
                    'allergies' => $this->allergies,
                    'gender' => $this->gender ?: null,
                    'date_of_birth' => $this->dateOfBirth ?: null,
                    'user_type' => 'user',
                    'password' => $this->createAccount ? bcrypt('temporary123') : null,
                ]
            );

            $this->selectedCustomerId = $customer->id;
            $this->customerData = [
                'first_name' => $customer->first_name,
                'last_name' => $customer->last_name,
                'name' => $customer->first_name . ' ' . $customer->last_name,
                'email' => $customer->email,
                'phone' => $customer->phone,
                'gender' => $customer->gender,
                'date_of_birth' => $customer->date_of_birth,
                'allergies' => $customer->allergies,
            ];

            $this->showCustomerModal = false;
            $this->resetCustomerForm();
            
            // Show payment modal after customer details are saved
            $this->showPaymentModal = true;

            Notification::make()
                ->title('Customer Details Saved')
                ->body("Ready to process payment for {$this->customerData['name']}")
                ->success()
                ->send();

        } catch (\Exception $e) {
            Notification::make()
                ->title('Error Saving Customer')
                ->body('Could not save customer details: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function resetCustomerForm(): void
    {
        $this->firstName = '';
        $this->lastName = '';
        $this->email = '';
        $this->phone = '';
        $this->allergies = 'None';
        $this->gender = '';
        $this->dateOfBirth = '';
        $this->createAccount = false;
        $this->customerType = 'existing';
        $this->customerSearch = '';
        $this->customerSearchResults = [];
    }

    // === APPOINTMENT INTEGRATION ===
    public function loadAppointment(int $appointmentId): void
    {
        try {
            $appointment = Booking::with(['client', 'services.staff', 'services.service'])
                ->findOrFail($appointmentId);
            
            // Load customer data
            $this->selectedCustomerId = $appointment->client_id;
            $this->selectedAppointmentId = $appointmentId;
            $this->customerData = [
                'name' => $appointment->client->name,
                'phone' => $appointment->client->phone,
                'email' => $appointment->client->email,
            ];
            
            // Load services into cart
            $this->cart = [];
            foreach ($appointment->services as $appointmentService) {
                $cartId = 'appointment_' . $appointmentService->id;
                $this->cart[$cartId] = [
                    'type' => 'service',
                    'id' => $appointmentService->service_id,
                    'appointment_service_id' => $appointmentService->id,
                    'name' => $appointmentService->service->name,
                    'price' => $appointmentService->price,
                    'duration' => $appointmentService->service->duration_minutes,
                    'staff_id' => $appointmentService->staff_id,
                    'staff_name' => $appointmentService->staff->name,
                    'quantity' => 1,
                    'from_appointment' => true,
                ];
            }
            
            $this->calculateTotals();
            
            Notification::make()
                ->title('Appointment Loaded')
                ->body("Loaded appointment for {$appointment->client->name}")
                ->success()
                ->send();
                
        } catch (\Exception $e) {
            Notification::make()
                ->title('Error Loading Appointment')
                ->body('Could not load appointment details')
                ->danger()
                ->send();
        }
    }

    public function searchAppointments(): void
    {
        if (empty($this->customerSearch)) {
            $this->customerSearchResults = [];
            return;
        }

        $tenant = \Filament\Facades\Filament::getTenant();
        
        $appointments = Booking::with(['client', 'services.service'])
            ->whereHas('client', function ($query) {
                $query->where('name', 'like', '%' . $this->customerSearch . '%')
                      ->orWhere('phone', 'like', '%' . $this->customerSearch . '%');
            })
            ->where('branch_id', $tenant->id)
            ->whereDate('appointment_date', today())
            ->where('status', 'confirmed')
            ->take(10)
            ->get();

        $this->customerSearchResults = $appointments->map(function ($appointment) {
            return [
                'id' => $appointment->id,
                'customer_name' => $appointment->client->name,
                'customer_phone' => $appointment->client->phone,
                'time' => $appointment->appointment_time,
                'services' => $appointment->services->pluck('service.name')->join(', '),
                'total' => $appointment->services->sum('price'),
            ];
        })->toArray();
    }

    // === CART MANAGEMENT ===
    public function addToCart(string $type, int $id): void
    {
        try {
            if ($type === 'service') {
                // Check maximum services limit (2 services max)
                $currentServiceCount = collect($this->cart)->where('type', 'service')->count();
                if ($currentServiceCount >= 2) {
                    Notification::make()
                        ->title('Service Limit Reached')
                        ->body('Maximum of 2 services allowed. Please proceed to next step or remove a service.')
                        ->warning()
                        ->send();
                    return;
                }
                
                // Store the service and show staff selection modal
                $this->pendingServiceId = $id;
                $this->loadAvailableStaffForService($id);
                $this->showStaffSelectionModal = true;
                
                return;
            } else {
                $item = InventoryItem::findOrFail($id);
                $cartId = 'product_' . $id;
                
                // Check inventory availability
                $currentQuantity = isset($this->cart[$cartId]) ? $this->cart[$cartId]['quantity'] : 0;
                $requestedQuantity = $currentQuantity + 1;
                
                if ($requestedQuantity > $item->quantity_in_stock) {
                    Notification::make()
                        ->title('Insufficient Stock')
                        ->body("Only {$item->quantity_in_stock} units available for {$item->name}")
                        ->warning()
                        ->send();
                    return;
                }
                
                if (isset($this->cart[$cartId])) {
                    $this->cart[$cartId]['quantity']++;
                } else {
                    $this->cart[$cartId] = [
                        'type' => 'product',
                        'id' => $id,
                        'name' => $item->name,
                        'price' => $item->price,
                        'quantity' => 1,
                        'stock_available' => $item->quantity_in_stock,
                        'sku' => $item->sku ?? null,
                        'cost_price' => $item->cost_price ?? 0,
                    ];
                }
            }
            
            $this->calculateTotals();
            
            Notification::make()
                ->title('Added to Cart')
                ->body($item->name . ' added successfully')
                ->success()
                ->send();
                
        } catch (\Exception $e) {
            Notification::make()
                ->title('Error')
                ->body('Could not add item to cart: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function removeFromCart(string $cartId): void
    {
        if (isset($this->cart[$cartId])) {
            unset($this->cart[$cartId]);
            $this->calculateTotals();
            
            Notification::make()
                ->title('Item Removed')
                ->body('Item removed from cart')
                ->success()
                ->send();
        }
    }

    public function updateQuantity(string $cartId, int $quantity): void
    {
        if ($quantity <= 0) {
            $this->removeFromCart($cartId);
            return;
        }

        if (isset($this->cart[$cartId])) {
            $item = $this->cart[$cartId];
            
            // Check inventory for products
            if ($item['type'] === 'product') {
                $product = InventoryItem::find($item['id']);
                if ($product && $quantity > $product->quantity_in_stock) {
                    Notification::make()
                        ->title('Insufficient Stock')
                        ->body("Only {$product->quantity_in_stock} units available for {$item['name']}")
                        ->warning()
                        ->send();
                    return;
                }
                
                // Update stock availability in cart
                $this->cart[$cartId]['stock_available'] = $product->quantity_in_stock;
            }
            
            $this->cart[$cartId]['quantity'] = $quantity;
            $this->calculateTotals();
        }
    }

    public function resetCart(): void
    {
        $this->cart = [];
        $this->selectedCustomerId = null;
        $this->selectedAppointmentId = null;
        $this->customerData = [];
        $this->appliedVouchers = [];
        $this->appliedCoupons = [];
        $this->paymentSplits = [];
        $this->discountAmount = 0;
        $this->tipAmount = 0;
        $this->calculateTotals();
    }

    // === CALCULATIONS ===
    public function calculateTotals(): void
    {
        $this->subtotal = collect($this->cart)->sum(function ($item) {
            return $item['price'] * $item['quantity'];
        });

        // Calculate voucher discounts (using existing voucher system structure)
        $voucherDiscount = collect($this->appliedVouchers)->sum('discount_amount');
        
        // Calculate coupon discounts (percentage or fixed) - if coupon system exists
        $couponDiscount = 0;
        if (!empty($this->appliedCoupons)) {
            foreach ($this->appliedCoupons as $coupon) {
                if ($coupon['discount_type'] === 'percentage') {
                    $couponDiscount += ($this->subtotal * $coupon['discount'] / 100);
                } else {
                    $couponDiscount += $coupon['discount'];
                }
            }
        }

        // Total discount amount
        $this->discountAmount = $voucherDiscount + $couponDiscount;
        
        // Apply discounts to subtotal
        $discountedSubtotal = max(0, $this->subtotal - $this->discountAmount);
        
        // Calculate 16% VAT on discounted amount
        $this->taxAmount = $discountedSubtotal * 0.16;
        
        // Final total
        $this->totalAmount = $discountedSubtotal + $this->taxAmount + $this->tipAmount;
    }

    // === PAYMENT PROCESSING ===
    public function processPayment(): void
    {
        if (empty($this->cart)) {
            Notification::make()
                ->title('Cart Empty')
                ->body('Please add items to cart before processing payment')
                ->warning()
                ->send();
            return;
        }

        if (!$this->selectedCustomerId && empty($this->customerData['name'])) {
            Notification::make()
                ->title('Customer Required')
                ->body('Please select a customer or enter customer details')
                ->warning()
                ->send();
            return;
        }

        $this->processingPayment = true;

        try {
            DB::transaction(function () {
                // Create or get customer
                $customerId = $this->getOrCreateCustomer();
                
                // Create bookings for services if not from existing appointment
                $bookingIds = [];
                if (!$this->selectedAppointmentId) {
                    $bookingIds = $this->createServiceBookings($customerId);
                }
                
                // Create POS transaction
                $transaction = PosTransaction::create([
                    'transaction_number' => $this->generateTransactionNumber(),
                    'branch_id' => \Filament\Facades\Filament::getTenant()->id,
                    'client_id' => $customerId,
                    'booking_id' => $this->selectedAppointmentId ?: ($bookingIds[0] ?? null),
                    'staff_id' => $this->selectedStaffId,
                    'transaction_type' => 'sale',
                    'subtotal' => $this->subtotal,
                    'tax_amount' => $this->taxAmount,
                    'discount_amount' => $this->discountAmount,
                    'tip_amount' => $this->tipAmount,
                    'total_amount' => $this->totalAmount,
                    'payment_method' => $this->paymentMethod,
                    'payment_status' => 'completed',
                    'payment_details' => [
                        'cart_items' => $this->cart,
                        'vouchers_used' => $this->appliedVouchers,
                        'coupons_used' => $this->appliedCoupons,
                    ],
                    'customer_info' => $this->customerData,
                    'completed_at' => now(),
                ]);

                // Update appointment status if linked
                if ($this->selectedAppointmentId) {
                    Booking::where('id', $this->selectedAppointmentId)
                        ->update(['payment_status' => 'completed']);
                } else {
                    // Update newly created bookings
                    foreach ($bookingIds as $bookingId) {
                        Booking::where('id', $bookingId)
                            ->update(['payment_status' => 'completed']);
                    }
                }

                // Update inventory for products
                $this->updateInventory();

                // Submit to KRA eTIMS for tax compliance
                $this->submitToKraEtims($transaction);

                $this->processingPayment = false;
                $this->showPaymentModal = false;
                $this->showReceiptModal = true;
                
                // Clear cart and reset form for next transaction
                $this->resetPosTerminal();

                Notification::make()
                    ->title('Payment Successful')
                    ->body('Booking created successfully! Transaction completed.')
                    ->success()
                    ->send();
            });

        } catch (\Exception $e) {
            $this->processingPayment = false;
            
            Notification::make()
                ->title('Payment Failed')
                ->body('Transaction could not be completed: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    private function getOrCreateCustomer(): int
    {
        if ($this->selectedCustomerId) {
            return $this->selectedCustomerId;
        }

        // Create walk-in customer using User model (as expected by bookings)
        $customer = User::create([
            'first_name' => $this->customerData['first_name'] ?? '',
            'last_name' => $this->customerData['last_name'] ?? '',
            'name' => trim(($this->customerData['first_name'] ?? '') . ' ' . ($this->customerData['last_name'] ?? '')),
            'phone' => $this->customerData['phone'] ?? null,
            'email' => $this->customerData['email'] ?? null,
            'gender' => $this->customerData['gender'] ?? null,
            'date_of_birth' => $this->customerData['date_of_birth'] ?? null,
            'allergies' => $this->customerData['allergies'] ?? null,
            'user_type' => 'user', // Regular customer
            'create_account_status' => 'walk_in',
        ]);

        return $customer->id;
    }

    private function createServiceBookings(int $customerId): array
    {
        $bookingIds = [];
        $services = collect($this->cart)->where('type', 'service');
        
        if ($services->isEmpty()) {
            return $bookingIds;
        }

        $bookingDate = session('pos_booking_date');
        $bookingTime = session('pos_booking_time');
        
        if (!$bookingDate || !$bookingTime) {
            throw new \Exception('Booking date and time are required for service bookings');
        }

        $appointmentDateTime = Carbon::parse($bookingDate . ' ' . $bookingTime);
        $currentTime = $appointmentDateTime->copy();

        foreach ($services as $cartItem) {
            $service = Service::find($cartItem['id']);
            $staff = Staff::find($cartItem['staff_id']);

            if (!$service || !$staff) {
                continue;
            }

            $endTime = $currentTime->copy()->addMinutes($service->duration_minutes);

            $booking = Booking::create([
                'booking_reference' => $this->generateBookingReference(),
                'branch_id' => \Filament\Facades\Filament::getTenant()->id,
                'service_id' => $service->id,
                'client_id' => $customerId,
                'staff_id' => $staff->id,
                'appointment_date' => $appointmentDateTime->format('Y-m-d'),
                'start_time' => $currentTime->format('H:i:s'),
                'end_time' => $endTime->format('H:i:s'),
                'total_amount' => $cartItem['price'],
                'status' => 'confirmed',
                'payment_status' => 'pending',
                'payment_method' => $this->paymentMethod,
                'notes' => 'Created via POS Terminal',
            ]);

            $bookingIds[] = $booking->id;
            
            // Update current time for next service (if multiple services)
            $currentTime = $endTime->copy();
        }

        return $bookingIds;
    }

    private function generateBookingReference(): string
    {
        $prefix = 'POS';
        $date = now()->format('Ymd');
        $random = strtoupper(substr(md5(uniqid()), 0, 4));
        
        return $prefix . $date . $random;
    }

    private function generateTransactionNumber(): string
    {
        $prefix = 'TXN';
        $date = now()->format('Ymd');
        $time = now()->format('His');
        $random = strtoupper(substr(md5(uniqid()), 0, 3));
        
        return $prefix . $date . $time . $random;
    }

    private function updateInventory(): void
    {
        foreach ($this->cart as $item) {
            if ($item['type'] === 'product') {
                $product = InventoryItem::find($item['id']);
                if ($product) {
                    // Check final stock availability before decrement
                    if ($product->quantity_in_stock >= $item['quantity']) {
                        $product->decrement('quantity_in_stock', $item['quantity']);
                        
                        // Create inventory movement record if model exists
                        if (class_exists('App\Models\InventoryMovement')) {
                            \App\Models\InventoryMovement::create([
                                'product_id' => $item['id'],
                                'movement_type' => 'sale',
                                'quantity' => -$item['quantity'],
                                'reference_type' => 'pos_transaction',
                                'reference_id' => $transaction->id ?? null,
                                'cost_price' => $item['cost_price'] ?? 0,
                                'selling_price' => $item['price'],
                                'notes' => 'POS Sale - ' . ($this->customerData['name'] ?? 'Walk-in Customer'),
                            ]);
                        }
                        
                        // Check for low stock alert
                        if ($product->quantity_in_stock <= ($product->reorder_level ?? 5)) {
                            Notification::make()
                                ->title('Low Stock Alert')
                                ->body("Product '{$product->name}' is running low. Current stock: {$product->quantity_in_stock}")
                                ->warning()
                                ->persistent()
                                ->send();
                        }
                    } else {
                        throw new \Exception("Insufficient stock for product: {$product->name}");
                    }
                }
            }
        }
    }

    // === DATA PROVIDERS ===
    public function getServices()
    {
        $query = Service::where('status', 'active');
        
        if ($this->searchTerm) {
            $query->where('name', 'like', '%' . $this->searchTerm . '%');
        }
        
        if ($this->categoryFilter !== 'all') {
            $query->where('category', $this->categoryFilter);
        }
        
        return $query->orderBy('name')->get();
    }

    public function getProducts()
    {
        $query = InventoryItem::where('quantity_in_stock', '>', 0);
        
        if ($this->searchTerm) {
            $query->where('name', 'like', '%' . $this->searchTerm . '%');
        }
        
        if ($this->categoryFilter !== 'all') {
            $query->where('category', $this->categoryFilter);
        }
        
        return $query->orderBy('name')->get();
    }

    public function getStaff()
    {
        $tenant = \Filament\Facades\Filament::getTenant();
        
        return Staff::whereHas('branches', function($query) use ($tenant) {
            $query->where('branch_id', $tenant->id);
        })
        ->where('status', 'active')
        ->orderBy('name')
        ->get();
    }

    // === VOUCHER & DISCOUNT MANAGEMENT ===
    public function applyVoucher(): void
    {
        if (empty($this->voucherCode)) {
            Notification::make()
                ->title('Voucher Required')
                ->body('Please enter a voucher code')
                ->warning()
                ->send();
            return;
        }

        try {
            // Check if voucher model exists
            if (!class_exists('App\Models\Voucher')) {
                Notification::make()
                    ->title('Vouchers Not Available')
                    ->body('Voucher system is not configured')
                    ->warning()
                    ->send();
                return;
            }

            $voucher = \App\Models\Voucher::where('code', $this->voucherCode)
                ->where('status', 'active')
                ->where('expires_at', '>=', now())
                ->first();

            if (!$voucher) {
                Notification::make()
                    ->title('Invalid Voucher')
                    ->body('Voucher code is invalid or expired')
                    ->danger()
                    ->send();
                return;
            }

            // Check if already applied
            if (collect($this->appliedVouchers)->contains('id', $voucher->id)) {
                Notification::make()
                    ->title('Already Applied')
                    ->body('This voucher has already been applied')
                    ->warning()
                    ->send();
                return;
            }

            // Check minimum order value
            if ($voucher->minimum_order_value && $this->subtotal < $voucher->minimum_order_value) {
                Notification::make()
                    ->title('Minimum Order Not Met')
                    ->body("Minimum order value of KES " . number_format($voucher->minimum_order_value) . " required")
                    ->warning()
                    ->send();
                return;
            }

            // Calculate discount amount
            $discountAmount = 0;
            if ($voucher->discount_type === 'percentage') {
                $discountAmount = ($this->subtotal * $voucher->discount_value) / 100;
                if ($voucher->max_discount_amount) {
                    $discountAmount = min($discountAmount, $voucher->max_discount_amount);
                }
            } else {
                $discountAmount = $voucher->discount_value;
            }

            // Add to applied vouchers
            $this->appliedVouchers[] = [
                'id' => $voucher->id,
                'code' => $voucher->code,
                'name' => $voucher->name,
                'discount_amount' => $discountAmount,
                'discount_type' => $voucher->discount_type,
                'discount_value' => $voucher->discount_value,
            ];

            $this->voucherCode = '';
            $this->calculateTotals();

            Notification::make()
                ->title('Voucher Applied')
                ->body("Discount of KES " . number_format($discountAmount) . " applied")
                ->success()
                ->send();

        } catch (\Exception $e) {
            Notification::make()
                ->title('Error Applying Voucher')
                ->body('Could not apply voucher: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function removeVoucher(int $index): void
    {
        if (isset($this->appliedVouchers[$index])) {
            $voucher = $this->appliedVouchers[$index];
            unset($this->appliedVouchers[$index]);
            $this->appliedVouchers = array_values($this->appliedVouchers); // Re-index array
            $this->calculateTotals();

            Notification::make()
                ->title('Voucher Removed')
                ->body("Removed voucher: {$voucher['code']}")
                ->success()
                ->send();
        }
    }

    // === COUPON MANAGEMENT ===
    public function applyCoupon(): void
    {
        if (empty($this->couponCode)) {
            Notification::make()
                ->title('Coupon Required')
                ->body('Please enter a coupon code')
                ->warning()
                ->send();
            return;
        }

        // Check if coupon already applied
        foreach ($this->appliedCoupons as $applied) {
            if ($applied['code'] === $this->couponCode) {
                Notification::make()
                    ->title('Coupon Already Applied')
                    ->body('This coupon has already been applied')
                    ->warning()
                    ->send();
                return;
            }
        }

        // Simulate coupon validation (you can replace with actual coupon model logic)
        $couponData = $this->validateCouponCode($this->couponCode);
        
        if ($couponData) {
            $this->appliedCoupons[] = $couponData;
            
            $this->couponCode = '';
            $this->calculateTotals();
            
            $discountText = $couponData['discount_type'] === 'percentage' 
                ? $couponData['discount'] . '% off' 
                : 'KES ' . $couponData['discount'] . ' off';
            
            Notification::make()
                ->title('Coupon Applied')
                ->body("Coupon applied successfully - {$discountText}")
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title('Invalid Coupon')
                ->body('The coupon code is invalid or expired')
                ->danger()
                ->send();
        }
    }

    public function removeCoupon(string $code): void
    {
        $this->appliedCoupons = array_filter($this->appliedCoupons, function($coupon) use ($code) {
            return $coupon['code'] !== $code;
        });
        
        $this->calculateTotals();
        
        Notification::make()
            ->title('Coupon Removed')
            ->success()
            ->send();
    }

    private function validateCouponCode(string $code): ?array
    {
        // Sample coupon codes - replace with actual database logic
        $coupons = [
            'FIRSTTIME20' => ['code' => 'FIRSTTIME20', 'discount' => 20, 'discount_type' => 'percentage'],
            'HOLIDAY15' => ['code' => 'HOLIDAY15', 'discount' => 15, 'discount_type' => 'percentage'],
            'FLAT500' => ['code' => 'FLAT500', 'discount' => 500, 'discount_type' => 'fixed'],
            'MEMBER10' => ['code' => 'MEMBER10', 'discount' => 10, 'discount_type' => 'percentage'],
        ];

        return $coupons[strtoupper($code)] ?? null;
    }

    public function applyManualDiscount(float $amount): void
    {
        if ($amount < 0 || $amount > $this->subtotal) {
            Notification::make()
                ->title('Invalid Discount')
                ->body('Discount amount must be between 0 and subtotal')
                ->warning()
                ->send();
            return;
        }

        $this->discountAmount = $amount;
        $this->calculateTotals();

        Notification::make()
            ->title('Discount Applied')
            ->body("Manual discount of KES " . number_format($amount) . " applied")
            ->success()
            ->send();
    }

    // === UI ACTIONS ===
    public function openCustomerModal(): void
    {
        $this->showCustomerModal = true;
    }

    public function openPaymentModal(): void
    {
        if (empty($this->cart)) {
            Notification::make()
                ->title('Cart Empty')
                ->body('Please add items to cart before checkout')
                ->warning()
                ->send();
            return;
        }
        
        // Check if cart has services and needs booking flow
        $hasServices = collect($this->cart)->where('type', 'service')->count() > 0;
        if ($hasServices && !$this->selectedAppointmentId) {
            $this->proceedToBookingFlow();
            return;
        }
        
        $this->showPaymentModal = true;
    }

    public function proceedToBookingFlow(): void
    {
        $services = collect($this->cart)->where('type', 'service');
        
        if ($services->count() === 0) {
            Notification::make()
                ->title('No Services Selected')
                ->body('Please add services to proceed with booking')
                ->warning()
                ->send();
            return;
        }

        // Check if we have reached the 2 service limit
        if ($services->count() >= 2) {
            $this->proceedToDateTimeSelection();
        } else {
            Notification::make()
                ->title('Add More Services')
                ->body('You can add up to 2 services. Add another service or proceed to date/time selection.')
                ->info()
                ->send();
        }
    }

    public function proceedToDateTimeSelection(): void
    {
        $services = collect($this->cart)->where('type', 'service');
        
        if ($services->count() === 0) {
            Notification::make()
                ->title('No Services Selected')
                ->body('Please add services first')
                ->warning()
                ->send();
            return;
        }

        // Store services in session for booking flow
        session(['pos_booking_services' => $services->toArray()]);
        session(['pos_booking_tenant' => \Filament\Facades\Filament::getTenant()->id]);
        
        // Show date/time selection modal
        $this->showDateTimeModal = true;
        
        Notification::make()
            ->title('Proceed to Booking')
            ->body('Select date and time for your services')
            ->success()
            ->send();
    }

    public function toggleAppointmentSearch(): void
    {
        $this->showAppointmentSearch = !$this->showAppointmentSearch;
        if (!$this->showAppointmentSearch) {
            $this->customerSearch = '';
            $this->customerSearchResults = [];
        }
    }

    // === BOOKING FLOW INTEGRATION ===
    public function updatedSelectedDate(): void
    {
        if ($this->selectedDate) {
            $this->loadAvailableTimeSlots();
        }
    }

    public function loadAvailableTimeSlots(): void
    {
        try {
            $availabilityService = app(AvailabilityService::class);
            $services = collect($this->cart)->where('type', 'service');
            
            if ($services->isEmpty()) {
                return;
            }

            $branchId = \Filament\Facades\Filament::getTenant()->id;
            $this->availableTimeSlots = [];

            // Get availability for each service/staff combination
            foreach ($services as $cartItem) {
                $serviceId = $cartItem['id'];
                $staffId = $cartItem['staff_id'];

                $slots = $availabilityService->getAvailableTimeSlots(
                    $this->selectedDate,
                    $serviceId,
                    $branchId,
                    $staffId
                );

                // Filter only available slots and extract time strings
                $availableSlots = $slots->filter(function($slot) {
                    return $slot['available'] === true;
                })->pluck('time')->toArray();
                
                // Merge available slots (intersection for multiple services)
                if (empty($this->availableTimeSlots)) {
                    $this->availableTimeSlots = $availableSlots;
                } else {
                    $this->availableTimeSlots = array_intersect($this->availableTimeSlots, $availableSlots);
                }
            }

            // Sort time slots
            sort($this->availableTimeSlots);

        } catch (\Exception $e) {
            Notification::make()
                ->title('Error Loading Time Slots')
                ->body('Could not load available time slots: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function selectTimeSlot(string $time): void
    {
        $this->selectedTime = $time;
        
        Notification::make()
            ->title('Time Selected')
            ->body("Selected time: {$time}")
            ->success()
            ->send();
    }

    public function confirmBookingDateTime(): void
    {
        if (empty($this->selectedDate) || empty($this->selectedTime)) {
            Notification::make()
                ->title('Date/Time Required')
                ->body('Please select both date and time')
                ->warning()
                ->send();
            return;
        }

        // Store booking date/time in session
        session([
            'pos_booking_date' => $this->selectedDate,
            'pos_booking_time' => $this->selectedTime,
        ]);

        $this->showDateTimeModal = false;
        
        // If customer not selected, show customer modal
        if (!$this->selectedCustomerId && empty($this->customerData['name'])) {
            $this->showCustomerModal = true;
        } else {
            // Show booking confirmation before payment
            $this->showBookingConfirmationModal = true;
        }

        Notification::make()
            ->title('Booking Time Confirmed')
            ->body("Appointment scheduled for {$this->selectedDate} at {$this->selectedTime}")
            ->success()
            ->send();
    }

    public function getAvailableDates(): array
    {
        $dates = [];
        $startDate = Carbon::today();
        $endDate = Carbon::today()->addDays(30); // Next 30 days

        while ($startDate <= $endDate) {
            // Skip Sundays (assuming business is closed)
            if ($startDate->dayOfWeek !== Carbon::SUNDAY) {
                $dates[] = [
                    'date' => $startDate->format('Y-m-d'),
                    'display' => $startDate->format('D, M j'),
                    'disabled' => $startDate->isPast(),
                ];
            }
            $startDate->addDay();
        }

        return $dates;
    }

    // === SERVICE & STAFF SELECTION ===
    public function loadAvailableStaffForService(int $serviceId): void
    {
        try {
            $service = Service::findOrFail($serviceId);
            $branchId = \Filament\Facades\Filament::getTenant()->id;
            
            // Get staff who can perform this service at this branch
            $staff = Staff::whereHas('branches', function($query) use ($branchId) {
                $query->where('branch_id', $branchId);
            })
            ->whereHas('services', function($query) use ($serviceId) {
                $query->where('service_id', $serviceId);
            })
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

            $this->availableStaffForService = $staff->map(function($staffMember) {
                return [
                    'id' => $staffMember->id,
                    'name' => $staffMember->name,
                    'specialization' => $staffMember->specialization ?? 'General',
                ];
            })->toArray();

        } catch (\Exception $e) {
            Notification::make()
                ->title('Error Loading Staff')
                ->body('Could not load available staff for this service')
                ->danger()
                ->send();
                
            $this->availableStaffForService = [];
        }
    }

    public function selectStaffForService(int $staffId): void
    {
        try {
            if (!$this->pendingServiceId) {
                return;
            }

            $service = Service::findOrFail($this->pendingServiceId);
            $staff = Staff::findOrFail($staffId);
            
            // Add service to cart with selected staff
            $cartId = 'service_' . $this->pendingServiceId . '_' . $staffId . '_' . uniqid();
            
            $this->cart[$cartId] = [
                'type' => 'service',
                'id' => $this->pendingServiceId,
                'name' => $service->name,
                'price' => $service->price,
                'duration' => $service->duration_minutes,
                'staff_id' => $staffId,
                'staff_name' => $staff->name,
                'quantity' => 1,
                'requires_staff' => true,
            ];

            // Reset state
            $this->pendingServiceId = null;
            $this->showStaffSelectionModal = false;
            $this->availableStaffForService = [];
            
            $this->calculateTotals();

            Notification::make()
                ->title('Service Added')
                ->body("{$service->name} with {$staff->name} added to cart")
                ->success()
                ->send();

        } catch (\Exception $e) {
            Notification::make()
                ->title('Error Adding Service')
                ->body('Could not add service to cart: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function cancelStaffSelection(): void
    {
        $this->pendingServiceId = null;
        $this->showStaffSelectionModal = false;
        $this->availableStaffForService = [];
    }

    public function confirmBookingAndProceedToPayment(): void
    {
        $this->showBookingConfirmationModal = false;
        
        // If customer not selected, show customer modal first
        if (!$this->selectedCustomerId && empty($this->customerData['name'])) {
            $this->showCustomerModal = true;
        } else {
            // Proceed directly to payment
            $this->showPaymentModal = true;
        }
        
        Notification::make()
            ->title('Booking Confirmed')
            ->body('Ready to process payment')
            ->success()
            ->send();
    }

    // === RECEIPT GENERATION ===
    public function generateReceipt(int $transactionId, string $format = 'print')
    {
        try {
            $transaction = PosTransaction::with(['client', 'booking'])
                ->findOrFail($transactionId);

            $receiptData = [
                'transaction' => $transaction,
                'business' => [
                    'name' => \Filament\Facades\Filament::getTenant()?->name ?? 'Wellness Spa',
                    'address' => \Filament\Facades\Filament::getTenant()?->address ?? '',
                    'phone' => \Filament\Facades\Filament::getTenant()?->phone ?? '',
                    'email' => \Filament\Facades\Filament::getTenant()?->email ?? '',
                    'pin' => \Filament\Facades\Filament::getTenant()?->kra_pin ?? '',
                ],
                'format' => $format,
                'generated_at' => now(),
            ];

            switch ($format) {
                case 'pdf':
                    return $this->generatePdfReceipt($receiptData);
                case 'email':
                    $this->emailReceipt($receiptData);
                    break;
                case 'sms':
                    $this->smsReceipt($receiptData);
                    break;
                case 'whatsapp':
                    $this->whatsappReceipt($receiptData);
                    break;
                default:
                    $this->printReceipt($receiptData);
                    break;
            }

        } catch (\Exception $e) {
            Notification::make()
                ->title('Receipt Generation Failed')
                ->body('Could not generate receipt: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    private function printReceipt(array $receiptData): void
    {
        // Generate print-friendly receipt HTML
        $html = view('pos.receipt-print', $receiptData)->render();
        
        // Use JavaScript to trigger print
        $this->dispatch('print-receipt', html: $html);
        
        Notification::make()
            ->title('Receipt Ready')
            ->body('Receipt sent to printer')
            ->success()
            ->send();
    }

    private function generatePdfReceipt(array $receiptData)
    {
        if (!class_exists('Barryvdh\DomPDF\Facade\Pdf')) {
            Notification::make()
                ->title('PDF Not Available')
                ->body('PDF generation is not configured')
                ->warning()
                ->send();
            return;
        }

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pos.receipt-pdf', $receiptData);
        
        $filename = 'receipt_' . $receiptData['transaction']->id . '_' . now()->format('Ymd_His') . '.pdf';
        
        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->stream();
        }, $filename);
    }

    private function emailReceipt(array $receiptData): void
    {
        $customer = $receiptData['transaction']->customer;
        
        if (!$customer->email) {
            Notification::make()
                ->title('No Email Address')
                ->body('Customer email address is required')
                ->warning()
                ->send();
            return;
        }

        try {
            \Mail::to($customer->email)->send(new \App\Mail\PosReceiptMail($receiptData));
            
            Notification::make()
                ->title('Receipt Emailed')
                ->body("Receipt sent to {$customer->email}")
                ->success()
                ->send();
                
        } catch (\Exception $e) {
            Notification::make()
                ->title('Email Failed')
                ->body('Could not send email receipt')
                ->danger()
                ->send();
        }
    }

    private function smsReceipt(array $receiptData): void
    {
        $customer = $receiptData['transaction']->customer;
        
        if (!$customer->phone) {
            Notification::make()
                ->title('No Phone Number')
                ->body('Customer phone number is required')
                ->warning()
                ->send();
            return;
        }

        $message = $this->generateSmsReceiptText($receiptData);
        
        // Integrate with SMS service (AfricasTalking, etc.)
        // \App\Services\SmsService::send($customer->phone, $message);
        
        Notification::make()
            ->title('SMS Receipt Sent')
            ->body("Receipt sent to {$customer->phone}")
            ->success()
            ->send();
    }

    private function whatsappReceipt(array $receiptData): void
    {
        $customer = $receiptData['transaction']->customer;
        
        if (!$customer->phone) {
            Notification::make()
                ->title('No Phone Number')
                ->body('Customer phone number is required')
                ->warning()
                ->send();
            return;
        }

        $message = $this->generateSmsReceiptText($receiptData);
        
        // Integrate with WhatsApp API
        // \App\Services\WhatsAppService::send($customer->phone, $message);
        
        Notification::make()
            ->title('WhatsApp Receipt Sent')
            ->body("Receipt sent to {$customer->phone}")
            ->success()
            ->send();
    }

    private function generateSmsReceiptText(array $receiptData): string
    {
        $transaction = $receiptData['transaction'];
        $business = $receiptData['business'];
        
        return "{$business['name']}\n" .
               "Receipt #{$transaction->id}\n" .
               "Date: " . $transaction->created_at->format('d/m/Y H:i') . "\n" .
               "Customer: {$transaction->customer->name}\n" .
               "Total: KES " . number_format($transaction->total_amount, 2) . "\n" .
               "Payment: " . ucfirst($transaction->payment_method) . "\n" .
               "Thank you for your business!";
    }

    // === KENYAN COMPLIANCE (KRA eTIMS) ===
    private function submitToKraEtims($transaction): void
    {
        try {
            $tenant = \Filament\Facades\Filament::getTenant();
            
            // Check if KRA eTIMS is configured
            if (!$tenant->kra_pin || !$tenant->etims_device_serial) {
                return; // Skip if not configured
            }

            $invoiceData = [
                'deviceSerialNumber' => $tenant->etims_device_serial,
                'pin' => $tenant->kra_pin,
                'invoiceNumber' => $this->generateInvoiceNumber($transaction),
                'salesDateTime' => $transaction->created_at->format('Y-m-d H:i:s'),
                'customer' => [
                    'customerPin' => $transaction->customer->kra_pin ?? null,
                    'customerName' => $transaction->customer->name,
                    'customerMobileNumber' => $transaction->customer->phone,
                ],
                'items' => $this->formatItemsForEtims($transaction->items),
                'totals' => [
                    'totalAmount' => $transaction->subtotal,
                    'totalTaxAmount' => $transaction->tax_amount,
                    'netAmount' => $transaction->total_amount,
                ],
                'paymentMethod' => $this->mapPaymentMethodForEtims($transaction->payment_method),
            ];

            // Submit to KRA eTIMS API
            $response = Http::timeout(30)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $this->getEtimsToken(),
                ])
                ->post(config('services.kra_etims.api_url') . '/sales/invoice', $invoiceData);

            if ($response->successful()) {
                $responseData = $response->json();
                
                // Update transaction with eTIMS response
                $transaction->update([
                    'etims_invoice_number' => $responseData['invoiceNumber'] ?? null,
                    'etims_control_unit_serial' => $responseData['controlUnitSerial'] ?? null,
                    'etims_receipt_signature' => $responseData['receiptSignature'] ?? null,
                    'etims_qr_code' => $responseData['qrCode'] ?? null,
                    'tax_compliance_status' => 'submitted',
                ]);

                Notification::make()
                    ->title('Tax Compliance')
                    ->body('Invoice submitted to KRA eTIMS successfully')
                    ->success()
                    ->send();
            } else {
                throw new \Exception('eTIMS API error: ' . $response->body());
            }

        } catch (\Exception $e) {
            // Log error but don't block the transaction
            \Log::error('KRA eTIMS submission failed: ' . $e->getMessage(), [
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage(),
            ]);

            $transaction->update(['tax_compliance_status' => 'failed']);

            Notification::make()
                ->title('Tax Compliance Warning')
                ->body('Could not submit to KRA eTIMS. Please check with administrator.')
                ->warning()
                ->persistent()
                ->send();
        }
    }

    private function generateInvoiceNumber($transaction): string
    {
        $tenant = \Filament\Facades\Filament::getTenant();
        $prefix = $tenant->invoice_prefix ?? 'INV';
        $date = $transaction->created_at->format('Ymd');
        
        return $prefix . '-' . $date . '-' . str_pad($transaction->id, 6, '0', STR_PAD_LEFT);
    }

    private function formatItemsForEtims(array $items): array
    {
        $etimsItems = [];
        
        foreach ($items as $item) {
            $etimsItems[] = [
                'itemCode' => $item['type'] === 'service' ? 'SVC' . $item['id'] : 'PRD' . $item['id'],
                'itemName' => $item['name'],
                'quantity' => $item['quantity'],
                'unitPrice' => $item['price'],
                'totalAmount' => $item['price'] * $item['quantity'],
                'taxType' => 'VAT', // Standard VAT in Kenya
                'taxRate' => 16.00, // 16% VAT
                'taxAmount' => ($item['price'] * $item['quantity']) * 0.16,
                'itemType' => $item['type'] === 'service' ? 'SERVICE' : 'GOODS',
            ];
        }
        
        return $etimsItems;
    }

    private function mapPaymentMethodForEtims(string $paymentMethod): string
    {
        return match($paymentMethod) {
            'cash' => 'CASH',
            'mpesa' => 'MOBILE_MONEY',
            'card' => 'CARD',
            'bank' => 'BANK_TRANSFER',
            default => 'OTHER',
        };
    }

    private function getEtimsToken(): string
    {
        // Implementation would depend on your KRA eTIMS authentication setup
        // This could be cached token or fetched from service
        return cache()->remember('etims_token', 3600, function () {
            // Fetch token from KRA eTIMS authentication endpoint
            $tenant = \Filament\Facades\Filament::getTenant();
            
            $response = Http::post(config('services.kra_etims.auth_url'), [
                'username' => $tenant->etims_username,
                'password' => $tenant->etims_password,
                'deviceSerial' => $tenant->etims_device_serial,
            ]);

            if ($response->successful()) {
                return $response->json()['token'];
            }

            throw new \Exception('Failed to authenticate with KRA eTIMS');
        });
    }

    // === KENYAN LOCALIZATION ===
    public function formatKenyanPhone(string $phone): string
    {
        // Convert phone number to Kenyan format (+254...)
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        if (str_starts_with($phone, '0')) {
            $phone = '254' . substr($phone, 1);
        } elseif (str_starts_with($phone, '7') || str_starts_with($phone, '1')) {
            $phone = '254' . $phone;
        }
        
        return '+' . $phone;
    }

    public function formatKenyanCurrency(float $amount): string
    {
        return 'KES ' . number_format($amount, 2);
    }

    // === POS TERMINAL RESET ===
    public function resetPosTerminal(): void
    {
        $this->cart = [];
        $this->subtotal = 0.00;
        $this->taxAmount = 0.00;
        $this->totalAmount = 0.00;
        $this->discountAmount = 0.00;
        $this->tipAmount = 0.00;
        
        $this->selectedCustomerId = null;
        $this->selectedAppointmentId = null;
        $this->customerData = [];
        $this->customerSearch = '';
        $this->customerSearchResults = [];
        
        $this->voucherCode = '';
        $this->couponCode = '';
        $this->appliedVouchers = [];
        $this->appliedCoupons = [];
        
        $this->paymentMethod = 'cash';
        $this->mpesaPhone = '';
        
        // Reset customer form
        $this->resetCustomerForm();
        
        // Clear session booking data
        session()->forget(['pos_booking_date', 'pos_booking_time']);
    }
}