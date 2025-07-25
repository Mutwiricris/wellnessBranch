<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Header with Calendar Title --}}
        <div class="bg-white rounded-lg shadow p-6">
            <h1 class="text-2xl font-bold text-gray-900 mb-4">Booking Calendar</h1>
            
            {{-- Calendar Filters --}}
            <div class="flex flex-wrap gap-4 items-center justify-between">
                <div class="flex flex-wrap gap-4">
                    {{-- Date Selector --}}
                    <div class="flex items-center space-x-2">
                        <label class="text-sm font-medium text-gray-700">Date:</label>
                        <input 
                            type="date" 
                            wire:model.live="selectedDate"
                            class="border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-primary-600 focus:border-transparent"
                        >
                    </div>
                    
                    {{-- Staff Filter --}}
                    <div class="flex items-center space-x-2">
                        <label class="text-sm font-medium text-gray-700">Staff:</label>
                        <select 
                            wire:model.live="selectedStaff"
                            class="border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-primary-600 focus:border-transparent"
                        >
                            <option value="">All Staff</option>
                            @foreach($this->getStaffOptions() as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    
                    {{-- Status Filter --}}
                    <div class="flex items-center space-x-2">
                        <label class="text-sm font-medium text-gray-700">Status:</label>
                        <select 
                            wire:model.live="selectedStatus"
                            class="border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-primary-600 focus:border-transparent"
                        >
                            <option value="">All Statuses</option>
                            <option value="pending">Pending</option>
                            <option value="confirmed">Confirmed</option>
                            <option value="in_progress">In Progress</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                </div>
                
                {{-- Quick Date Navigation --}}
                <div class="flex space-x-2">
                    <button 
                        wire:click="$set('selectedDate', '{{ now()->format('Y-m-d') }}')"
                        class="px-3 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200 transition-colors"
                    >
                        Today
                    </button>
                    <button 
                        wire:click="$set('selectedDate', '{{ now()->addDay()->format('Y-m-d') }}')"
                        class="px-3 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200 transition-colors"
                    >
                        Tomorrow
                    </button>
                </div>
            </div>
        </div>
        
        {{-- Enhanced Overview Cards Grid --}}
        @php 
            $stats = $this->getTodayStats(); 
            $tenant = \Filament\Facades\Filament::getTenant();
            $baseQuery = \App\Models\Booking::where('branch_id', $tenant->id);
            
            // Enhanced stats calculations
            $todayBookings = $baseQuery->clone()->whereDate('appointment_date', $selectedDate);
            $yesterdayBookings = $baseQuery->clone()->whereDate('appointment_date', \Carbon\Carbon::parse($selectedDate)->subDay());
            
            $todayTotal = $todayBookings->count();
            $todayRevenue = $todayBookings->clone()->where('payment_status', 'completed')->sum('total_amount') ?? 0;
            $yesterdayTotal = $yesterdayBookings->count();
            $yesterdayRevenue = $yesterdayBookings->clone()->where('payment_status', 'completed')->sum('total_amount') ?? 0;
            
            $weeklyTotal = $baseQuery->clone()->whereBetween('appointment_date', [
                now()->startOfWeek()->format('Y-m-d'),
                now()->endOfWeek()->format('Y-m-d')
            ])->count();
            
            $monthlyTotal = $baseQuery->clone()->whereBetween('appointment_date', [
                now()->startOfMonth()->format('Y-m-d'),
                now()->endOfMonth()->format('Y-m-d')
            ])->count();
            
            $monthlyRevenue = $baseQuery->clone()->whereBetween('appointment_date', [
                now()->startOfMonth()->format('Y-m-d'),
                now()->endOfMonth()->format('Y-m-d')
            ])->where('payment_status', 'completed')->sum('total_amount') ?? 0;
            
            $totalPending = $baseQuery->clone()->where('appointment_date', '>=', today())->where('status', 'pending')->count();
            $totalBookings = $baseQuery->clone()->count();
            
            $dailyChange = $yesterdayTotal > 0 ? (($todayTotal - $yesterdayTotal) / $yesterdayTotal) * 100 : 0;
            $revenueChange = $yesterdayRevenue > 0 ? (($todayRevenue - $yesterdayRevenue) / $yesterdayRevenue) * 100 : 0;
        @endphp
        
        <div class="flex flex-col space-y-4">
            {{-- Today's Bookings Row --}}
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <div class="flex items-center justify-center w-12 h-12 bg-blue-100 rounded-lg mr-4">
                            <svg class="w-6 h-6 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">Today's Bookings</h3>
                            <p class="text-sm text-gray-500">{{ $stats['completed'] ?? 0 }} completed, {{ $stats['pending'] ?? 0 }} pending</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="text-3xl font-bold text-gray-900">{{ $todayTotal }}</div>
                        @if($dailyChange != 0)
                            <div class="flex items-center justify-end mt-1">
                                <span class="flex items-center text-sm {{ $dailyChange >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                    @if($dailyChange >= 0)
                                        <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M3.293 9.707a1 1 0 010-1.414l6-6a1 1 0 011.414 0l6 6a1 1 0 01-1.414 1.414L10 4.414 4.707 9.707a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                                        </svg>
                                    @else
                                        <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 10.293a1 1 0 010 1.414l-6 6a1 1 0 01-1.414 0l-6-6a1 1 0 111.414-1.414L10 15.586l5.293-5.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                        </svg>
                                    @endif
                                    {{ number_format(abs($dailyChange), 1) }}% from yesterday
                                </span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Today's Revenue Row --}}
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <div class="flex items-center justify-center w-12 h-12 bg-green-100 rounded-lg mr-4">
                            <svg class="w-6 h-6 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.071.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z"/>
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.563-.649-1.413-1.076-2.354-1.253V5z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">Today's Revenue</h3>
                            <p class="text-sm text-gray-500">From {{ $todayTotal }} bookings</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="text-3xl font-bold text-gray-900">KES {{ number_format($todayRevenue, 0) }}</div>
                        @if($revenueChange != 0)
                            <div class="flex items-center justify-end mt-1">
                                <span class="flex items-center text-sm {{ $revenueChange >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                    @if($revenueChange >= 0)
                                        <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M3.293 9.707a1 1 0 010-1.414l6-6a1 1 0 011.414 0l6 6a1 1 0 01-1.414 1.414L10 4.414 4.707 9.707a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                                        </svg>
                                    @else
                                        <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 10.293a1 1 0 010 1.414l-6 6a1 1 0 01-1.414 0l-6-6a1 1 0 111.414-1.414L10 15.586l5.293-5.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                        </svg>
                                    @endif
                                    {{ number_format(abs($revenueChange), 1) }}% from yesterday
                                </span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- This Week Row --}}
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <div class="flex items-center justify-center w-12 h-12 bg-purple-100 rounded-lg mr-4">
                            <svg class="w-6 h-6 text-purple-600" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">This Week</h3>
                            <p class="text-sm text-gray-500">Total bookings</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="text-3xl font-bold text-gray-900">{{ $weeklyTotal }}</div>
                    </div>
                </div>
            </div>

            {{-- This Month Row --}}
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <div class="flex items-center justify-center w-12 h-12 bg-indigo-100 rounded-lg mr-4">
                            <svg class="w-6 h-6 text-indigo-600" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">This Month</h3>
                            <p class="text-sm text-gray-500">KES {{ number_format($monthlyRevenue, 0) }} revenue</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="text-3xl font-bold text-gray-900">{{ $monthlyTotal }}</div>
                    </div>
                </div>
            </div>

            {{-- Pending Bookings Row --}}
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <div class="flex items-center justify-center w-12 h-12 bg-yellow-100 rounded-lg mr-4">
                            <svg class="w-6 h-6 text-yellow-600" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">Pending Bookings</h3>
                            <p class="text-sm text-gray-500">Need confirmation</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="text-3xl font-bold text-gray-900">{{ $totalPending }}</div>
                    </div>
                </div>
            </div>

            {{-- Total Bookings Row --}}
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <div class="flex items-center justify-center w-12 h-12 bg-gray-100 rounded-lg mr-4">
                            <svg class="w-6 h-6 text-gray-600" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M7 3a1 1 0 000 2h6a1 1 0 100-2H7zM4 7a1 1 0 011-1h10a1 1 0 110 2H5a1 1 0 01-1-1zM2 11a2 2 0 012-2h12a2 2 0 012 2v4a2 2 0 01-2 2H4a2 2 0 01-2-2v-4z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">Total Bookings</h3>
                            <p class="text-sm text-gray-500">All time</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="text-3xl font-bold text-gray-900">{{ $totalBookings }}</div>
                    </div>
                </div>
            </div>
        </div>
        
        {{-- Calendar Container --}}
        <div class="bg-white rounded-lg shadow p-6">
            <div id="calendar" wire:ignore></div>
        </div>
        
        {{-- Bookings List for Selected Date --}}
        <div class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">
                    Bookings for {{ \Carbon\Carbon::parse($selectedDate)->format('l, F j, Y') }}
                </h3>
            </div>
            <div class="p-6">
                @php
                    $tenant = \Filament\Facades\Filament::getTenant();
                    $dayBookings = \App\Models\Booking::with(['client', 'service', 'staff'])
                        ->where('branch_id', $tenant->id)
                        ->whereDate('appointment_date', $selectedDate)
                        ->when($selectedStaff, fn($q) => $q->where('staff_id', $selectedStaff))
                        ->when($selectedStatus, fn($q) => $q->where('status', $selectedStatus))
                        ->orderBy('start_time')
                        ->get();
                @endphp
                
                @if($dayBookings->count() > 0)
                    <div class="grid gap-4">
                        @foreach($dayBookings as $booking)
                            <div class="border rounded-lg p-4 hover:bg-gray-50 transition-colors">
                                <div class="flex justify-between items-start">
                                    <div class="flex-1">
                                        <div class="flex items-center space-x-4">
                                            <div class="text-lg font-semibold text-gray-900">
                                                {{ $booking->start_time }} - {{ $booking->end_time }}
                                            </div>
                                            <div class="px-2 py-1 text-xs font-medium rounded-full
                                                @if($booking->status === 'pending') bg-yellow-100 text-yellow-800
                                                @elseif($booking->status === 'confirmed') bg-blue-100 text-blue-800
                                                @elseif($booking->status === 'in_progress') bg-purple-100 text-purple-800
                                                @elseif($booking->status === 'completed') bg-green-100 text-green-800
                                                @elseif($booking->status === 'cancelled') bg-red-100 text-red-800
                                                @else bg-gray-100 text-gray-800
                                                @endif
                                            ">
                                                {{ ucfirst($booking->status) }}
                                            </div>
                                        </div>
                                        <div class="mt-2">
                                            <div class="text-sm font-medium text-gray-900">
                                                {{ $booking->client->first_name ?? '' }} {{ $booking->client->last_name ?? '' }}
                                            </div>
                                            <div class="text-sm text-gray-600">
                                                {{ $booking->service->name ?? 'No Service' }}
                                            </div>
                                            <div class="text-sm text-gray-600">
                                                Staff: {{ $booking->staff->name ?? 'Unassigned' }}
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <div class="text-sm font-medium text-gray-900">
                                            KES {{ number_format($booking->total_amount ?? 0, 2) }}
                                        </div>
                                        <a 
                                            href="{{ \App\Filament\Resources\BookingResource::getUrl('view', ['record' => $booking]) }}"
                                            class="inline-flex items-center px-3 py-1 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                                        >
                                            View
                                        </a>
                                    </div>
                                </div>
                                @if($booking->notes)
                                    <div class="mt-2 text-sm text-gray-600">
                                        <strong>Notes:</strong> {{ $booking->notes }}
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-8 text-gray-500">
                        <div class="text-6xl mb-4">📅</div>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">No bookings for this date</h3>
                        <p class="text-gray-500">Select a different date or create a new booking.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
    
    {{-- Include FullCalendar CSS and JS --}}
    @push('styles')
        <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css" rel="stylesheet">
        <style>
            .fc-event {
                cursor: pointer !important;
                border-radius: 4px !important;
                font-size: 12px !important;
            }
            .fc-event-title {
                font-weight: 600 !important;
            }
            .fc-toolbar-title {
                font-size: 1.5rem !important;
                font-weight: 700 !important;
            }
            .fc-button {
                text-transform: none !important;
            }
            .fc-daygrid-event {
                margin: 1px 0 !important;
            }
        </style>
    @endpush
    
    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                let calendar;
                const calendarEl = document.getElementById('calendar');
                
                function initializeCalendar() {
                    try {
                        if (calendar) {
                            calendar.destroy();
                        }
                        
                        calendar = new FullCalendar.Calendar(calendarEl, {
                            initialView: 'dayGridMonth',
                            headerToolbar: {
                                left: 'prev,next today',
                                center: 'title',
                                right: 'dayGridMonth,timeGridWeek,timeGridDay'
                            },
                            height: 'auto',
                            events: @json($this->getCalendarEvents()),
                            eventClick: function(info) {
                                // Open booking in new tab/window
                                if (info.event.extendedProps.url) {
                                    window.open(info.event.extendedProps.url, '_blank');
                                }
                            },
                            eventDidMount: function(info) {
                                // Add tooltip with booking details
                                const props = info.event.extendedProps;
                                info.el.setAttribute('title', 
                                    `${props.client || 'Unknown Client'}\n` +
                                    `Service: ${props.service || 'No Service'}\n` +
                                    `Staff: ${props.staff || 'Unassigned'}\n` +
                                    `Status: ${props.status || 'Unknown'}\n` +
                                    `Amount: KES ${props.total_amount || 0}\n` +
                                    `Reference: ${props.booking_reference || 'N/A'}`
                                );
                            },
                            dateClick: function(info) {
                                // Update selected date when clicking on a date
                                @this.set('selectedDate', info.dateStr);
                            },
                            eventContent: function(arg) {
                                const props = arg.event.extendedProps;
                                return {
                                    html: `
                                        <div class="fc-event-main-frame">
                                            <div class="fc-event-title-container">
                                                <div class="fc-event-title fc-sticky font-semibold">${arg.event.title || 'Unknown'}</div>
                                                <div class="text-xs opacity-90">${props.subtitle || ''}</div>
                                                <div class="text-xs opacity-75">${props.staff || 'Unassigned'}</div>
                                            </div>
                                        </div>
                                    `
                                };
                            }
                        });
                        
                        calendar.render();
                    } catch (error) {
                        console.error('Calendar initialization error:', error);
                        calendarEl.innerHTML = '<div class="p-8 text-center text-gray-500">Error loading calendar. Please refresh the page.</div>';
                    }
                }
                
                // Initialize calendar
                initializeCalendar();
                
                // Listen for Livewire events to refresh calendar
                Livewire.on('refresh-calendar', () => {
                    setTimeout(() => {
                        initializeCalendar();
                    }, 100);
                });
                
                // Re-initialize calendar when Livewire updates
                document.addEventListener('livewire:updated', function() {
                    setTimeout(() => {
                        initializeCalendar();
                    }, 100);
                });
            });
        </script>
    @endpush
</x-filament-panels::page>