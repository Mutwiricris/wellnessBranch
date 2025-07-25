<div class="space-y-4">
    @if (session()->has('error'))
        <div class="p-4 text-sm text-red-700 bg-red-100 rounded-lg">
            {{ session('error') }}
        </div>
    @endif

    {{-- Date Selection --}}
    @if (!empty($availableDates))
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Select Date</label>
            <div class="grid grid-cols-3 md:grid-cols-7 gap-3">
                @foreach ($availableDates as $date)
                    <button
                        type="button"
                        wire:click="$set('selectedDate', '{{ $date['date'] }}')"
                        class="p-3 text-sm rounded-xl border-2 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-1
                               {{ $selectedDate === $date['date'] 
                                  ? 'bg-blue-600 text-white border-blue-600 shadow-lg transform scale-105' 
                                  : 'bg-white text-gray-700 border-blue-200 hover:border-blue-400 hover:bg-blue-50 hover:shadow-md' }}
                               {{ !$date['has_availability'] ? 'opacity-50 cursor-not-allowed border-gray-200 bg-gray-100' : '' }}"
                        {{ !$date['has_availability'] ? 'disabled' : '' }}
                    >
                        <div class="font-semibold">{{ $date['formatted'] }}</div>
                        <div class="text-xs mt-1 {{ $selectedDate === $date['date'] ? 'text-blue-100' : 'text-gray-500' }}">{{ $date['day_name'] }}</div>
                        @if($date['has_availability'])
                            <div class="text-xs mt-1 {{ $selectedDate === $date['date'] ? 'text-green-200' : 'text-green-600' }}">Available</div>
                        @endif
                    </button>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Time Slots Selection --}}
    @if ($selectedDate && !empty($timeSlots))
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
                Available Time Slots for {{ \Carbon\Carbon::parse($selectedDate)->format('M j, Y') }}
            </label>
            
            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-3">
                @foreach ($timeSlots as $slot)
                    <button
                        type="button"
                        wire:click="selectTimeSlot('{{ $slot['time'] }}')"
                        class="p-4 text-sm rounded-xl border-2 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-1
                               {{ !$slot['available'] || $slot['is_past'] 
                                  ? 'bg-gray-100 text-gray-400 border-gray-200 cursor-not-allowed' 
                                  : ($selectedTime === $slot['time'] 
                                     ? 'bg-green-600 text-white border-green-600 shadow-lg transform scale-105' 
                                     : 'bg-white text-gray-700 border-blue-200 hover:border-green-400 hover:bg-green-50 hover:shadow-md active:scale-95') }}"
                        {{ !$slot['available'] || $slot['is_past'] ? 'disabled' : '' }}
                        title="{{ $slot['reason'] }}"
                    >
                        <div class="font-semibold text-base">{{ $slot['formatted_time'] }}</div>
                        
                        @if ($slot['staff_name'] && !$staffId)
                            <div class="text-xs mt-1 {{ $selectedTime === $slot['time'] ? 'text-green-100' : 'text-blue-600' }}">{{ $slot['staff_name'] }}</div>
                        @endif
                        
                        @if ($slot['available'] && !$slot['is_past'])
                            <div class="text-xs mt-1 {{ $selectedTime === $slot['time'] ? 'text-green-200' : 'text-green-600' }}">Available</div>
                        @elseif (!$slot['available'])
                            <div class="text-xs text-red-500 mt-1 font-medium">
                                {{ $slot['is_past'] ? 'Past' : 'Booked' }}
                            </div>
                        @endif
                    </button>
                @endforeach
            </div>
            
            @if (collect($timeSlots)->where('available', true)->isEmpty())
                <div class="text-center py-8 text-gray-500">
                    <div class="text-lg mb-2">😔</div>
                    <p>No available time slots for this date.</p>
                    <p class="text-sm">Please select a different date.</p>
                </div>
            @endif
        </div>
    @elseif ($selectedDate)
        <div class="text-center py-8 text-gray-500">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto mb-2"></div>
            <p>Loading available time slots...</p>
        </div>
    @endif

    {{-- Selected Time Display --}}
    @if ($selectedDate && $selectedTime)
        <div class="p-5 bg-gradient-to-r from-green-50 to-blue-50 border-2 border-green-300 rounded-xl shadow-md">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-10 h-10 bg-green-500 rounded-full flex items-center justify-center">
                        <svg class="h-6 w-6 text-white" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                    </div>
                </div>
                <div class="ml-4">
                    <p class="text-base font-bold text-green-800 mb-1">Appointment Scheduled</p>
                    <p class="text-sm font-semibold text-gray-700">
                        {{ \Carbon\Carbon::parse($selectedDate)->format('l, F j, Y') }} 
                        at {{ \Carbon\Carbon::parse($selectedTime)->format('g:i A') }}
                    </p>
                </div>
            </div>
        </div>
    @endif
</div>