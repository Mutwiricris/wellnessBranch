<!-- Confirmation Step -->
<div class="max-w-4xl mx-auto">
    <!-- Header -->
    <div class="text-center mb-8">
        <div class="w-16 h-16 bg-gradient-to-r from-green-500 to-emerald-600 rounded-2xl flex items-center justify-center mx-auto mb-4">
            <x-heroicon-o-check-circle class="w-8 h-8 text-white" />
        </div>
        <h4 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">Confirm Your Order</h4>
        <p class="text-lg text-gray-600 dark:text-gray-400">Please review all details before completing the purchase</p>
    </div>

    <!-- Order Summary -->
    <div class="bg-white dark:bg-gray-700 rounded-2xl border border-gray-200 dark:border-gray-600 shadow-lg mb-8">
        <!-- Header -->
        <div class="bg-gradient-to-r from-gray-50 to-blue-50 dark:from-gray-800 dark:to-blue-900/20 px-6 py-4 border-b border-gray-200 dark:border-gray-600 rounded-t-2xl">
            <h5 class="text-xl font-bold text-gray-900 dark:text-white flex items-center">
                <x-heroicon-o-document-text class="w-5 h-5 mr-2 text-blue-600" />
                Final Order Summary
            </h5>
        </div>
        
        <!-- Customer Information -->
        <div class="p-6 border-b border-gray-200 dark:border-gray-600">
            <h6 class="font-semibold text-gray-900 dark:text-white mb-3 flex items-center">
                <x-heroicon-o-user class="w-4 h-4 mr-2 text-emerald-600" />
                Customer Details
            </h6>
            <div class="bg-emerald-50 dark:bg-emerald-900/20 rounded-lg p-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                    <div>
                        <span class="font-medium text-gray-700 dark:text-gray-300">Name:</span>
                        <span class="ml-2 font-semibold text-emerald-800 dark:text-emerald-200">{{ $customerData['name'] ?: 'Walk-in Customer' }}</span>
                    </div>
                    @if($customerData['phone'])
                        <div>
                            <span class="font-medium text-gray-700 dark:text-gray-300">Phone:</span>
                            <span class="ml-2 font-semibold text-emerald-800 dark:text-emerald-200">{{ $customerData['phone'] }}</span>
                        </div>
                    @endif
                    @if($customerData['email'])
                        <div class="md:col-span-2">
                            <span class="font-medium text-gray-700 dark:text-gray-300">Email:</span>
                            <span class="ml-2 font-semibold text-emerald-800 dark:text-emerald-200">{{ $customerData['email'] }}</span>
                        </div>
                    @endif
                </div>
            </div>
        </div>
        
        <!-- Order Items -->
        <div class="p-6 border-b border-gray-200 dark:border-gray-600">
            <h6 class="font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                <x-heroicon-o-list-bullet class="w-4 h-4 mr-2 text-blue-600" />
                Items Ordered ({{ count($cart) }})
            </h6>
            <div class="space-y-4">
                @foreach($cart as $cartItemId => $item)
                    <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                        <div class="flex items-start justify-between">
                            <div class="flex items-start space-x-3 flex-1">
                                <div class="w-10 h-10 rounded-lg flex items-center justify-center {{ $item['type'] === 'service' ? 'bg-blue-100 dark:bg-blue-900' : 'bg-green-100 dark:bg-green-900' }}">
                                    @if($item['type'] === 'service')
                                        <x-heroicon-o-sparkles class="w-5 h-5 text-blue-600 dark:text-blue-400" />
                                    @else
                                        <x-heroicon-o-cube class="w-5 h-5 text-green-600 dark:text-green-400" />
                                    @endif
                                </div>
                                
                                <div class="flex-1">
                                    <div class="flex items-center space-x-2 mb-1">
                                        <h6 class="font-bold text-gray-900 dark:text-white">{{ $item['name'] }}</h6>
                                        @if($item['type'] === 'service' && $item['duration'])
                                            <span class="text-xs bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-400 px-2 py-1 rounded-full">
                                                {{ $item['duration'] }}min
                                            </span>
                                        @endif
                                        @if($item['quantity'] > 1)
                                            <span class="text-xs bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 px-2 py-1 rounded-full">
                                                Qty: {{ $item['quantity'] }}
                                            </span>
                                        @endif
                                    </div>
                                    
                                    @if($item['type'] === 'service')
                                        <div class="text-sm text-gray-600 dark:text-gray-400 space-y-1">
                                            <div>👤 Staff: {{ $item['staff_name'] ?? 'No preference' }}</div>
                                            @if(isset($item['appointment_date']))
                                                <div>📅 Date: {{ date('l, F j, Y', strtotime($item['appointment_date'])) }}</div>
                                            @endif
                                            @if(isset($item['appointment_time']))
                                                <div>🕐 Time: {{ date('g:i A', strtotime($item['appointment_time'])) }}</div>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            </div>
                            
                            <div class="text-right">
                                @if($item['quantity'] > 1)
                                    <div class="text-sm text-gray-500 dark:text-gray-400">
                                        KES {{ number_format($item['price']) }} × {{ $item['quantity'] }}
                                    </div>
                                @endif
                                <div class="font-bold text-gray-900 dark:text-white">
                                    KES {{ number_format($item['price'] * $item['quantity']) }}
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
        
        <!-- Payment Information -->
        <div class="p-6">
            <h6 class="font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                <x-heroicon-o-credit-card class="w-4 h-4 mr-2 text-green-600" />
                Payment Information
            </h6>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Payment Method -->
                <div>
                    <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4">
                        <div class="flex items-center space-x-3">
                            @if($paymentMethod === 'cash')
                                <x-heroicon-o-banknotes class="w-8 h-8 text-green-600" />
                                <div>
                                    <p class="font-bold text-green-800 dark:text-green-200">Cash Payment</p>
                                    <p class="text-sm text-green-600 dark:text-green-400">Payment at checkout</p>
                                </div>
                            @elseif($paymentMethod === 'mpesa')
                                <x-heroicon-o-device-phone-mobile class="w-8 h-8 text-green-600" />
                                <div>
                                    <p class="font-bold text-green-800 dark:text-green-200">M-Pesa Payment</p>
                                    <p class="text-sm text-green-600 dark:text-green-400">{{ $mpesaPhone ?? $customerData['phone'] }}</p>
                                </div>
                            @elseif($paymentMethod === 'card')
                                <x-heroicon-o-credit-card class="w-8 h-8 text-blue-600" />
                                <div>
                                    <p class="font-bold text-blue-800 dark:text-blue-200">Card Payment</p>
                                    <p class="text-sm text-blue-600 dark:text-blue-400">Credit/Debit Card</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
                
                <!-- Final Amount -->
                <div>
                    <div class="bg-gradient-to-r from-blue-50 to-purple-50 dark:from-blue-900/20 dark:to-purple-900/20 rounded-lg p-4 border border-blue-200 dark:border-blue-600">
                        <div class="text-center">
                            <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Total Amount</p>
                            <p class="text-3xl font-bold text-blue-600 dark:text-blue-400">
                                KES {{ number_format($subtotal * 1.16 - $discountAmount - $voucherDiscountAmount - $couponDiscountAmount, 2) }}
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Tax included</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Detailed Pricing Breakdown -->
    <div class="bg-white dark:bg-gray-700 rounded-2xl border border-gray-200 dark:border-gray-600 shadow-lg p-6 mb-8">
        <h5 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center">
            <x-heroicon-o-calculator class="w-5 h-5 mr-2 text-purple-600" />
            Pricing Breakdown
        </h5>
        
        <div class="space-y-3">
            <div class="flex justify-between text-base">
                <span class="text-gray-700 dark:text-gray-300">Subtotal</span>
                <span class="font-medium text-gray-900 dark:text-white">KES {{ number_format($subtotal, 2) }}</span>
            </div>
            
            <div class="flex justify-between text-base">
                <span class="text-gray-700 dark:text-gray-300">Tax (16% VAT)</span>
                <span class="font-medium text-gray-900 dark:text-white">KES {{ number_format($subtotal * 0.16, 2) }}</span>
            </div>
            
            @if($discountAmount > 0 || $voucherDiscountAmount > 0 || $couponDiscountAmount > 0)
                <div class="border-t border-gray-200 dark:border-gray-600 pt-3">
                    <h6 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Discounts Applied:</h6>
                    @if($discountAmount > 0)
                        <div class="flex justify-between text-green-600">
                            <span>Manual Discount</span>
                            <span class="font-medium">-KES {{ number_format($discountAmount, 2) }}</span>
                        </div>
                    @endif
                    @if($voucherDiscountAmount > 0)
                        <div class="flex justify-between text-green-600">
                            <span>Gift Voucher ({{ $appliedVoucherCode }})</span>
                            <span class="font-medium">-KES {{ number_format($voucherDiscountAmount, 2) }}</span>
                        </div>
                    @endif
                    @if($couponDiscountAmount > 0)
                        <div class="flex justify-between text-green-600">
                            <span>Coupon ({{ $appliedCouponCode }})</span>
                            <span class="font-medium">-KES {{ number_format($couponDiscountAmount, 2) }}</span>
                        </div>
                    @endif
                </div>
            @endif
            
            <div class="border-t-2 border-gray-300 dark:border-gray-600 pt-3">
                <div class="flex justify-between text-xl font-bold">
                    <span class="text-gray-900 dark:text-white">Final Total</span>
                    <span class="text-green-600 dark:text-green-400">
                        KES {{ number_format($subtotal * 1.16 - $discountAmount - $voucherDiscountAmount - $couponDiscountAmount, 2) }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Terms & Conditions -->
    <div class="bg-gray-50 dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-600 p-6 mb-8">
        <h5 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center">
            <x-heroicon-o-document-text class="w-5 h-5 mr-2 text-gray-600" />
            Terms & Conditions
        </h5>
        
        <div class="space-y-3 text-sm text-gray-700 dark:text-gray-300">
            <label class="flex items-start space-x-3 cursor-pointer">
                <input type="checkbox" required class="mt-1 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                <span>I agree to the <a href="#" class="text-blue-600 hover:text-blue-700 underline">Terms of Service</a> and <a href="#" class="text-blue-600 hover:text-blue-700 underline">Privacy Policy</a></span>
            </label>
            
            <label class="flex items-start space-x-3 cursor-pointer">
                <input type="checkbox" class="mt-1 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                <span>I would like to receive email notifications about my appointments and special offers</span>
            </label>
        </div>
    </div>

    <!-- Final Confirmation -->
    <div class="bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 rounded-2xl border-2 border-green-200 dark:border-green-600 p-6">
        <div class="text-center">
            <div class="w-12 h-12 bg-green-500 rounded-full flex items-center justify-center mx-auto mb-4">
                <x-heroicon-o-check class="w-6 h-6 text-white" />
            </div>
            <h5 class="text-xl font-bold text-gray-900 dark:text-white mb-2">Ready to Complete Purchase</h5>
            <p class="text-gray-600 dark:text-gray-400 mb-4">
                Click the button below to finalize your order and process payment
            </p>
            @if($paymentMethod === 'mpesa')
                <div class="bg-green-100 dark:bg-green-900/30 rounded-lg p-3 mb-4">
                    <p class="text-sm text-green-800 dark:text-green-200">
                        📱 <strong>M-Pesa Payment:</strong> You will receive an STK push notification on {{ $mpesaPhone ?? $customerData['phone'] }} to complete the payment.
                    </p>
                </div>
            @endif
        </div>
    </div>

    <!-- Security Notice -->
    <div class="mt-6 bg-blue-50 dark:bg-blue-900/20 rounded-xl p-4 border border-blue-200 dark:border-blue-700">
        <div class="flex items-start space-x-3">
            <x-heroicon-o-shield-check class="w-5 h-5 text-blue-600 mt-0.5 flex-shrink-0" />
            <div class="text-sm">
                <p class="font-medium text-blue-800 dark:text-blue-200 mb-1">Your transaction is secure:</p>
                <ul class="text-blue-700 dark:text-blue-300 space-y-1">
                    <li>• All payment data is encrypted and processed securely</li>
                    <li>• You will receive an immediate confirmation receipt</li>
                    <li>• Service bookings will be confirmed and calendar invites sent</li>
                    <li>• Full customer support available for any questions</li>
                </ul>
            </div>
        </div>
    </div>
</div>