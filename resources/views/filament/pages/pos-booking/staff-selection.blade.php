<!-- Staff Selection Step -->
<div class="text-center mb-8">
    <div class="w-20 h-20 bg-gradient-to-r from-blue-500 to-purple-600 rounded-2xl flex items-center justify-center mx-auto mb-4">
        <x-heroicon-o-user class="w-10 h-10 text-white" />
    </div>
    <h4 class="text-3xl font-bold text-gray-900 dark:text-white mb-3">Choose Your Therapist</h4>
    <p class="text-lg text-blue-600 dark:text-blue-400 font-medium max-w-2xl mx-auto">
        Select your preferred staff member or we'll assign the best available therapist for your service
    </p>
</div>

<!-- Staff Grid with No Preference Option -->
<div class="space-y-6">
    <!-- No Preference Option -->
    <div class="bg-gradient-to-r from-gray-50 to-blue-50 dark:from-gray-800 dark:to-blue-900/20 rounded-2xl p-6 border-2 border-dashed border-gray-300 dark:border-gray-600">
        <button wire:click="selectServiceStaff('')" 
                class="w-full group relative bg-white dark:bg-gray-700 rounded-xl border-2 border-gray-200 dark:border-gray-600 p-6 hover:border-blue-500 hover:bg-blue-50 dark:hover:bg-blue-900/20 hover:shadow-lg transition-all duration-200 focus:outline-none focus:ring-4 focus:ring-blue-300">
            <div class="flex items-center space-x-4">
                <div class="w-16 h-16 bg-gradient-to-r from-gray-400 to-gray-500 rounded-full flex items-center justify-center">
                    <x-heroicon-o-users class="w-8 h-8 text-white" />
                </div>
                <div class="text-left flex-1">
                    <h5 class="text-xl font-bold text-gray-900 dark:text-white mb-2">No Preference</h5>
                    <p class="text-gray-600 dark:text-gray-400 mb-1">We'll assign the best available therapist</p>
                    <p class="text-sm text-blue-600 dark:text-blue-400 font-medium">✨ Recommended for fastest booking</p>
                </div>
                <div class="opacity-0 group-hover:opacity-100 transition-opacity">
                    <x-heroicon-o-arrow-right class="w-6 h-6 text-blue-500" />
                </div>
            </div>
        </button>
    </div>

    <!-- Staff Members -->
    @if($this->getStaff()->count() > 0)
        <div>
            <h5 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                <x-heroicon-o-user-group class="w-5 h-5 mr-2 text-blue-600" />
                Available Staff Members
            </h5>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach($this->getStaff() as $staff)
                    <button wire:click="selectServiceStaff({{ $staff->id }})" 
                            class="group relative bg-white dark:bg-gray-700 rounded-xl border-2 border-gray-200 dark:border-gray-600 p-6 hover:border-blue-500 hover:bg-blue-50 dark:hover:bg-blue-900/20 hover:shadow-lg transition-all duration-200 focus:outline-none focus:ring-4 focus:ring-blue-300 text-left">
                        <div class="flex items-center space-x-4">
                            <div class="relative">
                                <div class="w-14 h-14 bg-gradient-to-r from-blue-500 to-purple-600 rounded-full flex items-center justify-center text-white font-bold text-lg">
                                    {{ substr($staff->name, 0, 2) }}
                                </div>
                                <div class="absolute -bottom-1 -right-1 w-5 h-5 bg-green-500 rounded-full border-2 border-white"></div>
                            </div>
                            <div class="flex-1">
                                <h5 class="text-lg font-bold text-gray-900 dark:text-white mb-1">{{ $staff->name }}</h5>
                                <p class="text-blue-600 dark:text-blue-400 font-medium text-sm mb-1">
                                    {{ $staff->specialization ?? 'Professional Therapist' }}
                                </p>
                                @if($staff->experience_years)
                                    <p class="text-gray-500 dark:text-gray-400 text-xs">
                                        {{ $staff->experience_years }}+ years experience
                                    </p>
                                @endif
                            </div>
                            <div class="opacity-0 group-hover:opacity-100 transition-opacity">
                                <x-heroicon-o-arrow-right class="w-5 h-5 text-blue-500" />
                            </div>
                        </div>
                        
                        <!-- Staff Rating/Badge (if available) -->
                        @if($staff->rating ?? false)
                            <div class="absolute top-3 right-3 flex items-center space-x-1 bg-yellow-100 dark:bg-yellow-900/30 px-2 py-1 rounded-lg">
                                <x-heroicon-o-star class="w-3 h-3 text-yellow-500" />
                                <span class="text-xs font-medium text-yellow-700 dark:text-yellow-300">{{ $staff->rating }}</span>
                            </div>
                        @endif
                    </button>
                @endforeach
            </div>
        </div>
    @else
        <div class="text-center py-12">
            <div class="w-16 h-16 bg-gray-100 dark:bg-gray-800 rounded-full flex items-center justify-center mx-auto mb-4">
                <x-heroicon-o-user-group class="w-8 h-8 text-gray-400" />
            </div>
            <h5 class="text-lg font-medium text-gray-900 dark:text-white mb-2">No Staff Available</h5>
            <p class="text-gray-600 dark:text-gray-400 mb-4">
                No staff members are currently available for this service.
            </p>
            <button wire:click="selectServiceStaff('')" 
                    class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-xl transition-colors">
                Continue Anyway
            </button>
        </div>
    @endif
</div>

<!-- Quick Tips -->
<div class="mt-8 bg-blue-50 dark:bg-blue-900/20 rounded-xl p-4 border border-blue-200 dark:border-blue-700">
    <div class="flex items-start space-x-3">
        <x-heroicon-o-light-bulb class="w-5 h-5 text-blue-600 mt-0.5 flex-shrink-0" />
        <div class="text-sm">
            <p class="font-medium text-blue-800 dark:text-blue-200 mb-1">Pro Tips:</p>
            <ul class="text-blue-700 dark:text-blue-300 space-y-1">
                <li>• Selecting "No Preference" allows for the earliest available slot</li>
                <li>• Each therapist brings unique specializations and techniques</li>
                <li>• Click any option to automatically continue to the next step</li>
            </ul>
        </div>
    </div>
</div>