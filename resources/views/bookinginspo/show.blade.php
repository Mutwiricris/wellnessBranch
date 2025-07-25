<x-layouts.app :title="'Booking Details - ' . $booking->booking_reference">
    <div class="flex h-full w-full flex-1 flex-col gap-6">
        <!-- Page Header -->
        <div class="flex items-center justify-between">
            <div>
                <flux:button href="{{ route('branch.bookings.index') }}" variant="ghost" wire:navigate>
                    <flux:icon.chevron-left class="mr-2 h-4 w-4" />
                    Back to Bookings
                </flux:button>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white mt-2">{{ $booking->booking_reference }}</h1>
                <p class="text-sm text-gray-600 dark:text-gray-400">Booking Details</p>
            </div>
            
            <div class="flex space-x-2">
                @php
                    $statusColors = [
                        'pending' => 'yellow',
                        'confirmed' => 'blue', 
                        'in_progress' => 'purple',
                        'completed' => 'green',
                        'cancelled' => 'red',
                        'no_show' => 'gray'
                    ];
                @endphp
                <flux:badge size="lg" :color="$statusColors[$booking->status] ?? 'gray'">
                    {{ ucfirst(str_replace('_', ' ', $booking->status)) }}
                </flux:badge>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-3">
            <!-- Main Booking Information -->
            <div class="lg:col-span-2">
                <flux:card class="p-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Booking Information</h2>
                    
                    <div class="grid gap-6 md:grid-cols-2">
                        <!-- Client Information -->
                        <div>
                            <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Client Details</h3>
                            <div class="space-y-2">
                                <div class="flex items-center">
                                    <flux:icon.user class="h-4 w-4 text-gray-400 mr-2" />
                                    <span class="text-sm text-gray-900 dark:text-white font-medium">{{ $booking->client->full_name }}</span>
                                </div>
                                <div class="flex items-center">
                                    <flux:icon.envelope class="h-4 w-4 text-gray-400 mr-2" />
                                    <span class="text-sm text-gray-600 dark:text-gray-400">{{ $booking->client->email }}</span>
                                </div>
                                <div class="flex items-center">
                                    <flux:icon.phone class="h-4 w-4 text-gray-400 mr-2" />
                                    <span class="text-sm text-gray-600 dark:text-gray-400">{{ $booking->client->formatted_phone }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Service Information -->
                        <div>
                            <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Service Details</h3>
                            <div class="space-y-2">
                                <div class="flex items-center">
                                    <flux:icon.sparkles class="h-4 w-4 text-gray-400 mr-2" />
                                    <span class="text-sm text-gray-900 dark:text-white font-medium">{{ $booking->service->name }}</span>
                                </div>
                                <div class="flex items-center">
                                    <flux:icon.clock class="h-4 w-4 text-gray-400 mr-2" />
                                    <span class="text-sm text-gray-600 dark:text-gray-400">{{ $booking->service->duration_minutes }} minutes</span>
                                </div>
                                <div class="flex items-center">
                                    <flux:icon.currency-dollar class="h-4 w-4 text-gray-400 mr-2" />
                                    <span class="text-sm text-gray-600 dark:text-gray-400">KES {{ number_format($booking->total_amount, 2) }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Appointment Details -->
                        <div>
                            <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Appointment Details</h3>
                            <div class="space-y-2">
                                <div class="flex items-center">
                                    <flux:icon.calendar class="h-4 w-4 text-gray-400 mr-2" />
                                    <span class="text-sm text-gray-900 dark:text-white font-medium">{{ $booking->appointment_date->format('l, F j, Y') }}</span>
                                </div>
                                <div class="flex items-center">
                                    <flux:icon.clock class="h-4 w-4 text-gray-400 mr-2" />
                                    <span class="text-sm text-gray-600 dark:text-gray-400">{{ $booking->formatted_time_slot }}</span>
                                </div>
                                <div class="flex items-center">
                                    <flux:icon.map-pin class="h-4 w-4 text-gray-400 mr-2" />
                                    <span class="text-sm text-gray-600 dark:text-gray-400">{{ $booking->branch->name }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Staff Assignment -->
                        <div>
                            <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Staff Assignment</h3>
                            @if($booking->staff)
                                <div class="space-y-2">
                                    <div class="flex items-center">
                                        <flux:icon.user-circle class="h-4 w-4 text-gray-400 mr-2" />
                                        <span class="text-sm text-gray-900 dark:text-white font-medium">{{ $booking->staff->name }}</span>
                                    </div>
                                    <div class="flex items-center">
                                        <flux:icon.envelope class="h-4 w-4 text-gray-400 mr-2" />
                                        <span class="text-sm text-gray-600 dark:text-gray-400">{{ $booking->staff->email }}</span>
                                    </div>
                                </div>
                            @else
                                <div class="flex items-center justify-between">
                                    <flux:badge variant="outline" color="gray">Unassigned</flux:badge>
                                    <flux:button size="sm" onclick="openStaffModal()">
                                        Assign Staff
                                    </flux:button>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Notes -->
                    @if($booking->notes)
                        <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                            <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Notes</h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400">{{ $booking->notes }}</p>
                        </div>
                    @endif
                </flux:card>

                <!-- Payment Information -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mt-6">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Payment Information</h2>
                        <div class="flex space-x-2">
                            @if($booking->payment_status !== 'completed')
                                <button class="btn btn-outline btn-sm" onclick="openPaymentModal()">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                                    </svg>
                                    Record Payment
                                </button>
                            @elseif($booking->payment)
                                <button class="btn btn-ghost btn-sm" onclick="openPaymentModal()">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                    Edit Payment
                                </button>
                            @endif
                        </div>
                    </div>
                    
                    <!-- Payment Status Progress -->
                    <div class="mb-6">
                        <div class="flex items-center justify-between text-sm mb-2">
                            <span class="text-gray-600 dark:text-gray-400">Payment Progress</span>
                            <span class="font-medium text-gray-900 dark:text-white">
                                {{ $booking->payment_status === 'completed' ? '100%' : '0%' }}
                            </span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2 dark:bg-gray-700">
                            <div class="h-2 rounded-full transition-all duration-300 {{ $booking->payment_status === 'completed' ? 'bg-green-500 w-full' : 'bg-yellow-500 w-0' }}"></div>
                        </div>
                        <div class="flex justify-between mt-2 text-xs text-gray-500">
                            <span>Pending</span>
                            <span>Completed</span>
                        </div>
                    </div>
                    
                    @if($booking->needsPaymentBeforeConfirmation())
                        <div class="mb-4 p-3 bg-yellow-50 border border-yellow-200 rounded-lg dark:bg-yellow-900/20 dark:border-yellow-700">
                            <div class="flex items-center">
                                <flux:icon.exclamation-triangle class="h-5 w-5 text-yellow-600 dark:text-yellow-400 mr-2" />
                                <div>
                                    <p class="text-sm font-medium text-yellow-800 dark:text-yellow-200">Payment Required</p>
                                    <p class="text-sm text-yellow-600 dark:text-yellow-400">This booking cannot be confirmed until payment is completed.</p>
                                </div>
                            </div>
                        </div>
                    @endif
                    
                    @if($booking->needsPaymentBeforeCompletion())
                        <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg dark:bg-red-900/20 dark:border-red-700">
                            <div class="flex items-center">
                                <flux:icon.exclamation-triangle class="h-5 w-5 text-red-600 dark:text-red-400 mr-2" />
                                <div>
                                    <p class="text-sm font-medium text-red-800 dark:text-red-200">Payment Required</p>
                                    <p class="text-sm text-red-600 dark:text-red-400">Service cannot be completed until payment is processed.</p>
                                </div>
                            </div>
                        </div>
                    @endif
                    
                    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                        <div>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Total Amount</p>
                            <p class="text-lg font-semibold text-gray-900 dark:text-white">KES {{ number_format($booking->total_amount, 2) }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Payment Status</p>
                            @php
                                $paymentStatusColors = [
                                    'pending' => 'badge-warning',
                                    'completed' => 'badge-success',
                                    'failed' => 'badge-error',
                                    'refunded' => 'badge-neutral'
                                ];
                            @endphp
                            <div class="badge {{ $paymentStatusColors[$booking->payment_status] ?? 'badge-neutral' }}">
                                {{ ucfirst($booking->payment_status) }}
                            </div>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Payment Method</p>
                            <p class="text-sm text-gray-900 dark:text-white font-medium">
                                @if($booking->payment_method === 'mpesa')
                                    M-Pesa
                                @elseif($booking->payment_method === 'bank_transfer')
                                    Bank Transfer
                                @else
                                    {{ ucfirst($booking->payment_method) }}
                                @endif
                            </p>
                        </div>
                    </div>

                    <!-- Payment Details from Payment Table -->
                    @if($booking->payment)
                        <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                            <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Payment Details</h3>
                            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                                <div>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">Payment Amount</p>
                                    <p class="text-sm text-gray-900 dark:text-white">KES {{ number_format($booking->payment->amount, 2) }}</p>
                                </div>
                                @if($booking->payment->transaction_reference)
                                    <div>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">Transaction Reference</p>
                                        <p class="text-sm text-gray-900 dark:text-white font-mono">{{ $booking->payment->transaction_reference }}</p>
                                    </div>
                                @endif
                                @if($booking->payment->processed_at)
                                    <div>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">Processed At</p>
                                        <p class="text-sm text-gray-900 dark:text-white">{{ $booking->payment->processed_at->format('M j, Y g:i A') }}</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Actions Sidebar -->
            <div>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Quick Actions</h2>
                    
                    <!-- Booking Status Flow -->
                    <div class="mb-6 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                        <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Booking Progress</h3>
                        <div class="space-y-3">
                            <!-- Step 1: Payment -->
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    @if($booking->payment_status === 'completed')
                                        <div class="w-6 h-6 bg-green-500 rounded-full flex items-center justify-center">
                                            <svg class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                            </svg>
                                        </div>
                                    @else
                                        <div class="w-6 h-6 bg-yellow-500 rounded-full flex items-center justify-center">
                                            <span class="text-xs font-medium text-white">1</span>
                                        </div>
                                    @endif
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm font-medium {{ $booking->payment_status === 'completed' ? 'text-green-600 dark:text-green-400' : 'text-yellow-600 dark:text-yellow-400' }}">
                                        Payment {{ $booking->payment_status === 'completed' ? 'Completed' : 'Required' }}
                                    </p>
                                    <p class="text-xs text-gray-500">KES {{ number_format($booking->total_amount, 2) }}</p>
                                </div>
                            </div>
                            
                            <!-- Step 2: Confirmation -->
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    @if($booking->status === 'confirmed' || in_array($booking->status, ['in_progress', 'completed']))
                                        <div class="w-6 h-6 bg-green-500 rounded-full flex items-center justify-center">
                                            <svg class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                            </svg>
                                        </div>
                                    @elseif($booking->hasValidPayment())
                                        <div class="w-6 h-6 bg-blue-500 rounded-full flex items-center justify-center">
                                            <span class="text-xs font-medium text-white">2</span>
                                        </div>
                                    @else
                                        <div class="w-6 h-6 bg-gray-300 rounded-full flex items-center justify-center">
                                            <span class="text-xs font-medium text-gray-600">2</span>
                                        </div>
                                    @endif
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm font-medium {{ in_array($booking->status, ['confirmed', 'in_progress', 'completed']) ? 'text-green-600 dark:text-green-400' : ($booking->hasValidPayment() ? 'text-blue-600 dark:text-blue-400' : 'text-gray-500') }}">
                                        Booking {{ in_array($booking->status, ['confirmed', 'in_progress', 'completed']) ? 'Confirmed' : ($booking->hasValidPayment() ? 'Ready to Confirm' : 'Awaiting Payment') }}
                                    </p>
                                    <p class="text-xs text-gray-500">{{ $booking->appointment_date->format('M j, Y') }} at {{ $booking->formatted_time_slot }}</p>
                                </div>
                            </div>
                            
                            <!-- Step 3: Service Completion -->
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    @if($booking->status === 'completed')
                                        <div class="w-6 h-6 bg-green-500 rounded-full flex items-center justify-center">
                                            <svg class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                            </svg>
                                        </div>
                                    @elseif($booking->status === 'in_progress')
                                        <div class="w-6 h-6 bg-purple-500 rounded-full flex items-center justify-center">
                                            <span class="text-xs font-medium text-white">3</span>
                                        </div>
                                    @else
                                        <div class="w-6 h-6 bg-gray-300 rounded-full flex items-center justify-center">
                                            <span class="text-xs font-medium text-gray-600">3</span>
                                        </div>
                                    @endif
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm font-medium {{ $booking->status === 'completed' ? 'text-green-600 dark:text-green-400' : ($booking->status === 'in_progress' ? 'text-purple-600 dark:text-purple-400' : 'text-gray-500') }}">
                                        Service {{ $booking->status === 'completed' ? 'Completed' : ($booking->status === 'in_progress' ? 'In Progress' : 'Pending') }}
                                    </p>
                                    <p class="text-xs text-gray-500">{{ $booking->service->name }} - {{ $booking->service->duration_minutes }}min</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Next Action Required -->
                    @php
                        $nextAction = '';
                        $actionColor = '';
                        $actionIcon = '';
                        
                        if ($booking->payment_status !== 'completed') {
                            $nextAction = 'Record Payment';
                            $actionColor = 'bg-yellow-500 text-white';
                            $actionIcon = 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z';
                        } elseif ($booking->status === 'pending') {
                            $nextAction = 'Confirm Booking';
                            $actionColor = 'bg-blue-500 text-white';
                            $actionIcon = 'M5 13l4 4L19 7';
                        } elseif ($booking->status === 'confirmed' && $booking->isToday()) {
                            $nextAction = 'Start Service';
                            $actionColor = 'bg-purple-500 text-white';
                            $actionIcon = 'M14.828 14.828a4 4 0 01-5.656 0M9 10h1.01M15 10h1.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z';
                        } elseif ($booking->status === 'in_progress') {
                            $nextAction = 'Complete Service';
                            $actionColor = 'bg-green-500 text-white';
                            $actionIcon = 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z';
                        } elseif ($booking->status === 'completed') {
                            $nextAction = 'Service Completed';
                            $actionColor = 'bg-green-500 text-white';
                            $actionIcon = 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z';
                        }
                    @endphp
                    
                    @if($nextAction)
                        <div class="mb-4 p-4 {{ $actionColor }} rounded-lg">
                            <div class="flex items-center">
                                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $actionIcon }}"></path>
                                </svg>
                                <div>
                                    <p class="font-medium">Next: {{ $nextAction }}</p>
                                    <p class="text-sm opacity-90">
                                        @if($booking->payment_status !== 'completed')
                                            Payment is required to proceed
                                        @elseif($booking->status === 'pending')
                                            Booking is ready for confirmation
                                        @elseif($booking->status === 'confirmed')
                                            Service scheduled for {{ $booking->formatted_time_slot }}
                                        @elseif($booking->status === 'in_progress')
                                            Service is currently being delivered
                                        @else
                                            All steps completed successfully
                                        @endif
                                    </p>
                                </div>
                            </div>
                        </div>
                    @endif
                    
                    <div class="space-y-3">
                        @if($booking->status === 'pending')
                            @if($booking->hasValidPayment())
                                <button class="btn btn-primary w-full" onclick="updateStatus('confirmed')">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    Confirm Booking
                                </button>
                            @else
                                <div class="space-y-2">
                                    <button class="btn btn-outline btn-error w-full" onclick="openPaymentModal()">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                                        </svg>
                                        Record Payment to Confirm
                                    </button>
                                    <p class="text-xs text-red-600 dark:text-red-400 text-center">
                                        Payment must be completed before confirming booking
                                    </p>
                                </div>
                            @endif
                        @endif

                        @if($booking->canBeStarted())
                            <button class="btn btn-outline w-full" onclick="updateStatus('in_progress')">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h1.01M15 10h1.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                Start Service
                            </button>
                        @endif

                        @if($booking->canBeCompleted())
                            @if($booking->hasValidPayment())
                                <button class="btn btn-success w-full" onclick="updateStatus('completed')">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    Complete Service
                                </button>
                            @else
                                <div class="space-y-2">
                                    <button class="btn btn-outline btn-error w-full" onclick="openPaymentModal()">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                                        </svg>
                                        Record Payment to Complete
                                    </button>
                                    <p class="text-xs text-red-600 dark:text-red-400 text-center">
                                        Payment must be completed before finishing service
                                    </p>
                                </div>
                            @endif
                        @endif

                        @if($booking->canBeCancelled())
                            <button class="btn btn-error w-full" onclick="openCancelModal()">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                Cancel Booking
                            </button>
                        @endif
                        
                        @if(in_array($booking->status, ['pending', 'confirmed']) && $booking->isToday())
                            <button class="btn btn-outline btn-warning w-full" onclick="updateStatus('no_show')">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                </svg>
                                Mark No Show
                            </button>
                        @endif

                        @if(!$booking->staff)
                            <button class="btn btn-outline w-full" onclick="openStaffModal()">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                </svg>
                                Assign Staff
                            </button>
                        @endif
                    </div>

                    <!-- Booking Timeline -->
                    <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                        <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Timeline</h3>
                        <div class="space-y-2 text-xs">
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400">Created</span>
                                <span class="text-gray-900 dark:text-white">{{ $booking->created_at->format('M j, g:i A') }}</span>
                            </div>
                            @if($booking->confirmed_at)
                                <div class="flex justify-between">
                                    <span class="text-green-600 dark:text-green-400">Confirmed</span>
                                    <span class="text-gray-900 dark:text-white">{{ $booking->confirmed_at->format('M j, g:i A') }}</span>
                                </div>
                            @endif
                            @if($booking->cancelled_at)
                                <div class="flex justify-between">
                                    <span class="text-red-600 dark:text-red-400">Cancelled</span>
                                    <span class="text-gray-900 dark:text-white">{{ $booking->cancelled_at->format('M j, g:i A') }}</span>
                                </div>
                            @endif
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400">Last Updated</span>
                                <span class="text-gray-900 dark:text-white">{{ $booking->updated_at->format('M j, g:i A') }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Staff Assignment Modal -->
    <flux:modal name="staffModal" class="md:w-96">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Assign Staff</flux:heading>
                <flux:subheading>Select a staff member for this appointment</flux:subheading>
            </div>

            <form id="staffForm" class="space-y-4">
                <flux:field>
                    <flux:label>Staff Member</flux:label>
                    <flux:select name="staff_id" required>
                        <flux:option value="">Select Staff Member</flux:option>
                        @foreach($staff as $staffMember)
                            <flux:option value="{{ $staffMember->id }}">
                                {{ $staffMember->name }}
                            </flux:option>
                        @endforeach
                    </flux:select>
                </flux:field>

                <div class="flex justify-end space-x-3">
                    <flux:button type="button" variant="ghost" onclick="closeStaffModal()">
                        Cancel
                    </flux:button>
                    <flux:button type="submit" variant="primary">
                        <flux:icon.user-plus class="mr-2 h-4 w-4" />
                        Assign Staff
                    </flux:button>
                </div>
            </form>
        </div>
    </flux:modal>

    <!-- Payment Modal -->
    <flux:modal name="paymentModal" class="md:w-96">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Record Payment</flux:heading>
                <flux:subheading>Enter payment details</flux:subheading>
            </div>

            <form id="paymentForm" class="space-y-4">
                <flux:field>
                    <flux:label>Amount (KES)</flux:label>
                    <flux:input type="number" name="amount" step="0.01" min="0" 
                               value="{{ $booking->total_amount }}" required />
                </flux:field>

                <flux:field>
                    <flux:label>Payment Method</flux:label>
                    <flux:select name="payment_method" required>
                        <flux:option value="">Select Payment Method</flux:option>
                        <flux:option value="cash">Cash</flux:option>
                        <flux:option value="card">Card</flux:option>
                        <flux:option value="mpesa">M-Pesa</flux:option>
                        <flux:option value="bank_transfer">Bank Transfer</flux:option>
                    </flux:select>
                </flux:field>

                <flux:field id="transactionRefField" class="hidden">
                    <flux:label>Transaction Reference</flux:label>
                    <flux:input type="text" name="transaction_reference" 
                               placeholder="Enter transaction reference" />
                </flux:field>

                <flux:field id="mpesaField" class="hidden">
                    <flux:label>M-Pesa Transaction ID</flux:label>
                    <flux:input type="text" name="mpesa_checkout_request_id" 
                               placeholder="Enter M-Pesa transaction ID" />
                </flux:field>

                <flux:field>
                    <flux:label>Payment Status</flux:label>
                    <flux:select name="status" required>
                        <flux:option value="completed">Completed</flux:option>
                        <flux:option value="pending">Pending</flux:option>
                        <flux:option value="failed">Failed</flux:option>
                    </flux:select>
                </flux:field>

                <div class="flex justify-end space-x-3">
                    <flux:button type="button" variant="ghost" onclick="closePaymentModal()">
                        Cancel
                    </flux:button>
                    <flux:button type="submit" variant="primary">
                        <flux:icon.credit-card class="mr-2 h-4 w-4" />
                        Record Payment
                    </flux:button>
                </div>
            </form>
        </div>
    </flux:modal>

    <!-- Cancel Modal -->
    <flux:modal name="cancelModal" class="md:w-96">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Cancel Booking</flux:heading>
                <flux:subheading>Please provide a reason for cancellation</flux:subheading>
            </div>

            <form id="cancelForm" class="space-y-4">
                <flux:field>
                    <flux:label>Cancellation Reason</flux:label>
                    <flux:textarea name="cancellation_reason" required 
                                  placeholder="Please provide a reason for cancellation"></flux:textarea>
                </flux:field>

                <div class="flex justify-end space-x-3">
                    <flux:button type="button" variant="ghost" onclick="closeCancelModal()">
                        Cancel
                    </flux:button>
                    <flux:button type="submit" variant="danger">
                        <flux:icon.x-circle class="mr-2 h-4 w-4" />
                        Cancel Booking
                    </flux:button>
                </div>
            </form>
        </div>
    </flux:modal>

    <script>
        // Simple JavaScript for form handling
        document.addEventListener('DOMContentLoaded', function() {
            // Handle payment method changes
            const paymentMethodSelect = document.querySelector('select[name="payment_method"]');
            const transactionRefField = document.querySelector('#transactionRefField');
            const mpesaField = document.querySelector('#mpesaField');
            
            if (paymentMethodSelect) {
                paymentMethodSelect.addEventListener('change', function() {
                    const method = this.value;
                    
                    // Hide all conditional fields
                    if (transactionRefField) transactionRefField.classList.add('hidden');
                    if (mpesaField) mpesaField.classList.add('hidden');
                    
                    // Show relevant fields based on payment method
                    if (method === 'mpesa') {
                        if (mpesaField) mpesaField.classList.remove('hidden');
                        if (transactionRefField) transactionRefField.classList.remove('hidden');
                    } else if (method === 'bank_transfer' || method === 'card') {
                        if (transactionRefField) transactionRefField.classList.remove('hidden');
                    }
                });
            }

            // Handle staff form submission
            const staffForm = document.getElementById('staffForm');
            if (staffForm) {
                staffForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    const formData = new FormData(this);
                    const data = Object.fromEntries(formData);
                    
                    if (!data.staff_id) {
                        alert('Please select a staff member');
                        return;
                    }

                    fetch(`/branch/bookings/{{ $booking->id }}/assign-staff`, {
                        method: 'PATCH',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify(data)
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showNotification('Success', data.message, 'success');
                            closeStaffModal();
                            setTimeout(() => {
                                window.location.reload();
                            }, 1000);
                        } else {
                            showNotification('Error', data.message || 'Error assigning staff', 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showNotification('Error', 'Error assigning staff', 'error');
                    });
                });
            }

            // Handle payment form submission
            const paymentForm = document.getElementById('paymentForm');
            if (paymentForm) {
                paymentForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    const formData = new FormData(this);
                    const data = Object.fromEntries(formData);
                    
                    if (!data.amount || !data.payment_method || !data.status) {
                        showNotification('Error', 'Please fill in all required fields', 'error');
                        return;
                    }
                    
                    // Validate M-Pesa fields
                    if (data.payment_method === 'mpesa' && !data.mpesa_checkout_request_id) {
                        showNotification('Error', 'M-Pesa Transaction ID is required', 'error');
                        return;
                    }
                    
                    // Validate bank transfer/card transaction reference
                    if ((data.payment_method === 'bank_transfer' || data.payment_method === 'card') && !data.transaction_reference) {
                        showNotification('Error', 'Transaction reference is required for ' + data.payment_method, 'error');
                        return;
                    }

                    fetch(`/branch/bookings/{{ $booking->id }}/payment`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify(data)
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showNotification('Success', data.message, 'success');
                            closePaymentModal();
                            setTimeout(() => {
                                window.location.reload();
                            }, 1000);
                        } else {
                            showNotification('Error', data.message || 'Error recording payment', 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showNotification('Error', 'Error recording payment', 'error');
                    });
                });
            }

            // Handle cancel form submission
            const cancelForm = document.getElementById('cancelForm');
            if (cancelForm) {
                cancelForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    const formData = new FormData(this);
                    const data = Object.fromEntries(formData);
                    
                    if (!data.cancellation_reason) {
                        alert('Please provide a cancellation reason');
                        return;
                    }

                    fetch(`/branch/bookings/{{ $booking->id }}/status`, {
                        method: 'PATCH',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            status: 'cancelled',
                            cancellation_reason: data.cancellation_reason
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showNotification('Success', data.message, 'success');
                            closeCancelModal();
                            setTimeout(() => {
                                window.location.reload();
                            }, 1000);
                        } else {
                            showNotification('Error', data.message || 'Error cancelling booking', 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showNotification('Error', 'Error cancelling booking', 'error');
                    });
                });
            }

            // Handle status updates
            window.updateStatus = function(status) {
                const messages = {
                    'confirmed': 'confirm this booking',
                    'in_progress': 'start this service',
                    'completed': 'complete this service',
                    'no_show': 'mark this as no-show'
                };

                if (confirm(`Are you sure you want to ${messages[status]}?`)) {
                    fetch(`/branch/bookings/{{ $booking->id }}/status`, {
                        method: 'PATCH',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ status: status })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showNotification('Success', data.message, 'success');
                            setTimeout(() => {
                                window.location.reload();
                            }, 1000);
                        } else {
                            showNotification('Error', data.message || 'Error updating status', 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showNotification('Error', 'Error updating status', 'error');
                    });
                }
            };

            // Modal handling functions using Flux modals
            window.openStaffModal = function() {
                document.dispatchEvent(new CustomEvent('modal-show', { detail: { name: 'staffModal' } }));
            };

            window.closeStaffModal = function() {
                document.dispatchEvent(new CustomEvent('modal-close', { detail: { name: 'staffModal' } }));
            };

            window.openPaymentModal = function() {
                document.dispatchEvent(new CustomEvent('modal-show', { detail: { name: 'paymentModal' } }));
            };

            window.closePaymentModal = function() {
                document.dispatchEvent(new CustomEvent('modal-close', { detail: { name: 'paymentModal' } }));
            };

            window.openCancelModal = function() {
                document.dispatchEvent(new CustomEvent('modal-show', { detail: { name: 'cancelModal' } }));
            };

            window.closeCancelModal = function() {
                document.dispatchEvent(new CustomEvent('modal-close', { detail: { name: 'cancelModal' } }));
            };

            // Notification function
            window.showNotification = function(title, message, type) {
                const notification = document.createElement('div');
                notification.className = `fixed top-4 right-4 p-4 rounded-lg shadow-lg z-50 ${
                    type === 'success' ? 'bg-green-500 text-white' : 'bg-red-500 text-white'
                }`;
                notification.innerHTML = `
                    <div class="flex items-center">
                        <div class="mr-3">
                            ${type === 'success' ? '✓' : '✗'}
                        </div>
                        <div>
                            <div class="font-semibold">${title}</div>
                            <div class="text-sm">${message}</div>
                        </div>
                    </div>
                `;
                
                document.body.appendChild(notification);
                
                setTimeout(() => {
                    notification.remove();
                }, 3000);
            };
        });
    </script>
</x-layouts.app>