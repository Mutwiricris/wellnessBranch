<!-- Date & Time Selection Step -->
<div class="text-center mb-8">
    <div class="w-20 h-20 bg-gradient-to-r from-purple-500 to-pink-600 rounded-2xl flex items-center justify-center mx-auto mb-4">
        <x-heroicon-o-calendar-days class="w-10 h-10 text-white" />
    </div>
    <h4 class="text-3xl font-bold text-gray-900 dark:text-white mb-3">Select Date & Time</h4>
    <p class="text-lg text-purple-600 dark:text-purple-400 font-medium max-w-2xl mx-auto">
        Choose your preferred appointment date and time slot
    </p>
</div>

@php 
    $bookingItem = $this->getServiceBookingItem();
    $selectedStaff = null;
    if ($bookingItem && isset($bookingItem['staff_id'])) {
        $selectedStaff = $this->getStaff()->where('id', $bookingItem['staff_id'])->first();
    }
@endphp

<!-- Selected Staff Display -->
@if($selectedStaff)
    <div class="mb-8 bg-green-50 dark:bg-green-900/20 rounded-xl p-4 border border-green-200 dark:border-green-700">
        <div class="flex items-center space-x-3">
            <div class="w-10 h-10 bg-green-500 rounded-full flex items-center justify-center">
                <x-heroicon-o-check class="w-5 h-5 text-white" />
            </div>
            <div>
                <p class="font-semibold text-green-800 dark:text-green-200">Selected Staff</p>
                <p class="text-sm text-green-700 dark:text-green-300">
                    {{ $selectedStaff->name }} - {{ $selectedStaff->specialization ?? 'Professional Therapist' }}
                </p>
            </div>
        </div>
    </div>
@elseif($bookingItem && empty($bookingItem['staff_id']))
    <div class="mb-8 bg-blue-50 dark:bg-blue-900/20 rounded-xl p-4 border border-blue-200 dark:border-blue-700">
        <div class="flex items-center space-x-3">
            <div class="w-10 h-10 bg-blue-500 rounded-full flex items-center justify-center">
                <x-heroicon-o-users class="w-5 h-5 text-white" />
            </div>
            <div>
                <p class="font-semibold text-blue-800 dark:text-blue-200">No Staff Preference</p>
                <p class="text-sm text-blue-700 dark:text-blue-300">
                    We'll assign the best available therapist for your selected time
                </p>
            </div>
        </div>
    </div>
@endif

<div class="grid lg:grid-cols-2 gap-8">
    <!-- Date Selection -->
    <div class="space-y-6">
        <div>
            <label class="block text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center">
                <x-heroicon-o-calendar class="w-5 h-5 mr-2 text-purple-600" />
                Select Date
            </label>
            <input wire:model.live="selectServiceDate" 
                   wire:change="selectServiceDate($event.target.value)"
                   type="date" 
                   min="{{ date('Y-m-d') }}"
                   max="{{ date('Y-m-d', strtotime('+3 months')) }}"
                   class="w-full px-6 py-4 border-2 border-purple-200 dark:border-purple-600 rounded-xl focus:ring-4 focus:ring-purple-300 focus:border-purple-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white font-medium text-lg hover:border-purple-400 transition-all duration-200">
        </div>

        <!-- Quick Date Selection -->
        <div class="grid grid-cols-3 gap-3">
            @php
                $quickDates = [
                    ['label' => 'Today', 'date' => date('Y-m-d'), 'available' => true],
                    ['label' => 'Tomorrow', 'date' => date('Y-m-d', strtotime('+1 day')), 'available' => true],
                    ['label' => 'Next Week', 'date' => date('Y-m-d', strtotime('+1 week')), 'available' => true]
                ];
            @endphp
            
            @foreach($quickDates as $quickDate)
                <button wire:click="selectServiceDate('{{ $quickDate['date'] }}')"
                        class="p-3 text-sm font-medium rounded-lg border-2 transition-all duration-200 hover:shadow-md
                        {{ $selectServiceDate === $quickDate['date'] ? 'border-purple-500 bg-purple-50 dark:bg-purple-900/20 text-purple-700 dark:text-purple-300' : 'border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:border-purple-300' }}">
                    <div class="text-center">
                        <div class="font-semibold">{{ $quickDate['label'] }}</div>
                        <div class="text-xs opacity-75">{{ date('M j', strtotime($quickDate['date'])) }}</div>
                    </div>
                </button>
            @endforeach
        </div>
    </div>

    <!-- Time Slots -->
    <div class="space-y-6">
        <div>
            <label class="block text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center">
                <x-heroicon-o-clock class="w-5 h-5 mr-2 text-purple-600" />
                Available Time Slots
            </label>
            
            @if($selectServiceDate && count($availableTimeSlots) > 0)
                <div class="grid grid-cols-2 gap-3 max-h-80 overflow-y-auto p-2 bg-gray-50 dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700">
                    @foreach($availableTimeSlots as $slot)
                        <button wire:click="selectServiceTime('{{ $slot['time'] }}')" 
                                class="group p-4 text-sm font-semibold rounded-xl border-2 transition-all duration-200 focus:outline-none focus:ring-4 focus:ring-purple-300
                                {{ $slot['available'] ? 'border-purple-200 dark:border-purple-600 bg-white dark:bg-gray-700 hover:border-purple-500 hover:bg-purple-50 dark:hover:bg-purple-900/20 text-purple-700 dark:text-purple-300 hover:shadow-md hover:scale-105' : 'border-gray-200 bg-gray-100 dark:bg-gray-600 text-gray-400 dark:text-gray-500 cursor-not-allowed opacity-60' }}"
                                {{ !$slot['available'] ? 'disabled' : '' }}>
                            <div class="flex flex-col items-center space-y-1">
                                <span class="text-base font-bold">{{ $slot['display'] }}</span>
                                @if($slot['available'])
                                    <span class="text-xs text-green-600 dark:text-green-400 bg-green-100 dark:bg-green-900/30 px-2 py-1 rounded-full">
                                        Available
                                    </span>
                                @else
                                    <span class="text-xs text-red-500 bg-red-100 dark:bg-red-900/30 px-2 py-1 rounded-full">
                                        Booked
                                    </span>
                                @endif
                            </div>
                        </button>
                    @endforeach
                </div>
            @elseif($selectServiceDate)
                <div class="text-center py-12 bg-gray-50 dark:bg-gray-800 rounded-xl border-2 border-dashed border-gray-300 dark:border-gray-600">
                    <x-heroicon-o-clock class="w-12 h-12 mx-auto mb-4 text-gray-400" />
                    <h5 class="text-lg font-medium text-gray-900 dark:text-white mb-2">No Available Slots</h5>
                    <p class="text-gray-600 dark:text-gray-400 mb-4">
                        Sorry, no time slots are available for the selected date.
                    </p>
                    <button wire:click="selectServiceDate('')" 
                            class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white font-medium rounded-lg transition-colors">
                        Choose Different Date
                    </button>
                </div>
            @else
                <div class="text-center py-12 bg-gray-50 dark:bg-gray-800 rounded-xl border-2 border-dashed border-gray-300 dark:border-gray-600">
                    <x-heroicon-o-calendar class="w-12 h-12 mx-auto mb-4 text-gray-400" />
                    <h5 class="text-lg font-medium text-gray-900 dark:text-white mb-2">Select a Date First</h5>
                    <p class="text-gray-600 dark:text-gray-400">
                        Please select a date to view available time slots
                    </p>
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Appointment Summary -->
@if($selectServiceDate && isset($bookingItem['appointment_time']))
    <div class="mt-8 bg-gradient-to-r from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20 rounded-xl p-6 border border-purple-200 dark:border-purple-700">
        <h5 class="text-lg font-bold text-purple-800 dark:text-purple-200 mb-4 flex items-center">
            <x-heroicon-o-check-circle class="w-5 h-5 mr-2" />
            Appointment Details Confirmed
        </h5>
        <div class="grid md:grid-cols-2 gap-4 text-sm">
            <div class="flex justify-between">
                <span class="font-medium text-gray-700 dark:text-gray-300">Date:</span>
                <span class="font-bold text-purple-700 dark:text-purple-300">
                    {{ date('l, F j, Y', strtotime($selectServiceDate)) }}
                </span>
            </div>
            <div class="flex justify-between">
                <span class="font-medium text-gray-700 dark:text-gray-300">Time:</span>
                <span class="font-bold text-purple-700 dark:text-purple-300">
                    {{ date('g:i A', strtotime($bookingItem['appointment_time'])) }}
                </span>
            </div>
        </div>
    </div>
@endif

<!-- Helpful Information -->
<div class="mt-8 bg-blue-50 dark:bg-blue-900/20 rounded-xl p-4 border border-blue-200 dark:border-blue-700">
    <div class="flex items-start space-x-3">
        <x-heroicon-o-information-circle class="w-5 h-5 text-blue-600 mt-0.5 flex-shrink-0" />
        <div class="text-sm">
            <p class="font-medium text-blue-800 dark:text-blue-200 mb-1">Booking Information:</p>
            <ul class="text-blue-700 dark:text-blue-300 space-y-1">
                <li>• Appointments can be booked up to 3 months in advance</li>
                <li>• Please arrive 15 minutes before your scheduled time</li>
                <li>• Same-day cancellations may incur a fee</li>
                <li>• Time slots are updated in real-time based on availability</li>
            </ul>
        </div>
    </div>
</div>