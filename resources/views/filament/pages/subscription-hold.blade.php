<x-filament-panels::page>
    <div class="space-y-6" @if ($activeCheckoutRequestId) wire:poll.2s="checkPaymentStatus" @endif>
        {{-- Banner Alert --}}
        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-red-500 to-pink-600 p-6 text-white shadow-lg">
            <div class="absolute -right-10 -top-10 h-40 w-40 rounded-full bg-white/10 blur-2xl"></div>
            <div class="flex items-start gap-4">
                <div class="rounded-xl bg-white/20 p-3 backdrop-blur-md">
                    <x-filament::icon icon="heroicon-o-shield-exclamation" class="h-8 h-8 text-white animate-pulse" />
                </div>
                <div>
                    <h2 class="text-xl font-bold">Access Restricted: Subscription On Hold</h2>
                    <p class="mt-1 text-sm text-red-100 max-w-2xl">
                        Your business administration portal access has been temporarily suspended because there is no active subscription for this branch. Please reactivate your plan to restore full administrative functions.
                    </p>
                </div>
            </div>
        </div>

        @if (auth()->user()->isDirector() || auth()->user()->isAdmin() || (\Filament\Facades\Filament::getTenant() && \Filament\Facades\Filament::getTenant()->user_id === auth()->id()))
            {{-- Director Interface --}}
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                {{-- Pricing Grid (2/3 width) --}}
                <div class="lg:col-span-2 space-y-6">
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-6">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-1">Choose a Subscription Plan</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-6">Select the plan that fits your business scale. Upgrade or downgrade at any time.</p>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            @foreach ($this->getPlans() as $plan)
                                <label @class([
                                    'relative flex flex-col justify-between p-5 border-2 rounded-2xl cursor-pointer hover:shadow-md transition-all duration-300',
                                    'border-primary-500 bg-primary-50/50 dark:bg-primary-950/20 shadow-sm' => $selectedPlanSlug === $plan->slug,
                                    'border-gray-200 dark:border-gray-700 dark:bg-gray-800 hover:border-gray-300' => $selectedPlanSlug !== $plan->slug
                                ])>
                                    <input type="radio" wire:model.live="selectedPlanSlug" value="{{ $plan->slug }}" class="sr-only" />
                                    
                                    @if ($selectedPlanSlug === $plan->slug)
                                        <div class="absolute top-3 right-3 text-primary-600 dark:text-primary-400">
                                            <x-filament::icon icon="heroicon-m-check-circle" class="h-6 w-6" />
                                        </div>
                                    @endif

                                    <div>
                                        <h4 class="text-base font-bold text-gray-900 dark:text-white">{{ $plan->name }}</h4>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 min-h-[40px]">{{ $plan->description }}</p>
                                        
                                        <div class="mt-4">
                                            <span class="text-2xl font-black text-gray-900 dark:text-white">KES {{ number_format($plan->price) }}</span>
                                            <span class="text-xs text-gray-500">/{{ $plan->interval }}</span>
                                        </div>
                                    </div>

                                    <div class="mt-6 pt-4 border-t border-gray-100 dark:border-gray-700">
                                        <ul class="space-y-2">
                                            @foreach ($plan->features ?? [] as $feat)
                                                <li class="flex items-start gap-2 text-xs text-gray-600 dark:text-gray-300">
                                                    <x-filament::icon icon="heroicon-m-check" class="h-4 w-4 text-emerald-500 shrink-0 mt-0.5" />
                                                    <span>{{ $feat }}</span>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- Payment Terminal Panel (1/3 width) --}}
                <div class="lg:col-span-1">
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-6 sticky top-6 space-y-6">
                        <div class="border-b border-gray-100 dark:border-gray-700 pb-4">
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white">Pay via M-Pesa</h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Instant plan activation via Safaricom Lipa Na M-Pesa STK Push.</p>
                        </div>

                        {{-- Payment Input --}}
                        <div class="space-y-4">
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-2">M-PESA PHONE NUMBER</label>
                                <x-filament::input.wrapper>
                                    <x-filament::input
                                        type="tel"
                                        wire:model="mpesaPhone"
                                        placeholder="e.g. 0712345678"
                                        disabled="{{ $isProcessing }}"
                                    />
                                </x-filament::input.wrapper>
                                <p class="text-[10px] text-gray-500 dark:text-gray-400 mt-1.5">
                                    Enter number in format: 07XXXXXXXX or 254XXXXXXXXX. You will receive an M-Pesa PIN prompt.
                                </p>
                            </div>

                            {{-- Payment Action button --}}
                            @if (!$isProcessing)
                                <x-filament::button
                                    wire:click="initiatePayment"
                                    size="lg"
                                    color="primary"
                                    class="w-full shadow-md py-2.5 font-bold transition-all duration-300 hover:scale-[1.02]"
                                    icon="heroicon-m-credit-card"
                                >
                                    Initiate Payment
                                </x-filament::button>
                            @endif
                        </div>

                        {{-- Live Status Screen --}}
                        @if ($isProcessing)
                            <div @class([
                                'rounded-xl p-4 border text-center space-y-3 transition-all duration-300',
                                'bg-blue-50/50 dark:bg-blue-950/20 border-blue-200 dark:border-blue-800' => $paymentStatus === 'processing' || $paymentStatus === 'pending_pin',
                                'bg-emerald-50/50 dark:bg-emerald-950/20 border-emerald-200 dark:border-emerald-800' => $paymentStatus === 'success',
                                'bg-red-50/50 dark:bg-red-950/20 border-red-200 dark:border-red-800' => $paymentStatus === 'failed',
                            ])>
                                <div class="flex justify-center">
                                    @if ($paymentStatus === 'processing' || $paymentStatus === 'pending_pin')
                                        <div class="relative flex items-center justify-center h-12 w-12 rounded-full bg-blue-100 dark:bg-blue-900/50">
                                            <svg class="animate-spin h-6 w-6 text-blue-600 dark:text-blue-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                        </div>
                                    @elseif ($paymentStatus === 'success')
                                        <div class="flex items-center justify-center h-12 w-12 rounded-full bg-emerald-100 dark:bg-emerald-900/50">
                                            <x-filament::icon icon="heroicon-o-check-circle" class="h-6 w-6 text-emerald-600 dark:text-emerald-400" />
                                        </div>
                                    @elseif ($paymentStatus === 'failed')
                                        <div class="flex items-center justify-center h-12 w-12 rounded-full bg-red-100 dark:bg-red-900/50">
                                            <x-filament::icon icon="heroicon-o-x-circle" class="h-6 w-6 text-red-600 dark:text-red-400" />
                                        </div>
                                    @endif
                                </div>

                                <div class="space-y-1">
                                    <h4 class="text-sm font-bold text-gray-900 dark:text-white">
                                        @if ($paymentStatus === 'processing')
                                            Requesting STK Push...
                                        @elseif ($paymentStatus === 'pending_pin')
                                            Waiting for PIN Entry...
                                        @elseif ($paymentStatus === 'success')
                                            Payment Successful!
                                        @elseif ($paymentStatus === 'failed')
                                            Payment Failed
                                        @endif
                                    </h4>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 leading-relaxed px-2">
                                        {{ $paymentMessage }}
                                    </p>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @else
            {{-- Staff / Manager Interface --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-8 text-center max-w-xl mx-auto space-y-6">
                <div class="flex justify-center">
                    <div class="rounded-full bg-orange-50 dark:bg-orange-950/20 p-4">
                        <x-filament::icon icon="heroicon-o-lock-closed" class="h-12 w-12 text-orange-500" />
                    </div>
                </div>
                
                <div class="space-y-2">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">Admin Panel Locked</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-300">
                        This branch's administrative subscription is currently on hold. As a branch manager or staff member, you do not have permissions to perform billing payments.
                    </p>
                </div>

                <div class="bg-gray-50 dark:bg-gray-700/50 rounded-xl p-4 text-xs text-gray-500 dark:text-gray-400 leading-relaxed">
                    Please contact the **Director / Spa Owner** of this organization to renew the subscription. Once payment is processed, your administrative access will be restored immediately.
                </div>

                <x-filament::button
                    color="gray"
                    outlined
                    class="w-full"
                    tag="a"
                    href="/"
                >
                    Return to Main Page
                </x-filament::button>
            </div>
        @endif
    </div>
</x-filament-panels::page>
