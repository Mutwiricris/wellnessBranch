<x-layouts.app :title="__('Select Staff')">
    <div class="flex h-full w-full flex-1 flex-col gap-8 p-6">
        <!-- Page Header -->
        <div class="flex items-center justify-between">
            <div class="space-y-1">
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Select Staff Member</h1>
                <p class="text-sm text-gray-600 dark:text-gray-400">Choose a staff member for {{ $service->name }}</p>
            </div>
            <div class="flex items-center gap-3">
                <flux:button variant="ghost" href="{{ route('branch.bookings.create') }}" wire:navigate>
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
                    <div class="flex items-center justify-center w-8 h-8 bg-blue-600 text-white rounded-full text-sm font-medium">
                        2
                    </div>
                    <span class="text-sm font-medium text-blue-600">Select Staff</span>
                </div>
                <div class="flex-1 h-px bg-gray-200 dark:bg-gray-700 mx-4"></div>
                <div class="flex items-center space-x-4">
                    <div class="flex items-center justify-center w-8 h-8 bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-400 rounded-full text-sm font-medium">
                        3
                    </div>
                    <span class="text-sm text-gray-600 dark:text-gray-400">Date & Time</span>
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

        <!-- Selected Service Info -->
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

        <!-- Staff Selection -->
        <flux:card class="p-6">
            <div class="mb-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Choose Staff Member</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">Select a preferred staff member or continue without preference</p>
            </div>

            <form method="POST" action="{{ route('branch.bookings.select-staff.post') }}">
                @csrf
                
                @if($staff->count() > 0)
                    <div class="space-y-4 mb-6">
                        <!-- No Preference Option -->
                        <label class="relative cursor-pointer">
                            <input type="radio" name="staff_id" value="" class="sr-only peer" checked>
                            <div class="border-2 border-gray-200 dark:border-gray-700 rounded-lg p-4 transition-all duration-200 
                                        peer-checked:border-blue-500 peer-checked:bg-blue-50 dark:peer-checked:bg-blue-900/20 
                                        hover:border-gray-300 dark:hover:border-gray-600">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center space-x-4">
                                        <div class="w-12 h-12 bg-gray-100 dark:bg-gray-800 rounded-full flex items-center justify-center">
                                            <flux:icon.users class="h-6 w-6 text-gray-400" />
                                        </div>
                                        <div>
                                            <h5 class="font-medium text-gray-900 dark:text-white">No Preference</h5>
                                            <p class="text-sm text-gray-600 dark:text-gray-400">We'll assign the best available therapist</p>
                                        </div>
                                    </div>
                                    <div class="w-5 h-5 border-2 border-gray-300 dark:border-gray-600 rounded-full 
                                                peer-checked:border-blue-500 peer-checked:bg-blue-500 
                                                flex items-center justify-center">
                                        <div class="w-2 h-2 bg-white rounded-full opacity-0 peer-checked:opacity-100"></div>
                                    </div>
                                </div>
                            </div>
                        </label>

                        <!-- Staff Members -->
                        @foreach($staff as $member)
                            <label class="relative cursor-pointer">
                                <input type="radio" name="staff_id" value="{{ $member->id }}" class="sr-only peer">
                                <div class="border-2 border-gray-200 dark:border-gray-700 rounded-lg p-4 transition-all duration-200 
                                            peer-checked:border-blue-500 peer-checked:bg-blue-50 dark:peer-checked:bg-blue-900/20 
                                            hover:border-gray-300 dark:hover:border-gray-600">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center space-x-4">
                                            <div class="w-12 h-12 bg-gradient-to-br from-blue-400 to-purple-500 rounded-full flex items-center justify-center text-white font-medium">
                                                {{ substr($member->name, 0, 2) }}
                                            </div>
                                            <div>
                                                <h5 class="font-medium text-gray-900 dark:text-white">{{ $member->name }}</h5>
                                                <p class="text-sm text-gray-600 dark:text-gray-400">{{ $member->specialization ?? 'Therapist' }}</p>
                                                @if($member->services->first())
                                                    <p class="text-xs text-blue-600 dark:text-blue-400 mt-1">
                                                        Rate: KES {{ number_format($member->services->first()->pivot->rate ?? $service->price, 2) }}
                                                    </p>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="w-5 h-5 border-2 border-gray-300 dark:border-gray-600 rounded-full 
                                                    peer-checked:border-blue-500 peer-checked:bg-blue-500 
                                                    flex items-center justify-center">
                                            <div class="w-2 h-2 bg-white rounded-full opacity-0 peer-checked:opacity-100"></div>
                                        </div>
                                    </div>
                                </div>
                            </label>
                        @endforeach
                    </div>

                    <div class="flex justify-between">
                        <flux:button variant="ghost" href="{{ route('branch.bookings.create') }}" wire:navigate>
                            <flux:icon.arrow-left class="mr-2 h-4 w-4" />
                            Back to Services
                        </flux:button>
                        <flux:button type="submit" variant="primary">
                            Continue to Date & Time
                            <flux:icon.arrow-right class="ml-2 h-4 w-4" />
                        </flux:button>
                    </div>
                @else
                    <div class="text-center py-12">
                        <div class="mx-auto h-24 w-24 rounded-full bg-gray-100 dark:bg-gray-800 flex items-center justify-center mb-4">
                            <flux:icon.user-group class="h-12 w-12 text-gray-400" />
                        </div>
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">No Staff Available</h3>
                        <p class="text-gray-600 dark:text-gray-400 mb-4">No staff members are available for this service at your branch.</p>
                        <div class="flex justify-center gap-4">
                            <flux:button variant="ghost" href="{{ route('branch.bookings.create') }}" wire:navigate>
                                Back to Services
                            </flux:button>
                            <form method="POST" action="{{ route('branch.bookings.select-staff.post') }}" class="inline">
                                @csrf
                                <input type="hidden" name="staff_id" value="">
                                <flux:button type="submit" variant="primary">
                                    Continue Anyway
                                </flux:button>
                            </form>
                        </div>
                    </div>
                @endif
            </form>
        </flux:card>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Add visual feedback for staff selection
            const staffInputs = document.querySelectorAll('input[name="staff_id"]');
            staffInputs.forEach(input => {
                input.addEventListener('change', function() {
                    // Remove previous selections
                    staffInputs.forEach(otherInput => {
                        const card = otherInput.closest('label').querySelector('div');
                        card.classList.remove('ring-2', 'ring-blue-500');
                    });
                    
                    // Highlight selected staff
                    const selectedCard = this.closest('label').querySelector('div');
                    selectedCard.classList.add('ring-2', 'ring-blue-500');
                });
            });
        });
    </script>
    @endpush
</x-layouts.app>
