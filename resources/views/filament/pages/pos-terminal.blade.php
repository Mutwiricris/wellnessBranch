<x-filament-panels::page>
    <div class="min-h-screen bg-gray-50 dark:bg-gray-900">
        
        <!-- POS Header -->
        <div class="bg-white dark:bg-gray-800 shadow-sm border-b border-gray-200 dark:border-gray-700 p-4 mb-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <div class="flex items-center space-x-2">
                        <div class="w-10 h-10 bg-gradient-to-r from-blue-500 to-purple-600 rounded-lg flex items-center justify-center">
                            <x-heroicon-o-device-tablet class="w-6 h-6 text-white" />
                        </div>
                        <div>
                            <h1 class="text-xl font-bold text-gray-900 dark:text-white">POS Terminal</h1>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ \Filament\Facades\Filament::getTenant()?->name ?? 'Wellness Spa' }}</p>
                        </div>
                    </div>
                    
                    <!-- Service-First Workflow Info -->
                    <div class="flex items-center space-x-2 ml-8">
                        <x-heroicon-o-information-circle class="w-5 h-5 text-blue-500" />
                        <span class="text-sm text-gray-600 dark:text-gray-400">Select service first, then choose staff member</span>
                    </div>
                </div>
                
                <!-- Action Buttons -->
                <div class="flex items-center space-x-3">
                    <button wire:click="toggleAppointmentSearch" 
                            class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                        <x-heroicon-o-calendar class="w-4 h-4 mr-2 inline" />
                        {{ $showAppointmentSearch ? 'Hide' : 'Load' }} Appointment
                    </button>
                    
                    <button wire:click="resetCart" 
                            class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                        <x-heroicon-o-arrow-path class="w-4 h-4 mr-2 inline" />
                        New Sale
                    </button>
                </div>
            </div>
            
            <!-- Appointment Search Section -->
            @if($showAppointmentSearch)
                <div class="mt-4 p-4 bg-green-50 dark:bg-green-900/20 rounded-lg border border-green-200 dark:border-green-700">
                    <div class="flex items-center space-x-4">
                        <div class="flex-1">
                            <input wire:model.live.debounce.300ms="customerSearch" 
                                   type="text" 
                                   placeholder="Search by customer name or phone..."
                                   class="w-full px-4 py-2 border border-green-300 dark:border-green-600 rounded-lg focus:ring-2 focus:ring-green-500 bg-white dark:bg-gray-800">
                        </div>
                    </div>
                    
                    @if(!empty($customerSearchResults))
                        <div class="mt-3 space-y-2">
                            @foreach($customerSearchResults as $appointment)
                                <div class="flex items-center justify-between p-3 bg-white dark:bg-gray-800 rounded-lg border border-green-200 dark:border-green-600">
                                    <div class="flex-1">
                                        <div class="font-medium text-gray-900 dark:text-white">{{ $appointment['customer_name'] }}</div>
                                        <div class="text-sm text-gray-500">{{ $appointment['customer_phone'] }} • {{ $appointment['time'] }}</div>
                                        <div class="text-sm text-green-600">{{ $appointment['services'] }}</div>
                                    </div>
                                    <div class="text-right">
                                        <div class="font-bold text-gray-900 dark:text-white">KES {{ number_format($appointment['total']) }}</div>
                                        <button wire:click="loadAppointment({{ $appointment['id'] }})" 
                                                class="mt-1 bg-green-500 hover:bg-green-600 text-white px-3 py-1 rounded text-sm font-medium">
                                            Load Appointment
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endif
        </div>

        <div class="flex gap-6 px-4">
            <!-- Left Panel - Products & Services -->
            <div class="flex-1">
                <!-- Search & Category Filter -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4 mb-4">
                    <div class="flex items-center space-x-4">
                        <div class="flex-1 relative">
                            <x-heroicon-o-magnifying-glass class="absolute left-3 top-1/2 transform -translate-y-1/2 w-5 h-5 text-gray-400" />
                            <input wire:model.live.debounce.300ms="searchTerm" 
                                   type="text" 
                                   placeholder="Search services and products..."
                                   class="w-full pl-10 pr-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white dark:bg-gray-800">
                        </div>
                        
                        <!-- Category Filter -->
                        <select wire:model.live="categoryFilter" 
                                class="border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 bg-white dark:bg-gray-800">
                            <option value="all">All Categories</option>
                            <option value="facial">Facial</option>
                            <option value="massage">Massage</option>
                            <option value="manicure">Manicure</option>
                            <option value="pedicure">Pedicure</option>
                            <option value="hair">Hair Care</option>
                        </select>
                    </div>
                </div>

                <!-- Service/Product Tabs -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
                    <!-- Tab Headers -->
                    <div class="border-b border-gray-200 dark:border-gray-700">
                        <nav class="flex space-x-8 px-4">
                            <button wire:click="$set('activeTab', 'services')" 
                                    class="py-4 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'services' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                                <x-heroicon-o-sparkles class="w-5 h-5 inline mr-2" />
                                Services
                            </button>
                            <button wire:click="$set('activeTab', 'products')" 
                                    class="py-4 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'products' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                                <x-heroicon-o-cube class="w-5 h-5 inline mr-2" />
                                Products
                            </button>
                        </nav>
                    </div>

                    <!-- Tab Content -->
                    <div class="p-4">
                        @if($activeTab === 'services')
                            <!-- Services Grid -->
                            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                                @foreach($this->getServices() as $service)
                                    <div wire:click="addToCart('service', {{ $service->id }})" 
                                         class="bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-lg border border-blue-200 dark:border-blue-700 p-4 cursor-pointer hover:shadow-md transition-all hover:scale-105">
                                        <div class="text-center">
                                            <div class="w-12 h-12 bg-blue-500 rounded-full flex items-center justify-center mx-auto mb-3">
                                                <x-heroicon-o-sparkles class="w-6 h-6 text-white" />
                                            </div>
                                            <h3 class="font-semibold text-gray-900 dark:text-white text-sm mb-1">{{ $service->name }}</h3>
                                            <p class="text-xs text-gray-600 dark:text-gray-400 mb-2">{{ $service->duration_minutes }}min</p>
                                            <p class="font-bold text-blue-600 dark:text-blue-400">KES {{ number_format($service->price) }}</p>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <!-- Products Grid -->
                            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                                @foreach($this->getProducts() as $product)
                                    <div wire:click="addToCart('product', {{ $product->id }})" 
                                         class="bg-gradient-to-br from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 rounded-lg border border-green-200 dark:border-green-700 p-4 cursor-pointer hover:shadow-md transition-all hover:scale-105">
                                        <div class="text-center">
                                            <div class="w-12 h-12 bg-green-500 rounded-full flex items-center justify-center mx-auto mb-3">
                                                <x-heroicon-o-cube class="w-6 h-6 text-white" />
                                            </div>
                                            <h3 class="font-semibold text-gray-900 dark:text-white text-sm mb-1">{{ $product->name }}</h3>
                                            <p class="text-xs text-gray-600 dark:text-gray-400 mb-2">Stock: {{ $product->quantity_in_stock }}</p>
                                            <p class="font-bold text-green-600 dark:text-green-400">KES {{ number_format($product->price) }}</p>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Right Panel - Cart & Checkout -->
            <div class="w-96">
                <!-- Customer Info -->
                @if($selectedCustomerId || !empty($customerData))
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4 mb-4">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-3">
                                <div class="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center">
                                    <x-heroicon-o-user class="w-4 h-4 text-white" />
                                </div>
                                <div>
                                    <div class="font-medium text-gray-900 dark:text-white">
                                        {{ $customerData['name'] ?? 'Walk-in Customer' }}
                                    </div>
                                    @if(!empty($customerData['phone']))
                                        <div class="text-sm text-gray-500">{{ $customerData['phone'] }}</div>
                                    @endif
                                </div>
                            </div>
                            @if($selectedAppointmentId)
                                <span class="bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 text-xs px-2 py-1 rounded-full">
                                    From Appointment
                                </span>
                            @endif
                        </div>
                    </div>
                @endif

                <!-- Cart -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
                    <!-- Cart Header -->
                    <div class="flex items-center justify-between p-4 border-b border-gray-200 dark:border-gray-700">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                            <x-heroicon-o-shopping-cart class="w-5 h-5 mr-2" />
                            Cart ({{ count($cart) }})
                        </h2>
                        @if(!empty($cart))
                            <button wire:click="resetCart" class="text-red-500 hover:text-red-600 text-sm font-medium">
                                Clear All
                            </button>
                        @endif
                    </div>

                    <!-- Cart Items -->
                    <div class="max-h-80 overflow-y-auto">
                        @if(empty($cart))
                            <div class="p-8 text-center">
                                <x-heroicon-o-shopping-cart class="w-12 h-12 text-gray-300 dark:text-gray-600 mx-auto mb-3" />
                                <p class="text-gray-500 dark:text-gray-400">Your cart is empty</p>
                                <p class="text-sm text-gray-400 dark:text-gray-500">Add services or products to get started</p>
                            </div>
                        @else
                            <div class="divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach($cart as $cartId => $item)
                                    <div class="p-4 flex items-center justify-between">
                                        <div class="flex items-center space-x-3 flex-1">
                                            <div class="w-8 h-8 rounded-lg flex items-center justify-center {{ $item['type'] === 'service' ? 'bg-blue-100 dark:bg-blue-900 text-blue-600' : 'bg-green-100 dark:bg-green-900 text-green-600' }}">
                                                @if($item['type'] === 'service')
                                                    <x-heroicon-o-sparkles class="w-4 h-4" />
                                                @else
                                                    <x-heroicon-o-cube class="w-4 h-4" />
                                                @endif
                                            </div>
                                            
                                            <div class="flex-1 min-w-0">
                                                <div class="font-medium text-gray-900 dark:text-white text-sm">{{ $item['name'] }}</div>
                                                
                                                @if($item['type'] === 'service')
                                                    <div class="text-xs text-gray-500">
                                                        {{ $item['duration'] }}min
                                                        @if($item['staff_name'])
                                                            • {{ $item['staff_name'] }}
                                                        @endif
                                                    </div>
                                                @endif
                                                
                                                <div class="flex items-center space-x-2 mt-1">
                                                    @if($item['type'] === 'product')
                                                        <div class="flex items-center space-x-1">
                                                            <button wire:click="updateQuantity('{{ $cartId }}', {{ $item['quantity'] - 1 }})" 
                                                                    class="w-5 h-5 rounded-full bg-gray-200 dark:bg-gray-600 flex items-center justify-center hover:bg-gray-300">
                                                                <x-heroicon-o-minus class="w-3 h-3" />
                                                            </button>
                                                            <span class="text-sm font-medium">{{ $item['quantity'] }}</span>
                                                            <button wire:click="updateQuantity('{{ $cartId }}', {{ $item['quantity'] + 1 }})" 
                                                                    class="w-5 h-5 rounded-full bg-gray-200 dark:bg-gray-600 flex items-center justify-center hover:bg-gray-300">
                                                                <x-heroicon-o-plus class="w-3 h-3" />
                                                            </button>
                                                        </div>
                                                    @endif
                                                    
                                                    <button wire:click="removeFromCart('{{ $cartId }}')" 
                                                            class="text-red-500 hover:text-red-600 p-1">
                                                        <x-heroicon-o-trash class="w-4 h-4" />
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="text-right ml-3">
                                            <div class="font-bold text-gray-900 dark:text-white">
                                                KES {{ number_format($item['price'] * $item['quantity']) }}
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <!-- Voucher & Discount Section -->
                    @if(!empty($cart))
                        <div class="border-t border-gray-200 dark:border-gray-700 p-4">
                            <div class="space-y-3">
                                <!-- Voucher Input -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Voucher Code</label>
                                    <div class="flex space-x-2">
                                        <input wire:model="voucherCode" 
                                               type="text" 
                                               placeholder="Enter voucher code"
                                               class="flex-1 px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-green-500 bg-white dark:bg-gray-800">
                                        <button wire:click="applyVoucher" 
                                                class="px-3 py-2 bg-green-500 hover:bg-green-600 text-white text-sm rounded-lg font-medium">
                                            Apply
                                        </button>
                                    </div>
                                </div>
                                
                                <!-- Applied Vouchers -->
                                @if(!empty($appliedVouchers))
                                    <div class="space-y-2">
                                        @foreach($appliedVouchers as $index => $voucher)
                                            <div class="flex items-center justify-between p-2 bg-green-50 dark:bg-green-900/20 rounded-lg border border-green-200 dark:border-green-700">
                                                <div class="flex-1">
                                                    <div class="text-sm font-medium text-green-800 dark:text-green-200">{{ $voucher['code'] }}</div>
                                                    <div class="text-xs text-green-600 dark:text-green-300">{{ $voucher['name'] }}</div>
                                                </div>
                                                <div class="flex items-center space-x-2">
                                                    <span class="text-sm font-bold text-green-700 dark:text-green-200">
                                                        -KES {{ number_format($voucher['discount_amount'], 2) }}
                                                    </span>
                                                    <button wire:click="removeVoucher({{ $index }})" 
                                                            class="text-red-500 hover:text-red-600 p-1">
                                                        <x-heroicon-o-x-mark class="w-4 h-4" />
                                                    </button>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- Cart Summary -->
                        <div class="border-t border-gray-200 dark:border-gray-700 p-4 space-y-2">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600 dark:text-gray-400">Subtotal</span>
                                <span class="font-medium">KES {{ number_format($subtotal, 2) }}</span>
                            </div>
                            
                            @if($discountAmount > 0 || !empty($appliedVouchers))
                                <div class="flex justify-between text-sm text-green-600">
                                    <span>Total Discounts</span>
                                    <span>-KES {{ number_format($discountAmount + collect($appliedVouchers)->sum('discount_amount'), 2) }}</span>
                                </div>
                            @endif
                            
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600 dark:text-gray-400">Tax (16% VAT)</span>
                                <span class="font-medium">KES {{ number_format($taxAmount, 2) }}</span>
                            </div>
                            
                            @if($tipAmount > 0)
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-600 dark:text-gray-400">Tip</span>
                                    <span class="font-medium">KES {{ number_format($tipAmount, 2) }}</span>
                                </div>
                            @endif
                            
                            <div class="border-t border-gray-200 dark:border-gray-700 pt-2">
                                <div class="flex justify-between text-lg font-bold">
                                    <span class="text-gray-900 dark:text-white">Total</span>
                                    <span class="text-green-600 dark:text-green-400">KES {{ number_format($totalAmount, 2) }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Checkout Actions -->
                        <div class="border-t border-gray-200 dark:border-gray-700 p-4 space-y-3">
                            @php
                                $serviceCount = collect($cart)->where('type', 'service')->count();
                                $hasServices = $serviceCount > 0;
                            @endphp
                            
                            @if($hasServices && $serviceCount < 2)
                                <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-700 rounded-lg p-3 mb-3">
                                    <div class="flex items-center space-x-2">
                                        <x-heroicon-o-information-circle class="w-5 h-5 text-blue-600" />
                                        <div class="text-sm text-blue-800 dark:text-blue-200">
                                            <strong>{{ $serviceCount }}/2 services selected.</strong> Add another service or proceed to booking.
                                        </div>
                                    </div>
                                </div>
                                
                                <button wire:click="proceedToDateTimeSelection" 
                                        class="w-full bg-primary-600 hover:bg-primary-700 text-white font-bold py-3 px-4 rounded-lg transition-colors shadow-lg">
                                    <x-heroicon-o-calendar class="w-5 h-5 inline mr-2" />
                                    Select Date & Time
                                </button>
                            @endif
                            
                            @if($hasServices && $serviceCount >= 2)
                                <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-700 rounded-lg p-3 mb-3">
                                    <div class="flex items-center space-x-2">
                                        <x-heroicon-o-check-circle class="w-5 h-5 text-green-600" />
                                        <div class="text-sm text-green-800 dark:text-green-200">
                                            <strong>Maximum services reached (2/2).</strong> Ready to proceed to booking.
                                        </div>
                                    </div>
                                </div>
                                
                                <button wire:click="proceedToDateTimeSelection" 
                                        class="w-full bg-primary-600 hover:bg-primary-700 text-white font-bold py-3 px-4 rounded-lg transition-colors shadow-lg">
                                    <x-heroicon-o-calendar class="w-5 h-5 inline mr-2" />
                                    Select Date & Time
                                </button>
                            @endif
                            
                            @if(!$selectedCustomerId && empty($customerData['name']) && !$hasServices)
                                <button wire:click="openCustomerModal" 
                                        class="w-full bg-primary-600 hover:bg-primary-700 text-white font-bold py-3 px-4 rounded-lg transition-colors shadow-lg">
                                    <x-heroicon-o-user class="w-5 h-5 inline mr-2" />
                                    Add Customer Details
                                </button>
                            @endif
                            
                            @if(!$hasServices || $selectedAppointmentId)
                                <button wire:click="openPaymentModal" 
                                        class="w-full bg-success-600 hover:bg-success-700 text-white font-bold py-3 px-4 rounded-lg transition-colors shadow-lg {{ $processingPayment ? 'opacity-50 cursor-not-allowed' : '' }}"
                                        {{ $processingPayment ? 'disabled' : '' }}>
                                    @if($processingPayment)
                                        <x-heroicon-o-arrow-path class="w-5 h-5 inline mr-2 animate-spin" />
                                        Processing...
                                    @else
                                        <x-heroicon-o-credit-card class="w-5 h-5 inline mr-2" />
                                        Complete Purchase
                                    @endif
                                </button>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Customer Modal -->
        @if($showCustomerModal)
            <div class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex items-center justify-center min-h-screen px-4">
                    <div class="fixed inset-0 bg-black opacity-50"></div>
                    <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-2xl w-full p-6">
                        <div class="flex items-center justify-between mb-6">
                            <h3 class="text-xl font-semibold text-gray-900 dark:text-white">Customer Information</h3>
                            <button wire:click="$set('showCustomerModal', false)" class="text-gray-400 hover:text-gray-600">
                                <x-heroicon-o-x-mark class="w-6 h-6" />
                            </button>
                        </div>
                        
                        <!-- Customer Type Selection -->
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Customer Type</label>
                            <div class="grid grid-cols-2 gap-4">
                                <label class="flex items-center p-4 border rounded-lg cursor-pointer {{ $customerType === 'existing' ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/20' : 'border-gray-300 dark:border-gray-600' }}">
                                    <input type="radio" wire:model.live="customerType" value="existing" class="sr-only">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-4 h-4 rounded-full border-2 {{ $customerType === 'existing' ? 'border-primary-500 bg-primary-500' : 'border-gray-300' }}"></div>
                                        <div>
                                            <div class="font-medium text-gray-900 dark:text-white">Existing Customer</div>
                                            <div class="text-sm text-gray-500">Search for existing customer</div>
                                        </div>
                                    </div>
                                </label>
                                
                                <label class="flex items-center p-4 border rounded-lg cursor-pointer {{ $customerType === 'new' ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/20' : 'border-gray-300 dark:border-gray-600' }}">
                                    <input type="radio" wire:model.live="customerType" value="new" class="sr-only">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-4 h-4 rounded-full border-2 {{ $customerType === 'new' ? 'border-primary-500 bg-primary-500' : 'border-gray-300' }}"></div>
                                        <div>
                                            <div class="font-medium text-gray-900 dark:text-white">New Customer</div>
                                            <div class="text-sm text-gray-500">Add new customer details</div>
                                        </div>
                                    </div>
                                </label>
                            </div>
                        </div>

                        @if($customerType === 'existing')
                            <!-- Customer Search -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Search Customer</label>
                                <input wire:model.live.debounce.300ms="customerSearch" 
                                       type="text" 
                                       placeholder="Search by name, email, or phone..."
                                       class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 bg-white dark:bg-gray-800">
                                
                                @if(!empty($customerSearchResults))
                                    <div class="mt-3 max-h-60 overflow-y-auto space-y-2">
                                        @foreach($customerSearchResults as $customer)
                                            <div wire:click="selectCustomer({{ $customer['id'] }})" 
                                                 class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg cursor-pointer hover:bg-primary-50 dark:hover:bg-primary-900/20 transition-colors">
                                                <div class="flex items-center space-x-3">
                                                    <div class="w-10 h-10 bg-gradient-to-r from-primary-500 to-purple-600 rounded-full flex items-center justify-center">
                                                        <span class="text-white font-semibold">{{ substr($customer['name'], 0, 1) }}</span>
                                                    </div>
                                                    <div>
                                                        <div class="font-medium text-gray-900 dark:text-white">{{ $customer['name'] }}</div>
                                                        <div class="text-sm text-gray-500">{{ $customer['email'] }} • {{ $customer['phone'] }}</div>
                                                    </div>
                                                </div>
                                                <button class="px-3 py-1 bg-primary-500 hover:bg-primary-600 text-white text-sm rounded-lg font-medium">
                                                    Select
                                                </button>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        @endif

                        @if($customerType === 'new')
                            <!-- New Customer Form -->
                            <div class="space-y-4">
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">First Name *</label>
                                        <input wire:model="firstName" type="text" 
                                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 bg-white dark:bg-gray-800">
                                        @error('firstName') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                    </div>
                                    
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Last Name *</label>
                                        <input wire:model="lastName" type="text" 
                                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 bg-white dark:bg-gray-800">
                                        @error('lastName') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                    </div>
                                </div>
                                
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Email Address *</label>
                                        <input wire:model="email" type="email" 
                                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 bg-white dark:bg-gray-800">
                                        @error('email') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                    </div>
                                    
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Phone Number *</label>
                                        <input wire:model="phone" type="tel" placeholder="0712345678"
                                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 bg-white dark:bg-gray-800">
                                        @error('phone') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                    </div>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Allergies or Medical Conditions</label>
                                    <textarea wire:model="allergies" rows="2" placeholder="Enter any allergies, medical conditions, or 'None'"
                                              class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 bg-white dark:bg-gray-800"></textarea>
                                    @error('allergies') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>
                                
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Gender</label>
                                        <select wire:model="gender" 
                                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 bg-white dark:bg-gray-800">
                                            <option value="">Select Gender</option>
                                            <option value="male">Male</option>
                                            <option value="female">Female</option>
                                            <option value="other">Other</option>
                                            <option value="prefer_not_to_say">Prefer not to say</option>
                                        </select>
                                    </div>
                                    
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Date of Birth</label>
                                        <input wire:model="dateOfBirth" type="date" 
                                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 bg-white dark:bg-gray-800">
                                    </div>
                                </div>
                                
                                <div class="flex items-center space-x-2 mt-4">
                                    <input wire:model="createAccount" type="checkbox" id="createAccount" 
                                           class="w-4 h-4 text-primary-600 bg-gray-100 border-gray-300 rounded focus:ring-primary-500">
                                    <label for="createAccount" class="text-sm text-gray-700 dark:text-gray-300">
                                        Create customer account for future bookings
                                    </label>
                                </div>
                            </div>
                        @endif
                        
                        <div class="flex justify-end space-x-3 mt-8">
                            <button wire:click="$set('showCustomerModal', false)" 
                                    class="px-6 py-2 text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 font-medium">
                                Cancel
                            </button>
                            
                            @if($customerType === 'new')
                                <button wire:click="saveCustomerDetails" 
                                        wire:loading.attr="disabled"
                                        wire:loading.class="opacity-50 cursor-not-allowed"
                                        class="px-6 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg font-medium shadow-lg transition-all duration-200">
                                    <span wire:loading.remove wire:target="saveCustomerDetails">Save Customer</span>
                                    <span wire:loading wire:target="saveCustomerDetails">Saving...</span>
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- Payment Modal -->
        @if($showPaymentModal)
            <div class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex items-center justify-center min-h-screen px-4">
                    <div class="fixed inset-0 bg-black opacity-50"></div>
                    <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-lg w-full p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Payment Details</h3>
                            <button wire:click="$set('showPaymentModal', false)" class="text-gray-400 hover:text-gray-600">
                                <x-heroicon-o-x-mark class="w-6 h-6" />
                            </button>
                        </div>
                        
                        <!-- Payment Method Selection -->
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Payment Method</label>
                                <div class="grid grid-cols-2 gap-3">
                                    <label class="flex items-center p-3 border border-gray-300 dark:border-gray-600 rounded-lg cursor-pointer {{ $paymentMethod === 'cash' ? 'border-green-500 bg-green-50 dark:bg-green-900/20' : '' }}">
                                        <input type="radio" wire:model="paymentMethod" value="cash" class="sr-only">
                                        <x-heroicon-o-banknotes class="w-5 h-5 mr-2 text-green-600" />
                                        <span class="text-sm font-medium">Cash</span>
                                    </label>
                                    
                                    <label class="flex items-center p-3 border border-gray-300 dark:border-gray-600 rounded-lg cursor-pointer {{ $paymentMethod === 'mpesa' ? 'border-green-500 bg-green-50 dark:bg-green-900/20' : '' }}">
                                        <input type="radio" wire:model="paymentMethod" value="mpesa" class="sr-only">
                                        <x-heroicon-o-device-phone-mobile class="w-5 h-5 mr-2 text-green-600" />
                                        <span class="text-sm font-medium">M-Pesa</span>
                                    </label>
                                    
                                    <label class="flex items-center p-3 border border-gray-300 dark:border-gray-600 rounded-lg cursor-pointer {{ $paymentMethod === 'card' ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20' : '' }}">
                                        <input type="radio" wire:model="paymentMethod" value="card" class="sr-only">
                                        <x-heroicon-o-credit-card class="w-5 h-5 mr-2 text-blue-600" />
                                        <span class="text-sm font-medium">Card</span>
                                    </label>
                                    
                                    <label class="flex items-center p-3 border border-gray-300 dark:border-gray-600 rounded-lg cursor-pointer {{ $paymentMethod === 'bank' ? 'border-purple-500 bg-purple-50 dark:bg-purple-900/20' : '' }}">
                                        <input type="radio" wire:model="paymentMethod" value="bank" class="sr-only">
                                        <x-heroicon-o-building-library class="w-5 h-5 mr-2 text-purple-600" />
                                        <span class="text-sm font-medium">Bank</span>
                                    </label>
                                </div>
                            </div>
                            
                            @if($paymentMethod === 'mpesa')
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">M-Pesa Phone Number</label>
                                    <input wire:model="mpesaPhone" type="tel" placeholder="0712345678" 
                                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-green-500 bg-white dark:bg-gray-800">
                                </div>
                            @endif
                            
                            <!-- Tip Amount -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tip Amount (Optional)</label>
                                <input wire:model.live="tipAmount" type="number" step="0.01" min="0" 
                                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white dark:bg-gray-800">
                            </div>
                            
                            <!-- Voucher Code -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Voucher Code (Optional)</label>
                                <div class="flex space-x-2">
                                    <input wire:model="voucherCode" type="text" placeholder="Enter voucher code" 
                                           class="flex-1 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white dark:bg-gray-800">
                                    <button wire:click="applyVoucher" 
                                            class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium">
                                        Apply
                                    </button>
                                </div>
                                @if(!empty($appliedVouchers))
                                    <div class="mt-2 space-y-1">
                                        @foreach($appliedVouchers as $index => $voucher)
                                            <div class="flex items-center justify-between text-sm p-2 bg-blue-50 dark:bg-blue-900/20 rounded">
                                                <span class="text-blue-700 dark:text-blue-300">{{ $voucher['code'] }} - KES {{ number_format($voucher['discount_amount'], 2) }} off</span>
                                                <button wire:click="removeVoucher({{ $index }})" class="text-red-500 hover:text-red-700">
                                                    ✕
                                                </button>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                            
                            <!-- Coupon Code -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Coupon Code (Optional)</label>
                                <div class="flex space-x-2">
                                    <input wire:model="couponCode" type="text" placeholder="Enter coupon code" 
                                           class="flex-1 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-green-500 bg-white dark:bg-gray-800">
                                    <button wire:click="applyCoupon" 
                                            class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-medium">
                                        Apply
                                    </button>
                                </div>
                                @if(!empty($appliedCoupons))
                                    <div class="mt-2 space-y-1">
                                        @foreach($appliedCoupons as $coupon)
                                            <div class="flex items-center justify-between text-sm p-2 bg-green-50 dark:bg-green-900/20 rounded">
                                                <span class="text-green-700 dark:text-green-300">{{ $coupon['code'] }} - {{ $coupon['discount_type'] === 'percentage' ? $coupon['discount'] . '% off' : 'KES ' . number_format($coupon['discount'], 2) . ' off' }}</span>
                                                <button wire:click="removeCoupon('{{ $coupon['code'] }}')" class="text-red-500 hover:text-red-700">
                                                    ✕
                                                </button>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                            
                            <!-- Payment Summary -->
                            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                                <div class="space-y-2 text-sm">
                                    <div class="flex justify-between">
                                        <span>Subtotal:</span>
                                        <span>KES {{ number_format($subtotal, 2) }}</span>
                                    </div>
                                    @if($discountAmount > 0)
                                        <div class="flex justify-between text-red-600">
                                            <span>Discounts:</span>
                                            <span>-KES {{ number_format($discountAmount, 2) }}</span>
                                        </div>
                                    @endif
                                    <div class="flex justify-between">
                                        <span>Tax (16% VAT):</span>
                                        <span>KES {{ number_format($taxAmount, 2) }}</span>
                                    </div>
                                    @if($tipAmount > 0)
                                        <div class="flex justify-between">
                                            <span>Tip:</span>
                                            <span>KES {{ number_format($tipAmount, 2) }}</span>
                                        </div>
                                    @endif
                                    <div class="border-t border-gray-300 dark:border-gray-600 pt-2 flex justify-between font-bold text-lg">
                                        <span>Total:</span>
                                        <span class="text-green-600">KES {{ number_format($totalAmount, 2) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="flex justify-end space-x-3 mt-6">
                            <button wire:click="$set('showPaymentModal', false)" 
                                    class="px-4 py-2 text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200">
                                Cancel
                            </button>
                            <button wire:click="processPayment" 
                                    class="px-8 py-3 bg-success-600 hover:bg-success-700 text-white rounded-lg font-bold shadow-lg {{ $processingPayment ? 'opacity-50 cursor-not-allowed' : '' }}"
                                    {{ $processingPayment ? 'disabled' : '' }}>
                                @if($processingPayment)
                                    <x-heroicon-o-arrow-path class="w-4 h-4 inline mr-2 animate-spin" />
                                    Processing Payment...
                                @else
                                    Complete Payment & Create Booking
                                @endif
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- Date/Time Selection Modal -->
        @if($showDateTimeModal)
            <div class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex items-center justify-center min-h-screen px-4">
                    <div class="fixed inset-0 bg-black opacity-50"></div>
                    <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-2xl w-full p-6">
                        <div class="flex items-center justify-between mb-6">
                            <h3 class="text-xl font-semibold text-gray-900 dark:text-white">Select Date & Time</h3>
                            <button wire:click="$set('showDateTimeModal', false)" class="text-gray-400 hover:text-gray-600">
                                <x-heroicon-o-x-mark class="w-6 h-6" />
                            </button>
                        </div>
                        
                        <!-- Selected Services Summary -->
                        <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4 mb-6">
                            <h4 class="font-medium text-gray-900 dark:text-white mb-2">Selected Services:</h4>
                            <div class="space-y-2">
                                @foreach(collect($cart)->where('type', 'service') as $service)
                                    <div class="flex items-center justify-between text-sm">
                                        <div class="flex items-center space-x-2">
                                            <x-heroicon-o-sparkles class="w-4 h-4 text-blue-600" />
                                            <span class="text-gray-900 dark:text-white">{{ $service['name'] }}</span>
                                            <span class="text-gray-500">({{ $service['duration'] }}min with {{ $service['staff_name'] }})</span>
                                        </div>
                                        <span class="font-medium text-gray-900 dark:text-white">KES {{ number_format($service['price']) }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Date Selection -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Select Date</label>
                                <div class="grid grid-cols-1 gap-2 max-h-64 overflow-y-auto">
                                    @foreach($this->getAvailableDates() as $dateOption)
                                        <button wire:click="$set('selectedDate', '{{ $dateOption['date'] }}')" 
                                                class="text-left p-3 rounded-lg border {{ $selectedDate === $dateOption['date'] ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20' : 'border-gray-300 dark:border-gray-600 hover:border-blue-300' }} {{ $dateOption['disabled'] ? 'opacity-50 cursor-not-allowed' : '' }}"
                                                {{ $dateOption['disabled'] ? 'disabled' : '' }}>
                                            <div class="font-medium text-gray-900 dark:text-white">{{ $dateOption['display'] }}</div>
                                            <div class="text-xs text-gray-500">{{ $dateOption['date'] }}</div>
                                        </button>
                                    @endforeach
                                </div>
                            </div>

                            <!-- Time Selection -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                                    Available Times 
                                    @if($selectedDate)
                                        <span class="text-xs text-gray-500">({{ \Carbon\Carbon::parse($selectedDate)->format('M j, Y') }})</span>
                                    @endif
                                </label>
                                
                                @if($selectedDate)
                                    @if(empty($availableTimeSlots))
                                        <div class="text-center py-8 text-gray-500">
                                            <x-heroicon-o-clock class="w-8 h-8 mx-auto mb-2" />
                                            <p>No available times for this date</p>
                                            <p class="text-xs">Please select a different date</p>
                                        </div>
                                    @else
                                        <div class="grid grid-cols-2 gap-2 max-h-64 overflow-y-auto">
                                            @foreach($availableTimeSlots as $timeSlot)
                                                <button wire:click="selectTimeSlot('{{ $timeSlot }}')" 
                                                        class="p-2 text-sm rounded-lg border {{ $selectedTime === $timeSlot ? 'border-green-500 bg-green-50 dark:bg-green-900/20 text-green-700' : 'border-gray-300 dark:border-gray-600 hover:border-green-300' }} transition-colors">
                                                    {{ \Carbon\Carbon::parse($timeSlot)->format('g:i A') }}
                                                </button>
                                            @endforeach
                                        </div>
                                    @endif
                                @else
                                    <div class="text-center py-8 text-gray-500">
                                        <x-heroicon-o-calendar class="w-8 h-8 mx-auto mb-2" />
                                        <p>Please select a date first</p>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- Booking Summary -->
                        @if($selectedDate && $selectedTime)
                            <div class="mt-6 bg-green-50 dark:bg-green-900/20 rounded-lg p-4">
                                <h4 class="font-medium text-gray-900 dark:text-white mb-2">Booking Summary:</h4>
                                <div class="text-sm text-gray-700 dark:text-gray-300">
                                    <p><strong>Date:</strong> {{ \Carbon\Carbon::parse($selectedDate)->format('l, F j, Y') }}</p>
                                    <p><strong>Time:</strong> {{ \Carbon\Carbon::parse($selectedTime)->format('g:i A') }}</p>
                                    <p><strong>Duration:</strong> {{ collect($cart)->where('type', 'service')->sum('duration') }} minutes</p>
                                    <p><strong>Total:</strong> KES {{ number_format(collect($cart)->where('type', 'service')->sum('price')) }}</p>
                                </div>
                            </div>
                        @endif

                        <!-- Action Buttons -->
                        <div class="flex justify-end space-x-3 mt-6">
                            <button wire:click="$set('showDateTimeModal', false)" 
                                    class="px-4 py-2 text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200">
                                Cancel
                            </button>
                            <button wire:click="confirmBookingDateTime" 
                                    class="px-8 py-3 bg-success-600 hover:bg-success-700 text-white rounded-lg font-bold shadow-lg {{ !$selectedDate || !$selectedTime ? 'opacity-50 cursor-not-allowed' : '' }}"
                                    {{ !$selectedDate || !$selectedTime ? 'disabled' : '' }}>
                                Confirm Date & Time
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- Staff Selection Modal -->
        @if($showStaffSelectionModal && $pendingServiceId)
            <div class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex items-center justify-center min-h-screen px-4">
                    <div class="fixed inset-0 bg-black opacity-50"></div>
                    <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-lg w-full p-6">
                        <div class="flex items-center justify-between mb-6">
                            <h3 class="text-xl font-semibold text-gray-900 dark:text-white">Select Staff Member</h3>
                            <button wire:click="cancelStaffSelection" class="text-gray-400 hover:text-gray-600">
                                <x-heroicon-o-x-mark class="w-6 h-6" />
                            </button>
                        </div>
                        
                        <!-- Service Info -->
                        @php
                            $pendingService = $pendingServiceId ? \App\Models\Service::find($pendingServiceId) : null;
                        @endphp
                        
                        @if($pendingService)
                            <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4 mb-6">
                                <div class="flex items-center space-x-3">
                                    <x-heroicon-o-sparkles class="w-8 h-8 text-blue-600" />
                                    <div>
                                        <h4 class="font-semibold text-gray-900 dark:text-white">{{ $pendingService->name }}</h4>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">
                                            {{ $pendingService->duration_minutes }}min • KES {{ number_format($pendingService->price) }}
                                        </p>
                                        @if($pendingService->description)
                                            <p class="text-xs text-gray-500 mt-1">{{ $pendingService->description }}</p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endif

                        <!-- Available Staff -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                                Available Staff Members
                            </label>
                            
                            @if(empty($availableStaffForService))
                                <div class="text-center py-8 text-gray-500">
                                    <x-heroicon-o-user-group class="w-12 h-12 mx-auto mb-3" />
                                    <p class="font-medium">No staff available</p>
                                    <p class="text-sm">No staff members are qualified for this service</p>
                                </div>
                            @else
                                <div class="space-y-3 max-h-64 overflow-y-auto">
                                    @foreach($availableStaffForService as $staffMember)
                                        <button wire:click="selectStaffForService({{ $staffMember['id'] }})" 
                                                class="w-full text-left p-4 border border-gray-300 dark:border-gray-600 rounded-lg hover:border-blue-500 hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-colors group">
                                            <div class="flex items-center space-x-3">
                                                <div class="w-10 h-10 bg-gradient-to-r from-blue-500 to-purple-600 rounded-full flex items-center justify-center">
                                                    <x-heroicon-o-user class="w-5 h-5 text-white" />
                                                </div>
                                                <div class="flex-1">
                                                    <h5 class="font-medium text-gray-900 dark:text-white group-hover:text-blue-600">
                                                        {{ $staffMember['name'] }}
                                                    </h5>
                                                    <p class="text-sm text-gray-500">
                                                        {{ $staffMember['specialization'] }}
                                                    </p>
                                                </div>
                                                <x-heroicon-o-chevron-right class="w-5 h-5 text-gray-400 group-hover:text-blue-500" />
                                            </div>
                                        </button>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex justify-end space-x-3 mt-6">
                            <button wire:click="cancelStaffSelection" 
                                    class="px-4 py-2 text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200">
                                Cancel
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- Booking Confirmation Modal -->
        @if($showBookingConfirmationModal)
            <div class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex items-center justify-center min-h-screen px-4">
                    <div class="fixed inset-0 bg-black opacity-50"></div>
                    <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-2xl w-full p-6">
                        <div class="flex items-center justify-between mb-6">
                            <h3 class="text-xl font-semibold text-gray-900 dark:text-white">Confirm Your Booking</h3>
                            <button wire:click="$set('showBookingConfirmationModal', false)" class="text-gray-400 hover:text-gray-600">
                                <x-heroicon-o-x-mark class="w-6 h-6" />
                            </button>
                        </div>
                        
                        <!-- Booking Summary -->
                        <div class="bg-gradient-to-r from-primary-50 to-purple-50 dark:from-primary-900/20 dark:to-purple-900/20 rounded-lg p-6 mb-6">
                            <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Booking Summary</h4>
                            
                            <!-- Services -->
                            <div class="space-y-4">
                                @foreach(collect($cart)->where('type', 'service') as $service)
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center space-x-3">
                                            <div class="w-12 h-12 bg-primary-500 rounded-full flex items-center justify-center">
                                                <x-heroicon-o-sparkles class="w-6 h-6 text-white" />
                                            </div>
                                            <div>
                                                <div class="font-semibold text-gray-900 dark:text-white">{{ $service['name'] }}</div>
                                                <div class="text-sm text-gray-600 dark:text-gray-400">
                                                    {{ $service['duration'] }}min with {{ $service['staff_name'] }}
                                                </div>
                                            </div>
                                        </div>
                                        <div class="text-right">
                                            <div class="font-bold text-gray-900 dark:text-white">KES {{ number_format($service['price']) }}</div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            
                            <!-- Date & Time -->
                            <div class="mt-6 pt-4 border-t border-primary-200 dark:border-primary-700">
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <div class="text-sm text-gray-600 dark:text-gray-400">Date</div>
                                        <div class="font-semibold text-gray-900 dark:text-white">
                                            {{ $selectedDate ? \Carbon\Carbon::parse($selectedDate)->format('l, F j, Y') : 'Not selected' }}
                                        </div>
                                    </div>
                                    <div>
                                        <div class="text-sm text-gray-600 dark:text-gray-400">Time</div>
                                        <div class="font-semibold text-gray-900 dark:text-white">
                                            {{ $selectedTime ? \Carbon\Carbon::parse($selectedTime)->format('g:i A') : 'Not selected' }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Customer -->
                            @if($selectedCustomerId || !empty($customerData['name']))
                                <div class="mt-4 pt-4 border-t border-primary-200 dark:border-primary-700">
                                    <div class="text-sm text-gray-600 dark:text-gray-400">Customer</div>
                                    <div class="font-semibold text-gray-900 dark:text-white">
                                        {{ $customerData['name'] ?? 'Walk-in Customer' }}
                                    </div>
                                    @if(!empty($customerData['phone']))
                                        <div class="text-sm text-gray-600 dark:text-gray-400">{{ $customerData['phone'] }}</div>
                                    @endif
                                </div>
                            @endif
                            
                            <!-- Total -->
                            <div class="mt-6 pt-4 border-t border-primary-200 dark:border-primary-700">
                                <div class="flex justify-between items-center">
                                    <div class="text-lg font-semibold text-gray-900 dark:text-white">Total Amount</div>
                                    <div class="text-2xl font-bold text-primary-600">KES {{ number_format($totalAmount, 2) }}</div>
                                </div>
                                <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                    Includes {{ collect($cart)->where('type', 'service')->sum('duration') }} minutes of services
                                </div>
                            </div>
                        </div>

                        <!-- Important Notes -->
                        <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-700 rounded-lg p-4 mb-6">
                            <div class="flex items-start space-x-3">
                                <x-heroicon-o-exclamation-triangle class="w-5 h-5 text-yellow-600 mt-0.5" />
                                <div>
                                    <h5 class="font-medium text-yellow-800 dark:text-yellow-200">Important Information</h5>
                                    <ul class="text-sm text-yellow-700 dark:text-yellow-300 mt-1 space-y-1">
                                        <li>• Please arrive 10 minutes before your appointment time</li>
                                        <li>• Cancellations must be made at least 24 hours in advance</li>
                                        <li>• Payment will be processed after confirmation</li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex justify-end space-x-3">
                            <button wire:click="$set('showBookingConfirmationModal', false)" 
                                    class="px-6 py-2 text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 font-medium">
                                Back to Edit
                            </button>
                            <button wire:click="confirmBookingAndProceedToPayment" 
                                    class="px-8 py-3 bg-primary-600 hover:bg-primary-700 text-white rounded-lg font-bold shadow-lg">
                                Confirm Booking & Proceed to Payment
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>