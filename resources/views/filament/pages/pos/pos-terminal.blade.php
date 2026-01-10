<x-filament-panels::page>
    <div x-data="{ activeTab: @entangle('activeTab') }">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Products/Services Section (Left - 2/3) --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Search and Filters --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4">
                <div class="flex gap-4">
                    <div class="flex-1">
                        <x-filament::input.wrapper>
                            <x-filament::input
                                type="search"
                                wire:model.live.debounce.300ms="searchTerm"
                                placeholder="Search products or services..."
                                prefix-icon="heroicon-m-magnifying-glass"
                            />
                        </x-filament::input.wrapper>
                    </div>
                </div>

                {{-- Tabs --}}
                <div class="mt-4">
                    <x-filament::tabs>
                        <x-filament::tabs.item
                            alpine-active="activeTab === 'services'"
                            wire:click="$set('activeTab', 'services')"
                            icon="heroicon-m-sparkles"
                        >
                            Services
                        </x-filament::tabs.item>

                        <x-filament::tabs.item
                            alpine-active="activeTab === 'products'"
                            wire:click="$set('activeTab', 'products')"
                            icon="heroicon-m-cube"
                        >
                            Products
                        </x-filament::tabs.item>
                    </x-filament::tabs>
                </div>
            </div>

            {{-- Items Grid --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4">
                @if ($activeTab === 'services')
                    {{-- Services Grid --}}
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                        @forelse ($this->services as $service)
                            <button
                                wire:click="addService({{ $service->id }})"
                                class="group relative bg-white dark:bg-gray-700 border-2 border-gray-200 dark:border-gray-600 rounded-lg p-4 hover:border-primary-500 hover:shadow-md transition-all"
                            >
                                <div class="flex flex-col h-full">
                                    <div class="flex-1">
                                        <h3 class="font-semibold text-gray-900 dark:text-white text-sm mb-1 line-clamp-2">
                                            {{ $service->name }}
                                        </h3>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">
                                            {{ $service->duration_minutes }} min
                                        </p>
                                    </div>
                                    <div class="flex items-center justify-between mt-auto">
                                        <span class="text-lg font-bold text-primary-600 dark:text-primary-400">
                                            KES {{ number_format($service->price, 2) }}
                                        </span>
                                        <x-filament::icon
                                            icon="heroicon-m-plus-circle"
                                            class="w-6 h-6 text-gray-400 group-hover:text-primary-500 transition"
                                        />
                                    </div>
                                </div>
                            </button>
                        @empty
                            <div class="col-span-full text-center py-12">
                                <x-filament::icon
                                    icon="heroicon-o-sparkles"
                                    class="w-12 h-12 text-gray-400 mx-auto mb-3"
                                />
                                <p class="text-gray-500 dark:text-gray-400">No services available</p>
                            </div>
                        @endforelse
                    </div>
                @else
                    {{-- Products Grid --}}
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                        @forelse ($this->products as $product)
                            <button
                                wire:click="addProduct({{ $product->id }})"
                                class="group relative bg-white dark:bg-gray-700 border-2 border-gray-200 dark:border-gray-600 rounded-lg p-4 hover:border-primary-500 hover:shadow-md transition-all"
                            >
                                <div class="flex flex-col h-full">
                                    @if ($product->getMainImage())
                                        <img
                                            src="{{ Storage::url($product->getMainImage()) }}"
                                            alt="{{ $product->name }}"
                                            class="w-full h-24 object-cover rounded-md mb-3"
                                        />
                                    @endif
                                    <div class="flex-1">
                                        <h3 class="font-semibold text-gray-900 dark:text-white text-sm mb-1 line-clamp-2">
                                            {{ $product->name }}
                                        </h3>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">
                                            SKU: {{ $product->sku }}
                                        </p>
                                    </div>
                                    <div class="flex items-center justify-between mt-auto">
                                        <span class="text-lg font-bold text-primary-600 dark:text-primary-400">
                                            KES {{ number_format($product->base_price, 2) }}
                                        </span>
                                        <x-filament::icon
                                            icon="heroicon-m-plus-circle"
                                            class="w-6 h-6 text-gray-400 group-hover:text-primary-500 transition"
                                        />
                                    </div>
                                </div>
                            </button>
                        @empty
                            <div class="col-span-full text-center py-12">
                                <x-filament::icon
                                    icon="heroicon-o-cube"
                                    class="w-12 h-12 text-gray-400 mx-auto mb-3"
                                />
                                <p class="text-gray-500 dark:text-gray-400">No products available</p>
                            </div>
                        @endforelse
                    </div>
                @endif
            </div>
        </div>

        {{-- Cart Section (Right - 1/3) --}}
        <div class="lg:col-span-1">
            <div class="sticky top-6 space-y-4">
                {{-- Customer Selection --}}
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4">
                    <h3 class="font-semibold text-gray-900 dark:text-white mb-3">Customer</h3>
                    @if ($customerId)
                        <div class="flex items-center justify-between bg-gray-50 dark:bg-gray-700 rounded-lg p-3">
                            <div class="flex items-center gap-3">
                                <x-filament::icon
                                    icon="heroicon-m-user-circle"
                                    class="w-8 h-8 text-primary-500"
                                />
                                <div>
                                    <p class="font-medium text-gray-900 dark:text-white">{{ $customerName }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Regular customer</p>
                                </div>
                            </div>
                            <button
                                wire:click="$set('customerId', null)"
                                class="text-gray-400 hover:text-danger-500"
                            >
                                <x-filament::icon icon="heroicon-m-x-mark" class="w-5 h-5" />
                            </button>
                        </div>
                    @else
                        <div class="text-center py-3 text-gray-500 dark:text-gray-400 text-sm">
                            Walk-in Customer
                        </div>
                    @endif
                </div>

                {{-- Cart Items --}}
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4">
                    <h3 class="font-semibold text-gray-900 dark:text-white mb-3">
                        Cart ({{ count($cart) }})
                    </h3>

                    @if (empty($cart))
                        <div class="text-center py-12">
                            <x-filament::icon
                                icon="heroicon-o-shopping-cart"
                                class="w-12 h-12 text-gray-300 dark:text-gray-600 mx-auto mb-3"
                            />
                            <p class="text-gray-500 dark:text-gray-400 text-sm">Cart is empty</p>
                            <p class="text-gray-400 dark:text-gray-500 text-xs mt-1">Add items to get started</p>
                        </div>
                    @else
                        <div class="space-y-3 max-h-96 overflow-y-auto">
                            @foreach ($cart as $index => $item)
                                <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-3">
                                    <div class="flex items-start justify-between mb-2">
                                        <div class="flex-1">
                                            <h4 class="font-medium text-gray-900 dark:text-white text-sm">
                                                {{ $item['name'] }}
                                            </h4>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                                KES {{ number_format($item['unit_price'], 2) }} each
                                                @if ($item['item_type'] === 'service' && isset($item['duration_minutes']))
                                                    • {{ $item['duration_minutes'] }} min
                                                @endif
                                            </p>
                                        </div>
                                        <button
                                            wire:click="removeItem({{ $index }})"
                                            class="text-gray-400 hover:text-danger-500"
                                        >
                                            <x-filament::icon icon="heroicon-m-trash" class="w-4 h-4" />
                                        </button>
                                    </div>

                                    {{-- Staff Selection for Services --}}
                                    @if ($item['item_type'] === 'service')
                                        <div class="mb-2">
                                            <select
                                                wire:model.change="cart.{{ $index }}.staff_id"
                                                class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300"
                                            >
                                                <option value="">Select Staff (Optional)</option>
                                                @foreach ($this->staff as $staffMember)
                                                    <option value="{{ $staffMember->id }}">{{ $staffMember->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    @endif

                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-2">
                                            <button
                                                wire:click="updateQuantity({{ $index }}, {{ $item['quantity'] - 1 }})"
                                                class="w-8 h-8 flex items-center justify-center bg-white dark:bg-gray-600 rounded-lg text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-500"
                                            >
                                                <x-filament::icon icon="heroicon-m-minus" class="w-4 h-4" />
                                            </button>
                                            <span class="w-12 text-center font-medium text-gray-900 dark:text-white">
                                                {{ $item['quantity'] }}
                                            </span>
                                            <button
                                                wire:click="updateQuantity({{ $index }}, {{ $item['quantity'] + 1 }})"
                                                class="w-8 h-8 flex items-center justify-center bg-white dark:bg-gray-600 rounded-lg text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-500"
                                            >
                                                <x-filament::icon icon="heroicon-m-plus" class="w-4 h-4" />
                                            </button>
                                        </div>
                                        <div class="font-bold text-primary-600 dark:text-primary-400">
                                            KES {{ number_format($item['unit_price'] * $item['quantity'] - ($item['discount_amount'] ?? 0), 2) }}
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        {{-- Discounts & Promotions --}}
                        <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-600">
                            <details class="group">
                                <summary class="flex items-center justify-between cursor-pointer text-sm font-medium text-gray-700 dark:text-gray-300">
                                    <span>Discounts & Promotions</span>
                                    <x-filament::icon icon="heroicon-m-chevron-down" class="w-4 h-4 transition-transform group-open:rotate-180" />
                                </summary>

                                <div class="mt-3 space-y-3">
                                    {{-- Coupon Code --}}
                                    <div>
                                        <label class="block text-xs text-gray-600 dark:text-gray-400 mb-1">Coupon Code</label>
                                        <div class="flex gap-2">
                                            <input
                                                type="text"
                                                wire:model="couponCode"
                                                placeholder="Enter code"
                                                class="flex-1 text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300"
                                            />
                                            <x-filament::button
                                                wire:click="applyCoupon"
                                                size="xs"
                                                color="success"
                                            >
                                                Apply
                                            </x-filament::button>
                                        </div>
                                    </div>

                                    {{-- Voucher Code --}}
                                    <div>
                                        <label class="block text-xs text-gray-600 dark:text-gray-400 mb-1">Voucher Code</label>
                                        <div class="flex gap-2">
                                            <input
                                                type="text"
                                                wire:model="voucherCode"
                                                placeholder="Enter voucher"
                                                class="flex-1 text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300"
                                            />
                                            <x-filament::button
                                                wire:click="applyVoucher"
                                                size="xs"
                                                color="success"
                                            >
                                                Apply
                                            </x-filament::button>
                                        </div>
                                    </div>

                                    {{-- Loyalty Points (Only if customer selected) --}}
                                    @if ($customerId)
                                        <div>
                                            <label class="block text-xs text-gray-600 dark:text-gray-400 mb-1">
                                                Redeem Loyalty Points
                                                @if ($customerLoyaltyPoints)
                                                    <span class="text-primary-500">(Available: {{ $customerLoyaltyPoints }})</span>
                                                @endif
                                            </label>
                                            <div class="flex gap-2">
                                                <input
                                                    type="number"
                                                    wire:model="loyaltyPointsToUse"
                                                    placeholder="Points"
                                                    min="0"
                                                    class="flex-1 text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300"
                                                />
                                                <x-filament::button
                                                    wire:click="useLoyaltyPoints"
                                                    size="xs"
                                                    color="success"
                                                >
                                                    Redeem
                                                </x-filament::button>
                                            </div>
                                        </div>
                                    @endif

                                    {{-- Manual Discount --}}
                                    <div>
                                        <label class="block text-xs text-gray-600 dark:text-gray-400 mb-1">Manual Discount</label>
                                        <div class="flex gap-2">
                                            <input
                                                type="number"
                                                wire:model="manualDiscountAmount"
                                                placeholder="Amount"
                                                min="0"
                                                step="0.01"
                                                class="flex-1 text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300"
                                            />
                                            <select
                                                wire:model="manualDiscountType"
                                                class="text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300"
                                            >
                                                <option value="fixed">KES</option>
                                                <option value="percentage">%</option>
                                            </select>
                                            <x-filament::button
                                                wire:click="applyManualDiscount"
                                                size="xs"
                                                color="success"
                                            >
                                                Apply
                                            </x-filament::button>
                                        </div>
                                    </div>
                                </div>
                            </details>
                        </div>

                        {{-- Cart Totals --}}
                        <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-600 space-y-2">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600 dark:text-gray-400">Subtotal</span>
                                <span class="font-medium text-gray-900 dark:text-white">
                                    KES {{ number_format($totals['subtotal'], 2) }}
                                </span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600 dark:text-gray-400">Tax (16%)</span>
                                <span class="font-medium text-gray-900 dark:text-white">
                                    KES {{ number_format($totals['tax'], 2) }}
                                </span>
                            </div>
                            @if ($totals['discount'] > 0)
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-600 dark:text-gray-400">Discount</span>
                                    <span class="font-medium text-danger-600 dark:text-danger-400">
                                        -KES {{ number_format($totals['discount'], 2) }}
                                    </span>
                                </div>
                            @endif
                            <div class="flex justify-between text-lg font-bold pt-2 border-t border-gray-200 dark:border-gray-600">
                                <span class="text-gray-900 dark:text-white">Total</span>
                                <span class="text-primary-600 dark:text-primary-400">
                                    KES {{ number_format($totals['total'], 2) }}
                                </span>
                            </div>
                        </div>

                        {{-- Checkout Actions --}}
                        <div class="mt-4 space-y-2">
                            <x-filament::button
                                wire:click="openPaymentModal"
                                size="lg"
                                color="primary"
                                class="w-full"
                                icon="heroicon-m-credit-card"
                            >
                                Checkout
                            </x-filament::button>

                            <x-filament::button
                                wire:click="resetCart"
                                size="sm"
                                color="gray"
                                outlined
                                class="w-full"
                                icon="heroicon-m-trash"
                            >
                                Clear Cart
                            </x-filament::button>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Payment Modal --}}
    <x-filament::modal
        id="payment-modal"
        width="2xl"
    >
        <x-slot name="heading">
            Process Payment
        </x-slot>

        <div class="space-y-6">
            {{-- Payment Method Selection --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Payment Method
                </label>
                <div class="grid grid-cols-2 gap-3">
                    <button
                        wire:click="$set('paymentMethod', 'cash')"
                        @class([
                            'p-4 border-2 rounded-lg text-center transition',
                            'border-primary-500 bg-primary-50 dark:bg-primary-900/20' => $paymentMethod === 'cash',
                            'border-gray-200 dark:border-gray-600 hover:border-primary-300' => $paymentMethod !== 'cash',
                        ])
                    >
                        <x-filament::icon icon="heroicon-o-banknotes" class="w-8 h-8 mx-auto mb-2" />
                        <span class="font-medium">Cash</span>
                    </button>

                    <button
                        wire:click="$set('paymentMethod', 'mpesa')"
                        @class([
                            'p-4 border-2 rounded-lg text-center transition',
                            'border-primary-500 bg-primary-50 dark:bg-primary-900/20' => $paymentMethod === 'mpesa',
                            'border-gray-200 dark:border-gray-600 hover:border-primary-300' => $paymentMethod !== 'mpesa',
                        ])
                    >
                        <x-filament::icon icon="heroicon-o-device-phone-mobile" class="w-8 h-8 mx-auto mb-2" />
                        <span class="font-medium">M-Pesa</span>
                    </button>

                    <button
                        wire:click="$set('paymentMethod', 'card')"
                        @class([
                            'p-4 border-2 rounded-lg text-center transition',
                            'border-primary-500 bg-primary-50 dark:bg-primary-900/20' => $paymentMethod === 'card',
                            'border-gray-200 dark:border-gray-600 hover:border-primary-300' => $paymentMethod !== 'card',
                        ])
                    >
                        <x-filament::icon icon="heroicon-o-credit-card" class="w-8 h-8 mx-auto mb-2" />
                        <span class="font-medium">Card</span>
                    </button>

                    <button
                        wire:click="$set('paymentMethod', 'bank_transfer')"
                        @class([
                            'p-4 border-2 rounded-lg text-center transition',
                            'border-primary-500 bg-primary-50 dark:bg-primary-900/20' => $paymentMethod === 'bank_transfer',
                            'border-gray-200 dark:border-gray-600 hover:border-primary-300' => $paymentMethod !== 'bank_transfer',
                        ])
                    >
                        <x-filament::icon icon="heroicon-o-building-library" class="w-8 h-8 mx-auto mb-2" />
                        <span class="font-medium">Bank Transfer</span>
                    </button>
                </div>
            </div>

            @if ($paymentMethod === 'mpesa')
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        M-Pesa Phone Number
                    </label>
                    <x-filament::input.wrapper>
                        <x-filament::input
                            type="tel"
                            wire:model="mpesaPhone"
                            placeholder="0712345678 or 254712345678"
                            required
                        />
                    </x-filament::input.wrapper>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        Enter the phone number registered with M-Pesa
                    </p>
                </div>

                @if ($paymentStatus === 'pending')
                    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                        <div class="flex items-center gap-3">
                            <div class="flex-shrink-0">
                                <svg class="animate-spin h-5 w-5 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </div>
                            <div class="flex-1">
                                <p class="text-sm font-medium text-blue-900 dark:text-blue-100">
                                    Waiting for payment...
                                </p>
                                <p class="text-xs text-blue-700 dark:text-blue-300 mt-1">
                                    {{ $paymentMessage }}
                                </p>
                            </div>
                        </div>
                    </div>
                @endif
            @endif

            {{-- Payment Summary --}}
            <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                <h4 class="font-semibold text-gray-900 dark:text-white mb-3">Payment Summary</h4>
                <div class="space-y-2">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600 dark:text-gray-400">Subtotal</span>
                        <span class="font-medium">KES {{ number_format($totals['subtotal'], 2) }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600 dark:text-gray-400">Tax</span>
                        <span class="font-medium">KES {{ number_format($totals['tax'], 2) }}</span>
                    </div>
                    <div class="flex justify-between text-lg font-bold pt-2 border-t border-gray-200 dark:border-gray-600">
                        <span>Total Amount</span>
                        <span class="text-primary-600 dark:text-primary-400">
                            KES {{ number_format($totals['total'], 2) }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <x-slot name="footerActions">
            <x-filament::button
                wire:click="processPayment"
                wire:loading.attr="disabled"
                color="primary"
                size="lg"
            >
                <span wire:loading.remove wire:target="processPayment">
                    Confirm Payment
                </span>
                <span wire:loading wire:target="processPayment">
                    Processing...
                </span>
            </x-filament::button>

            <x-filament::button
                wire:click="$set('showPaymentModal', false)"
                color="gray"
                outlined
            >
                Cancel
            </x-filament::button>
        </x-slot>
    </x-filament::modal>

    {{-- Receipt Modal --}}
    <x-filament::modal
        id="receipt-modal"
        width="lg"
    >
        <x-slot name="heading">
            Transaction Receipt
        </x-slot>

        @if ($lastReceipt)
            <div class="space-y-4">
                <div class="text-center border-b border-gray-200 dark:border-gray-600 pb-4">
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $lastReceipt['branch'] }}</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ $lastReceipt['date'] }}</p>
                    <p class="text-lg font-semibold text-primary-600 dark:text-primary-400 mt-2">
                        #{{ $lastReceipt['transaction_number'] }}
                    </p>
                </div>

                <div class="space-y-2">
                    @foreach ($lastReceipt['items'] as $item)
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-700 dark:text-gray-300">
                                {{ $item['quantity'] }}x {{ $item['name'] }}
                            </span>
                            <span class="font-medium">KES {{ number_format($item['total'], 2) }}</span>
                        </div>
                    @endforeach
                </div>

                <div class="border-t border-gray-200 dark:border-gray-600 pt-3 space-y-2">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600 dark:text-gray-400">Subtotal</span>
                        <span>KES {{ number_format($lastReceipt['subtotal'], 2) }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600 dark:text-gray-400">Tax</span>
                        <span>KES {{ number_format($lastReceipt['tax'], 2) }}</span>
                    </div>
                    <div class="flex justify-between text-lg font-bold pt-2 border-t border-gray-200 dark:border-gray-600">
                        <span>Total Paid</span>
                        <span class="text-primary-600 dark:text-primary-400">
                            KES {{ number_format($lastReceipt['total'], 2) }}
                        </span>
                    </div>
                </div>

                <div class="text-center text-sm text-gray-500 dark:text-gray-400 mt-4">
                    <p>Payment Method: {{ ucfirst($lastReceipt['payment_method']) }}</p>
                    <p>Served by: {{ $lastReceipt['staff'] }}</p>
                    <p class="mt-2">Thank you for your business!</p>
                </div>
            </div>
        @endif

        <x-slot name="footerActions">
            <x-filament::button
                wire:click="$set('showReceiptModal', false)"
                color="primary"
                class="w-full"
            >
                Close
            </x-filament::button>
        </x-slot>
    </x-filament::modal>
    </div>
</x-filament-panels::page>
