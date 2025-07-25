<!-- Payment Method Step -->
<div class="max-w-4xl mx-auto">
    <!-- Header -->
    <div class="text-center mb-8">
        <div class="w-16 h-16 bg-gradient-to-r from-green-500 to-emerald-600 rounded-2xl flex items-center justify-center mx-auto mb-4">
            <x-heroicon-o-credit-card class="w-8 h-8 text-white" />
        </div>
        <h4 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">Choose Payment Method</h4>
        <p class="text-lg text-gray-600 dark:text-gray-400">Select how you'd like to complete this transaction</p>
    </div>

    <!-- Payment Methods -->
    <div class="bg-white dark:bg-gray-700 rounded-2xl border border-gray-200 dark:border-gray-600 shadow-lg p-6 mb-8">
        <h5 class="text-xl font-bold text-gray-900 dark:text-white mb-6 flex items-center">
            <x-heroicon-o-credit-card class="w-5 h-5 mr-2 text-green-600" />
            Payment Options
        </h5>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <!-- Cash Payment -->
            <label class="relative cursor-pointer">
                <input type="radio" wire:model="paymentMethod" value="cash" class="sr-only peer">
                <div class="border-2 border-gray-300 dark:border-gray-600 rounded-xl p-6 transition-all duration-200 
                            peer-checked:border-green-500 peer-checked:bg-green-50 dark:peer-checked:bg-green-900/20 
                            hover:border-gray-400 dark:hover:border-gray-500 hover:shadow-md">
                    <div class="text-center">
                        <div class="w-12 h-12 bg-green-100 dark:bg-green-900 rounded-full flex items-center justify-center mx-auto mb-3">
                            <x-heroicon-o-banknotes class="w-6 h-6 text-green-600 dark:text-green-400" />
                        </div>
                        <h6 class="font-bold text-gray-900 dark:text-white mb-1">Cash</h6>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Pay with cash at checkout</p>
                        <div class="mt-3 text-xs text-green-600 dark:text-green-400 font-medium">Instant • No fees</div>
                    </div>
                </div>
            </label>

            <!-- M-Pesa Payment -->
            <label class="relative cursor-pointer">
                <input type="radio" wire:model="paymentMethod" value="mpesa" class="sr-only peer">
                <div class="border-2 border-gray-300 dark:border-gray-600 rounded-xl p-6 transition-all duration-200 
                            peer-checked:border-green-500 peer-checked:bg-green-50 dark:peer-checked:bg-green-900/20 
                            hover:border-gray-400 dark:hover:border-gray-500 hover:shadow-md">
                    <div class="text-center">
                        <div class="w-12 h-12 bg-green-100 dark:bg-green-900 rounded-full flex items-center justify-center mx-auto mb-3">
                            <x-heroicon-o-device-phone-mobile class="w-6 h-6 text-green-600 dark:text-green-400" />
                        </div>
                        <h6 class="font-bold text-gray-900 dark:text-white mb-1">M-Pesa</h6>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Mobile money payment</p>
                        <div class="mt-3 text-xs text-green-600 dark:text-green-400 font-medium">Secure • Fast</div>
                    </div>
                </div>
            </label>

            <!-- Card Payment -->
            <label class="relative cursor-pointer">
                <input type="radio" wire:model="paymentMethod" value="card" class="sr-only peer">
                <div class="border-2 border-gray-300 dark:border-gray-600 rounded-xl p-6 transition-all duration-200 
                            peer-checked:border-blue-500 peer-checked:bg-blue-50 dark:peer-checked:bg-blue-900/20 
                            hover:border-gray-400 dark:hover:border-gray-500 hover:shadow-md">
                    <div class="text-center">
                        <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900 rounded-full flex items-center justify-center mx-auto mb-3">
                            <x-heroicon-o-credit-card class="w-6 h-6 text-blue-600 dark:text-blue-400" />
                        </div>
                        <h6 class="font-bold text-gray-900 dark:text-white mb-1">Credit/Debit Card</h6>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Visa, Mastercard accepted</p>
                        <div class="mt-3 text-xs text-blue-600 dark:text-blue-400 font-medium">Secure • Encrypted</div>
                    </div>
                </div>
            </label>
        </div>

        <!-- M-Pesa Phone Number Input -->
        @if($paymentMethod === 'mpesa')
            <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4 border border-green-200 dark:border-green-700">
                <label class="block text-sm font-medium text-green-800 dark:text-green-200 mb-2">
                    M-Pesa Phone Number *
                </label>
                <input wire:model="mpesaPhone" 
                       type="tel" 
                       placeholder="e.g., 0712345678"
                       class="w-full px-4 py-3 border border-green-300 dark:border-green-600 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 bg-white dark:bg-gray-800 text-gray-900 dark:text-white">
                <p class="text-xs text-green-600 dark:text-green-400 mt-2">
                    📱 You will receive an STK push notification to complete the payment
                </p>
            </div>
        @endif
    </div>

    <!-- Discounts & Promotions -->
    <div class="bg-white dark:bg-gray-700 rounded-2xl border border-gray-200 dark:border-gray-600 shadow-lg p-6 mb-8">
        <h5 class="text-xl font-bold text-gray-900 dark:text-white mb-6 flex items-center">
            <x-heroicon-o-tag class="w-5 h-5 mr-2 text-purple-600" />
            Discounts & Promotions
        </h5>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <!-- Gift Voucher -->
            <div class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-4 hover:border-purple-400 transition-colors">
                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center space-x-2">
                        <x-heroicon-o-gift class="w-5 h-5 text-purple-600" />
                        <span class="font-medium text-gray-900 dark:text-white">Gift Voucher</span>
                    </div>
                    @if($appliedVoucherCode)
                        <span class="text-xs bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 px-2 py-1 rounded-full">Applied</span>
                    @endif
                </div>
                @if($appliedVoucherCode)
                    <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-3">
                        <p class="text-sm font-medium text-green-800 dark:text-green-200">{{ $appliedVoucherCode }}</p>
                        <p class="text-xs text-green-600 dark:text-green-400">Saving: KES {{ number_format($voucherDiscountAmount, 2) }}</p>
                        <button wire:click="removeVoucher" class="text-xs text-red-600 hover:text-red-700 mt-1">Remove</button>
                    </div>
                @else
                    <div class="flex space-x-2">
                        <input wire:model="appliedVoucherCode" 
                               type="text" 
                               placeholder="Enter voucher code"
                               class="flex-1 px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-1 focus:ring-purple-500 bg-white dark:bg-gray-800 text-gray-900 dark:text-white">
                        <button wire:click="applyVoucher" 
                                class="px-3 py-2 bg-purple-600 hover:bg-purple-700 text-white text-sm font-medium rounded-lg transition-colors">
                            Apply
                        </button>
                    </div>
                @endif
            </div>

            <!-- Discount Coupon -->
            <div class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-4 hover:border-orange-400 transition-colors">
                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center space-x-2">
                        <x-heroicon-o-ticket class="w-5 h-5 text-orange-600" />
                        <span class="font-medium text-gray-900 dark:text-white">Discount Coupon</span>
                    </div>
                    @if($appliedCouponCode)
                        <span class="text-xs bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 px-2 py-1 rounded-full">Applied</span>
                    @endif
                </div>
                @if($appliedCouponCode)
                    <div class="bg-orange-50 dark:bg-orange-900/20 rounded-lg p-3">
                        <p class="text-sm font-medium text-orange-800 dark:text-orange-200">{{ $appliedCouponCode }}</p>
                        <p class="text-xs text-orange-600 dark:text-orange-400">Saving: KES {{ number_format($couponDiscountAmount, 2) }}</p>
                        <button wire:click="removeCoupon" class="text-xs text-red-600 hover:text-red-700 mt-1">Remove</button>
                    </div>
                @else
                    <div class="flex space-x-2">
                        <input wire:model="appliedCouponCode" 
                               type="text" 
                               placeholder="Enter coupon code"
                               class="flex-1 px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-1 focus:ring-orange-500 bg-white dark:bg-gray-800 text-gray-900 dark:text-white">
                        <button wire:click="applyCoupon" 
                                class="px-3 py-2 bg-orange-600 hover:bg-orange-700 text-white text-sm font-medium rounded-lg transition-colors">
                            Apply
                        </button>
                    </div>
                @endif
            </div>
        </div>

        <!-- Manual Discount (Staff Only) -->
        <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-600">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-2">
                    <x-heroicon-o-percentage class="w-5 h-5 text-gray-600" />
                    <span class="font-medium text-gray-900 dark:text-white">Manual Discount</span>
                    <span class="text-xs bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 px-2 py-1 rounded-full">Staff Only</span>
                </div>
                <div class="flex items-center space-x-2">
                    <span class="text-sm text-gray-600 dark:text-gray-400">KES</span>
                    <input wire:model.live="discountAmount" 
                           type="number" 
                           step="0.01" 
                           min="0"
                           placeholder="0.00"
                           class="w-24 px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-1 focus:ring-blue-500 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-right">
                </div>
            </div>
        </div>
    </div>

    <!-- Payment Summary -->
    <div class="bg-gradient-to-r from-green-50 to-emerald-50 dark:from-gray-800 dark:to-gray-700 rounded-2xl border border-green-200 dark:border-green-600 p-6">
        <h5 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center">
            <x-heroicon-o-calculator class="w-5 h-5 mr-2 text-green-600" />
            Payment Summary
        </h5>
        
        <div class="space-y-3">
            <div class="flex justify-between text-lg">
                <span class="font-medium text-gray-700 dark:text-gray-300">Subtotal</span>
                <span class="font-semibold text-gray-900 dark:text-white">KES {{ number_format($subtotal, 2) }}</span>
            </div>
            
            <div class="flex justify-between text-lg">
                <span class="font-medium text-gray-700 dark:text-gray-300">Tax (16% VAT)</span>
                <span class="font-semibold text-gray-900 dark:text-white">KES {{ number_format($subtotal * 0.16, 2) }}</span>
            </div>
            
            @if($discountAmount > 0 || $voucherDiscountAmount > 0 || $couponDiscountAmount > 0)
                <div class="border-t border-green-200 dark:border-green-700 pt-2">
                    @if($discountAmount > 0)
                        <div class="flex justify-between text-green-600">
                            <span class="font-medium">Manual Discount</span>
                            <span class="font-semibold">-KES {{ number_format($discountAmount, 2) }}</span>
                        </div>
                    @endif
                    @if($voucherDiscountAmount > 0)
                        <div class="flex justify-between text-green-600">
                            <span class="font-medium">Gift Voucher</span>
                            <span class="font-semibold">-KES {{ number_format($voucherDiscountAmount, 2) }}</span>
                        </div>
                    @endif
                    @if($couponDiscountAmount > 0)
                        <div class="flex justify-between text-green-600">
                            <span class="font-medium">Coupon Discount</span>
                            <span class="font-semibold">-KES {{ number_format($couponDiscountAmount, 2) }}</span>
                        </div>
                    @endif
                </div>
            @endif
            
            <div class="border-t-2 border-green-300 dark:border-green-600 pt-3">
                <div class="flex justify-between text-2xl font-bold">
                    <span class="text-gray-900 dark:text-white">Total to Pay</span>
                    <span class="text-green-600 dark:text-green-400">
                        KES {{ number_format($subtotal * 1.16 - $discountAmount - $voucherDiscountAmount - $couponDiscountAmount, 2) }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Payment Security Notice -->
    <div class="mt-6 bg-blue-50 dark:bg-blue-900/20 rounded-xl p-4 border border-blue-200 dark:border-blue-700">
        <div class="flex items-start space-x-3">
            <x-heroicon-o-shield-check class="w-5 h-5 text-blue-600 mt-0.5 flex-shrink-0" />
            <div class="text-sm">
                <p class="font-medium text-blue-800 dark:text-blue-200 mb-1">Secure Payment:</p>
                <ul class="text-blue-700 dark:text-blue-300 space-y-1">
                    <li>• All payments are processed securely with industry-standard encryption</li>
                    <li>• Your payment information is never stored on our servers</li>
                    <li>• You will receive a receipt immediately after successful payment</li>
                </ul>
            </div>
        </div>
    </div>
</div>