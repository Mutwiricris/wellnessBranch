<x-layouts.app :title="__('Bookings Management')">
    <div class="flex h-full w-full flex-1 flex-col gap-8 p-6">
        <!-- Page Header -->
        <div class="flex items-center justify-between">
            <div class="space-y-1">
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Bookings Management</h1>
                <p class="text-sm text-gray-600 dark:text-gray-400">Manage {{ auth()->user()->branch->name }} bookings and appointments</p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('branch.bookings.create') }}" class="btn btn-primary" wire:navigate>
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    New Booking
                </a>
            </div>
        </div>

        <!-- Today's Statistics with Enhanced DaisyUI Cards -->
        <div class="grid gap-6 md:grid-cols-3 lg:grid-cols-6">
            @foreach([
                ['title' => 'Total Today', 'icon' => 'calendar', 'color' => 'blue', 'main' => $todayStats['today']['total'], 'sub' => $todayStats['today']['pending'] . ' pending', 'trend' => null],
                ['title' => 'Completed', 'icon' => 'check-circle', 'color' => 'green', 'main' => $todayStats['today']['completed'], 'sub' => $todayStats['today']['confirmed'] . ' confirmed', 'trend' => 'up'],
                ['title' => 'Today Revenue', 'icon' => 'currency-dollar', 'color' => 'yellow', 'main' => 'KES ' . number_format($todayStats['today']['revenue'], 2), 'sub' => ($todayStats['today']['daily_growth'] >= 0 ? '↑' : '↓') . ' ' . abs($todayStats['today']['daily_growth']) . '% vs yesterday', 'sub_class' => $todayStats['today']['daily_growth'] >= 0 ? 'text-success' : 'text-error', 'trend' => $todayStats['today']['daily_growth'] >= 0 ? 'up' : 'down'],
                ['title' => 'Staff Utilization', 'icon' => 'users', 'color' => 'purple', 'main' => $todayStats['staff_utilization'] . '%', 'sub' => $todayStats['active_staff'] . '/' . $todayStats['total_staff'] . ' staff active', 'trend' => null],
                ['title' => 'No-Show Rate', 'icon' => 'user-minus', 'color' => 'red', 'main' => $todayStats['today']['no_show_rate'] . '%', 'sub' => $todayStats['today']['no_show'] . ' no-shows', 'trend' => $todayStats['today']['no_show_rate'] > 0 ? 'down' : null],
                ['title' => 'Avg Service Time', 'icon' => 'clock', 'color' => 'orange', 'main' => $todayStats['today']['avg_service_time'] . 'm', 'sub' => $todayStats['upcoming'] . ' upcoming', 'trend' => null]
            ] as $stat)
                <div class="card bg-base-100 shadow-lg border border-base-200 hover:shadow-xl transition-all duration-200 transform hover:-translate-y-1">
                    <div class="card-body p-5">
                        <div class="flex items-center gap-4">
                            <div class="avatar">
                                <div class="w-12 rounded-full bg-{{ $stat['color'] }}-100 dark:bg-{{ $stat['color'] }}-900 flex items-center justify-center">
                                    @if($stat['icon'] === 'calendar')
                                        <svg class="h-6 w-6 text-{{ $stat['color'] }}-600 dark:text-{{ $stat['color'] }}-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                        </svg>
                                    @elseif($stat['icon'] === 'check-circle')
                                        <svg class="h-6 w-6 text-{{ $stat['color'] }}-600 dark:text-{{ $stat['color'] }}-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                    @elseif($stat['icon'] === 'currency-dollar')
                                        <svg class="h-6 w-6 text-{{ $stat['color'] }}-600 dark:text-{{ $stat['color'] }}-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                                        </svg>
                                    @elseif($stat['icon'] === 'users')
                                        <svg class="h-6 w-6 text-{{ $stat['color'] }}-600 dark:text-{{ $stat['color'] }}-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                                        </svg>
                                    @elseif($stat['icon'] === 'user-minus')
                                        <svg class="h-6 w-6 text-{{ $stat['color'] }}-600 dark:text-{{ $stat['color'] }}-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7a4 4 0 11-8 0 4 4 0 018 0zM9 14a6 6 0 00-6 6v1h12v-1a6 6 0 00-6-6zM21 12h-6"></path>
                                        </svg>
                                    @elseif($stat['icon'] === 'clock')
                                        <svg class="h-6 w-6 text-{{ $stat['color'] }}-600 dark:text-{{ $stat['color'] }}-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                    @endif
                                </div>
                            </div>
                            <div class="flex-1">
                                <div class="flex items-center gap-2">
                                    <h3 class="card-title text-sm font-medium opacity-70">{{ $stat['title'] }}</h3>
                                    @if($stat['trend'])
                                        <div class="badge badge-sm {{ $stat['trend'] === 'up' ? 'badge-success' : 'badge-error' }}">
                                            @if($stat['trend'] === 'up')
                                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M3.293 9.707a1 1 0 010-1.414l6-6a1 1 0 011.414 0l6 6a1 1 0 01-1.414 1.414L11 5.414V17a1 1 0 11-2 0V5.414L4.707 9.707a1 1 0 01-1.414 0z" clip-rule="evenodd"></path></svg>
                                            @else
                                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 10.293a1 1 0 010 1.414l-6 6a1 1 0 01-1.414 0l-6-6a1 1 0 111.414-1.414L9 14.586V3a1 1 0 012 0v11.586l4.293-4.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                                <p class="text-2xl font-bold">{{ $stat['main'] }}</p>
                                <p class="text-sm {{ $stat['sub_class'] ?? 'opacity-60' }}">{{ $stat['sub'] }}</p>
                            </div>
                        </div>
                        @if($stat['trend'])
                            <progress class="progress progress-{{ $stat['trend'] === 'up' ? 'success' : 'error' }} w-full h-1" value="{{ rand(30, 90) }}" max="100"></progress>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Revenue & Growth Stats -->
        <div class="grid gap-6 md:grid-cols-3">
            @foreach([
                ['title' => 'This Week', 'data' => $todayStats['week'], 'color' => 'blue'],
                ['title' => 'This Month', 'data' => $todayStats['month'], 'color' => 'blue']
            ] as $period)
                <flux:card class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ $period['title'] }}</h3>
                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="text-center">
                            <p class="text-2xl font-bold text-{{ $period['color'] }}-600 dark:text-{{ $period['color'] }}-400">{{ $period['data']['total'] }}</p>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Total Bookings</p>
                            <p class="text-xs text-gray-500">{{ $period['data']['avg_daily'] }}/day avg</p>
                        </div>
                        <div class="text-center">
                            <p class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">{{ number_format($period['data']['revenue'], 0) }}</p>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Revenue (KES)</p>
                            <p class="text-xs {{ $period['data']['growth'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                {{ $period['data']['growth'] >= 0 ? '↑' : '↓' }} {{ abs($period['data']['growth']) }}% vs last {{ strtolower($period['title']) }}
                            </p>
                        </div>
                    </div>
                </flux:card>
            @endforeach
            <flux:card class="p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Financial Overview</h3>
                <div class="space-y-4">
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600 dark:text-gray-400">Pending Payments</span>
                        <div class="text-right">
                            <p class="text-lg font-bold text-red-600 dark:text-red-400">KES {{ number_format($todayStats['pending_payments_amount'], 2) }}</p>
                            <p class="text-xs text-gray-500">{{ $todayStats['pending_payments'] }} bookings</p>
                        </div>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600 dark:text-gray-400">Client Retention</span>
                        <div class="text-right">
                            <p class="text-lg font-bold text-green-600 dark:text-green-400">{{ $todayStats['client_retention_rate'] }}%</p>
                            <p class="text-xs text-gray-500">{{ $todayStats['repeat_clients'] }}/{{ $todayStats['total_clients_month'] }} clients</p>
                        </div>
                    </div>
                </div>
            </flux:card>
        </div>

        <!-- Business Insights -->
        <div class="grid gap-6 md:grid-cols-2">
            <flux:card class="p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Popular Services Today</h3>
                <div class="space-y-3">
                    @forelse($todayStats['popular_services'] as $service)
                        <div class="flex justify-between items-center p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                            <div>
                                <p class="font-medium text-gray-900 dark:text-white">{{ $service['service'] }}</p>
                                <p class="text-sm text-gray-600 dark:text-gray-400">{{ $service['count'] }} bookings</p>
                            </div>
                            <p class="font-bold text-green-600 dark:text-green-400">KES {{ number_format($service['revenue'], 2) }}</p>
                        </div>
                    @empty
                        <p class="text-gray-500 dark:text-gray-400 text-center py-4">No services booked today</p>
                    @endforelse
                </div>
            </flux:card>
            <flux:card class="p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Peak Hours Today</h3>
                <div class="space-y-3">
                    @forelse($todayStats['peak_hours'] as $hour => $count)
                        <div class="flex justify-between items-center p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                            <div>
                                <p class="font-medium text-gray-900 dark:text-white">{{ $hour }}:00 - {{ $hour + 1 }}:00</p>
                                <p class="text-sm text-gray-600 dark:text-gray-400">Peak hour</p>
                            </div>
                            <p class="font-bold text-blue-600 dark:text-blue-400">{{ $count }} bookings</p>
                        </div>
                    @empty
                        <p class="text-gray-500 dark:text-gray-400 text-center py-4">No peak hours data available</p>
                    @endforelse
                </div>
            </flux:card>
        </div>

        <!-- Filters -->
        <flux:card class="p-6">
            <form method="GET" class="space-y-6">
                <flux:field>
                    <flux:label>Search</flux:label>
                    <flux:input type="text" name="search" value="{{ request('search') }}" 
                               placeholder="Search by booking reference, client name, email, phone, or service..." 
                               class="w-full" />
                </flux:field>

                <div class="grid gap-4 md:grid-cols-3 lg:grid-cols-6">
                    <flux:field>
                        <flux:label>Date</flux:label>
                        <flux:input type="date" name="date" value="{{ request('date') }}" />
                    </flux:field>
                    <flux:field>
                        <flux:label>From Date</flux:label>
                        <flux:input type="date" name="date_from" value="{{ request('date_from') }}" />
                    </flux:field>
                    <flux:field>
                        <flux:label>To Date</flux:label>
                        <flux:input type="date" name="date_to" value="{{ request('date_to') }}" />
                    </flux:field>
                    <flux:field>
                        <flux:label>Status</flux:label>
                        <flux:select name="status">
                            <flux:option value="">All Statuses</flux:option>
                            @foreach(['pending', 'confirmed', 'in_progress', 'completed', 'cancelled', 'no_show'] as $status)
                                <flux:option value="{{ $status }}" :selected="request('status') === '{{ $status }}'">
                                    {{ ucfirst(str_replace('_', ' ', $status)) }}
                                </flux:option>
                            @endforeach
                        </flux:select>
                    </flux:field>
                    <flux:field>
                        <flux:label>Payment Status</flux:label>
                        <flux:select name="payment_status">
                            <flux:option value="">All Payment Status</flux:option>
                            @foreach(['pending', 'completed', 'failed', 'refunded'] as $paymentStatus)
                                <flux:option value="{{ $paymentStatus }}" :selected="request('payment_status') === '{{ $paymentStatus }}'">
                                    {{ ucfirst($paymentStatus) }}
                                </flux:option>
                            @endforeach
                        </flux:select>
                    </flux:field>
                    <flux:field>
                        <flux:label>Payment Method</flux:label>
                        <flux:select name="payment_method">
                            <flux:option value="">All Methods</flux:option>
                            @foreach(['cash', 'card', 'mpesa', 'bank_transfer'] as $method)
                                <flux:option value="{{ $method }}" :selected="request('payment_method') === '{{ $method }}'">
                                    {{ ucfirst(str_replace('_', ' ', $method)) }}
                                </flux:option>
                            @endforeach
                        </flux:select>
                    </flux:field>
                </div>

                <div class="grid gap-4 md:grid-cols-3">
                    <flux:field>
                        <flux:label>Staff</flux:label>
                        <flux:select name="staff_id">
                            <flux:option value="">All Staff</flux:option>
                            @foreach($staff as $staffMember)
                                <flux:option value="{{ $staffMember->id }}" :selected="request('staff_id') == $staffMember->id">
                                    {{ $staffMember->name }}
                                </flux:option>
                            @endforeach
                        </flux:select>
                    </flux:field>
                    <div class="flex items-end gap-2">
                        <flux:button type="submit" variant="primary">
                            <flux:icon.magnifying-glass class="mr-2 h-4 w-4" />
                            Filter
                        </flux:button>
                        <flux:button type="button" variant="ghost" onclick="window.location.href='{{ route('branch.bookings.index') }}'">
                            <flux:icon.x-mark class="mr-2 h-4 w-4" />
                            Clear
                        </flux:button>
                    </div>
                    <div class="flex items-end justify-end gap-2">
                        <flux:button type="button" variant="outline" onclick="exportBookings()">
                            <flux:icon.arrow-down-tray class="mr-2 h-4 w-4" />
                            Export CSV
                        </flux:button>
                        <flux:button type="button" variant="outline" onclick="openExportModal()">
                            <flux:icon.document-text class="mr-2 h-4 w-4" />
                            Export PDF
                        </flux:button>
                    </div>
                </div>
            </form>
        </flux:card>

        <!-- Bookings Table -->
        <flux:card class="overflow-hidden">
            <div class="bg-white dark:bg-zinc-900 border-b border-gray-200 dark:border-gray-700 px-6 py-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">All Bookings</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">Manage and track all booking appointments</p>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-zinc-800">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Booking Reference
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Client Information
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Service Details
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Staff Assignment
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Appointment Schedule
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Status
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Payment
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Amount
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-zinc-900 divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($bookings as $booking)
                            <tr class="hover:bg-gray-50 dark:hover:bg-zinc-800 transition-colors duration-150 group" 
                                data-booking-id="{{ $booking->id }}">
                                
                                <!-- Booking Reference -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10">
                                            <div class="h-10 w-10 rounded-full bg-blue-100 dark:bg-blue-900 flex items-center justify-center">
                                                <svg class="h-5 w-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                            </svg>
                                            </div>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900 dark:text-white font-mono">
                                                {{ $booking->booking_reference }}
                                            </div>
                                            <div class="text-xs text-gray-500">
                                                Created {{ $booking->created_at->format('M j, Y') }}
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Client Information -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10">
                                            <div class="h-10 w-10 rounded-full bg-green-100 dark:bg-green-900 flex items-center justify-center">
                                                <span class="text-sm font-medium text-green-600 dark:text-green-400">
                                                    {{ substr($booking->client->full_name, 0, 1) }}
                                                </span>
                                            </div>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900 dark:text-white">
                                                {{ $booking->client->full_name }}
                                            </div>
                                            <div class="text-xs text-gray-500">
                                                {{ $booking->client->phone }}
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Service Details -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10">
                                            <div class="h-10 w-10 rounded-full bg-purple-100 dark:bg-purple-900 flex items-center justify-center">
                                                <svg class="h-5 w-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"></path>
                                                </svg>
                                            </div>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900 dark:text-white">
                                                {{ $booking->service->name }}
                                            </div>
                                            <div class="text-xs text-gray-500">
                                                {{ $booking->service->duration_minutes }} minutes
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Staff Assignment -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($booking->staff)
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-10 w-10">
                                                <div class="h-10 w-10 rounded-full bg-indigo-100 dark:bg-indigo-900 flex items-center justify-center">
                                                    <span class="text-sm font-medium text-indigo-600 dark:text-indigo-400">
                                                        {{ substr($booking->staff->name, 0, 1) }}
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-gray-900 dark:text-white">
                                                    {{ $booking->staff->name }}
                                                </div>
                                                <div class="text-xs text-gray-500">
                                                    Staff Member
                                                </div>
                                            </div>
                                        </div>
                                    @else
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-10 w-10">
                                                <div class="h-10 w-10 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center">
                                                    <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                                    </svg>
                                                </div>
                                            </div>
                                            <div class="ml-4">
                                                <flux:badge variant="outline" color="gray" size="sm">
                                                    Unassigned
                                                </flux:badge>
                                            </div>
                                        </div>
                                    @endif
                                </td>

                                <!-- Appointment Schedule -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10">
                                            <div class="h-10 w-10 rounded-full bg-yellow-100 dark:bg-yellow-900 flex items-center justify-center">
                                                <svg class="h-5 w-5 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                </svg>
                                            </div>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900 dark:text-white">
                                                {{ $booking->appointment_date->format('M j, Y') }}
                                            </div>
                                            <div class="text-xs text-gray-500">
                                                {{ $booking->start_time->format('H:i') }} - {{ $booking->end_time->format('H:i') }}
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Status -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex flex-col space-y-1">
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
                                        @php
                                            $statusBadgeClasses = [
                                                'pending' => 'badge-warning',
                                                'confirmed' => 'badge-info', 
                                                'in_progress' => 'badge-secondary',
                                                'completed' => 'badge-success',
                                                'cancelled' => 'badge-error',
                                                'no_show' => 'badge-neutral'
                                            ];
                                        @endphp
                                        <div class="badge badge-sm {{ $statusBadgeClasses[$booking->status] ?? 'badge-neutral' }}">
                                            {{ ucfirst(str_replace('_', ' ', $booking->status)) }}
                                        </div>
                                        @if($booking->confirmed_at)
                                            <div class="text-xs text-green-600 dark:text-green-400">
                                                Confirmed {{ $booking->confirmed_at->format('M j') }}
                                            </div>
                                        @endif
                                        @if($booking->cancelled_at)
                                            <div class="text-xs text-red-600 dark:text-red-400">
                                                Cancelled {{ $booking->cancelled_at->format('M j') }}
                                            </div>
                                        @endif
                                    </div>
                                </td>

                                <!-- Payment -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex flex-col space-y-1">
                                        @php
                                            $paymentStatusColors = [
                                                'pending' => 'yellow',
                                                'completed' => 'green',
                                                'failed' => 'red',
                                                'refunded' => 'gray'
                                            ];
                                            $paymentMethodIcons = [
                                                'cash' => '<svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.071.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z"></path><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.563-.649-1.413-1.076-2.354-1.253V5z" clip-rule="evenodd"></path></svg>',
                                                'card' => '<svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path d="M4 4a2 2 0 00-2 2v1h16V6a2 2 0 00-2-2H4z"></path><path fill-rule="evenodd" d="M18 9H2v5a2 2 0 002 2h12a2 2 0 002-2V9zM4 13a1 1 0 011-1h1a1 1 0 110 2H5a1 1 0 01-1-1zm5-1a1 1 0 100 2h1a1 1 0 100-2H9z" clip-rule="evenodd"></path></svg>',
                                                'mpesa' => '<svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M7 2a2 2 0 00-2 2v12a2 2 0 002 2h6a2 2 0 002-2V4a2 2 0 00-2-2H7zM8 5a1 1 0 000 2h4a1 1 0 100-2H8zm0 6a1 1 0 100 2h4a1 1 0 100-2H8z" clip-rule="evenodd"></path></svg>',
                                                'bank_transfer' => '<svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4 4a2 2 0 00-2 2v8a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2H4zm0 2h12v8H4V6z" clip-rule="evenodd"></path></svg>'
                                            ];
                                        @endphp
                                        @php
                                            $paymentBadgeClasses = [
                                                'pending' => 'badge-warning',
                                                'completed' => 'badge-success',
                                                'failed' => 'badge-error',
                                                'refunded' => 'badge-neutral'
                                            ];
                                        @endphp
                                        <div class="badge badge-sm {{ $paymentBadgeClasses[$booking->payment_status] ?? 'badge-neutral' }}">
                                            <!-- Payment Workflow Status -->
                                            @if($booking->payment_status === 'pending')
                                                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"></path></svg>
                                            @elseif($booking->payment_status === 'completed')
                                                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>
                                            @endif
                                            {{ ucfirst($booking->payment_status) }}
                                        </div>
                                        <div class="text-xs text-gray-500 flex items-center">
                                            <span class="mr-1">{!! $paymentMethodIcons[$booking->payment_method] ?? '' !!}</span>
                                            {{ ucfirst(str_replace('_', ' ', $booking->payment_method)) }}
                                        </div>
                                        
                                        <!-- Booking Workflow Progress -->
                                        <div class="mt-2">
                                            <div class="text-xs text-gray-400 mb-1">Progress</div>
                                            <div class="flex items-center space-x-1">
                                                <!-- Step 1: Payment -->
                                                <div class="w-2 h-2 rounded-full {{ $booking->payment_status === 'completed' ? 'bg-green-500' : 'bg-yellow-500' }}" title="Payment {{ $booking->payment_status === 'completed' ? 'Completed' : 'Pending' }}"></div>
                                                <div class="w-3 h-0.5 {{ $booking->payment_status === 'completed' ? 'bg-green-300' : 'bg-gray-300' }}"></div>
                                                
                                                <!-- Step 2: Confirmation -->
                                                <div class="w-2 h-2 rounded-full {{ in_array($booking->status, ['confirmed', 'in_progress', 'completed']) ? 'bg-green-500' : ($booking->payment_status === 'completed' ? 'bg-blue-500' : 'bg-gray-300') }}" title="Booking {{ in_array($booking->status, ['confirmed', 'in_progress', 'completed']) ? 'Confirmed' : 'Pending' }}"></div>
                                                <div class="w-3 h-0.5 {{ $booking->status === 'completed' ? 'bg-green-300' : 'bg-gray-300' }}"></div>
                                                
                                                <!-- Step 3: Completion -->
                                                <div class="w-2 h-2 rounded-full {{ $booking->status === 'completed' ? 'bg-green-500' : ($booking->status === 'in_progress' ? 'bg-purple-500' : 'bg-gray-300') }}" title="Service {{ $booking->status === 'completed' ? 'Completed' : ($booking->status === 'in_progress' ? 'In Progress' : 'Pending') }}"></div>
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Amount -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white" data-amount="{{ $booking->total_amount }}">
                                        KES {{ number_format($booking->total_amount, 2) }}
                                    </div>
                                    @if($booking->notes)
                                        <div class="text-xs text-gray-500 mt-1" title="{{ $booking->notes }}">
                                            <svg class="h-3 w-3 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                                            </svg>
                                            Note available
                                        </div>
                                    @endif
                                </td>

                                <!-- Enhanced Actions with DaisyUI -->
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="dropdown dropdown-end">
                                        <div tabindex="0" role="button" class="btn btn-ghost btn-sm">
                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z"></path>
                                            </svg>
                                        </div>
                                        <ul tabindex="0" class="dropdown-content z-[1] menu p-2 shadow bg-base-100 rounded-box w-52 border">
                                            <!-- View Action -->
                                            <li>
                                                <a href="{{ route('branch.bookings.show', $booking) }}" wire:navigate class="flex items-center gap-2">
                                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                                    </svg>
                                                    View Details
                                                </a>
                                            </li>
                                            
                                            <!-- Status Actions -->
                                            @if($booking->status === 'pending')
                                                <li>
                                                    <button onclick="updateBookingStatus({{ $booking->id }}, 'confirmed')" class="flex items-center gap-2 text-success">
                                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                        </svg>
                                                        Confirm Booking
                                                    </button>
                                                </li>
                                                <li>
                                                    <button onclick="updateBookingStatus({{ $booking->id }}, 'cancelled')" class="flex items-center gap-2 text-error">
                                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                        </svg>
                                                        Cancel Booking
                                                    </button>
                                                </li>
                                            @endif
                                            
                                            @if($booking->status === 'confirmed')
                                                <li>
                                                    <button onclick="updateBookingStatus({{ $booking->id }}, 'in_progress')" class="flex items-center gap-2 text-info">
                                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h1.01M15 10h1.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                        </svg>
                                                        Start Service
                                                    </button>
                                                </li>
                                                <li>
                                                    <button onclick="updateBookingStatus({{ $booking->id }}, 'completed')" class="flex items-center gap-2 text-success">
                                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                        </svg>
                                                        Mark Complete
                                                    </button>
                                                </li>
                                            @endif
                                            
                                            @if($booking->status === 'in_progress')
                                                <li>
                                                    <button onclick="updateBookingStatus({{ $booking->id }}, 'completed')" class="flex items-center gap-2 text-success">
                                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                        </svg>
                                                        Complete Service
                                                    </button>
                                                </li>
                                            @endif
                                            
                                            <!-- Payment Actions -->
                                            @if($booking->payment_status !== 'completed')
                                                <div class="divider my-1"></div>
                                                <li>
                                                    <button onclick="openPaymentModal({{ $booking->id }})" class="flex items-center gap-2 text-warning">
                                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                                                        </svg>
                                                        Record Payment
                                                    </button>
                                                </li>
                                            @endif
                                            
                                            <!-- Communication Actions -->
                                            <div class="divider my-1"></div>
                                            <li>
                                                <button onclick="sendBookingReminder({{ $booking->id }})" class="flex items-center gap-2">
                                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                                                    </svg>
                                                    Send Reminder
                                                </button>
                                            </li>
                                            <li>
                                                <button onclick="openNotesModal({{ $booking->id }})" class="flex items-center gap-2">
                                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                                                    </svg>
                                                    Add Note
                                                </button>
                                            </li>
                                        </ul>
                                    </div>
                                    
                                    <!-- Quick Action Buttons for Important Actions -->
                                    <div class="flex flex-col gap-1 mt-2">
                                        @if($booking->status === 'pending')
                                            <div class="flex gap-1">
                                                <button onclick="updateBookingStatus({{ $booking->id }}, 'confirmed')" 
                                                        class="btn btn-success btn-xs" title="Quick Confirm">
                                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>
                                                </button>
                                                <button onclick="updateBookingStatus({{ $booking->id }}, 'cancelled')" 
                                                        class="btn btn-error btn-xs" title="Quick Cancel">
                                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>
                                                </button>
                                            </div>
                                        @elseif($booking->status === 'confirmed')
                                            <button onclick="updateBookingStatus({{ $booking->id }}, 'completed')" 
                                                    class="btn btn-success btn-xs" title="Quick Complete">
                                                Complete
                                            </button>
                                        @endif
                                        
                                        @if($booking->payment_status !== 'completed')
                                            <button onclick="openPaymentModal({{ $booking->id }})" 
                                                    class="btn btn-warning btn-xs" title="Record Payment">
                                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.071.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z"></path><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.563-.649-1.413-1.076-2.354-1.253V5z" clip-rule="evenodd"></path></svg>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="px-6 py-16 text-center">
                                    <div class="text-gray-500 dark:text-gray-400">
                                        <div class="mx-auto h-24 w-24 rounded-full bg-gray-100 dark:bg-gray-800 flex items-center justify-center mb-4">
                                            <svg class="h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                            </svg>
                                        </div>
                                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">No bookings found</h3>
                                        <p class="text-sm">No bookings match your current filters. Try adjusting your search criteria.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            @if($bookings->hasPages())
                <div class="bg-white dark:bg-zinc-900 border-t border-gray-200 dark:border-gray-700 px-6 py-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center text-sm text-gray-500 dark:text-gray-400">
                            <span>Showing {{ $bookings->firstItem() }} to {{ $bookings->lastItem() }} of {{ $bookings->total() }} results</span>
                        </div>
                        <div>
                            {{ $bookings->links() }}
                        </div>
                    </div>
                </div>
            @endif
        </flux:card>

        <!-- Export PDF Modal -->
        <flux:modal name="exportModal" class="md:max-w-lg">
            <div class="space-y-6 p-6">
                <div>
                    <flux:heading size="lg">Export Bookings Report</flux:heading>
                    <flux:subheading>Generate a comprehensive PDF report</flux:subheading>
                </div>
                <form id="exportForm" class="space-y-4">
                    <flux:field>
                        <flux:label>Report Type</flux:label>
                        <flux:select name="report_type" required>
                            <flux:option value="detailed">Detailed Report</flux:option>
                            <flux:option value="summary">Summary Report</flux:option>
                            <flux:option value="financial">Financial Report</flux:option>
                        </flux:select>
                    </flux:field>
                    <flux:field>
                        <flux:label>Date Range</flux:label>
                        <flux:select name="date_range" id="dateRangeSelect" required>
                            <flux:option value="today">Today</flux:option>
                            <flux:option value="yesterday">Yesterday</flux:option>
                            <flux:option value="this_week">This Week</flux:option>
                            <flux:option value="last_week">Last Week</flux:option>
                            <flux:option value="this_month">This Month</flux:option>
                            <flux:option value="last_month">Last Month</flux:option>
                            <flux:option value="custom">Custom Range</flux:option>
                        </flux:select>
                    </flux:field>
                    <div id="customDateRange" class="hidden space-y-4">
                        <flux:field>
                            <flux:label>From Date</flux:label>
                            <flux:input type="date" name="date_from" />
                        </flux:field>
                        <flux:field>
                            <flux:label>To Date</flux:label>
                            <flux:input type="date" name="date_to" />
                        </flux:field>
                    </div>
                    <flux:field>
                        <flux:label>Status Filter</flux:label>
                        <flux:select name="status">
                            <flux:option value="">All Statuses</flux:option>
                            @foreach(['pending', 'confirmed', 'in_progress', 'completed', 'cancelled', 'no_show'] as $status)
                                <flux:option value="{{ $status }}">{{ ucfirst(str_replace('_', ' ', $status)) }}</flux:option>
                            @endforeach
                        </flux:select>
                    </flux:field>
                    <flux:field>
                        <flux:label>Payment Status Filter</flux:label>
                        <flux:select name="payment_status">
                            <flux:option value="">All Payment Status</flux:option>
                            @foreach(['pending', 'completed', 'failed', 'refunded'] as $paymentStatus)
                                <flux:option value="{{ $paymentStatus }}">{{ ucfirst($paymentStatus) }}</flux:option>
                            @endforeach
                        </flux:select>
                    </flux:field>
                    <flux:field>
                        <flux:label>Staff Filter</flux:label>
                        <flux:select name="staff_id">
                            <flux:option value="">All Staff</flux:option>
                            @foreach($staff as $staffMember)
                                <flux:option value="{{ $staffMember->id }}">{{ $staffMember->name }}</flux:option>
                            @endforeach
                        </flux:select>
                    </flux:field>
                    <div class="flex justify-end gap-3">
                        <flux:button type="button" variant="ghost" onclick="closeExportModal()">
                            Cancel
                        </flux:button>
                        <flux:button type="submit" variant="primary">
                            <flux:icon.document-text class="mr-2 h-4 w-4" />
                            Generate PDF
                        </flux:button>
                    </div>
                </form>
            </div>
        </flux:modal>

        <!-- Payment Modal -->
        <flux:modal name="paymentModal" class="md:max-w-lg">
            <div class="space-y-6 p-6">
                <div>
                    <flux:heading size="lg">Record Payment</flux:heading>
                    <flux:subheading>Enter payment details for booking</flux:subheading>
                </div>
                <form id="paymentForm" class="space-y-4">
                    <input type="hidden" id="paymentBookingId" name="booking_id" />
                    <flux:field>
                        <flux:label>Amount (KES)</flux:label>
                        <flux:input type="number" name="amount" step="0.01" min="0" id="paymentAmount" required />
                    </flux:field>
                    <flux:field>
                        <flux:label>Payment Method</flux:label>
                        <flux:select name="payment_method" required>
                            <flux:option value="">Select Payment Method</flux:option>
                            @foreach(['cash', 'card', 'mpesa', 'bank_transfer'] as $method)
                                <flux:option value="{{ $method }}">{{ ucfirst(str_replace('_', ' ', $method)) }}</flux:option>
                            @endforeach
                        </flux:select>
                    </flux:field>
                    <flux:field id="transactionRefField" class="hidden">
                        <flux:label>Transaction Reference</flux:label>
                        <flux:input type="text" name="transaction_reference" placeholder="Enter transaction reference" />
                    </flux:field>
                    <flux:field id="mpesaField" class="hidden">
                        <flux:label>M-Pesa Transaction ID</flux:label>
                        <flux:input type="text" name="mpesa_checkout_request_id" placeholder="Enter M-Pesa transaction ID" />
                    </flux:field>
                    <flux:field>
                        <flux:label>Payment Status</flux:label>
                        <flux:select name="status" required>
                            <flux:option value="completed">Completed</flux:option>
                            <flux:option value="pending">Pending</flux:option>
                            <flux:option value="failed">Failed</flux:option>
                        </flux:select>
                    </flux:field>
                    <div class="flex justify-end gap-3">
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
    </div>

    @push('scripts')

    <script>
        function updateBookingStatus(bookingId, status) {
            const messages = {
                'confirmed': 'confirm this booking',
                'in_progress': 'start this service',
                'completed': 'complete this service',
                'cancelled': 'cancel this booking',
                'no_show': 'mark this as no-show'
            };
            
            // Payment validation for status changes
            const bookingRow = document.querySelector(`[data-booking-id="${bookingId}"]`);
            if (bookingRow && (status === 'confirmed' || status === 'completed')) {
                const paymentBadge = bookingRow.querySelector('.badge-success');
                const hasCompletedPayment = paymentBadge && paymentBadge.textContent.trim().toLowerCase().includes('completed');
                
                if (!hasCompletedPayment) {
                    if (confirm('Payment has not been completed. You need to record payment first. Would you like to record payment now?')) {
                        openPaymentModal(bookingId);
                        return;
                    } else {
                        return;
                    }
                }
            }
            
            if (confirm(`Are you sure you want to ${messages[status]}?`)) {
                fetch(`/branch/bookings/${bookingId}/status`, {
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
                        const bookingRef = data.booking?.booking_reference || 'Unknown';
                        switch(status) {
                            case 'confirmed':
                                if (notificationManager) notificationManager.onBookingConfirmed(bookingId, bookingRef);
                                break;
                            case 'cancelled':
                                if (notificationManager) notificationManager.onBookingCancelled(bookingId, bookingRef);
                                break;
                            case 'completed':
                                if (notificationManager) notificationManager.onBookingCompleted(bookingId, bookingRef);
                                break;
                            default:
                                showNotification('Success', data.message, 'success');
                        }
                        setTimeout(() => window.location.reload(), 1000);
                    } else {
                        if (notificationManager) {
                            notificationManager.onBookingError(data.message || 'Error updating booking status');
                        } else {
                            showNotification('Error', data.message || 'Error updating booking status', 'error');
                        }
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    if (notificationManager) {
                        notificationManager.onBookingError('Error updating booking status');
                    } else {
                        showNotification('Error', 'Error updating booking status', 'error');
                    }
                });
            }
        }

        function openPaymentModal(bookingId) {
            document.getElementById('paymentBookingId').value = bookingId;
            const bookingRow = document.querySelector(`[data-booking-id="${bookingId}"]`);
            if (bookingRow) {
                const amountCell = bookingRow.querySelector('[data-amount]');
                if (amountCell) {
                    document.getElementById('paymentAmount').value = amountCell.getAttribute('data-amount');
                }
            }
            document.dispatchEvent(new CustomEvent('modal-show', { detail: { name: 'paymentModal' } }));
        }

        function closePaymentModal() {
            document.dispatchEvent(new CustomEvent('modal-close', { detail: { name: 'paymentModal' } }));
        }

        function exportBookings() {
            const form = document.querySelector('form');
            const formData = new FormData(form);
            const params = new URLSearchParams();
            for (const [key, value] of formData.entries()) {
                if (value) params.append(key, value);
            }
            params.append('export', 'csv');
            window.open('{{ route("branch.bookings.index") }}?' + params.toString(), '_blank');
        }

        function showNotification(title, message, type) {
            const notification = document.createElement('div');
            notification.className = `fixed top-4 right-4 p-4 rounded-lg shadow-lg z-50 ${
                type === 'success' ? 'bg-green-500 text-white' : 'bg-red-500 text-white'
            }`;
            notification.innerHTML = `
                <div class="flex items-center">
                    <div class="mr-3">${type === 'success' ? '✓' : '✗'}</div>
                    <div>
                        <div class="font-semibold">${title}</div>
                        <div class="text-sm">${message}</div>
                    </div>
                </div>
            `;
            document.body.appendChild(notification);
            setTimeout(() => notification.remove(), 3000);
        }

        let autoRefreshInterval;
        function startAutoRefresh() {
            autoRefreshInterval = setInterval(() => {
                if (!document.querySelector('.modal:not([hidden])')) {
                    window.location.reload();
                }
            }, 60000);
        }

        function stopAutoRefresh() {
            if (autoRefreshInterval) clearInterval(autoRefreshInterval);
        }

        function openExportModal() {
            document.dispatchEvent(new CustomEvent('modal-show', { detail: { name: 'exportModal' } }));
        }

        function closeExportModal() {
            document.dispatchEvent(new CustomEvent('modal-close', { detail: { name: 'exportModal' } }));
        }

        document.addEventListener('DOMContentLoaded', function() {
            startAutoRefresh();
            const dateRangeSelect = document.querySelector('#dateRangeSelect');
            const customDateRange = document.querySelector('#customDateRange');
            if (dateRangeSelect) {
                dateRangeSelect.addEventListener('change', function() {
                    customDateRange.classList.toggle('hidden', this.value !== 'custom');
                });
            }

            const exportForm = document.getElementById('exportForm');
            if (exportForm) {
                exportForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    const formData = new FormData(this);
                    const params = new URLSearchParams();
                    for (const [key, value] of formData.entries()) {
                        if (value) params.append(key, value);
                    }
                    params.append('export', 'pdf');
                    window.open('{{ route("branch.bookings.index") }}?' + params.toString(), '_blank');
                    setTimeout(() => closeExportModal(), 500);
                });
            }

            const paymentMethodSelect = document.querySelector('select[name="payment_method"]');
            const transactionRefField = document.querySelector('#transactionRefField');
            const mpesaField = document.querySelector('#mpesaField');
            if (paymentMethodSelect) {
                paymentMethodSelect.addEventListener('change', function() {
                    const method = this.value;
                    if (transactionRefField) transactionRefField.classList.add('hidden');
                    if (mpesaField) mpesaField.classList.add('hidden');
                    if (method === 'mpesa') {
                        if (mpesaField) mpesaField.classList.remove('hidden');
                        if (transactionRefField) transactionRefField.classList.remove('hidden');
                    } else if (method === 'bank_transfer' || method === 'card') {
                        if (transactionRefField) transactionRefField.classList.remove('hidden');
                    }
                });
            }

            const paymentForm = document.getElementById('paymentForm');
            if (paymentForm) {
                paymentForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    const formData = new FormData(this);
                    const data = Object.fromEntries(formData);
                    const bookingId = data.booking_id;
                    if (!data.amount || !data.payment_method || !data.status) {
                        showNotification('Error', 'Please fill in all required fields', 'error');
                        return;
                    }
                    if (data.payment_method === 'mpesa' && !data.mpesa_checkout_request_id) {
                        showNotification('Error', 'M-Pesa Transaction ID is required', 'error');
                        return;
                    }
                    if ((data.payment_method === 'bank_transfer' || data.payment_method === 'card') && !data.transaction_reference) {
                        showNotification('Error', 'Transaction reference is required for ' + data.payment_method, 'error');
                        return;
                    }
                    fetch(`/branch/bookings/${bookingId}/payment`, {
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
                            const bookingRef = data.booking?.booking_reference || 'Unknown';
                            const amount = data.payment?.amount || 0;
                            if (notificationManager) {
                                notificationManager.onPaymentReceived(bookingId, bookingRef, amount);
                            } else {
                                showNotification('Success', data.message, 'success');
                            }
                            closePaymentModal();
                            setTimeout(() => window.location.reload(), 1000);
                        } else {
                            if (notificationManager) {
                                notificationManager.onBookingError(data.message || 'Error recording payment');
                            } else {
                                showNotification('Error', data.message || 'Error recording payment', 'error');
                            }
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        if (notificationManager) {
                            notificationManager.onBookingError('Error recording payment');
                        } else {
                            showNotification('Error', 'Error recording payment', 'error');
                        }
                    });
                });
            }
        });

        document.addEventListener('visibilitychange', function() {
            if (document.hidden) {
                stopAutoRefresh();
            } else {
                startAutoRefresh();
            }
        });

        // Additional functions for enhanced booking management
        function sendBookingReminder(bookingId) {
            if (confirm('Send reminder to client for this booking?')) {
                fetch(`/branch/bookings/${bookingId}/reminder`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        if (window.notificationManager) {
                            window.notificationManager.addNotification('Reminder Sent', data.message, 'success');
                        } else {
                            showNotification('Success', data.message, 'success');
                        }
                    } else {
                        showNotification('Error', data.message || 'Error sending reminder', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('Error', 'Error sending reminder', 'error');
                });
            }
        }

        function openNotesModal(bookingId) {
            // This would open a modal to add notes to the booking
            // For now, using a simple prompt
            const note = prompt('Add a note for this booking:');
            if (note && note.trim()) {
                fetch(`/branch/bookings/${bookingId}/notes`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ note: note.trim() })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        if (window.notificationManager) {
                            window.notificationManager.addNotification('Note Added', 'Note has been added to the booking', 'success');
                        } else {
                            showNotification('Success', 'Note added successfully', 'success');
                        }
                        setTimeout(() => window.location.reload(), 1000);
                    } else {
                        showNotification('Error', data.message || 'Error adding note', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('Error', 'Error adding note', 'error');
                });
            }
        }

        // Enhanced table row animations
        document.addEventListener('DOMContentLoaded', function() {
            // Add entrance animations to table rows
            const tableRows = document.querySelectorAll('tbody tr[data-booking-id]');
            tableRows.forEach((row, index) => {
                row.style.opacity = '0';
                row.style.transform = 'translateY(20px)';
                setTimeout(() => {
                    row.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                    row.style.opacity = '1';
                    row.style.transform = 'translateY(0)';
                }, index * 50);
            });

            // Add hover effects for better UX
            tableRows.forEach(row => {
                row.addEventListener('mouseenter', function() {
                    this.classList.add('scale-[1.01]', 'shadow-md');
                });
                row.addEventListener('mouseleave', function() {
                    this.classList.remove('scale-[1.01]', 'shadow-md');
                });
            });
        });
    </script>
    @endpush
</x-layouts.app>