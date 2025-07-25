<div x-data="timeSlotGrid()" x-init="initializeGrid()" class="time-slot-grid">
    @if($message)
        <div class="p-4 text-sm text-gray-600 bg-gray-50 rounded-lg border">
            {{ $message }}
            @if(isset($debug) && config('app.debug'))
                <div class="mt-2 text-xs text-gray-500">
                    Debug: {{ $debug }}
                </div>
            @endif
        </div>
    @elseif(!empty($timeSlots))
        <div class="space-y-4">
            {{-- Date Header --}}
            @if($date)
                <div class="bg-gray-50 border border-gray-200 rounded-lg p-3 mb-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 text-gray-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"/>
                            </svg>
                            <div>
                                <p class="font-semibold text-gray-700">
                                    {{ \Carbon\Carbon::parse($date)->format('l, F j, Y') }}
                                    @if(\Carbon\Carbon::parse($date)->isToday())
                                        <span class="ml-2 px-2 py-1 bg-blue-100 text-blue-700 text-xs font-medium rounded-full">Today</span>
                                    @elseif(\Carbon\Carbon::parse($date)->isTomorrow())
                                        <span class="ml-2 px-2 py-1 bg-green-100 text-green-700 text-xs font-medium rounded-full">Tomorrow</span>
                                    @endif
                                </p>
                                @if(\Carbon\Carbon::parse($date)->isToday())
                                    <p class="text-xs text-gray-500">
                                        Current time: {{ \Carbon\Carbon::now('Africa/Nairobi')->format('g:i A') }} EAT
                                        <br><span class="text-amber-600 font-medium">Note: Bookings require 1 hour advance notice</span>
                                    </p>
                                @endif
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-medium text-gray-600">
                                {{ collect($timeSlots)->where('available', true)->count() }} slots available
                            </p>
                        </div>
                    </div>
                </div>
            @endif
            
            {{-- Legend --}}
            <div class="flex flex-wrap gap-4 text-sm">
                <div class="flex items-center gap-2">
                    <div class="w-4 h-4 bg-green-100 border border-green-300 rounded"></div>
                    <span class="text-gray-600">Available</span>
                </div>
                <div class="flex items-center gap-2">
                    <div class="w-4 h-4 bg-blue-500 border border-blue-600 rounded"></div>
                    <span class="text-gray-600">Selected</span>
                </div>
                <div class="flex items-center gap-2">
                    <div class="w-4 h-4 bg-gray-200 border border-gray-300 rounded"></div>
                    <span class="text-gray-600">Booked</span>
                </div>
                <div class="flex items-center gap-2">
                    <div class="w-4 h-4 bg-red-100 border border-red-300 rounded"></div>
                    <span class="text-gray-600">Past Time</span>
                </div>
            </div>

            {{-- Time Periods Headers --}}
            <div class="text-sm text-gray-500 mb-2">
                <div class="grid grid-cols-1 gap-4">
                    @php
                        $morningSlots = collect($timeSlots)->filter(fn($slot) => intval(explode(':', $slot['time'])[0]) < 12);
                        $afternoonSlots = collect($timeSlots)->filter(fn($slot) => intval(explode(':', $slot['time'])[0]) >= 12 && intval(explode(':', $slot['time'])[0]) < 17);
                        $eveningSlots = collect($timeSlots)->filter(fn($slot) => intval(explode(':', $slot['time'])[0]) >= 17);
                        $isToday = collect($timeSlots)->first()['is_today'] ?? false;
                    @endphp
                    
                    {{-- Morning Slots --}}
                    @if($morningSlots->count() > 0)
                        <div>
                            <h4 class="font-medium text-gray-700 mb-3 flex items-center">
                                <svg class="w-4 h-4 mr-2 text-yellow-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 2a1 1 0 011 1v1.323l3.954 1.582 1.599-.8a1 1 0 01.894 1.79l-1.233.616 1.738 5.42a1 1 0 01-.285 1.05A3.989 3.989 0 0115 14a3.989 3.989 0 01-2.667-1.019 1 1 0 01-.285-1.05l1.715-5.349L11 5.677V9a1 1 0 11-2 0V3a1 1 0 011-1z" clip-rule="evenodd"/>
                                </svg>
                                Morning (9:00 AM - 12:00 PM)
                                <span class="ml-2 text-xs bg-gray-100 px-2 py-1 rounded">
                                    {{ $morningSlots->where('available', true)->count() }} available
                                </span>
                            </h4>
                            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3">
                                @foreach($morningSlots as $slot)
                                    @include('filament.forms.partials.time-slot-button', ['slot' => $slot])
                                @endforeach
                            </div>
                        </div>
                    @endif
                    
                    {{-- Afternoon Slots --}}
                    @if($afternoonSlots->count() > 0)
                        <div>
                            <h4 class="font-medium text-gray-700 mb-3 flex items-center">
                                <svg class="w-4 h-4 mr-2 text-orange-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 2a1 1 0 011 1v1.323l3.954 1.582 1.599-.8a1 1 0 01.894 1.79l-1.233.616 1.738 5.42a1 1 0 01-.285 1.05A3.989 3.989 0 0115 14a3.989 3.989 0 01-2.667-1.019 1 1 0 01-.285-1.05l1.715-5.349L11 5.677V9a1 1 0 11-2 0V3a1 1 0 011-1z" clip-rule="evenodd"/>
                                </svg>
                                Afternoon (12:00 PM - 5:00 PM)
                                <span class="ml-2 text-xs bg-gray-100 px-2 py-1 rounded">
                                    {{ $afternoonSlots->where('available', true)->count() }} available
                                </span>
                            </h4>
                            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3">
                                @foreach($afternoonSlots as $slot)
                                    @include('filament.forms.partials.time-slot-button', ['slot' => $slot])
                                @endforeach
                            </div>
                        </div>
                    @endif
                    
                    {{-- Evening Slots --}}
                    @if($eveningSlots->count() > 0)
                        <div>
                            <h4 class="font-medium text-gray-700 mb-3 flex items-center">
                                <svg class="w-4 h-4 mr-2 text-purple-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 2a1 1 0 011 1v1.323l3.954 1.582 1.599-.8a1 1 0 01.894 1.79l-1.233.616 1.738 5.42a1 1 0 01-.285 1.05A3.989 3.989 0 0115 14a3.989 3.989 0 01-2.667-1.019 1 1 0 01-.285-1.05l1.715-5.349L11 5.677V9a1 1 0 11-2 0V3a1 1 0 011-1z" clip-rule="evenodd"/>
                                </svg>
                                Evening (5:00 PM - 6:00 PM)
                                <span class="ml-2 text-xs bg-gray-100 px-2 py-1 rounded">
                                    {{ $eveningSlots->where('available', true)->count() }} available
                                </span>
                            </h4>
                            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3">
                                @foreach($eveningSlots as $slot)
                                    @include('filament.forms.partials.time-slot-button', ['slot' => $slot])
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>
            
            {{-- No Available Slots Message --}}
            @if(collect($timeSlots)->where('available', true)->isEmpty())
                <div class="text-center py-8">
                    <div class="text-6xl mb-4">😔</div>
                    <h3 class="text-lg font-semibold text-gray-700 mb-2">No Available Time Slots</h3>
                    <p class="text-gray-500 mb-4">All time slots for this date are booked or unavailable.</p>
                    <div class="text-sm text-gray-400">
                        Please select a different date or try with a different staff member.
                    </div>
                </div>
            @endif
            
            {{-- Time Selection Required Message --}}
            <div x-show="!selectedTime && {{ collect($timeSlots)->where('available', true)->isNotEmpty() ? 'true' : 'false' }}" 
                 x-transition 
                 class="mt-4 p-3 bg-amber-50 border border-amber-200 rounded-lg">
                <div class="flex items-center">
                    <svg class="h-5 w-5 text-amber-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                    <p class="text-sm font-medium text-amber-800">
                        Please select a time slot to continue with your booking.
                    </p>
                </div>
            </div>
            
            {{-- Selected Time Display --}}
            <div x-show="selectedTime" x-transition class="mt-4 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <svg class="h-5 w-5 text-blue-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <div>
                            <p class="text-sm font-semibold text-blue-800">
                                Selected Time: <span x-text="selectedTime ? new Date('2000-01-01 ' + selectedTime).toLocaleTimeString('en-US', {hour: 'numeric', minute: '2-digit', hour12: true}) : ''"></span>
                            </p>
                            @if($date)
                                <p class="text-xs text-blue-600">
                                    {{ \Carbon\Carbon::parse($date)->format('l, F j, Y') }}
                                </p>
                            @endif
                            @if(config('app.debug'))
                                <p class="text-xs text-gray-500" x-text="'Debug: Alpine time = ' + selectedTime"></p>
                                <p class="text-xs text-gray-500" x-text="'Form field value = ' + (document.querySelector('input[name=start_time]')?.value || 'not found')"></p>
                            @endif
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        @if(config('app.debug'))
                            <button 
                                type="button" 
                                @click="console.log('Form Debug:', {
                                    alpineTime: selectedTime,
                                    formField: document.querySelector('input[name=start_time]'),
                                    formValue: document.querySelector('input[name=start_time]')?.value,
                                    allInputs: Array.from(document.querySelectorAll('input')).map(i => ({name: i.name, value: i.value, type: i.type}))
                                })"
                                class="px-2 py-1 bg-gray-200 text-gray-700 text-xs rounded hover:bg-gray-300"
                            >
                                Debug
                            </button>
                        @endif
                        <button 
                            type="button" 
                            @click="clearTime()" 
                            class="text-blue-600 hover:text-blue-800 text-sm underline"
                        >
                            Change Time
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

<script>
function timeSlotGrid() {
    return {
        selectedTime: '{{ $selectedTime ?? "" }}',
        startTimeValue: '{{ $selectedTime ?? "" }}', // Alpine model for form sync
        formFieldId: '{{ $formFieldId ?? "start_time_field" }}',
        
        initializeGrid() {
            console.log('🚀 Initializing enhanced time slot grid', {
                selectedTime: this.selectedTime,
                formFieldId: this.formFieldId
            });
            
            // Wait for DOM to be ready
            this.$nextTick(() => {
                this.setupFormFieldSync();
                this.watchForFormChanges();
            });
        },
        
        init() {
            // Initialize with current selected time if any
            const startTimeInput = document.querySelector('input[name="start_time"]');
            if (startTimeInput && startTimeInput.value) {
                this.selectedTime = startTimeInput.value;
                console.log('Initialized with selected time:', this.selectedTime);
            }
            
            // Watch for changes to the hidden input
            if (startTimeInput) {
                startTimeInput.addEventListener('input', (e) => {
                    if (e.target.value !== this.selectedTime) {
                        this.selectedTime = e.target.value;
                        console.log('Updated selected time from input:', this.selectedTime);
                    }
                });
            }
            
            // Listen for date changes to refresh grid
            this.$watch('$store.global.appointment_date', () => {
                this.selectedTime = '';
                this.updateFormField('');
            });
        },
        
        selectTime(time) {
            console.log('🎯 Attempting to select time:', time);
            
            // Check if the button is disabled/unavailable
            const button = document.querySelector(`button[data-time-slot="${time}"]`);
            if (button) {
                const isAvailable = button.getAttribute('data-available') === 'true';
                const isDisabled = button.hasAttribute('disabled');
                
                if (!isAvailable || isDisabled) {
                    console.warn('⚠️ Cannot select disabled/unavailable time slot:', time);
                    return; // Prevent selection of disabled slots
                }
                
                // Add visual feedback for selection
                button.classList.add('just-selected');
                button.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                setTimeout(() => button.classList.remove('just-selected'), 300);
            }
            
            // Update all time-related properties
            this.selectedTime = time;
            this.startTimeValue = time;
            this.updateFormField(time);
            
            // Emit a custom event for other components
            this.$dispatch('time-selected', { time: time });
        },
        
        clearTime() {
            console.log('Clearing selected time');
            this.selectedTime = '';
            this.updateFormField('');
        },
        
        isSelected(time) {
            return this.selectedTime === time;
        },
        
        setupFormFieldSync() {
            const startTimeInput = this.findStartTimeInput();
            if (startTimeInput) {
                console.log('✅ Form field found and connected:', startTimeInput);
                
                // Sync Alpine model with input value
                if (startTimeInput.value && !this.selectedTime) {
                    this.selectedTime = startTimeInput.value;
                    this.startTimeValue = startTimeInput.value;
                }
                
                // Set up two-way binding
                this.$watch('startTimeValue', (value) => {
                    if (startTimeInput.value !== value) {
                        startTimeInput.value = value;
                        this.triggerInputEvents(startTimeInput);
                    }
                });
                
                // Listen for external changes to the input
                startTimeInput.addEventListener('input', (e) => {
                    if (this.startTimeValue !== e.target.value) {
                        this.startTimeValue = e.target.value;
                        this.selectedTime = e.target.value;
                    }
                });
            } else {
                console.warn('⚠️ Could not find start_time input field');
                setTimeout(() => this.setupFormFieldSync(), 500); // Retry after 500ms
            }
        },
        
        watchForFormChanges() {
            // Watch for form structure changes (in case Livewire re-renders)
            const observer = new MutationObserver(() => {
                const input = this.findStartTimeInput();
                if (input && !input.hasAttribute('data-alpine-synced')) {
                    input.setAttribute('data-alpine-synced', 'true');
                    this.setupFormFieldSync();
                }
            });
            
            observer.observe(document.body, {
                childList: true,
                subtree: true
            });
        },
        
        updateFormField(time) {
            console.log('⏰ Updating form field with time:', time);
            
            // Update Alpine model (which should trigger the watcher)
            this.startTimeValue = time;
            
            // Also directly update the input as a fallback
            const startTimeInput = this.findStartTimeInput();
            if (startTimeInput) {
                startTimeInput.value = time;
                this.triggerInputEvents(startTimeInput);
                
                // Verify the update
                setTimeout(() => {
                    if (startTimeInput.value === time) {
                        console.log('✅ Form field update verified');
                    } else {
                        console.warn('⚠️ Form field update failed, retrying...');
                        startTimeInput.value = time;
                        this.triggerInputEvents(startTimeInput);
                    }
                }, 100);
            }
        },
        
        findStartTimeInput() {
            // Try multiple selectors in order of preference
            const selectors = [
                `#${this.formFieldId}`, // Use the provided form field ID
                'input[name="start_time"]',
                'input.filament-start-time-field',
                'input[data-field="start_time"]',
                'input[wire\\:model="data.start_time"]',
                'input[wire\\:model*="start_time"]',
                'input[x-model="startTimeValue"]'
            ];
            
            for (let selector of selectors) {
                try {
                    const input = document.querySelector(selector);
                    if (input) {
                        console.log(`📍 Found input with selector: ${selector}`);
                        return input;
                    }
                } catch (e) {
                    console.warn(`Selector failed: ${selector}`, e);
                }
            }
            
            // Fallback: search through all inputs
            const allInputs = document.querySelectorAll('input');
            for (let input of allInputs) {
                if (input.name && (input.name.includes('start_time') || input.name.endsWith('[start_time]'))) {
                    console.log('📍 Found input via fallback search:', input);
                    return input;
                }
            }
            
            console.error('❌ Could not find start_time input field. Available inputs:');
            console.log(Array.from(document.querySelectorAll('input')).map(i => ({
                name: i.name,
                id: i.id,
                class: i.className,
                type: i.type
            })));
            
            return null;
        },
        
        triggerInputEvents(input) {
            const events = [
                new Event('input', { bubbles: true, cancelable: true }),
                new Event('change', { bubbles: true, cancelable: true }),
                new Event('blur', { bubbles: true }),
                new Event('focus', { bubbles: true }),
                new KeyboardEvent('keyup', { bubbles: true }),
                new CustomEvent('livewire:update', { bubbles: true })
            ];
            
            events.forEach(event => {
                try {
                    input.dispatchEvent(event);
                } catch (e) {
                    console.warn('Event dispatch failed:', e);
                }
            });
        },
        
        triggerLivewireUpdate(input, value) {
            if (window.Livewire) {
                try {
                    // Try different Livewire update methods
                    if (window.Livewire.emit) {
                        window.Livewire.emit('updated', 'start_time', value);
                        window.Livewire.emit('fieldUpdated', 'start_time', value);
                    }
                    
                    // Try to find and update the component
                    const wireId = input.closest('[wire\\:id]')?.getAttribute('wire:id');
                    if (wireId && window.Livewire.find) {
                        const component = window.Livewire.find(wireId);
                        if (component && component.set) {
                            component.set('data.start_time', value);
                        }
                    }
                    
                    // Force a general Livewire refresh
                    if (window.Livewire.rescan) {
                        window.Livewire.rescan();
                    }
                } catch (e) {
                    console.warn('Livewire update failed:', e);
                }
            }
        },
        
        forceValidationUpdate(input, value) {
            // Try to trigger form validation
            const form = input.closest('form');
            if (form) {
                // Dispatch custom events
                const events = [
                    new CustomEvent('form-field-updated', {
                        detail: { field: 'start_time', value: value },
                        bubbles: true
                    }),
                    new CustomEvent('validation:update', {
                        detail: { field: 'start_time', value: value },
                        bubbles: true
                    })
                ];
                
                events.forEach(event => form.dispatchEvent(event));
            }
            
            // Try to trigger Alpine.js model updates
            if (input._x_model) {
                try {
                    input._x_model.set(value);
                } catch (e) {
                    console.warn('Alpine model update failed:', e);
                }
            }
        },
        
        verifyUpdate(input, expectedValue) {
            if (input.value !== expectedValue) {
                console.warn('Value verification failed. Expected:', expectedValue, 'Got:', input.value);
                // Try one more time with a more aggressive approach
                input.value = expectedValue;
                input.setAttribute('value', expectedValue);
                this.triggerInputEvents(input);
            } else {
                console.log('Value successfully verified:', input.value);
            }
        },
        
        createFallbackField(time) {
            console.log('Creating fallback field for time:', time);
            
            // Try to find a form container
            const formContainer = document.querySelector('form') || document.querySelector('[data-field-wrapper]');
            if (formContainer) {
                // Create a hidden input as fallback
                const hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.name = 'start_time';
                hiddenInput.value = time;
                hiddenInput.setAttribute('data-fallback-field', 'true');
                
                formContainer.appendChild(hiddenInput);
                console.log('Created fallback field:', hiddenInput);
                
                this.triggerInputEvents(hiddenInput);
            }
        }
    }
}

// Filament-specific form update helper (make it globally available)
window.updateFilamentField = function(fieldName, value) {
    console.log('Updating Filament field:', fieldName, 'to:', value);
    
    // Find all possible input selectors for this field
    const selectors = [
        `input[name="${fieldName}"]`,
        `input[data-field="${fieldName}"]`,
        `input[id="${fieldName}_field"]`,
        `input[wire\\:model="data.${fieldName}"]`,
        `input[wire\\:model*="${fieldName}"]`
    ];
    
    let updated = false;
    
    for (let selector of selectors) {
        const input = document.querySelector(selector);
        if (input) {
            console.log('Found input with selector:', selector, input);
            
            // Update the value
            input.value = value;
            input.setAttribute('value', value);
            
            // Trigger comprehensive events
            const events = [
                'input', 'change', 'blur', 'focus', 'keyup',
                'livewire:update', 'alpine:update'
            ];
            
            events.forEach(eventType => {
                try {
                    const event = new Event(eventType, { bubbles: true, cancelable: true });
                    input.dispatchEvent(event);
                } catch (e) {
                    // Try CustomEvent for special events
                    try {
                        const customEvent = new CustomEvent(eventType, { 
                            bubbles: true, 
                            detail: { field: fieldName, value: value }
                        });
                        input.dispatchEvent(customEvent);
                    } catch (ce) {
                        console.warn('Could not dispatch event:', eventType, ce);
                    }
                }
            });
            
            updated = true;
        }
    }
    
    // Try Livewire component update
    if (window.Livewire) {
        try {
            // Find the component that contains this field
            const formElement = document.querySelector('form');
            if (formElement) {
                const wireId = formElement.closest('[wire\\:id]')?.getAttribute('wire:id');
                if (wireId) {
                    const component = window.Livewire.find(wireId);
                    if (component) {
                        console.log('Updating Livewire component:', wireId);
                        
                        // Try different update methods
                        if (component.set) {
                            component.set(`data.${fieldName}`, value);
                            updated = true;
                        }
                        
                        // Force component refresh
                        if (component.$refresh) {
                            component.$refresh();
                        }
                    }
                }
            }
        } catch (e) {
            console.warn('Livewire component update failed:', e);
        }
    }
    
    return updated;
}

// Global event listener for form updates
document.addEventListener('DOMContentLoaded', function() {
    console.log('Time slot grid DOM loaded');
    
    // Enhanced click handler with Filament integration
    document.addEventListener('click', function(e) {
        if (e.target.matches('[data-time-slot]') || e.target.closest('[data-time-slot]')) {
            const button = e.target.matches('[data-time-slot]') ? e.target : e.target.closest('[data-time-slot]');
            const time = button.getAttribute('data-time-slot');
            const available = button.getAttribute('data-available') === 'true';
            
            if (available) {
                console.log('Time slot button clicked:', time);
                
                // Try Alpine.js update first
                const gridComponent = button.closest('[x-data]');
                if (gridComponent && gridComponent._x_dataStack && gridComponent._x_dataStack[0]) {
                    gridComponent._x_dataStack[0].selectTime(time);
                }
                
                // Also try direct Filament field update
                updateFilamentField('start_time', time);
                
                // Force form validation update
                setTimeout(() => {
                    const form = document.querySelector('form');
                    if (form) {
                        const validationEvent = new CustomEvent('filament:field-updated', {
                            bubbles: true,
                            detail: { field: 'start_time', value: time }
                        });
                        form.dispatchEvent(validationEvent);
                    }
                }, 100);
            }
        }
    });
    
    // Monitor form state changes
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.type === 'childList') {
                // Check if start_time field was added
                const addedNodes = Array.from(mutation.addedNodes);
                addedNodes.forEach(node => {
                    if (node.nodeType === 1) { // Element node
                        const startTimeInput = node.querySelector ? 
                            node.querySelector('input[name="start_time"], input[data-field="start_time"]') : 
                            (node.matches && node.matches('input[name="start_time"], input[data-field="start_time"]') ? node : null);
                        
                        if (startTimeInput) {
                            console.log('start_time field detected:', startTimeInput);
                            
                            // Add additional event listeners
                            startTimeInput.addEventListener('input', function(e) {
                                console.log('start_time input event:', e.target.value);
                            });
                        }
                    }
                });
            }
        });
    });
    
    // Start observing the entire document for changes
    observer.observe(document.body, { 
        childList: true, 
        subtree: true 
    });
});
</script>

<style>
.time-slot-grid .grid > button:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.time-slot-grid .grid > button:active:not(:disabled) {
    transform: translateY(0);
}

.time-slot-grid .grid > button[disabled] {
    cursor: not-allowed;
}

/* Enhanced selection styling */
.time-slot-grid .grid > button[data-available="true"]:hover:not(.selected) {
    background-color: rgb(34, 197, 94) !important; /* green-500 */
    color: white !important;
    border-color: rgb(21, 128, 61) !important; /* green-700 */
    transform: translateY(-2px) scale(1.02);
}

.time-slot-grid .grid > button.selected,
.time-slot-grid .grid > button[data-selected="true"] {
    background-color: rgb(59, 130, 246) !important; /* blue-500 */
    color: white !important;
    border-color: rgb(37, 99, 235) !important; /* blue-600 */
    transform: scale(1.05);
    box-shadow: 0 8px 16px rgba(59, 130, 246, 0.3);
}

/* Click animation */
.time-slot-grid .grid > button[data-available="true"]:active {
    transform: scale(0.98);
    transition: transform 0.1s;
}

/* Selection feedback animation */
@keyframes selectSlot {
    0% { transform: scale(1); }
    50% { transform: scale(1.1); }
    100% { transform: scale(1.05); }
}

.time-slot-grid .grid > button.just-selected {
    animation: selectSlot 0.3s ease-out;
}

/* Add pulse animation for selected slots */
.time-slot-grid .grid > button.selected::before {
    content: '';
    position: absolute;
    top: -2px;
    left: -2px;
    right: -2px;
    bottom: -2px;
    background: linear-gradient(45deg, rgb(59, 130, 246), rgb(37, 99, 235));
    border-radius: 10px;
    z-index: -1;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0%, 100% { opacity: 0.7; }
    50% { opacity: 1; }
}
</style>