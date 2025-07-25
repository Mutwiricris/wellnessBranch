<x-filament-panels::page>
<div class="h-screen bg-gray-50 dark:bg-gray-900 -m-6" 
     x-data="{
        init() {
            this.setupKeyboardShortcuts();
        },
        setupKeyboardShortcuts() {
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') {
                    @this.set('searchTerm', '');
                }
                if (e.key === 'Enter' && e.ctrlKey) {
                    @this.call('processPayment');
                }
                if (e.key === 'v' && e.ctrlKey) {
                    e.preventDefault();
                    @this.set('showVoucherModal', true);
                }
                if (e.key === 'c' && e.ctrlKey) {
                    e.preventDefault();
                    @this.set('showCouponModal', true);
                }
                if (e.key === 'l' && e.ctrlKey) {
                    e.preventDefault();
                    @this.set('showLoyaltyModal', true);
                }
            });
        }
     }" 
     x-init="init()"
     x-on:livewire:initialized="
        @this.on('mpesa-payment-initiated', (event) => {
            setTimeout(() => {
                const success = Math.random() > 0.3;
                if (success) {
                    const mpesaId = 'MPX' + Math.random().toString(36).substr(2, 9).toUpperCase();
                    @this.call('handleMpesaSuccess', event.transaction_id, mpesaId);
                } else {
                    @this.call('handleMpesaFailure', event.transaction_id, 'Customer cancelled payment');
                }
            }, 3000);
        });
        @this.on('print-receipt', (event) => {
            console.log('Printing receipt for transaction:', event.transaction_id);
        });
     "
     style="[x-cloak] { display: none !important; } .modal-backdrop { backdrop-filter: blur(4px); }">
         
    <!-- Modern Header Bar -->
    <div class="bg-white dark:bg-gray-800 shadow-sm border-b border-gray-200 dark:border-gray-700 px-6 py-4">
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
            </div>
            
            <div class="flex items-center space-x-3">
                <!-- Staff Selector -->
                <div class="flex items-center space-x-2">
                    <x-heroicon-o-user class="w-5 h-5 text-gray-400" />
                    <select wire:model.live="selectedStaffId" 
                            class="border-0 bg-gray-100 dark:bg-gray-700 rounded-lg text-sm font-medium focus:ring-2 focus:ring-blue-500">
                        <option value="">Select Staff</option>
                        @foreach($this->getStaff() as $staff)
                            <option value="{{ $staff->id }}">{{ $staff->name }}</option>
                        @endforeach
                    </select>
                </div>
                
                <!-- Action Buttons -->
                <button wire:click="clearCart" 
                        class="bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 px-4 py-2 rounded-lg transition-colors">
                    <x-heroicon-o-arrow-path class="w-4 h-4 mr-2 inline" />
                    New Sale
                </button>
                
                <button wire:click="showDailySummary" 
                        class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg transition-colors">
                    <x-heroicon-o-chart-bar class="w-4 h-4 mr-2 inline" />
                    Daily Summary
                </button>
            </div>
        </div>
    </div>

    <div class="flex h-full">
        <!-- Left Panel - Product Categories & Items -->
        <div class="flex-1 p-6 overflow-y-auto">
            <!-- Search Bar -->
            <div class="mb-6">
                <div class="relative">
                    <x-heroicon-o-magnifying-glass class="absolute left-3 top-1/2 transform -translate-y-1/2 w-5 h-5 text-gray-400" />
                    <input wire:model.live="searchTerm" 
                           type="text" 
                           placeholder="Search products and services..." 
                           class="w-full pl-10 pr-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-gray-800 text-gray-900 dark:text-white">
                </div>
            </div>

            <!-- Category Tabs -->
            <div class="mb-6">
                <div class="flex space-x-2 overflow-x-auto pb-2">
                    @foreach($this->getServiceCategories() as $categoryId => $categoryName)
                        <button wire:click="setActiveCategory('{{ $categoryId }}')" 
                                class="flex-shrink-0 px-4 py-2 rounded-lg font-medium transition-colors {{ $activeCategory === $categoryId ? 'bg-blue-500 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600' }}">
                            {{ $categoryName }}
                        </button>
                    @endforeach
                    
                    <!-- Product Category Dropdown -->
                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open" 
                                class="flex-shrink-0 px-4 py-2 rounded-lg font-medium transition-colors {{ $activeCategory === 'products' ? 'bg-green-500 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600' }} flex items-center space-x-1">
                            <x-heroicon-o-cube class="w-4 h-4" />
                            <span>Products</span>
                            <x-heroicon-o-chevron-down class="w-4 h-4" />
                        </button>
                        
                        <div x-show="open" 
                             @click.away="open = false"
                             x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="opacity-0 scale-95"
                             x-transition:enter-end="opacity-100 scale-100"
                             x-transition:leave="transition ease-in duration-75"
                             x-transition:leave-start="opacity-100 scale-100"
                             x-transition:leave-end="opacity-0 scale-95"
                             class="absolute z-10 mt-2 w-48 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700">
                            @foreach($this->getProductCategories() as $categoryId => $categoryName)
                                <button wire:click="setActiveCategory('{{ $categoryId }}'); open = false" 
                                        class="block w-full text-left px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-700 first:rounded-t-lg last:rounded-b-lg">
                                    {{ $categoryName }}
                                </button>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <!-- Items Grid -->
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                @if($activeCategory && str_starts_with($activeCategory, 'service_'))
                    @foreach($this->getFilteredServices() as $service)
                        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden hover:shadow-lg transition-shadow cursor-pointer"
                             wire:click="addToCart('service', {{ $service->id }})">
                            @if($service->image_url)
                                <img src="{{ $service->image_url }}" alt="{{ $service->name }}" class="w-full h-32 object-cover">
                            @else
                                <div class="w-full h-32 bg-gradient-to-br from-blue-100 to-purple-100 dark:from-blue-900 dark:to-purple-900 flex items-center justify-center">
                                    <x-heroicon-o-sparkles class="w-12 h-12 text-blue-500" />
                                </div>
                            @endif
                            
                            <div class="p-4">
                                <div class="flex items-center justify-between mb-2">
                                    <h3 class="font-semibold text-gray-900 dark:text-white text-sm">{{ $service->name }}</h3>
                                    <button class="text-blue-500 hover:text-blue-600">
                                        <x-heroicon-o-plus class="w-5 h-5" />
                                    </button>
                                </div>
                                <p class="text-xs text-gray-600 dark:text-gray-400 mb-2 line-clamp-2">{{ $service->description }}</p>
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-bold text-gray-900 dark:text-white">KES {{ number_format($service->price) }}</span>
                                    @if($service->duration_minutes)
                                        <span class="text-xs text-gray-500 dark:text-gray-400 flex items-center">
                                            <x-heroicon-o-clock class="w-3 h-3 mr-1" />
                                            {{ $service->duration_minutes }}min
                                        </span>
                                    @endif
                                </div>
                                @if($service->is_popular)
                                    <div class="mt-2">
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200">
                                            <x-heroicon-o-star class="w-3 h-3 mr-1" />
                                            Popular
                                        </span>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                @else
                    @foreach($this->getFilteredProducts() as $product)
                        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden hover:shadow-lg transition-shadow cursor-pointer"
                             wire:click="addToCart('product', {{ $product->id }})">
                            @if($product->image_url)
                                <img src="{{ $product->image_url }}" alt="{{ $product->name }}" class="w-full h-32 object-cover">
                            @else
                                <div class="w-full h-32 bg-gradient-to-br from-green-100 to-emerald-100 dark:from-green-900 dark:to-emerald-900 flex items-center justify-center">
                                    <x-heroicon-o-cube class="w-12 h-12 text-green-500" />
                                </div>
                            @endif
                            
                            <div class="p-4">
                                <div class="flex items-center justify-between mb-2">
                                    <h3 class="font-semibold text-gray-900 dark:text-white text-sm">{{ $product->name }}</h3>
                                    <button class="text-green-500 hover:text-green-600">
                                        <x-heroicon-o-plus class="w-5 h-5" />
                                    </button>
                                </div>
                                <p class="text-xs text-gray-600 dark:text-gray-400 mb-2 line-clamp-2">{{ $product->description }}</p>
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-bold text-gray-900 dark:text-white">KES {{ number_format($product->selling_price) }}</span>
                                    <span class="text-xs text-gray-500 dark:text-gray-400">Stock: {{ $product->current_stock }}</span>
                                </div>
                            </div>
                        </div>
                    @endforeach
                @endif
            </div>

            <!-- Quick Actions -->
            <div class="mt-8 grid grid-cols-2 md:grid-cols-4 gap-4">
                <button wire:click="showVoucherModal" 
                        class="p-4 bg-gradient-to-r from-purple-500 to-pink-500 hover:from-purple-600 hover:to-pink-600 text-white rounded-xl text-center transition-colors">
                    <div class="flex flex-col items-center space-y-2">
                        <x-heroicon-o-gift class="w-6 h-6" />
                        <span class="text-sm font-medium">Gift Voucher</span>
                    </div>
                </button>
                
                <button wire:click="showCouponModal" 
                        class="p-4 bg-gradient-to-r from-orange-500 to-red-500 hover:from-orange-600 hover:to-red-600 text-white rounded-xl text-center transition-colors">
                    <div class="flex flex-col items-center space-y-2">
                        <x-heroicon-o-ticket class="w-6 h-6" />
                        <span class="text-sm font-medium">Coupon</span>
                    </div>
                </button>
                
                <button wire:click="showLoyaltyModal" 
                        class="p-4 bg-gradient-to-r from-indigo-500 to-blue-500 hover:from-indigo-600 hover:to-blue-600 text-white rounded-xl text-center transition-colors">
                    <div class="flex flex-col items-center space-y-2">
                        <x-heroicon-o-star class="w-6 h-6" />
                        <span class="text-sm font-medium">Loyalty Points</span>
                    </div>
                </button>
                
                <button wire:click="showBookingModal" 
                        class="p-4 bg-gradient-to-r from-green-500 to-teal-500 hover:from-green-600 hover:to-teal-600 text-white rounded-xl text-center transition-colors">
                    <div class="flex flex-col items-center space-y-2">
                        <x-heroicon-o-calendar-days class="w-6 h-6" />
                        <span class="text-sm font-medium">Book Service</span>
                    </div>
                </button>
            </div>
        </div>

        <!-- Right Panel - Cart & Checkout -->
        <div class="w-96 bg-white dark:bg-gray-800 border-l border-gray-200 dark:border-gray-700 flex flex-col">
            <!-- Cart Header -->
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white flex items-center">
                        <x-heroicon-o-shopping-cart class="w-6 h-6 mr-2" />
                        Cart ({{ count($cart) }})
                    </h2>
                    @if(!empty($cart))
                        <button wire:click="clearCart" class="text-red-500 hover:text-red-600 text-sm">
                            Clear All
                        </button>
                    @endif
                </div>
            </div>

            <!-- Cart Items -->
            <div class="flex-1 overflow-y-auto p-6">
                @if(empty($cart))
                    <div class="text-center py-12">
                        <x-heroicon-o-shopping-cart class="w-16 h-16 text-gray-300 dark:text-gray-600 mx-auto mb-4" />
                        <p class="text-gray-500 dark:text-gray-400">Your cart is empty</p>
                        <p class="text-sm text-gray-400 dark:text-gray-500 mt-1">Add services or products to get started</p>
                    </div>
                @else
                    <div class="space-y-4">
                        @foreach($cart as $cartItemId => $item)
                            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                                <div class="flex items-start justify-between">
                                    <div class="flex items-start space-x-3 flex-1">
                                        <div class="w-8 h-8 rounded-lg flex items-center justify-center {{ $item['type'] === 'service' ? 'bg-blue-100 dark:bg-blue-900' : 'bg-green-100 dark:bg-green-900' }}">
                                            @if($item['type'] === 'service')
                                                <x-heroicon-o-sparkles class="w-4 h-4 text-blue-600 dark:text-blue-400" />
                                            @else
                                                <x-heroicon-o-cube class="w-4 h-4 text-green-600 dark:text-green-400" />
                                            @endif
                                        </div>
                                        
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center space-x-2 mb-1">
                                                <h4 class="font-semibold text-gray-900 dark:text-white text-sm truncate">{{ $item['name'] }}</h4>
                                                <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-medium {{ $item['type'] === 'service' ? 'bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200' : 'bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200' }}">
                                                    {{ ucfirst($item['type']) }}
                                                </span>
                                            </div>
                                            
                                            @if($item['type'] === 'service' && isset($item['duration']))
                                                <div class="text-xs text-gray-500 dark:text-gray-400 flex items-center mb-2">
                                                    <x-heroicon-o-clock class="w-3 h-3 mr-1" />
                                                    {{ $item['duration'] }} minutes
                                                </div>
                                            @endif

                                            @if($item['type'] === 'service')
                                                <button onclick="$wire.editServiceBooking('{{ $cartItemId }}')" 
                                                        class="text-xs text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300">
                                                    📅 {{ isset($item['appointment_date']) ? date('M j', strtotime($item['appointment_date'])) : 'Set Date/Time' }}
                                                    @if(isset($item['appointment_time']))
                                                        at {{ date('g:i A', strtotime($item['appointment_time'])) }}
                                                    @endif
                                                    
                                                    @if(isset($item['staff_name']))
                                                        <div class="mt-1">
                                                            👤 {{ $item['staff_name'] }}
                                                        </div>
                                                    @endif
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                    
                                    <button wire:click="removeFromCart('{{ $cartItemId }}')" 
                                            class="text-red-500 hover:text-red-600 p-1">
                                        <x-heroicon-o-trash class="w-4 h-4" />
                                    </button>
                                </div>
                                
                                <div class="flex items-center justify-between mt-3">
                                    <!-- Quantity Controls -->
                                    @if($item['type'] === 'product')
                                        <div class="flex items-center space-x-2">
                                            <button wire:click="updateQuantity('{{ $cartItemId }}', {{ $item['quantity'] - 1 }})" 
                                                    class="w-6 h-6 rounded-full bg-gray-200 dark:bg-gray-600 flex items-center justify-center hover:bg-gray-300 dark:hover:bg-gray-500">
                                                <x-heroicon-o-minus class="w-3 h-3" />
                                            </button>
                                            <span class="font-medium text-gray-900 dark:text-white">{{ $item['quantity'] }}</span>
                                            <button wire:click="updateQuantity('{{ $cartItemId }}', {{ $item['quantity'] + 1 }})" 
                                                    class="w-6 h-6 rounded-full bg-gray-200 dark:bg-gray-600 flex items-center justify-center hover:bg-gray-300 dark:hover:bg-gray-500">
                                                <x-heroicon-o-plus class="w-3 h-3" />
                                            </button>
                                        </div>
                                    @else
                                        <div class="text-sm text-gray-500 dark:text-gray-400 flex items-center">
                                            <x-heroicon-o-information-circle class="w-4 h-4 mr-1" />
                                            Service
                                        </div>
                                    @endif
                                    
                                    <!-- Price -->
                                    <div class="text-right">
                                        @if($item['quantity'] > 1)
                                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                                KES {{ number_format($item['price']) }} × {{ $item['quantity'] }}
                                            </div>
                                        @endif
                                        <div class="font-bold text-gray-900 dark:text-white">
                                            KES {{ number_format($item['price'] * $item['quantity']) }}
                                        </div>
                                    </div>
                                </div>
                                
                                @if($item['type'] === 'service' && !isset($item['appointment_date']))
                                    <div class="mt-2 text-xs text-amber-600 dark:text-amber-400 flex items-center">
                                        <x-heroicon-o-exclamation-triangle class="w-3 h-3 mr-1" />
                                        Please set booking date & time
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <!-- Simple Checkout Button (when not in checkout flow) -->
            @if(!empty($cart) && !$showCheckoutFlow)
                <div class="border-t border-gray-200 dark:border-gray-700 p-6">
                    <!-- Quick Summary -->
                    <div class="mb-4">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600 dark:text-gray-400">Subtotal</span>
                            <span class="font-medium">KES {{ number_format($subtotal, 2) }}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600 dark:text-gray-400">Tax (16%)</span>
                            <span class="font-medium">KES {{ number_format($subtotal * 0.16, 2) }}</span>
                        </div>
                        <div class="flex justify-between text-lg font-bold border-t pt-2 mt-2">
                            <span>Total</span>
                            <span>KES {{ number_format($subtotal * 1.16, 2) }}</span>
                        </div>
                    </div>
                    
                    <button wire:click="startCheckout" 
                            class="w-full bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white font-bold py-4 px-6 rounded-xl flex items-center justify-center space-x-2 transition-all">
                        <x-heroicon-o-shopping-cart class="w-5 h-5" />
                        <span class="text-lg">Proceed to Checkout</span>
                        <x-heroicon-o-arrow-right class="w-5 h-5" />
                    </button>
                </div>
            @endif
        </div>
    </div>

    <!-- Multi-Step Checkout Flow -->
    @if($showCheckoutFlow)
    <div x-data="{ show: @entangle('showCheckoutFlow') }"
         x-show="show" 
         x-cloak
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 modal-backdrop"
         style="background-color: rgba(0, 0, 0, 0.5);">
        
        <div class="min-h-screen px-4 text-center">
            <div class="inline-block w-full max-w-6xl my-8 text-left align-middle transition-all transform bg-white dark:bg-gray-800 shadow-2xl rounded-2xl overflow-hidden">
                
                <!-- Checkout Header -->
                <div class="bg-gradient-to-r from-blue-500 to-purple-600 px-8 py-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-2xl font-bold mb-1">Checkout Process</h3>
                            <p class="text-blue-100">Step {{ $checkoutStep === 'cart_review' ? '1' : ($checkoutStep === 'payment' ? '2' : '3') }} of 3</p>
                        </div>
                        <button wire:click="cancelCheckout" 
                                class="text-white hover:text-gray-200 transition-colors">
                            <x-heroicon-o-x-mark class="w-6 h-6" />
                        </button>
                    </div>
                </div>

                <!-- Progress Steps -->
                <div class="bg-gradient-to-r from-blue-500 to-purple-600 px-8 pb-6">
                    <div class="flex items-center justify-center">
                        <!-- Step 1: Cart Review -->
                        <div class="flex flex-col items-center flex-1">
                            <div class="w-10 h-10 {{ $checkoutStep === 'cart_review' ? 'bg-white text-blue-600 ring-4 ring-white/30' : ($checkoutStep === 'payment' || $checkoutStep === 'confirmation' ? 'bg-green-500 text-white' : 'bg-white/20 text-white border-2 border-white/40') }} rounded-full flex items-center justify-center text-sm font-bold shadow-lg transition-all duration-300">
                                @if($checkoutStep === 'payment' || $checkoutStep === 'confirmation')
                                    <x-heroicon-o-check class="w-5 h-5" />
                                @else
                                    <x-heroicon-o-shopping-cart class="w-5 h-5" />
                                @endif
                            </div>
                            <span class="text-xs font-semibold mt-2 {{ $checkoutStep === 'cart_review' ? 'text-white' : 'text-blue-200' }}">Review Cart</span>
                        </div>
                        
                        <div class="flex-1 h-1 mx-3 {{ $checkoutStep === 'payment' || $checkoutStep === 'confirmation' ? 'bg-green-400' : 'bg-white/30' }} rounded-full transition-all duration-500"></div>
                        
                        <!-- Step 2: Payment -->
                        <div class="flex flex-col items-center flex-1">
                            <div class="w-10 h-10 {{ $checkoutStep === 'payment' ? 'bg-white text-blue-600 ring-4 ring-white/30' : ($checkoutStep === 'confirmation' ? 'bg-green-500 text-white' : 'bg-white/20 text-white border-2 border-white/40') }} rounded-full flex items-center justify-center text-sm font-bold shadow-lg transition-all duration-300">
                                @if($checkoutStep === 'confirmation')
                                    <x-heroicon-o-check class="w-5 h-5" />
                                @else
                                    <x-heroicon-o-credit-card class="w-5 h-5" />
                                @endif
                            </div>
                            <span class="text-xs font-semibold mt-2 {{ $checkoutStep === 'payment' ? 'text-white' : 'text-blue-200' }}">Payment</span>
                        </div>
                        
                        <div class="flex-1 h-1 mx-3 {{ $checkoutStep === 'confirmation' ? 'bg-green-400' : 'bg-white/30' }} rounded-full transition-all duration-500"></div>
                        
                        <!-- Step 3: Confirmation -->
                        <div class="flex flex-col items-center flex-1">
                            <div class="w-10 h-10 {{ $checkoutStep === 'confirmation' ? 'bg-white text-blue-600 ring-4 ring-white/30' : 'bg-white/20 text-white border-2 border-white/40' }} rounded-full flex items-center justify-center text-sm font-bold shadow-lg transition-all duration-300">
                                <x-heroicon-o-check-circle class="w-5 h-5" />
                            </div>
                            <span class="text-xs font-semibold mt-2 {{ $checkoutStep === 'confirmation' ? 'text-white' : 'text-blue-200' }}">Confirm</span>
                        </div>
                    </div>
                </div>

                <!-- Dynamic Checkout Content -->
                <div class="flex-1 overflow-y-auto p-8">
                    @if($checkoutStep === 'cart_review')
                        @include('filament.pages.checkout.cart-review')
                    @elseif($checkoutStep === 'payment')
                        @include('filament.pages.checkout.payment-method')
                    @elseif($checkoutStep === 'confirmation')
                        @include('filament.pages.checkout.confirmation')
                    @endif
                </div>

                <!-- Checkout Footer Actions -->
                <div class="bg-gray-50 dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 p-6">
                    <div class="flex justify-between items-center">
                        @if($checkoutStep === 'cart_review')
                            <button wire:click="cancelCheckout" 
                                    class="flex items-center space-x-2 px-6 py-3 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 font-semibold rounded-xl transition-colors">
                                <x-heroicon-o-x-mark class="w-5 h-5" />
                                <span>Cancel</span>
                            </button>
                            <button wire:click="proceedToCheckoutPayment" 
                                    class="flex items-center space-x-2 px-8 py-3 bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white font-bold rounded-xl transition-colors">
                                <span>Continue to Payment</span>
                                <x-heroicon-o-arrow-right class="w-5 h-5" />
                            </button>
                        @elseif($checkoutStep === 'payment')
                            <button wire:click="goBackInCheckout" 
                                    class="flex items-center space-x-2 px-6 py-3 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 font-semibold rounded-xl transition-colors">
                                <x-heroicon-o-arrow-left class="w-5 h-5" />
                                <span>Back to Cart</span>
                            </button>
                            <button wire:click="proceedToConfirmation" 
                                    class="flex items-center space-x-2 px-8 py-3 bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 text-white font-bold rounded-xl transition-colors">
                                <span>Review Order</span>
                                <x-heroicon-o-arrow-right class="w-5 h-5" />
                            </button>
                        @elseif($checkoutStep === 'confirmation')
                            <button wire:click="goBackInCheckout" 
                                    class="flex items-center space-x-2 px-6 py-3 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 font-semibold rounded-xl transition-colors">
                                <x-heroicon-o-arrow-left class="w-5 h-5" />
                                <span>Back to Payment</span>
                            </button>
                            <button wire:click="completeTransaction" 
                                    class="flex items-center space-x-2 px-8 py-3 bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 text-white font-bold rounded-xl transition-colors">
                                <x-heroicon-o-check class="w-5 h-5" />
                                <span>Complete Purchase</span>
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Service Booking Modal -->
    @if($showServiceBookingFlow)
        <div x-data="{ show: @entangle('showServiceBookingFlow') }"
             x-show="show" 
             x-cloak
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 z-50 modal-backdrop"
             style="background-color: rgba(0, 0, 0, 0.5);">
            
            <div class="min-h-screen px-4 text-center">
                <div class="inline-block w-full max-w-4xl my-8 text-left align-middle transition-all transform bg-white dark:bg-gray-800 shadow-2xl rounded-2xl overflow-hidden">
                    
                    <!-- Modal Header -->
                    <div class="bg-gradient-to-r from-blue-500 to-purple-600 px-6 py-4">
                        <div class="flex items-center justify-between">
                            <h3 class="text-xl font-bold text-white">Complete Service Booking</h3>
                            <button wire:click="closeServiceBookingModal" 
                                    class="text-white hover:text-gray-200 transition-colors">
                                <x-heroicon-o-x-mark class="w-6 h-6" />
                            </button>
                        </div>
                    </div>

                    <!-- Modal Content -->
                    <div class="p-6">
                        @if($bookingStep === 'staff')
                            @include('filament.pages.pos-booking.staff-selection')
                        @elseif($bookingStep === 'datetime')
                            @include('filament.pages.pos-booking.datetime-selection')
                        @elseif($bookingStep === 'customer')
                            @include('filament.pages.pos-booking.customer-details')
                        @elseif($bookingStep === 'summary')
                            @include('filament.pages.pos-booking.booking-summary')
                        @endif
                    </div>

                    <!-- Modal Footer -->
                    <div class="bg-gray-50 dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 px-6 py-4">
                        <div class="flex justify-between items-center">
                            @if($bookingStep === 'staff')
                                <button wire:click="closeServiceBookingModal" 
                                        class="px-4 py-2 text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200 transition-colors">
                                    <x-heroicon-o-x-mark class="w-4 h-4 mr-2 inline" />
                                    Cancel
                                </button>
                                <button wire:click="proceedToDatetime" 
                                        class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition-colors">
                                    <x-heroicon-o-information-circle class="w-4 h-4 mr-2 inline" />
                                    Continue
                                </button>
                            @elseif($bookingStep === 'datetime')
                                <button wire:click="goBackToStaff" 
                                        class="px-4 py-2 text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200 transition-colors">
                                    <x-heroicon-o-arrow-left class="w-4 h-4 mr-2 inline" />
                                    Back
                                </button>
                                <button wire:click="proceedToCustomer" 
                                        class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition-colors">
                                    <x-heroicon-o-information-circle class="w-4 h-4 mr-2 inline" />
                                    Continue
                                </button>
                            @elseif($bookingStep === 'customer')
                                <button wire:click="goBackToDatetime" 
                                        class="px-4 py-2 text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200 transition-colors">
                                    <x-heroicon-o-arrow-left class="w-4 h-4 mr-2 inline" />
                                    Back
                                </button>
                                <button wire:click="proceedToSummary" 
                                        class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition-colors">
                                    @if(isset($customerData['name']) && $customerData['name'])
                                        <x-heroicon-o-check-circle class="w-4 h-4 mr-2 inline" />
                                        Review Booking
                                    @else
                                        <x-heroicon-o-exclamation-triangle class="w-4 h-4 mr-2 inline" />
                                        Complete Details
                                    @endif
                                </button>
                            @elseif($bookingStep === 'summary')
                                <button wire:click="goBackToCustomer" 
                                        class="px-4 py-2 text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200 transition-colors">
                                    <x-heroicon-o-arrow-left class="w-4 h-4 mr-2 inline" />
                                    Back
                                </button>
                                <button wire:click="confirmServiceBooking" 
                                        class="px-6 py-2 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg transition-colors">
                                    <x-heroicon-o-shopping-cart class="w-4 h-4 mr-2 inline" />
                                    Add to Cart
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Daily Summary Modal -->
    @if($showDailySummary)
        <div x-data="{ show: @entangle('showDailySummary') }"
             x-show="show" 
             x-cloak
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 z-50 modal-backdrop"
             style="background-color: rgba(0, 0, 0, 0.5);">
            
            <div class="min-h-screen px-4 text-center">
                <div class="inline-block w-full max-w-2xl my-8 text-left align-middle transition-all transform bg-white dark:bg-gray-800 shadow-2xl rounded-2xl overflow-hidden">
                    
                    <!-- Modal Header -->
                    <div class="bg-gradient-to-r from-indigo-500 to-blue-600 px-6 py-4">
                        <div class="flex items-center justify-between">
                            <h3 class="text-xl font-bold text-white flex items-center">
                                <x-heroicon-o-chart-bar class="w-6 h-6 mr-2" />
                                Daily Summary - {{ date('M j, Y') }}
                            </h3>
                            <button wire:click="closeDailySummary" 
                                    class="text-white hover:text-gray-200 transition-colors">
                                <x-heroicon-o-x-mark class="w-6 h-6" />
                            </button>
                        </div>
                    </div>

                    <!-- Summary Content -->
                    <div class="p-6">
                        @if($dailySummaryData)
                            <div class="grid grid-cols-2 gap-4 mb-6">
                                <div class="bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 rounded-lg p-4">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <p class="text-sm font-medium text-green-600 dark:text-green-400">Total Sales</p>
                                            <p class="text-2xl font-bold text-green-800 dark:text-green-200">
                                                KES {{ number_format($dailySummaryData['total_sales'] ?? 0, 2) }}
                                            </p>
                                        </div>
                                        <x-heroicon-o-currency-dollar class="w-8 h-8 text-green-500" />
                                    </div>
                                </div>
                                
                                <div class="bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-lg p-4">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <p class="text-sm font-medium text-blue-600 dark:text-blue-400">Transactions</p>
                                            <p class="text-2xl font-bold text-blue-800 dark:text-blue-200">
                                                {{ $dailySummaryData['transaction_count'] ?? 0 }}
                                            </p>
                                        </div>
                                        <x-heroicon-o-document-text class="w-8 h-8 text-blue-500" />
                                    </div>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div class="bg-gradient-to-r from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20 rounded-lg p-4">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <p class="text-sm font-medium text-purple-600 dark:text-purple-400">Services Sold</p>
                                            <p class="text-2xl font-bold text-purple-800 dark:text-purple-200">
                                                {{ $dailySummaryData['services_count'] ?? 0 }}
                                            </p>
                                        </div>
                                        <x-heroicon-o-sparkles class="w-8 h-8 text-purple-500" />
                                    </div>
                                </div>
                                
                                <div class="bg-gradient-to-r from-orange-50 to-red-50 dark:from-orange-900/20 dark:to-red-900/20 rounded-lg p-4">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <p class="text-sm font-medium text-orange-600 dark:text-orange-400">Products Sold</p>
                                            <p class="text-2xl font-bold text-orange-800 dark:text-orange-200">
                                                {{ $dailySummaryData['products_count'] ?? 0 }}
                                            </p>
                                        </div>
                                        <x-heroicon-o-cube class="w-8 h-8 text-orange-500" />
                                    </div>
                                </div>
                            </div>
                        @else
                            <div class="text-center py-8">
                                <x-heroicon-o-chart-bar class="w-16 h-16 text-gray-300 dark:text-gray-600 mx-auto mb-4" />
                                <p class="text-gray-500 dark:text-gray-400">No sales data available for today</p>
                            </div>
                        @endif
                    </div>

                    <!-- Modal Footer -->
                    <div class="bg-gray-50 dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 px-6 py-4">
                        <div class="flex justify-between items-center">
                            <div class="flex space-x-3">
                                <button wire:click="exportDailySummary" 
                                        class="flex items-center space-x-2 px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg transition-colors">
                                    <x-heroicon-o-printer class="w-4 h-4" />
                                    <span>Print</span>
                                </button>
                                <button wire:click="emailDailySummary" 
                                        class="flex items-center space-x-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition-colors">
                                    <x-heroicon-o-envelope class="w-4 h-4" />
                                    <span>Email</span>
                                </button>
                            </div>
                            <button wire:click="closeDailySummary" 
                                    class="px-4 py-2 text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200 transition-colors">
                                <x-heroicon-o-x-mark class="w-4 h-4 mr-2 inline" />
                                Close
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
</x-filament-panels::page>