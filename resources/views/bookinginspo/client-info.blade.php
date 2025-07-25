<x-layouts.app :title="__('Client Information')">
    <div class="flex h-full w-full flex-1 flex-col gap-8 p-6">
        <!-- Page Header -->
        <div class="flex items-center justify-between">
            <div class="space-y-1">
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Client Information</h1>
                <p class="text-sm text-gray-600 dark:text-gray-400">Enter client details to complete the booking</p>
            </div>
            <div class="flex items-center gap-3">
                <flux:button variant="ghost" href="{{ route('branch.bookings.select-datetime') }}" wire:navigate>
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
                    <div class="flex items-center justify-center w-8 h-8 bg-green-600 text-white rounded-full text-sm font-medium">
                        <flux:icon.check class="h-4 w-4" />
                    </div>
                    <span class="text-sm font-medium text-green-600">Date & Time Selected</span>
                </div>
                <div class="flex-1 h-px bg-gray-200 dark:bg-gray-700 mx-4"></div>
                <div class="flex items-center space-x-4">
                    <div class="flex items-center justify-center w-8 h-8 bg-blue-600 text-white rounded-full text-sm font-medium">
                        4
                    </div>
                    <span class="text-sm font-medium text-blue-600">Client Info</span>
                </div>
            </div>
        </div>

        <!-- Booking Summary -->
        <div class="grid gap-4 md:grid-cols-3">
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
                                Best available therapist
                            @endif
                        </p>
                    </div>
                    <flux:icon.check-circle class="h-8 w-8 text-green-600" />
                </div>
            </flux:card>

            <flux:card class="p-4 bg-purple-50 dark:bg-purple-900/20 border-purple-200 dark:border-purple-800">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="font-medium text-purple-900 dark:text-purple-100">
                            {{ \Carbon\Carbon::parse($bookingData['date'])->format('M j, Y') }}
                        </h3>
                        <p class="text-sm text-purple-700 dark:text-purple-300">
                            {{ \Carbon\Carbon::parse($bookingData['time'])->format('g:i A') }}
                        </p>
                    </div>
                    <flux:icon.check-circle class="h-8 w-8 text-purple-600" />
                </div>
            </flux:card>
        </div>

        <!-- Client Information Form -->
        <flux:card class="p-6">
            <div class="mb-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Client Information</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">Please provide the client's details for this booking</p>
            </div>

            <form method="POST" action="{{ route('branch.bookings.client-info.post') }}" class="space-y-6">
                @csrf
                
                <!-- Personal Information -->
                <div class="grid gap-6 md:grid-cols-2">
                    <flux:field>
                        <flux:label for="first_name">First Name *</flux:label>
                        <flux:input 
                            type="text" 
                            name="first_name" 
                            id="first_name"
                            value="{{ old('first_name') }}"
                            placeholder="Enter first name"
                            required />
                        <flux:error name="first_name" />
                    </flux:field>

                    <flux:field>
                        <flux:label for="last_name">Last Name *</flux:label>
                        <flux:input 
                            type="text" 
                            name="last_name" 
                            id="last_name"
                            value="{{ old('last_name') }}"
                            placeholder="Enter last name"
                            required />
                        <flux:error name="last_name" />
                    </flux:field>
                </div>

                <!-- Contact Information -->
                <div class="grid gap-6 md:grid-cols-2">
                    <flux:field>
                        <flux:label for="email">Email Address *</flux:label>
                        <flux:input 
                            type="email" 
                            name="email" 
                            id="email"
                            value="{{ old('email') }}"
                            placeholder="Enter email address"
                            required />
                        <flux:error name="email" />
                    </flux:field>

                    <flux:field>
                        <flux:label for="phone">Phone Number *</flux:label>
                        <flux:input 
                            type="tel" 
                            name="phone" 
                            id="phone"
                            value="{{ old('phone') }}"
                            placeholder="Enter phone number"
                            required />
                        <flux:error name="phone" />
                    </flux:field>
                </div>

                <!-- Additional Information -->
                <div class="grid gap-6 md:grid-cols-2">
                    <flux:field>
                        <flux:label for="gender">Gender</flux:label>
                        <flux:select name="gender" id="gender">
                            <flux:option value="">Select gender</flux:option>
                            <flux:option value="male" :selected="old('gender') === 'male'">Male</flux:option>
                            <flux:option value="female" :selected="old('gender') === 'female'">Female</flux:option>
                            <flux:option value="other" :selected="old('gender') === 'other'">Other</flux:option>
                            <flux:option value="prefer_not_to_say" :selected="old('gender') === 'prefer_not_to_say'">Prefer not to say</flux:option>
                        </flux:select>
                        <flux:error name="gender" />
                    </flux:field>

                    <flux:field>
                        <flux:label for="date_of_birth">Date of Birth</flux:label>
                        <flux:input 
                            type="date" 
                            name="date_of_birth" 
                            id="date_of_birth"
                            value="{{ old('date_of_birth') }}"
                            max="{{ date('Y-m-d') }}" />
                        <flux:error name="date_of_birth" />
                    </flux:field>
                </div>

                <!-- Allergies and Special Notes -->
                <flux:field>
                    <flux:label for="allergies">Allergies & Special Notes</flux:label>
                    <flux:textarea 
                        name="allergies" 
                        id="allergies"
                        rows="3"
                        placeholder="Please list any allergies, medical conditions, or special requirements (or write 'None')">{{ old('allergies') }}</flux:textarea>
                    <flux:description>This information helps our staff provide the best possible service</flux:description>
                    <flux:error name="allergies" />
                </flux:field>

                <!-- Payment Method -->
                <flux:field>
                    <flux:label>Payment Method *</flux:label>
                    <div class="grid gap-4 md:grid-cols-3 mt-2">
                        <label class="relative cursor-pointer">
                            <input type="radio" name="payment_method" value="cash" class="sr-only peer" {{ old('payment_method') === 'cash' ? 'checked' : '' }}>
                            <div class="border-2 border-gray-200 dark:border-gray-700 rounded-lg p-4 transition-all duration-200 
                                        peer-checked:border-blue-500 peer-checked:bg-blue-50 dark:peer-checked:bg-blue-900/20 
                                        hover:border-gray-300 dark:hover:border-gray-600">
                                <div class="flex items-center space-x-3">
                                    <flux:icon.banknotes class="h-6 w-6 text-green-600" />
                                    <div>
                                        <div class="font-medium text-gray-900 dark:text-white">Cash</div>
                                        <div class="text-sm text-gray-600 dark:text-gray-400">Pay at the spa</div>
                                    </div>
                                </div>
                            </div>
                        </label>

                        <label class="relative cursor-pointer">
                            <input type="radio" name="payment_method" value="mpesa" class="sr-only peer" {{ old('payment_method') === 'mpesa' ? 'checked' : '' }}>
                            <div class="border-2 border-gray-200 dark:border-gray-700 rounded-lg p-4 transition-all duration-200 
                                        peer-checked:border-blue-500 peer-checked:bg-blue-50 dark:peer-checked:bg-blue-900/20 
                                        hover:border-gray-300 dark:hover:border-gray-600">
                                <div class="flex items-center space-x-3">
                                    <flux:icon.device-phone-mobile class="h-6 w-6 text-green-600" />
                                    <div>
                                        <div class="font-medium text-gray-900 dark:text-white">M-Pesa</div>
                                        <div class="text-sm text-gray-600 dark:text-gray-400">Mobile payment</div>
                                    </div>
                                </div>
                            </div>
                        </label>

                        <label class="relative cursor-pointer">
                            <input type="radio" name="payment_method" value="card" class="sr-only peer" {{ old('payment_method') === 'card' ? 'checked' : '' }}>
                            <div class="border-2 border-gray-200 dark:border-gray-700 rounded-lg p-4 transition-all duration-200 
                                        peer-checked:border-blue-500 peer-checked:bg-blue-50 dark:peer-checked:bg-blue-900/20 
                                        hover:border-gray-300 dark:hover:border-gray-600">
                                <div class="flex items-center space-x-3">
                                    <flux:icon.credit-card class="h-6 w-6 text-blue-600" />
                                    <div>
                                        <div class="font-medium text-gray-900 dark:text-white">Card</div>
                                        <div class="text-sm text-gray-600 dark:text-gray-400">Credit/Debit card</div>
                                    </div>
                                </div>
                            </div>
                        </label>
                    </div>
                    <flux:error name="payment_method" />
                </flux:field>

                <!-- Form Actions -->
                <div class="flex justify-between pt-6 border-t border-gray-200 dark:border-gray-700">
                    <flux:button variant="ghost" href="{{ route('branch.bookings.select-datetime') }}" wire:navigate>
                        <flux:icon.arrow-left class="mr-2 h-4 w-4" />
                        Back to Date & Time
                    </flux:button>
                    <flux:button type="submit" variant="primary">
                        <flux:icon.check class="mr-2 h-4 w-4" />
                        Create Booking
                    </flux:button>
                </div>
            </form>
        </flux:card>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Add visual feedback for payment method selection
            const paymentInputs = document.querySelectorAll('input[name="payment_method"]');
            paymentInputs.forEach(input => {
                input.addEventListener('change', function() {
                    // Remove previous selections
                    paymentInputs.forEach(otherInput => {
                        const card = otherInput.closest('label').querySelector('div');
                        card.classList.remove('ring-2', 'ring-blue-500');
                    });
                    
                    // Highlight selected payment method
                    const selectedCard = this.closest('label').querySelector('div');
                    selectedCard.classList.add('ring-2', 'ring-blue-500');
                });
            });

            // Auto-format phone number (basic Kenya format)
            const phoneInput = document.getElementById('phone');
            if (phoneInput) {
                phoneInput.addEventListener('input', function() {
                    let value = this.value.replace(/\D/g, ''); // Remove non-digits
                    
                    // Format for Kenya numbers
                    if (value.startsWith('254')) {
                        // International format
                        if (value.length <= 12) {
                            this.value = value.replace(/(\d{3})(\d{3})(\d{3})(\d{3})/, '+$1 $2 $3 $4');
                        }
                    } else if (value.startsWith('0')) {
                        // Local format
                        if (value.length <= 10) {
                            this.value = value.replace(/(\d{4})(\d{3})(\d{3})/, '$1 $2 $3');
                        }
                    } else if (value.startsWith('7') || value.startsWith('1')) {
                        // Add leading 0 for local numbers
                        value = '0' + value;
                        if (value.length <= 10) {
                            this.value = value.replace(/(\d{4})(\d{3})(\d{3})/, '$1 $2 $3');
                        }
                    }
                });
            }

            // Form validation
            const form = document.querySelector('form');
            if (form) {
                form.addEventListener('submit', function(e) {
                    const requiredFields = ['first_name', 'last_name', 'email', 'phone', 'payment_method'];
                    let isValid = true;

                    requiredFields.forEach(fieldName => {
                        const field = document.querySelector(`[name="${fieldName}"]`);
                        if (field && !field.value.trim()) {
                            isValid = false;
                            field.focus();
                            return false;
                        }
                    });

                    if (!isValid) {
                        e.preventDefault();
                        alert('Please fill in all required fields.');
                    }
                });
            }
        });
    </script>
    @endpush
</x-layouts.app>
