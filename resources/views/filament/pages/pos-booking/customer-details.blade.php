<!-- Customer Details Step -->
<div class="text-center mb-8">
    <div class="w-20 h-20 bg-gradient-to-r from-emerald-500 to-teal-600 rounded-2xl flex items-center justify-center mx-auto mb-4">
        <x-heroicon-o-user-plus class="w-10 h-10 text-white" />
    </div>
    <h4 class="text-3xl font-bold text-gray-900 dark:text-white mb-3">Customer Information</h4>
    <p class="text-lg text-emerald-600 dark:text-emerald-400 font-medium max-w-2xl mx-auto">
        Please provide customer details to complete the booking
    </p>
</div>

<!-- Customer Type Selection -->
<div class="mb-8">
    <h5 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center">
        <x-heroicon-o-identification class="w-5 h-5 mr-2 text-emerald-600" />
        Customer Type
    </h5>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <button wire:click="selectCustomerType('existing')" 
                class="group p-6 border-2 rounded-xl transition-all duration-200 focus:outline-none focus:ring-4 focus:ring-emerald-300
                {{ $selectedCustomerType === 'existing' ? 'border-emerald-500 bg-emerald-50 dark:bg-emerald-900/20 text-emerald-700 dark:text-emerald-300' : 'border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:border-emerald-400' }}">
            <div class="text-center">
                <div class="w-16 h-16 mx-auto mb-4 rounded-2xl flex items-center justify-center
                    {{ $selectedCustomerType === 'existing' ? 'bg-emerald-100 dark:bg-emerald-900/40' : 'bg-gray-100 dark:bg-gray-800' }}">
                    <x-heroicon-o-user-circle class="w-8 h-8 {{ $selectedCustomerType === 'existing' ? 'text-emerald-600' : 'text-gray-500' }}" />
                </div>
                <h6 class="font-bold text-lg mb-2">Existing Customer</h6>
                <p class="text-sm opacity-75">Search and select from customer database</p>
                @if($selectedCustomerType === 'existing')
                    <div class="mt-3 inline-flex items-center space-x-1 bg-emerald-100 dark:bg-emerald-900/30 px-3 py-1 rounded-full">
                        <x-heroicon-o-check class="w-4 h-4 text-emerald-600" />
                        <span class="text-xs font-medium text-emerald-700 dark:text-emerald-300">Selected</span>
                    </div>
                @endif
            </div>
        </button>
        
        <button wire:click="selectCustomerType('new')" 
                class="group p-6 border-2 rounded-xl transition-all duration-200 focus:outline-none focus:ring-4 focus:ring-emerald-300
                {{ $selectedCustomerType === 'new' ? 'border-emerald-500 bg-emerald-50 dark:bg-emerald-900/20 text-emerald-700 dark:text-emerald-300' : 'border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:border-emerald-400' }}">
            <div class="text-center">
                <div class="w-16 h-16 mx-auto mb-4 rounded-2xl flex items-center justify-center
                    {{ $selectedCustomerType === 'new' ? 'bg-emerald-100 dark:bg-emerald-900/40' : 'bg-gray-100 dark:bg-gray-800' }}">
                    <x-heroicon-o-user-plus class="w-8 h-8 {{ $selectedCustomerType === 'new' ? 'text-emerald-600' : 'text-gray-500' }}" />
                </div>
                <h6 class="font-bold text-lg mb-2">New Customer</h6>
                <p class="text-sm opacity-75">Add new customer to the system</p>
                @if($selectedCustomerType === 'new')
                    <div class="mt-3 inline-flex items-center space-x-1 bg-emerald-100 dark:bg-emerald-900/30 px-3 py-1 rounded-full">
                        <x-heroicon-o-check class="w-4 h-4 text-emerald-600" />
                        <span class="text-xs font-medium text-emerald-700 dark:text-emerald-300">Selected</span>
                    </div>
                @endif
            </div>
        </button>
    </div>
</div>

<!-- Customer Details Form/Search -->
@if($selectedCustomerType === 'existing')
    <!-- Existing Customer Search -->
    <div class="space-y-6">
        <div>
            <label class="block text-lg font-bold text-gray-900 dark:text-white mb-3 flex items-center">
                <x-heroicon-o-magnifying-glass class="w-5 h-5 mr-2 text-emerald-600" />
                Search Customer
            </label>
            <div class="relative">
                <input wire:model.live="customerSearch" 
                       type="text" 
                       placeholder="Enter name, phone number, or email address..."
                       class="w-full px-6 py-4 pr-12 border-2 border-emerald-200 dark:border-emerald-600 rounded-xl focus:ring-4 focus:ring-emerald-300 focus:border-emerald-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-lg placeholder-gray-400 dark:placeholder-gray-500">
                <x-heroicon-o-magnifying-glass class="absolute right-4 top-1/2 transform -translate-y-1/2 w-5 h-5 text-gray-400" />
            </div>
        </div>
        
        @if(!empty($customerSearchResults))
            <div class="bg-white dark:bg-gray-700 rounded-xl border border-gray-200 dark:border-gray-600 shadow-lg max-h-80 overflow-y-auto">
                <div class="p-4 bg-emerald-50 dark:bg-emerald-900/20 border-b border-emerald-200 dark:border-emerald-700">
                    <h6 class="font-semibold text-emerald-800 dark:text-emerald-200 flex items-center">
                        <x-heroicon-o-users class="w-4 h-4 mr-2" />
                        Search Results ({{ count($customerSearchResults) }})
                    </h6>
                </div>
                @foreach($customerSearchResults as $customer)
                    <button wire:click="selectExistingCustomer({{ $customer->id }})" 
                            class="w-full p-4 text-left hover:bg-emerald-50 dark:hover:bg-emerald-900/20 border-b border-gray-100 dark:border-gray-700 last:border-b-0 transition-colors group">
                        <div class="flex items-center space-x-4">
                            <div class="w-12 h-12 bg-gradient-to-r from-emerald-500 to-teal-600 rounded-full flex items-center justify-center text-white font-bold">
                                {{ substr($customer->name, 0, 2) }}
                            </div>
                            <div class="flex-1">
                                <p class="font-bold text-gray-900 dark:text-white group-hover:text-emerald-700 dark:group-hover:text-emerald-300">
                                    {{ $customer->name }}
                                </p>
                                <div class="flex items-center space-x-4 text-sm text-gray-600 dark:text-gray-400">
                                    @if($customer->phone)
                                        <span class="flex items-center">
                                            <x-heroicon-o-phone class="w-3 h-3 mr-1" />
                                            {{ $customer->phone }}
                                        </span>
                                    @endif
                                    @if($customer->email)
                                        <span class="flex items-center">
                                            <x-heroicon-o-envelope class="w-3 h-3 mr-1" />
                                            {{ $customer->email }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                            <x-heroicon-o-arrow-right class="w-5 h-5 text-gray-400 group-hover:text-emerald-500" />
                        </div>
                    </button>
                @endforeach
            </div>
        @elseif(!empty($customerSearch))
            <div class="text-center py-8 bg-gray-50 dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-600">
                <x-heroicon-o-magnifying-glass class="w-12 h-12 mx-auto mb-4 text-gray-400" />
                <h6 class="font-medium text-gray-900 dark:text-white mb-2">No customers found</h6>
                <p class="text-gray-600 dark:text-gray-400 mb-4">
                    No customers match your search criteria
                </p>
                <button wire:click="selectCustomerType('new')" 
                        class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-medium rounded-lg transition-colors">
                    Add as New Customer
                </button>
            </div>
        @endif
    </div>
    
@elseif($selectedCustomerType === 'new')
    <!-- New Customer Form -->
    <div class="space-y-6">
        <div>
            <h5 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center">
                <x-heroicon-o-document-text class="w-5 h-5 mr-2 text-emerald-600" />
                Customer Details
            </h5>
        </div>
        
        <div class="bg-white dark:bg-gray-700 rounded-xl border border-gray-200 dark:border-gray-600 p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- First Name -->
                <div>
                    <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">
                        First Name <span class="text-red-500">*</span>
                    </label>
                    <input wire:model="customerData.first_name" 
                           type="text" 
                           placeholder="Enter first name"
                           class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 bg-white dark:bg-gray-800 text-gray-900 dark:text-white">
                </div>
                
                <!-- Last Name -->
                <div>
                    <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">
                        Last Name <span class="text-red-500">*</span>
                    </label>
                    <input wire:model="customerData.last_name" 
                           type="text" 
                           placeholder="Enter last name"
                           class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 bg-white dark:bg-gray-800 text-gray-900 dark:text-white">
                </div>
                
                <!-- Phone Number -->
                <div>
                    <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">
                        Phone Number <span class="text-red-500">*</span>
                    </label>
                    <input wire:model="customerData.phone" 
                           type="tel" 
                           placeholder="Enter phone number"
                           class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 bg-white dark:bg-gray-800 text-gray-900 dark:text-white">
                </div>
                
                <!-- Email Address -->
                <div>
                    <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">
                        Email Address
                    </label>
                    <input wire:model="customerData.email" 
                           type="email" 
                           placeholder="Enter email address"
                           class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 bg-white dark:bg-gray-800 text-gray-900 dark:text-white">
                </div>
                
                <!-- Gender -->
                <div>
                    <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">
                        Gender
                    </label>
                    <select wire:model="customerData.gender" 
                            class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 bg-white dark:bg-gray-800 text-gray-900 dark:text-white">
                        <option value="">Select gender</option>
                        <option value="male">Male</option>
                        <option value="female">Female</option>
                        <option value="other">Other</option>
                        <option value="prefer_not_to_say">Prefer not to say</option>
                    </select>
                </div>
                
                <!-- Date of Birth -->
                <div>
                    <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">
                        Date of Birth
                    </label>
                    <input wire:model="customerData.date_of_birth" 
                           type="date" 
                           max="{{ date('Y-m-d') }}"
                           class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 bg-white dark:bg-gray-800 text-gray-900 dark:text-white">
                </div>
            </div>
            
            <!-- Special Notes -->
            <div class="mt-6">
                <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">
                    Allergies & Special Notes
                </label>
                <textarea wire:model="customerData.notes" 
                          rows="4" 
                          placeholder="Please list any allergies, medical conditions, or special requirements (or write 'None')"
                          class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 bg-white dark:bg-gray-800 text-gray-900 dark:text-white resize-none"></textarea>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                    This information helps our staff provide the best possible service
                </p>
            </div>
        </div>
    </div>
@endif

<!-- Selected Customer Display -->
@if(isset($selectedCustomer) && !empty($selectedCustomer))
    <div class="mt-8 bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 rounded-xl p-6 border border-green-200 dark:border-green-700">
        <div class="flex items-center space-x-4">
            <div class="w-16 h-16 bg-gradient-to-r from-green-500 to-emerald-600 rounded-full flex items-center justify-center">
                <x-heroicon-o-check class="w-8 h-8 text-white" />
            </div>
            <div class="flex-1">
                <h6 class="text-lg font-bold text-green-800 dark:text-green-200 mb-1">Customer Selected</h6>
                <div class="space-y-1">
                    <p class="font-semibold text-green-700 dark:text-green-300">
                        {{ $selectedCustomer['name'] }}
                    </p>
                    <div class="flex items-center space-x-4 text-sm text-green-600 dark:text-green-400">
                        @if(!empty($selectedCustomer['phone']))
                            <span class="flex items-center">
                                <x-heroicon-o-phone class="w-3 h-3 mr-1" />
                                {{ $selectedCustomer['phone'] }}
                            </span>
                        @endif
                        @if(!empty($selectedCustomer['email']))
                            <span class="flex items-center">
                                <x-heroicon-o-envelope class="w-3 h-3 mr-1" />
                                {{ $selectedCustomer['email'] }}
                            </span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif

<!-- Privacy & Terms -->
<div class="mt-8 bg-blue-50 dark:bg-blue-900/20 rounded-xl p-4 border border-blue-200 dark:border-blue-700">
    <div class="flex items-start space-x-3">
        <x-heroicon-o-shield-check class="w-5 h-5 text-blue-600 mt-0.5 flex-shrink-0" />
        <div class="text-sm">
            <p class="font-medium text-blue-800 dark:text-blue-200 mb-1">Privacy & Data Protection:</p>
            <ul class="text-blue-700 dark:text-blue-300 space-y-1">
                <li>• All customer information is securely stored and encrypted</li>
                <li>• We use this data only for appointment management and service delivery</li>
                <li>• You can request data deletion at any time</li>
                <li>• We comply with all applicable data protection regulations</li>
            </ul>
        </div>
    </div>
</div>