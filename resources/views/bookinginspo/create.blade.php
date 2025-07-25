<x-layouts.app :title="__('Create New Booking')">
    <div class="flex h-full w-full flex-1 flex-col gap-8 p-6">
        <!-- Page Header -->
        <div class="flex items-center justify-between">
            <div class="space-y-1">
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Create New Booking</h1>
                <p class="text-sm text-gray-600 dark:text-gray-400">Create a new booking for {{ $branch->name }}</p>
            </div>
            <div class="flex items-center gap-3">
                <flux:button variant="ghost" href="{{ route('branch.bookings.index') }}" wire:navigate>
                    <flux:icon.arrow-left class="mr-2 h-4 w-4" />
                    Back to Bookings
                </flux:button>
            </div>
        </div>

        <!-- Progress Steps -->
        <div class="bg-white dark:bg-zinc-900 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center justify-between mb-8">
                <div class="flex items-center space-x-4">
                    <div class="flex items-center justify-center w-8 h-8 bg-blue-600 text-white rounded-full text-sm font-medium">
                        1
                    </div>
                    <span class="text-sm font-medium text-blue-600">Select Service</span>
                </div>
                <div class="flex-1 h-px bg-gray-200 dark:bg-gray-700 mx-4"></div>
                <div class="flex items-center space-x-4">
                    <div class="flex items-center justify-center w-8 h-8 bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-400 rounded-full text-sm font-medium">
                        2
                    </div>
                    <span class="text-sm text-gray-600 dark:text-gray-400">Select Staff</span>
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

        <!-- Service Selection -->
        <flux:card class="p-6">
            <div class="mb-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Select a Service</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">Choose the service for this booking</p>
            </div>

            <form method="POST" action="{{ route('branch.bookings.select-service') }}">
                @csrf
                
                @if($services->count() > 0)
                    <div class="space-y-6">
                        @foreach($services as $categoryName => $categoryServices)
                            <div>
                                <h4 class="text-md font-medium text-gray-900 dark:text-white mb-3">{{ $categoryName ?: 'Other Services' }}</h4>
                                <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                                    @foreach($categoryServices as $service)
                                        <label class="relative cursor-pointer">
                                            <input type="radio" name="service_id" value="{{ $service->id }}" 
                                                   class="sr-only peer" required>
                                            <div class="border-2 border-gray-200 dark:border-gray-700 rounded-lg p-4 transition-all duration-200 
                                                        peer-checked:border-blue-500 peer-checked:bg-blue-50 dark:peer-checked:bg-blue-900/20 
                                                        hover:border-gray-300 dark:hover:border-gray-600">
                                                <div class="flex items-start justify-between">
                                                    <div class="flex-1">
                                                        <h5 class="font-medium text-gray-900 dark:text-white">{{ $service->name }}</h5>
                                                        @if($service->description)
                                                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ Str::limit($service->description, 100) }}</p>
                                                        @endif
                                                        <div class="flex items-center gap-4 mt-2">
                                                            <span class="text-sm text-gray-500 dark:text-gray-400">
                                                                <flux:icon.clock class="inline h-4 w-4 mr-1" />
                                                                {{ $service->duration ?? 30 }}min
                                                            </span>
                                                            <span class="text-lg font-semibold text-blue-600 dark:text-blue-400">
                                                                KES {{ number_format($service->price, 2) }}
                                                            </span>
                                                        </div>
                                                    </div>
                                                    <div class="ml-4">
                                                        <div class="w-5 h-5 border-2 border-gray-300 dark:border-gray-600 rounded-full 
                                                                    peer-checked:border-blue-500 peer-checked:bg-blue-500 
                                                                    flex items-center justify-center">
                                                            <div class="w-2 h-2 bg-white rounded-full opacity-0 peer-checked:opacity-100"></div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="flex justify-end mt-8">
                        <flux:button type="submit" variant="primary">
                            Continue to Staff Selection
                            <flux:icon.arrow-right class="ml-2 h-4 w-4" />
                        </flux:button>
                    </div>
                @else
                    <div class="text-center py-12">
                        <div class="mx-auto h-24 w-24 rounded-full bg-gray-100 dark:bg-gray-800 flex items-center justify-center mb-4">
                            <flux:icon.exclamation-triangle class="h-12 w-12 text-gray-400" />
                        </div>
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">No Services Available</h3>
                        <p class="text-gray-600 dark:text-gray-400 mb-4">There are no services configured for this branch.</p>
                        <flux:button href="{{ route('branch.bookings.index') }}" wire:navigate>
                            Back to Bookings
                        </flux:button>
                    </div>
                @endif
            </form>
        </flux:card>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Add visual feedback for service selection
            const serviceInputs = document.querySelectorAll('input[name="service_id"]');
            serviceInputs.forEach(input => {
                input.addEventListener('change', function() {
                    // Remove previous selections
                    serviceInputs.forEach(otherInput => {
                        const card = otherInput.closest('label').querySelector('div');
                        card.classList.remove('ring-2', 'ring-blue-500');
                    });
                    
                    // Highlight selected service
                    const selectedCard = this.closest('label').querySelector('div');
                    selectedCard.classList.add('ring-2', 'ring-blue-500');
                });
            });
        });
    </script>
    @endpush
</x-layouts.app>
