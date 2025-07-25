<!-- Cart Review Step -->
<div class="max-w-4xl mx-auto">
    <!-- Header -->
    <div class="text-center mb-8">
        <div class="w-16 h-16 bg-gradient-to-r from-blue-500 to-purple-600 rounded-2xl flex items-center justify-center mx-auto mb-4">
            <x-heroicon-o-shopping-cart class="w-8 h-8 text-white" />
        </div>
        <h4 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">Review Your Order</h4>
        <p class="text-lg text-gray-600 dark:text-gray-400">Please review your items before proceeding to payment</p>
    </div>

    <!-- Cart Items -->
    <div class="bg-white dark:bg-gray-700 rounded-2xl border border-gray-200 dark:border-gray-600 shadow-lg overflow-hidden mb-8">
        <div class="bg-gradient-to-r from-gray-50 to-blue-50 dark:from-gray-800 dark:to-blue-900/20 px-6 py-4 border-b border-gray-200 dark:border-gray-600">
            <h5 class="text-lg font-bold text-gray-900 dark:text-white flex items-center">
                <x-heroicon-o-list-bullet class="w-5 h-5 mr-2 text-blue-600" />
                Order Items ({{ count($cart) }})
            </h5>
        </div>
        
        <div class="divide-y divide-gray-200 dark:divide-gray-600">
            @foreach($cart as $cartItemId => $item)
                <div class="p-6">
                    <div class="flex items-start justify-between">
                        <!-- Item Details -->
                        <div class="flex items-start space-x-4 flex-1">
                            <!-- Item Icon -->
                            <div class="w-12 h-12 rounded-xl flex items-center justify-center {{ $item['type'] === 'service' ? 'bg-blue-100 dark:bg-blue-900' : 'bg-green-100 dark:bg-green-900' }}">
                                @if($item['type'] === 'service')
                                    <x-heroicon-o-sparkles class="w-6 h-6 text-blue-600 dark:text-blue-400" />
                                @else
                                    <x-heroicon-o-cube class="w-6 h-6 text-green-600 dark:text-green-400" />
                                @endif
                            </div>
                            
                            <div class="flex-1">
                                <!-- Item Name & Type -->
                                <div class="flex items-center space-x-2 mb-2">
                                    <h6 class="text-lg font-bold text-gray-900 dark:text-white">{{ $item['name'] }}</h6>
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium {{ $item['type'] === 'service' ? 'bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200' : 'bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200' }}">
                                        {{ ucfirst($item['type']) }}
                                    </span>
                                    @if($item['type'] === 'service' && $item['duration'])
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300">
                                            <x-heroicon-o-clock class="w-3 h-3 mr-1" />
                                            {{ $item['duration'] }}min
                                        </span>
                                    @endif
                                </div>
                                
                                <!-- Service Booking Details -->
                                @if($item['type'] === 'service')
                                    <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-3 space-y-2">
                                        <h6 class="font-semibold text-blue-800 dark:text-blue-200 text-sm">Booking Details</h6>
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-2 text-sm">
                                            <div class="flex items-center space-x-2">
                                                <x-heroicon-o-user class="w-4 h-4 text-gray-500" />
                                                <span class="text-gray-700 dark:text-gray-300">
                                                    <strong>Staff:</strong> {{ $item['staff_name'] ?? 'No preference' }}
                                                </span>
                                            </div>
                                            @if(isset($item['appointment_date']))
                                                <div class="flex items-center space-x-2">
                                                    <x-heroicon-o-calendar class="w-4 h-4 text-gray-500" />
                                                    <span class="text-gray-700 dark:text-gray-300">
                                                        <strong>Date:</strong> {{ date('M j, Y', strtotime($item['appointment_date'])) }}
                                                    </span>
                                                </div>
                                            @endif
                                            @if(isset($item['appointment_time']))
                                                <div class="flex items-center space-x-2">
                                                    <x-heroicon-o-clock class="w-4 h-4 text-gray-500" />
                                                    <span class="text-gray-700 dark:text-gray-300">
                                                        <strong>Time:</strong> {{ date('g:i A', strtotime($item['appointment_time'])) }}
                                                    </span>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @endif
                                
                                <!-- Product Quantity -->
                                @if($item['type'] === 'product' && $item['quantity'] > 1)
                                    <div class="mt-2">
                                        <span class="text-sm text-gray-600 dark:text-gray-400">
                                            Quantity: <strong>{{ $item['quantity'] }}</strong>
                                        </span>
                                    </div>
                                @endif
                            </div>
                        </div>
                        
                        <!-- Price -->
                        <div class="text-right ml-4">
                            @if($item['quantity'] > 1)
                                <div class="text-sm text-gray-500 dark:text-gray-400 mb-1">
                                    KES {{ number_format($item['price']) }} × {{ $item['quantity'] }}
                                </div>
                            @endif
                            <div class="text-xl font-bold text-gray-900 dark:text-white">
                                KES {{ number_format($item['price'] * $item['quantity']) }}
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Order Summary -->
    <div class="bg-gradient-to-r from-blue-50 to-purple-50 dark:from-gray-800 dark:to-gray-700 rounded-2xl border border-blue-200 dark:border-blue-600 p-6">
        <h5 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center">
            <x-heroicon-o-calculator class="w-5 h-5 mr-2 text-blue-600" />
            Order Summary
        </h5>
        
        <div class="space-y-3">
            <!-- Subtotal -->
            <div class="flex justify-between text-lg">
                <span class="font-medium text-gray-700 dark:text-gray-300">Subtotal</span>
                <span class="font-semibold text-gray-900 dark:text-white">KES {{ number_format($subtotal, 2) }}</span>
            </div>
            
            <!-- Tax -->
            <div class="flex justify-between text-lg">
                <span class="font-medium text-gray-700 dark:text-gray-300">Tax (16% VAT)</span>
                <span class="font-semibold text-gray-900 dark:text-white">KES {{ number_format($subtotal * 0.16, 2) }}</span>
            </div>
            
            <!-- Discounts (if any) -->
            @if($discountAmount > 0)
                <div class="flex justify-between text-lg text-green-600">
                    <span class="font-medium">Manual Discount</span>
                    <span class="font-semibold">-KES {{ number_format($discountAmount, 2) }}</span>
                </div>
            @endif
            
            @if($voucherDiscountAmount > 0)
                <div class="flex justify-between text-lg text-green-600">
                    <span class="font-medium">Gift Voucher</span>
                    <span class="font-semibold">-KES {{ number_format($voucherDiscountAmount, 2) }}</span>
                </div>
            @endif
            
            @if($couponDiscountAmount > 0)
                <div class="flex justify-between text-lg text-orange-600">
                    <span class="font-medium">Coupon Discount</span>
                    <span class="font-semibold">-KES {{ number_format($couponDiscountAmount, 2) }}</span>
                </div>
            @endif
            
            <!-- Total -->
            <div class="border-t-2 border-blue-300 dark:border-blue-600 pt-3">
                <div class="flex justify-between text-2xl font-bold">
                    <span class="text-gray-900 dark:text-white">Total Amount</span>
                    <span class="text-blue-600 dark:text-blue-400">KES {{ number_format($subtotal * 1.16 - $discountAmount - $voucherDiscountAmount - $couponDiscountAmount, 2) }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Customer Information Section -->
    <div class="mt-8 bg-white dark:bg-gray-700 rounded-2xl border border-gray-200 dark:border-gray-600 shadow-lg p-6">
        <h5 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center">
            <x-heroicon-o-user class="w-5 h-5 mr-2 text-emerald-600" />
            Customer Information
        </h5>
        
        @if($customerData['type'] === 'walk_in')
            <!-- Walk-in Customer Form -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Customer Name *</label>
                    <input wire:model="customerData.name" 
                           type="text" 
                           placeholder="Enter customer name"
                           class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-gray-800 text-gray-900 dark:text-white">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Phone Number *</label>
                    <input wire:model="customerData.phone" 
                           type="tel" 
                           placeholder="Enter phone number"
                           class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-gray-800 text-gray-900 dark:text-white">
                </div>
                
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Email Address (Optional)</label>
                    <input wire:model="customerData.email" 
                           type="email" 
                           placeholder="Enter email address"
                           class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-gray-800 text-gray-900 dark:text-white">
                </div>
            </div>
        @else
            <!-- Registered Customer Display -->
            <div class="bg-emerald-50 dark:bg-emerald-900/20 rounded-lg p-4">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-emerald-500 rounded-full flex items-center justify-center">
                        <x-heroicon-o-check class="w-5 h-5 text-white" />
                    </div>
                    <div>
                        <p class="font-semibold text-emerald-800 dark:text-emerald-200">{{ $customerData['name'] }}</p>
                        <p class="text-sm text-emerald-600 dark:text-emerald-400">{{ $customerData['phone'] }} • {{ $customerData['email'] }}</p>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <!-- Important Notes -->
    <div class="mt-6 bg-amber-50 dark:bg-amber-900/20 rounded-xl p-4 border border-amber-200 dark:border-amber-700">
        <div class="flex items-start space-x-3">
            <x-heroicon-o-information-circle class="w-5 h-5 text-amber-600 mt-0.5 flex-shrink-0" />
            <div class="text-sm">
                <p class="font-medium text-amber-800 dark:text-amber-200 mb-1">Important Notes:</p>
                <ul class="text-amber-700 dark:text-amber-300 space-y-1">
                    <li>• Please review all details carefully before proceeding</li>
                    <li>• Service bookings cannot be modified after payment</li>
                    <li>• Cancellation policies apply to all services</li>
                    <li>• Receipt will be provided after successful payment</li>
                </ul>
            </div>
        </div>
    </div>
</div>