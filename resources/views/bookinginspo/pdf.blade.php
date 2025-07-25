<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Report - {{ $branch->name ?? 'Wellness Spa' }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            font-size: 14px;
            line-height: 1.5;
            background-color: #f9fafb;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background-color: white;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        
        /* Header styles */
        .header {
            border-bottom: 4px solid #1f2937;
            padding: 24px;
            margin-bottom: 32px;
            text-align: center;
        }
        
        .company-name {
            font-size: 30px;
            font-weight: bold;
            color: #1f2937;
            margin-bottom: 8px;
        }
        
        .branch-info {
            color: #6b7280;
            margin-bottom: 16px;
        }
        
        .branch-info p {
            margin: 0;
            line-height: 1.4;
        }
        
        .report-title {
            font-size: 20px;
            font-weight: bold;
            color: #374151;
            margin-top: 16px;
        }
        
        .report-subtitle {
            font-size: 12px;
            color: #9ca3af;
            margin-top: 8px;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
        }
        
        /* Content container */
        .content {
            padding: 24px;
        }
        
        /* Statistics section */
        .stats-overview {
            margin-bottom: 32px;
        }
        
        .overview-title {
            font-size: 18px;
            font-weight: bold;
            color: #1f2937;
            margin-bottom: 24px;
            text-align: center;
            text-transform: uppercase;
            letter-spacing: 1px;
            border-bottom: 2px solid #1f2937;
            padding-bottom: 12px;
        }
        
        /* Primary KPIs Grid */
        .kpi-grid {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: stretch;
            gap: 16px;
            margin-bottom: 24px;
        }
        
        .kpi-card {
            flex: 1;
            min-width: 180px;
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            padding: 16px;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        
        .kpi-card.blue { border-left: 4px solid #3b82f6; }
        .kpi-card.green { border-left: 4px solid #10b981; }
        .kpi-card.purple { border-left: 4px solid #8b5cf6; }
        .kpi-card.yellow { border-left: 4px solid #f59e0b; }
        .kpi-card.cyan { border-left: 4px solid #06b6d4; }
        
        .kpi-value {
            font-size: 24px;
            font-weight: bold;
            color: #1f2937;
            margin-bottom: 4px;
        }
        
        .kpi-label {
            font-size: 12px;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
        }
        
        .kpi-sublabel {
            font-size: 10px;
            color: #94a3b8;
            margin-top: 4px;
        }
        
        /* Key Performance Metrics */
        .key-metrics {
            background: #f1f5f9;
            padding: 16px;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            margin-bottom: 24px;
        }
        
        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 16px;
            text-align: center;
        }
        
        .metric-item {
            padding: 0 16px;
            border-right: 1px solid #cbd5e1;
        }
        
        .metric-item:last-child {
            border-right: none;
        }
        
        .metric-value {
            font-size: 18px;
            font-weight: bold;
            color: #1f2937;
        }
        
        .metric-label {
            font-size: 12px;
            color: #64748b;
            text-transform: uppercase;
            margin-top: 4px;
        }
        
        /* Performance Indicators */
        .performance-indicators {
            background: white;
            padding: 16px;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
            margin-bottom: 24px;
        }
        
        .performance-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            text-align: center;
        }
        
        .indicator {
            padding: 12px;
        }
        
        .indicator-value {
            font-size: 18px;
            font-weight: bold;
            color: #374151;
        }
        
        .indicator-label {
            font-size: 12px;
            color: #6b7280;
            text-transform: uppercase;
            margin-top: 4px;
        }
        
        .indicator.good .indicator-value { color: #059669; }
        .indicator.warning .indicator-value { color: #d97706; }
        .indicator.danger .indicator-value { color: #dc2626; }
        
        /* Table styles */
        .table-container {
            margin-top: 20px;
        }
        
        .section-title {
            font-size: 16px;
            font-weight: bold;
            color: #1f2937;
            margin-bottom: 15px;
            border-bottom: 2px solid #e5e7eb;
            padding-bottom: 5px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        th, td {
            border: 1px solid #e5e7eb;
            padding: 8px;
            text-align: left;
            font-size: 10px;
        }
        
        th {
            background-color: #f3f4f6;
            font-weight: bold;
            color: #374151;
        }
        
        tr:nth-child(even) {
            background-color: #f9fafb;
        }
        
        /* Status badges */
        .status-badge {
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .status-pending { background: #fef3c7; color: #92400e; }
        .status-confirmed { background: #dbeafe; color: #1e40af; }
        .status-in_progress { background: #e9d5ff; color: #7c3aed; }
        .status-completed { background: #d1fae5; color: #065f46; }
        .status-cancelled { background: #fee2e2; color: #dc2626; }
        .status-no_show { background: #f3f4f6; color: #6b7280; }
        
        /* Payment status badges */
        .payment-pending { background: #fef3c7; color: #92400e; }
        .payment-completed { background: #d1fae5; color: #065f46; }
        .payment-failed { background: #fee2e2; color: #dc2626; }
        .payment-refunded { background: #f3f4f6; color: #6b7280; }
        
        /* Footer */
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            color: #9ca3af;
            border-top: 1px solid #e5e7eb;
            padding-top: 15px;
        }
        
        /* Service stats */
        .service-stats {
            margin-bottom: 30px;
        }
        
        .service-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 12px;
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            margin-bottom: 5px;
        }
        
        .service-name {
            font-weight: bold;
            color: #374151;
        }
        
        .service-count {
            font-size: 11px;
            color: #6b7280;
        }
        
        .service-revenue {
            font-weight: bold;
            color: #059669;
        }
        
        /* Responsive adjustments */
        @media print {
            .stats-flex-container {
                flex-wrap: nowrap;
                gap: 8px;
            }
            
            .stat-card {
                min-width: 120px;
                padding: 12px 8px;
            }
            
            .stat-value {
                font-size: 16px;
            }
            
            .stat-label {
                font-size: 9px;
            }
            
            .key-metrics {
                padding: 12px;
            }
            
            .metric-value {
                font-size: 14px;
            }
            
            th, td {
                font-size: 9px;
                padding: 6px;
            }
        }
    </style>
</head>
<body>
    <!-- Header with letterhead -->
    <div class="header">
        <div class="company-name">{{ $branch->name ?? 'Wellness Spa' }}</div>
        <div class="branch-info">
            @if($branch->address)
                {{ $branch->address }}<br>
            @endif
            @if($branch->phone)
                Phone: {{ $branch->phone }} | 
            @endif
            @if($branch->email)
                Email: {{ $branch->email }}
            @endif
        </div>
        <div class="report-title">
            @switch($request->report_type)
                @case('summary')
                    Bookings Summary Report
                    @break
                @case('financial')
                    Financial Report
                    @break
                @default
                    Detailed Bookings Report
            @endswitch
        </div>
        <div class="report-subtitle">
            Report Period: 
            @switch($request->date_range)
                @case('today')
                    Today ({{ now()->format('F j, Y') }})
                    @break
                @case('yesterday')
                    Yesterday ({{ now()->subDay()->format('F j, Y') }})
                    @break
                @case('this_week')
                    This Week ({{ now()->startOfWeek()->format('M j') }} - {{ now()->endOfWeek()->format('M j, Y') }})
                    @break
                @case('last_week')
                    Last Week ({{ now()->subWeek()->startOfWeek()->format('M j') }} - {{ now()->subWeek()->endOfWeek()->format('M j, Y') }})
                    @break
                @case('this_month')
                    This Month ({{ now()->format('F Y') }})
                @break
                @case('last_month')
                    Last Month ({{ now()->subMonth()->format('F Y') }})
                    @break
                @case('custom')
                    Custom Range
                    @if($request->date_from && $request->date_to)
                        ({{ \Carbon\Carbon::parse($request->date_from)->format('M j, Y') }} - {{ \Carbon\Carbon::parse($request->date_to)->format('M j, Y') }})
                    @endif
                    @break
                @default
                    All Time
            @endswitch
            | Generated: {{ now()->format('F j, Y g:i A') }}
        </div>
    </div>

    <!-- Statistics Overview -->
    <div class="stats-overview">
        <div class="overview-title">Executive Summary</div>
        
        <!-- Primary KPIs -->
        <div class="kpi-grid">
            <div class="kpi-card blue">
                <div class="kpi-value">{{ $stats['total_bookings'] }}</div>
                <div class="kpi-label">Total Bookings</div>
                <div class="kpi-sublabel">{{ $stats['pending_bookings'] }} pending</div>
            </div>
            <div class="kpi-card green">
                <div class="kpi-value">KES {{ number_format($stats['total_revenue'], 0) }}</div>
                <div class="kpi-label">Total Revenue</div>
                <div class="kpi-sublabel">Confirmed payments</div>
            </div>
            <div class="kpi-card purple">
                <div class="kpi-value">{{ number_format($stats['completion_rate'], 1) }}%</div>
                <div class="kpi-label">Completion Rate</div>
                <div class="kpi-sublabel">{{ $stats['completed_bookings'] }} completed</div>
            </div>
            <div class="kpi-card yellow">
                <div class="kpi-value">KES {{ number_format($stats['pending_payments'], 0) }}</div>
                <div class="kpi-label">Pending Payments</div>
                <div class="kpi-sublabel">{{ $stats['total_bookings'] - $stats['completed_bookings'] }} bookings</div>
            </div>
            <div class="kpi-card cyan">
                <div class="kpi-value">KES {{ number_format($stats['average_booking_value'], 0) }}</div>
                <div class="kpi-label">Average Value</div>
                <div class="kpi-sublabel">Per booking</div>
            </div>
        </div>
        
        <!-- Key Performance Metrics -->
        <div class="key-metrics">
            <div class="metrics-grid">
                <div class="metric-item">
                    <div class="metric-value">{{ $stats['confirmed_bookings'] }}</div>
                    <div class="metric-label">Confirmed</div>
                </div>
                <div class="metric-item">
                    <div class="metric-value">{{ $stats['cancelled_bookings'] }}</div>
                    <div class="metric-label">Cancelled</div>
                </div>
                <div class="metric-item">
                    <div class="metric-value">{{ $stats['no_show_bookings'] }}</div>
                    <div class="metric-label">No Shows</div>
                </div>
                <div class="metric-item">
                    <div class="metric-value">{{ number_format($stats['cancellation_rate'], 1) }}%</div>
                    <div class="metric-label">Cancel Rate</div>
                </div>
                <div class="metric-item">
                    <div class="metric-value">{{ number_format($stats['no_show_rate'], 1) }}%</div>
                    <div class="metric-label">No Show Rate</div>
                </div>
            </div>
        </div>
        
        <!-- Performance Indicators -->
        <div class="performance-indicators">
            <div class="performance-grid">
                <div class="indicator {{ $stats['completion_rate'] >= 80 ? 'good' : ($stats['completion_rate'] >= 60 ? 'warning' : 'danger') }}">
                    <div class="indicator-value">{{ number_format($stats['completion_rate'], 1) }}%</div>
                    <div class="indicator-label">Service Completion</div>
                </div>
                <div class="indicator {{ $stats['cancellation_rate'] <= 10 ? 'good' : ($stats['cancellation_rate'] <= 20 ? 'warning' : 'danger') }}">
                    <div class="indicator-value">{{ number_format($stats['cancellation_rate'], 1) }}%</div>
                    <div class="indicator-label">Cancellation Rate</div>
                </div>
                <div class="indicator {{ $stats['no_show_rate'] <= 5 ? 'good' : ($stats['no_show_rate'] <= 15 ? 'warning' : 'danger') }}">
                    <div class="indicator-value">{{ number_format($stats['no_show_rate'], 1) }}%</div>
                    <div class="indicator-label">No Show Rate</div>
                </div>
                <div class="indicator {{ $stats['pending_payments'] <= ($stats['total_revenue'] * 0.2) ? 'good' : 'warning' }}">
                    <div class="indicator-value">KES {{ number_format($stats['pending_payments'], 0) }}</div>
                    <div class="indicator-label">Outstanding</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Popular Services -->
    @if($stats['service_stats']->count() > 0)
        <div class="service-stats">
            <div class="section-title">Popular Services</div>
            @foreach($stats['service_stats'] as $service)
                <div class="service-item">
                    <div>
                        <div class="service-name">{{ $service['service'] }}</div>
                        <div class="service-count">{{ $service['count'] }} bookings</div>
                    </div>
                    <div class="service-revenue">KES {{ number_format($service['revenue'], 2) }}</div>
                </div>
            @endforeach
        </div>
    @endif

    <!-- Detailed Bookings Table -->
    @if($request->report_type !== 'summary')
        <div class="table-container">
            <div class="section-title">Booking Details</div>
            <table>
                <thead>
                    <tr>
                        <th>Reference</th>
                        <th>Client</th>
                        <th>Service</th>
                        <th>Staff</th>
                        <th>Date & Time</th>
                        <th>Status</th>
                        <th>Payment</th>
                        <th>Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($bookings as $booking)
                        <tr>
                            <td>{{ $booking->booking_reference }}</td>
                            <td>
                                <strong>{{ $booking->client->full_name }}</strong><br>
                                <small>{{ $booking->client->phone }}</small>
                            </td>
                            <td>{{ $booking->service->name }}</td>
                            <td>{{ $booking->staff ? $booking->staff->name : 'Unassigned' }}</td>
                            <td>
                                {{ $booking->appointment_date->format('M j, Y') }}<br>
                                <small>{{ $booking->start_time->format('H:i') }} - {{ $booking->end_time->format('H:i') }}</small>
                            </td>
                            <td>
                                <span class="status-badge status-{{ $booking->status }}">
                                    {{ ucfirst(str_replace('_', ' ', $booking->status)) }}
                                </span>
                            </td>
                            <td>
                                <span class="status-badge payment-{{ $booking->payment_status }}">
                                    {{ ucfirst($booking->payment_status) }}
                                </span><br>
                                <small>{{ ucfirst(str_replace('_', ' ', $booking->payment_method)) }}</small>
                            </td>
                            <td>KES {{ number_format($booking->total_amount, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 20px; color: #6b7280;">
                                No bookings found for the selected criteria.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endif

    <!-- Financial Summary (for financial report) -->
    @if($request->report_type === 'financial')
        <div class="table-container">
            <div class="section-title">Financial Summary</div>
            <table>
                <thead>
                    <tr>
                        <th>Metric</th>
                        <th>Count</th>
                        <th>Amount (KES)</th>
                        <th>Percentage</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Completed Bookings</td>
                        <td>{{ $stats['completed_bookings'] }}</td>
                        <td>{{ number_format($stats['total_revenue'], 2) }}</td>
                        <td>{{ number_format($stats['completion_rate'], 1) }}%</td>
                    </tr>
                    <tr>
                        <td>Pending Payments</td>
                        <td>{{ $stats['total_bookings'] - $stats['completed_bookings'] }}</td>
                        <td>{{ number_format($stats['pending_payments'], 2) }}</td>
                        <td>{{ number_format(100 - $stats['completion_rate'], 1) }}%</td>
                    </tr>
                    <tr>
                        <td>Cancelled Bookings</td>
                        <td>{{ $stats['cancelled_bookings'] }}</td>
                        <td>-</td>
                        <td>{{ number_format($stats['cancellation_rate'], 1) }}%</td>
                    </tr>
                    <tr>
                        <td>No-Show Bookings</td>
                        <td>{{ $stats['no_show_bookings'] }}</td>
                        <td>-</td>
                        <td>{{ number_format($stats['no_show_rate'], 1) }}%</td>
                    </tr>
                </tbody>
            </table>
        </div>
    @endif

    <!-- Footer -->
    <div class="footer">
        <p><strong>{{ $branch->name ?? 'Wellness Spa' }}</strong> - Professional Spa Management System</p>
        <p>This report was generated automatically on {{ now()->format('F j, Y \a\t g:i A') }}.</p>
        <p>For inquiries, contact: {{ $branch->email ?? 'info@wellnessspa.com' }} | {{ $branch->phone ?? '+254 700 000 000' }}</p>
    </div>
</body>
</html>