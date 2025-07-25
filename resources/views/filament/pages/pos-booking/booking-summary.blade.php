<!-- Booking Summary Step -->
<div class="text-center mb-8">
    <div class="w-20 h-20 bg-gradient-to-r from-green-500 to-emerald-600 rounded-2xl flex items-center justify-center mx-auto mb-4">
        <x-heroicon-o-check-circle class="w-10 h-10 text-white" />
    </div>
    <h4 class="text-3xl font-bold text-gray-900 dark:text-white mb-3">Booking Complete!</h4>
    <p class="text-lg text-green-600 dark:text-green-400 font-medium max-w-2xl mx-auto">
        Review your booking details and proceed to add it to your cart
    </p>
</div>

@php 
    $bookingItem = $this->getServiceBookingItem();
    $selectedStaff = null;
    if ($bookingItem && isset($bookingItem['staff_id']) && !empty($bookingItem['staff_id'])) {
        $selectedStaff = $this->getStaff()->where('id', $bookingItem['staff_id'])->first();
    }
@endphp

<!-- Comprehensive Booking Summary -->
<div class="bg-gradient-to-r from-blue-50 to-purple-50 dark:from-gray-700 dark:to-gray-800 rounded-2xl border-2 border-blue-200 dark:border-blue-600 overflow-hidden">
    <!-- Header -->
    <div class="bg-gradient-to-r from-blue-600 to-purple-600 text-white p-6">
        <div class="flex items-center space-x-3">
            <x-heroicon-o-document-check class="w-8 h-8" />
            <div>
                <h5 class="text-xl font-bold">Booking Summary</h5>
                <p class="text-blue-100">Please review all details before proceeding</p>
            </div>
        </div>
    </div>
    
    <!-- Content -->
    <div class="p-6 space-y-6">
        <!-- Service Details -->
        <div class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-blue-100 dark:border-blue-700 shadow-sm">
            <h6 class="font-bold text-blue-700 dark:text-blue-300 mb-4 flex items-center text-lg">
                <x-heroicon-o-sparkles class="w-5 h-5 mr-2" />
                Service Details
            </h6>
            <div class="grid md:grid-cols-2 gap-4">
                <div class="flex justify-between items-center py-2 border-b border-gray-100 dark:border-gray-700">
                    <span class="font-medium text-gray-700 dark:text-gray-300">Service:</span>
                    <span class="font-bold text-blue-700 dark:text-blue-300">{{ $bookingItem['name'] }}</span>
                </div>
                <div class="flex justify-between items-center py-2 border-b border-gray-100 dark:border-gray-700">
                    <span class="font-medium text-gray-700 dark:text-gray-300">Duration:</span>
                    <span class="font-semibold text-gray-900 dark:text-white">{{ $bookingItem['duration'] ?? 30 }} minutes</span>
                </div>
                <div class="flex justify-between items-center py-2 border-b border-gray-100 dark:border-gray-700">
                    <span class="font-medium text-gray-700 dark:text-gray-300">Price:</span>
                    <span class="font-bold text-lg text-green-600 dark:text-green-400">KES {{ number_format($bookingItem['price']) }}</span>
                </div>
                <div class="flex justify-between items-center py-2 border-b border-gray-100 dark:border-gray-700">
                    <span class="font-medium text-gray-700 dark:text-gray-300">Category:</span>
                    <span class="font-semibold text-gray-900 dark:text-white">{{ ucfirst($bookingItem['category'] ?? 'Wellness') }}</span>
                </div>
            </div>
        </div>
        
        <!-- Staff Details -->
        <div class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-green-100 dark:border-green-700 shadow-sm">
            <h6 class="font-bold text-green-700 dark:text-green-300 mb-4 flex items-center text-lg">
                <x-heroicon-o-user class="w-5 h-5 mr-2" />
                Staff Assignment
            </h6>
            @if($selectedStaff)
                <div class="flex items-center space-x-4">
                    <div class="w-16 h-16 bg-gradient-to-r from-green-500 to-emerald-600 rounded-full flex items-center justify-center text-white font-bold text-xl">
                        {{ substr($selectedStaff->name, 0, 2) }}
                    </div>
                    <div class="flex-1">
                        <h6 class="font-bold text-green-800 dark:text-green-200 text-lg">{{ $selectedStaff->name }}</h6>
                        <p class="text-green-600 dark:text-green-400 font-medium">{{ $selectedStaff->specialization ?? 'Professional Therapist' }}</p>
                        @if($selectedStaff->experience_years)
                            <p class="text-sm text-gray-600 dark:text-gray-400">{{ $selectedStaff->experience_years }}+ years experience</p>
                        @endif
                    </div>
                    <div class="text-right">
                        <div class="inline-flex items-center space-x-1 bg-green-100 dark:bg-green-900/30 px-3 py-1 rounded-full">
                            <x-heroicon-o-check class="w-4 h-4 text-green-600" />
                            <span class="text-sm font-medium text-green-700 dark:text-green-300">Assigned</span>
                        </div>
                    </div>
                </div>
            @else
                <div class="flex items-center space-x-4">
                    <div class="w-16 h-16 bg-gradient-to-r from-gray-400 to-gray-500 rounded-full flex items-center justify-center text-white">
                        <x-heroicon-o-users class="w-8 h-8" />
                    </div>
                    <div class="flex-1">
                        <h6 class="font-bold text-gray-800 dark:text-gray-200 text-lg">No Preference</h6>
                        <p class="text-gray-600 dark:text-gray-400">We'll assign the best available therapist</p>
                        <p class="text-sm text-blue-600 dark:text-blue-400 font-medium">✨ Flexible scheduling for earliest availability</p>
                    </div>
                    <div class="text-right">
                        <div class="inline-flex items-center space-x-1 bg-blue-100 dark:bg-blue-900/30 px-3 py-1 rounded-full">
                            <x-heroicon-o-clock class="w-4 h-4 text-blue-600" />
                            <span class="text-sm font-medium text-blue-700 dark:text-blue-300">Auto-assign</span>
                        </div>
                    </div>
                </div>
            @endif
        </div>
        
        <!-- Date & Time Details -->
        @if(isset($bookingItem['appointment_date']) && isset($bookingItem['appointment_time']))
            <div class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-purple-100 dark:border-purple-700 shadow-sm">
                <h6 class="font-bold text-purple-700 dark:text-purple-300 mb-4 flex items-center text-lg">
                    <x-heroicon-o-calendar-days class="w-5 h-5 mr-2" />
                    Appointment Schedule
                </h6>
                <div class="grid md:grid-cols-2 gap-6">
                    <div class="text-center p-4 bg-purple-50 dark:bg-purple-900/20 rounded-xl border border-purple-200 dark:border-purple-700">
                        <x-heroicon-o-calendar class="w-8 h-8 text-purple-600 mx-auto mb-2" />
                        <p class="text-sm font-medium text-purple-700 dark:text-purple-300 mb-1">Date</p>
                        <p class="font-bold text-lg text-purple-800 dark:text-purple-200">
                            {{ date('l', strtotime($bookingItem['appointment_date'])) }}
                        </p>
                        <p class="font-semibold text-purple-700 dark:text-purple-300">
                            {{ date('F j, Y', strtotime($bookingItem['appointment_date'])) }}
                        </p>
                    </div>
                    <div class="text-center p-4 bg-purple-50 dark:bg-purple-900/20 rounded-xl border border-purple-200 dark:border-purple-700">
                        <x-heroicon-o-clock class="w-8 h-8 text-purple-600 mx-auto mb-2" />
                        <p class="text-sm font-medium text-purple-700 dark:text-purple-300 mb-1">Time</p>
                        <p class="font-bold text-2xl text-purple-800 dark:text-purple-200">
                            {{ date('g:i A', strtotime($bookingItem['appointment_time'])) }}
                        </p>
                        <p class="text-sm text-purple-600 dark:text-purple-400">
                            ({{ $bookingItem['duration'] ?? 30 }} min session)
                        </p>
                    </div>
                </div>
            </div>
        @endif
        
        <!-- Customer Details -->
        @if(isset($selectedCustomer) || ($selectedCustomerType === 'new' && !empty($customerData['name'])))
            <div class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-emerald-100 dark:border-emerald-700 shadow-sm">
                <h6 class="font-bold text-emerald-700 dark:text-emerald-300 mb-4 flex items-center text-lg">
                    <x-heroicon-o-user-plus class="w-5 h-5 mr-2" />
                    Customer Information
                </h6>
                <div class="flex items-center space-x-4">
                    <div class="w-16 h-16 bg-gradient-to-r from-emerald-500 to-teal-600 rounded-full flex items-center justify-center text-white font-bold text-xl">
                        {{ substr($selectedCustomer['name'] ?? $customerData['name'], 0, 2) }}
                    </div>
                    <div class="flex-1">
                        <h6 class="font-bold text-emerald-800 dark:text-emerald-200 text-lg">
                            {{ $selectedCustomer['name'] ?? $customerData['name'] }}
                        </h6>
                        <div class="space-y-1">
                            <div class="flex items-center space-x-4 text-sm">
                                @if(!empty($selectedCustomer['phone']) || !empty($customerData['phone']))
                                    <span class="flex items-center text-emerald-600 dark:text-emerald-400">
                                        <x-heroicon-o-phone class="w-4 h-4 mr-1" />
                                        {{ $selectedCustomer['phone'] ?? $customerData['phone'] }}
                                    </span>
                                @endif
                                @if(!empty($selectedCustomer['email']) || !empty($customerData['email']))
                                    <span class="flex items-center text-emerald-600 dark:text-emerald-400">
                                        <x-heroicon-o-envelope class="w-4 h-4 mr-1" />
                                        {{ $selectedCustomer['email'] ?? $customerData['email'] }}
                                    </span>
                                @endif
                            </div>
                            @if($selectedCustomerType === 'new')
                                <div class="inline-flex items-center space-x-1 bg-emerald-100 dark:bg-emerald-900/30 px-2 py-1 rounded-full">
                                    <x-heroicon-o-user-plus class="w-3 h-3 text-emerald-600" />
                                    <span class="text-xs font-medium text-emerald-700 dark:text-emerald-300">New Customer</span>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endif
        
        <!-- Pricing Summary -->
        <div class="bg-gradient-to-r from-yellow-50 to-orange-50 dark:from-yellow-900/20 dark:to-orange-900/20 rounded-xl p-6 border-2 border-yellow-200 dark:border-yellow-700">
            <h6 class="font-bold text-yellow-800 dark:text-yellow-200 mb-4 flex items-center text-lg">
                <x-heroicon-o-currency-dollar class="w-5 h-5 mr-2" />
                Pricing Details
            </h6>
            <div class="space-y-3">
                <div class="flex justify-between items-center text-lg">
                    <span class="font-medium text-gray-700 dark:text-gray-300">Service Cost:</span>
                    <span class="font-semibold text-gray-900 dark:text-white">KES {{ number_format($bookingItem['price']) }}</span>
                </div>
                <div class="flex justify-between items-center text-lg">
                    <span class="font-medium text-gray-700 dark:text-gray-300">Tax (16% VAT):</span>
                    <span class="font-semibold text-gray-900 dark:text-white">KES {{ number_format($bookingItem['price'] * 0.16) }}</span>
                </div>
                <div class="border-t-2 border-yellow-300 dark:border-yellow-600 pt-3">
                    <div class="flex justify-between items-center text-2xl font-bold">
                        <span class="text-gray-800 dark:text-gray-200">Total Amount:</span>
                        <span class="text-green-600 dark:text-green-400">KES {{ number_format($bookingItem['price'] * 1.16) }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Action Buttons -->
<div class="mt-8 text-center">
    <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 border border-gray-200 dark:border-gray-700 shadow-sm">
        <h6 class="font-bold text-gray-900 dark:text-white mb-4 text-lg">Ready to Proceed?</h6>
        <p class="text-gray-600 dark:text-gray-400 mb-6">
            Your booking details have been confirmed. Click below to add this service to your cart and continue with payment processing.
        </p>
        
        <div class="flex flex-col sm:flex-row gap-4 justify-center items-center">
            <button wire:click="goBackBookingStep" 
                    class="flex items-center space-x-2 px-6 py-3 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 font-semibold rounded-xl transition-all duration-200 border-2 border-gray-300 dark:border-gray-600">
                <x-heroicon-o-arrow-left class="w-5 h-5" />
                <span>Review Customer Info</span>
            </button>
            
            <button wire:click="completeServiceBooking" 
                    class="flex items-center space-x-3 px-8 py-4 bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 text-white font-bold text-lg rounded-xl transition-all duration-200 transform hover:scale-105 shadow-lg hover:shadow-xl border-2 border-green-500 hover:border-green-600">
                <x-heroicon-o-shopping-cart class="w-6 h-6" />
                <span>Add to Cart & Continue</span>
                <x-heroicon-o-arrow-right class="w-5 h-5" />
            </button>
        </div>
    </div>
</div>

<!-- Important Notes -->
<div class="mt-6 bg-blue-50 dark:bg-blue-900/20 rounded-xl p-4 border border-blue-200 dark:border-blue-700">
    <div class="flex items-start space-x-3">
        <x-heroicon-o-information-circle class="w-5 h-5 text-blue-600 mt-0.5 flex-shrink-0" />
        <div class="text-sm">
            <p class="font-medium text-blue-800 dark:text-blue-200 mb-1">Important Notes:</p>
            <ul class="text-blue-700 dark:text-blue-300 space-y-1">
                <li>• This booking will be added to your current cart for payment processing</li>
                <li>• You can make changes to the booking details before final payment</li>
                <li>• A confirmation email/SMS will be sent after successful payment</li>
                <li>• Please arrive 15 minutes before your scheduled appointment time</li>
            </ul>
        </div>
    </div>
</div>