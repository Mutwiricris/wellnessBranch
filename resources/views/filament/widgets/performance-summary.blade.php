<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            📊 Business Performance Summary
        </x-slot>

        <div class="space-y-6">
            {{-- Weekly Summary --}}
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="bg-gradient-to-r from-blue-500 to-blue-600 rounded-lg p-4 text-white">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-blue-100 text-sm">This Week</p>
                            <p class="text-2xl font-bold">{{ $weekly_summary['current']['total_bookings'] }}</p>
                            <p class="text-blue-100 text-xs">Total Bookings</p>
                        </div>
                        <div class="text-right">
                            @if($weekly_summary['booking_change'] >= 0)
                                <span class="inline-flex items-center text-green-200 text-sm">
                                    ↗️ +{{ $weekly_summary['booking_change'] }}%
                                </span>
                            @else
                                <span class="inline-flex items-center text-red-200 text-sm">
                                    ↘️ {{ $weekly_summary['booking_change'] }}%
                                </span>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="bg-gradient-to-r from-green-500 to-green-600 rounded-lg p-4 text-white">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-green-100 text-sm">Revenue</p>
                            <p class="text-2xl font-bold">{{ number_format($weekly_summary['current']['revenue'], 0) }}</p>
                            <p class="text-green-100 text-xs">KES This Week</p>
                        </div>
                        <div class="text-right">
                            @if($weekly_summary['revenue_change'] >= 0)
                                <span class="inline-flex items-center text-green-200 text-sm">
                                    ↗️ +{{ $weekly_summary['revenue_change'] }}%
                                </span>
                            @else
                                <span class="inline-flex items-center text-red-200 text-sm">
                                    ↘️ {{ $weekly_summary['revenue_change'] }}%
                                </span>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="bg-gradient-to-r from-purple-500 to-purple-600 rounded-lg p-4 text-white">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-purple-100 text-sm">Completion Rate</p>
                            <p class="text-2xl font-bold">{{ $weekly_summary['completion_rate'] }}%</p>
                            <p class="text-purple-100 text-xs">This Week</p>
                        </div>
                        <div class="text-2xl">
                            @if($weekly_summary['completion_rate'] >= 90)
                                🎯
                            @elseif($weekly_summary['completion_rate'] >= 75)
                                👍
                            @else
                                ⚠️
                            @endif
                        </div>
                    </div>
                </div>

                <div class="bg-gradient-to-r from-orange-500 to-orange-600 rounded-lg p-4 text-white">
                    <div>
                        <p class="text-orange-100 text-sm">Pending</p>
                        <p class="text-2xl font-bold">{{ $weekly_summary['current']['pending_bookings'] }}</p>
                        <p class="text-orange-100 text-xs">Need Attention</p>
                    </div>
                </div>
            </div>

            {{-- Alerts Section --}}
            @if(!empty($alerts))
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                    <h3 class="text-lg font-semibold text-yellow-800 mb-3">🚨 Alerts & Recommendations</h3>
                    <div class="space-y-2">
                        @foreach($alerts as $alert)
                            <div class="flex items-start space-x-3 p-3 rounded-md 
                                @if($alert['type'] === 'error') bg-red-100 text-red-800
                                @elseif($alert['type'] === 'warning') bg-yellow-100 text-yellow-800
                                @else bg-blue-100 text-blue-800
                                @endif">
                                <span class="text-lg">{{ $alert['icon'] }}</span>
                                <div>
                                    <p class="font-medium">{{ $alert['title'] }}</p>
                                    <p class="text-sm opacity-90">{{ $alert['message'] }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Performance Grid --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                {{-- Top Performers --}}
                <div class="bg-white border border-gray-200 rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">🏆 Top Performers (This Week)</h3>
                    @if(!empty($top_performers))
                        <div class="space-y-3">
                            @foreach($top_performers as $index => $performer)
                                <div class="flex items-center justify-between p-3 rounded-lg bg-gray-50">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-3 h-3 rounded-full" style="background-color: {{ $performer['color'] ?? '#6b7280' }}"></div>
                                        <div>
                                            <p class="font-medium text-gray-900">{{ $performer['name'] }}</p>
                                            <p class="text-sm text-gray-500">{{ $performer['bookings'] }} bookings</p>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <p class="font-semibold text-gray-900">KES {{ number_format($performer['revenue'], 0) }}</p>
                                        <p class="text-sm text-gray-500">{{ $performer['completion_rate'] }}% complete</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-gray-500 text-center py-4">No performance data available</p>
                    @endif
                </div>

                {{-- Service Performance --}}
                <div class="bg-white border border-gray-200 rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">📋 Top Services (This Week)</h3>
                    @if(!empty($service_performance))
                        <div class="space-y-3">
                            @foreach($service_performance as $service)
                                <div class="flex items-center justify-between p-3 rounded-lg bg-gray-50">
                                    <div>
                                        <p class="font-medium text-gray-900">{{ $service['name'] }}</p>
                                        <p class="text-sm text-gray-500">{{ $service['bookings'] }} bookings • {{ $service['duration'] }}min</p>
                                    </div>
                                    <div class="text-right">
                                        <p class="font-semibold text-gray-900">KES {{ number_format($service['revenue'], 0) }}</p>
                                        <p class="text-sm text-gray-500">KES {{ number_format($service['price'], 0) }} each</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-gray-500 text-center py-4">No service data available</p>
                    @endif
                </div>
            </div>

            {{-- Payment Insights --}}
            <div class="bg-white border border-gray-200 rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">💳 Payment Insights (This Week)</h3>
                <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-4">
                    <div class="text-center">
                        <p class="text-2xl font-bold text-blue-600">{{ $payment_insights['total'] }}</p>
                        <p class="text-sm text-gray-500">Total</p>
                    </div>
                    <div class="text-center">
                        <p class="text-2xl font-bold text-green-600">{{ $payment_insights['completed'] }}</p>
                        <p class="text-sm text-gray-500">Completed</p>
                    </div>
                    <div class="text-center">
                        <p class="text-2xl font-bold text-yellow-600">{{ $payment_insights['pending'] }}</p>
                        <p class="text-sm text-gray-500">Pending</p>
                    </div>
                    <div class="text-center">
                        <p class="text-2xl font-bold text-red-600">{{ $payment_insights['failed'] }}</p>
                        <p class="text-sm text-gray-500">Failed</p>
                    </div>
                    <div class="text-center">
                        <p class="text-2xl font-bold text-purple-600">{{ $payment_insights['success_rate'] }}%</p>
                        <p class="text-sm text-gray-500">Success Rate</p>
                    </div>
                </div>
                
                @if(!empty($payment_insights['methods']))
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                        @foreach($payment_insights['methods'] as $method => $count)
                            <div class="bg-gray-50 rounded-lg p-3 text-center">
                                <p class="font-semibold text-gray-900">{{ $count }}</p>
                                <p class="text-sm text-gray-500 capitalize">{{ str_replace('_', ' ', $method) }}</p>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Weekly Trend --}}
            <div class="bg-white border border-gray-200 rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">📈 7-Day Trend</h3>
                @if(!empty($trends))
                    <div class="grid grid-cols-7 gap-2">
                        @foreach($trends as $day)
                            <div class="text-center p-3 rounded-lg bg-gray-50">
                                <p class="text-xs text-gray-500 mb-1">{{ $day['date'] }}</p>
                                <p class="font-semibold text-gray-900">{{ $day['bookings'] }}</p>
                                <p class="text-xs text-gray-500">KES {{ number_format($day['revenue'], 0) }}</p>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-gray-500 text-center py-4">No trend data available</p>
                @endif
            </div>

            {{-- Monthly Overview --}}
            <div class="bg-gradient-to-r from-indigo-500 to-purple-600 rounded-lg p-6 text-white">
                <h3 class="text-xl font-semibold mb-4">🗓️ Monthly Overview</h3>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div>
                        <p class="text-indigo-100 text-sm">Total Revenue</p>
                        <p class="text-2xl font-bold">KES {{ number_format($monthly_summary['revenue'], 0) }}</p>
                    </div>
                    <div>
                        <p class="text-indigo-100 text-sm">Growth</p>
                        <p class="text-2xl font-bold">
                            @if($monthly_summary['revenue_growth'] >= 0)
                                +{{ $monthly_summary['revenue_growth'] }}%
                            @else
                                {{ $monthly_summary['revenue_growth'] }}%
                            @endif
                        </p>
                    </div>
                    <div>
                        <p class="text-indigo-100 text-sm">Total Bookings</p>
                        <p class="text-2xl font-bold">{{ $monthly_summary['total_bookings'] }}</p>
                    </div>
                    <div>
                        <p class="text-indigo-100 text-sm">Daily Average</p>
                        <p class="text-2xl font-bold">KES {{ number_format($monthly_summary['avg_daily_revenue'], 0) }}</p>
                    </div>
                </div>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>