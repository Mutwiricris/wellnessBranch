<x-layouts.app :title="__('Select Date & Time')">
    <div class="flex h-full w-full flex-1 flex-col gap-8 p-6">
        <!-- Page Header -->
        <div class="flex items-center justify-between">
            <div class="space-y-1">
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Select Date & Time</h1>
                <p class="text-sm text-gray-600 dark:text-gray-400">Choose your preferred appointment date and time</p>
            </div>
            <div class="flex items-center gap-3">
                <flux:button variant="ghost" href="{{ route('branch.bookings.select-staff') }}" wire:navigate>
                    <flux:icon.arrow-left class="mr-2 h-4 w-4" />
                    Back
                </flux:button>
            </div>
        </div>

        <!-- Progress Steps -->
        <div class="bg-white dark:bg-zinc-900 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center justify-between mb-8">
                <div class="flex items-center space-x-4">
                    <div class="flex items-center justify-center w-8 h-8 bg-green-600 text-white rounded-full text-sm font-medium">
                        <flux:icon.check class="h-4 w-4" />
                    </div>
                    <span class="text-sm font-medium text-green-600">Service Selected</span>
                </div>
                <div class="flex-1 h-px bg-gray-200 dark:bg-gray-700 mx-4"></div>
                <div class="flex items-center space-x-4">
                    <div class="flex items-center justify-center w-8 h-8 bg-green-600 text-white rounded-full text-sm font-medium">
                        <flux:icon.check class="h-4 w-4" />
                    </div>
                    <span class="text-sm font-medium text-green-600">Staff Selected</span>
                </div>
                <div class="flex-1 h-px bg-gray-200 dark:bg-gray-700 mx-4"></div>
                <div class="flex items-center space-x-4">
                    <div class="flex items-center justify-center w-8 h-8 bg-blue-600 text-white rounded-full text-sm font-medium">
                        3
                    </div>
                    <span class="text-sm font-medium text-blue-600">Date & Time</span>
                </div>
                <div class="flex-1 h-px bg-gray-200 dark:bg-gray-700 mx-4"></div>
                <div class="flex items-center space-x-4">
                    <div class="flex items-center justify-center w-8 h-8 bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-400 rounded-full text-sm font-medium">
                        4
                    </div>
                    <span class="text-sm text-gray-600 dark:text-gray-400">Client Info</span>
                </div>
            </div>
        </div>

        <!-- Booking Summary -->
        <div class="grid gap-4 md:grid-cols-2">
            <flux:card class="p-4 bg-blue-50 dark:bg-blue-900/20 border-blue-200 dark:border-blue-800">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="font-medium text-blue-900 dark:text-blue-100">{{ $service->name }}</h3>
                        <p class="text-sm text-blue-700 dark:text-blue-300">
                            {{ $service->duration ?? 30 }}min • KES {{ number_format($service->price, 2) }}
                        </p>
                    </div>
                    <flux:icon.check-circle class="h-8 w-8 text-blue-600" />
                </div>
            </flux:card>

            <flux:card class="p-4 bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="font-medium text-green-900 dark:text-green-100">
                            @if($staff)
                                {{ $staff->name }}
                            @else
                                No Preference
                            @endif
                        </h3>
                        <p class="text-sm text-green-700 dark:text-green-300">
                            @if($staff)
                                {{ $staff->specialization ?? 'Therapist' }}
                            @else
                                We'll assign the best available therapist
                            @endif
                        </p>
                    </div>
                    <flux:icon.check-circle class="h-8 w-8 text-green-600" />
                </div>
            </flux:card>
        </div>

        <!-- Date and Time Selection -->
        <div class="grid gap-8 lg:grid-cols-2">
            <!-- Date Selection -->
            <flux:card class="p-6">
                <div class="mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Select Date</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Choose your preferred appointment date</p>
                </div>

                <div class="grid gap-2 max-h-96 overflow-y-auto">
                    @foreach($availableDates as $date)
                        <button type="button" 
                                class="date-option w-full text-left p-3 rounded-lg border border-gray-200 dark:border-gray-700 
                                       hover:border-blue-300 dark:hover:border-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 
                                       transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                data-date="{{ $date['date'] }}">
                            <div class="flex items-center justify-between">
                                <div>
                                    <div class="font-medium text-gray-900 dark:text-white">
                                        {{ $date['day_name'] }}, {{ $date['formatted'] }}
                                        @if($date['is_today'])
                                            <span class="text-xs bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 px-2 py-1 rounded-full ml-2">Today</span>
                                        @elseif($date['is_tomorrow'])
                                            <span class="text-xs bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 px-2 py-1 rounded-full ml-2">Tomorrow</span>
                                        @endif
                                    </div>
                                    <div class="text-sm text-gray-600 dark:text-gray-400">{{ $date['year'] }}</div>
                                </div>
                                <flux:icon.chevron-right class="h-5 w-5 text-gray-400" />
                            </div>
                        </button>
                    @endforeach
                </div>
            </flux:card>

            <!-- Time Selection -->
            <flux:card class="p-6">
                <div class="mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Select Time</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Choose your preferred appointment time</p>
                </div>

                <div id="time-slots-container" class="space-y-2">
                    <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                        <flux:icon.calendar class="h-12 w-12 mx-auto mb-2 text-gray-300" />
                        <p>Please select a date first</p>
                    </div>
                </div>

                <div id="loading-spinner" class="hidden text-center py-8">
                    <div class="inline-flex items-center px-4 py-2 font-semibold leading-6 text-sm shadow rounded-md text-blue-500 bg-blue-100 dark:bg-blue-900 transition ease-in-out duration-150">
                        <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Loading available times...
                    </div>
                </div>
            </flux:card>
        </div>

        <!-- Continue Button -->
        <div class="flex justify-between">
            <flux:button variant="ghost" href="{{ route('branch.bookings.select-staff') }}" wire:navigate>
                <flux:icon.arrow-left class="mr-2 h-4 w-4" />
                Back to Staff Selection
            </flux:button>
            
            <form id="datetime-form" method="POST" action="{{ route('branch.bookings.select-datetime.post') }}" class="hidden">
                @csrf
                <input type="hidden" name="date" id="selected-date">
                <input type="hidden" name="time" id="selected-time">
            </form>
            
            <flux:button id="continue-btn" type="button" variant="primary" disabled>
                Continue to Client Info
                <flux:icon.arrow-right class="ml-2 h-4 w-4" />
            </flux:button>
        </div>
    </div>

    @push('scripts')
    <script>
        let selectedDate = null;
        let selectedTime = null;

        document.addEventListener('DOMContentLoaded', function() {
            const dateOptions = document.querySelectorAll('.date-option');
            const timeSlotsContainer = document.getElementById('time-slots-container');
            const loadingSpinner = document.getElementById('loading-spinner');
            const continueBtn = document.getElementById('continue-btn');
            const datetimeForm = document.getElementById('datetime-form');

            // Date selection handler
            dateOptions.forEach(option => {
                option.addEventListener('click', function() {
                    const date = this.dataset.date;
                    selectedDate = date;
                    selectedTime = null;

                    // Update UI
                    dateOptions.forEach(opt => {
                        opt.classList.remove('border-blue-500', 'bg-blue-50', 'dark:bg-blue-900/20');
                        opt.classList.add('border-gray-200', 'dark:border-gray-700');
                    });
                    this.classList.remove('border-gray-200', 'dark:border-gray-700');
                    this.classList.add('border-blue-500', 'bg-blue-50', 'dark:bg-blue-900/20');

                    // Load time slots
                    loadTimeSlots(date);
                    updateContinueButton();
                });
            });

            // Continue button handler
            continueBtn.addEventListener('click', function() {
                if (selectedDate && selectedTime) {
                    document.getElementById('selected-date').value = selectedDate;
                    document.getElementById('selected-time').value = selectedTime;
                    datetimeForm.submit();
                }
            });

            function loadTimeSlots(date) {
                timeSlotsContainer.classList.add('hidden');
                loadingSpinner.classList.remove('hidden');

                fetch(`{{ route('branch.bookings.get-timeslots') }}?date=${date}`)
                    .then(response => response.json())
                    .then(data => {
                        loadingSpinner.classList.add('hidden');
                        timeSlotsContainer.classList.remove('hidden');
                        
                        if (data.success && data.timeSlots.length > 0) {
                            renderTimeSlots(data.timeSlots);
                        } else {
                            renderNoTimeSlots(data.message || 'No available time slots for this date.');
                        }
                    })
                    .catch(error => {
                        console.error('Error loading time slots:', error);
                        loadingSpinner.classList.add('hidden');
                        timeSlotsContainer.classList.remove('hidden');
                        renderNoTimeSlots('Error loading time slots. Please try again.');
                    });
            }

            function renderTimeSlots(timeSlots) {
                const slotsHtml = timeSlots.map(slot => {
                    const timeFormatted = slot.formatted_time || formatTime(slot.time);
                    
                    // Determine slot styling based on status
                    let buttonClass = 'time-slot w-full text-left p-3 rounded-lg border transition-all duration-200 focus:outline-none';
                    let statusBadge = '';
                    let isClickable = true;
                    
                    if (slot.is_past || slot.disabled) {
                        // Past/disabled slots - gray and unclickable
                        buttonClass += ' border-gray-300 dark:border-gray-600 bg-gray-100 dark:bg-gray-800 opacity-60 cursor-not-allowed';
                        statusBadge = '<span class="text-xs bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-400 px-2 py-1 rounded-full">Past</span>';
                        isClickable = false;
                    } else if (!slot.available) {
                        // Booked slots - red and unclickable
                        buttonClass += ' border-red-200 dark:border-red-700 bg-red-50 dark:bg-red-900/20 opacity-75 cursor-not-allowed';
                        statusBadge = '<span class="text-xs bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200 px-2 py-1 rounded-full">Booked</span>';
                        isClickable = false;
                    } else {
                        // Available slots - green and clickable
                        buttonClass += ' border-gray-200 dark:border-gray-700 hover:border-blue-300 dark:hover:border-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 focus:ring-2 focus:ring-blue-500';
                        statusBadge = '<span class="text-xs bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 px-2 py-1 rounded-full">Available</span>';
                    }
                    
                    return `
                        <button type="button" 
                                class="${buttonClass}"
                                data-time="${slot.time}"
                                ${!isClickable ? 'disabled' : ''}>
                            <div class="flex items-center justify-between">
                                <div>
                                    <div class="font-medium ${slot.is_past ? 'text-gray-500 dark:text-gray-500' : 'text-gray-900 dark:text-white'}">${timeFormatted}</div>
                                    ${slot.staff_name ? `<div class="text-sm ${slot.is_past ? 'text-gray-400 dark:text-gray-600' : 'text-gray-600 dark:text-gray-400'}">with ${slot.staff_name}</div>` : ''}
                                    ${slot.reason && slot.is_past ? `<div class="text-xs ${slot.is_past ? 'text-gray-400 dark:text-gray-600' : 'text-gray-500 dark:text-gray-500'} mt-1">${slot.reason}</div>` : ''}
                                </div>
                                <div class="flex items-center">
                                    ${statusBadge}
                                </div>
                            </div>
                        </button>
                    `;
                }).join('');

                timeSlotsContainer.innerHTML = slotsHtml;

                // Add event listeners to time slots
                const timeSlotButtons = timeSlotsContainer.querySelectorAll('.time-slot:not([disabled])');
                timeSlotButtons.forEach(button => {
                    button.addEventListener('click', function() {
                        const time = this.dataset.time;
                        selectedTime = time;

                        // Update UI
                        timeSlotButtons.forEach(btn => {
                            btn.classList.remove('border-blue-500', 'bg-blue-50', 'dark:bg-blue-900/20');
                            btn.classList.add('border-gray-200', 'dark:border-gray-700');
                        });
                        this.classList.remove('border-gray-200', 'dark:border-gray-700');
                        this.classList.add('border-blue-500', 'bg-blue-50', 'dark:bg-blue-900/20');

                        updateContinueButton();
                    });
                });
            }

            function renderNoTimeSlots(message) {
                timeSlotsContainer.innerHTML = `
                    <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                        <svg class="h-12 w-12 mx-auto mb-2 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <p>${message}</p>
                    </div>
                `;
            }

            function formatTime(time) {
                const [hours, minutes] = time.split(':');
                const hour = parseInt(hours);
                const ampm = hour >= 12 ? 'PM' : 'AM';
                const displayHour = hour % 12 || 12;
                return `${displayHour}:${minutes} ${ampm}`;
            }

            function updateContinueButton() {
                if (selectedDate && selectedTime) {
                    continueBtn.disabled = false;
                    continueBtn.classList.remove('opacity-50', 'cursor-not-allowed');
                } else {
                    continueBtn.disabled = true;
                    continueBtn.classList.add('opacity-50', 'cursor-not-allowed');
                }
            }
        });
    </script>
    @endpush
</x-layouts.app>
