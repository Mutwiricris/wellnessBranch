<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics Export - {{ $period }}</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; color: #333; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #ddd; padding-bottom: 20px; }
        .metrics-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin: 20px 0; }
        .metric { background: #f8f9fa; padding: 15px; border-radius: 8px; text-align: center; border: 1px solid #e9ecef; }
        .metric-value { font-size: 24px; font-weight: bold; color: #2563eb; margin-bottom: 5px; }
        .metric-label { font-size: 14px; color: #6b7280; }
        .section { margin: 30px 0; }
        .section-title { font-size: 18px; font-weight: bold; margin-bottom: 15px; color: #1f2937; }
        .table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        .table th, .table td { border: 1px solid #e5e7eb; padding: 12px; text-align: left; }
        .table th { background: #f3f4f6; font-weight: bold; }
        .table tr:nth-child(even) { background: #f9fafb; }
        .footer { margin-top: 40px; text-align: center; color: #6b7280; font-size: 12px; border-top: 1px solid #e5e7eb; padding-top: 20px; }
        .success { color: #059669; }
        .warning { color: #d97706; }
        .danger { color: #dc2626; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Analytics Dashboard Export</h1>
        <p><strong>Period:</strong> {{ $period }}</p>
        <p><strong>Generated:</strong> {{ now()->format('M j, Y H:i:s') }}</p>
    </div>

    <!-- Key Metrics -->
    <div class="section">
        <h2 class="section-title">Key Performance Metrics</h2>
        <div class="metrics-grid">
            <div class="metric">
                <div class="metric-value">{{ number_format($metrics['total_bookings']) }}</div>
                <div class="metric-label">Total Bookings</div>
            </div>
            <div class="metric">
                <div class="metric-value success">{{ number_format($metrics['completed_bookings']) }}</div>
                <div class="metric-label">Completed Bookings</div>
            </div>
            <div class="metric">
                <div class="metric-value">KES {{ number_format($metrics['total_revenue'], 2) }}</div>
                <div class="metric-label">Total Revenue</div>
            </div>
            <div class="metric">
                <div class="metric-value">KES {{ number_format($metrics['average_booking_value'], 2) }}</div>
                <div class="metric-label">Average Booking Value</div>
            </div>
            <div class="metric">
                <div class="metric-value">{{ number_format($metrics['completion_rate'], 1) }}%</div>
                <div class="metric-label">Completion Rate</div>
            </div>
            <div class="metric">
                <div class="metric-value">{{ number_format($metrics['unique_clients']) }}</div>
                <div class="metric-label">Unique Clients</div>
            </div>
        </div>
    </div>

    <!-- Popular Services -->
    @if($popular_services->count() > 0)
    <div class="section">
        <h2 class="section-title">Popular Services</h2>
        <table class="table">
            <thead>
                <tr>
                    <th>Service Name</th>
                    <th style="text-align: center;">Bookings</th>
                    <th style="text-align: right;">Revenue</th>
                </tr>
            </thead>
            <tbody>
                @foreach($popular_services as $service)
                <tr>
                    <td>{{ $service['name'] }}</td>
                    <td style="text-align: center;">{{ $service['count'] }}</td>
                    <td style="text-align: right;" class="success">KES {{ number_format($service['revenue'], 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <!-- Staff Performance -->
    @if($staff_performance->count() > 0)
    <div class="section">
        <h2 class="section-title">Staff Performance</h2>
        <table class="table">
            <thead>
                <tr>
                    <th>Staff Member</th>
                    <th style="text-align: center;">Total Bookings</th>
                    <th style="text-align: center;">Completed</th>
                    <th style="text-align: center;">Completion Rate</th>
                    <th style="text-align: right;">Revenue Generated</th>
                </tr>
            </thead>
            <tbody>
                @foreach($staff_performance as $staff)
                <tr>
                    <td>{{ $staff['name'] }}</td>
                    <td style="text-align: center;">{{ $staff['total_bookings'] }}</td>
                    <td style="text-align: center;" class="success">{{ $staff['completed_bookings'] }}</td>
                    <td style="text-align: center;">{{ number_format($staff['completion_rate'], 1) }}%</td>
                    <td style="text-align: right;" class="success">KES {{ number_format($staff['revenue'], 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <!-- Client Analytics -->
    <div class="section">
        <h2 class="section-title">Client Analytics</h2>
        <div class="metrics-grid">
            <div class="metric">
                <div class="metric-value">{{ $client_analytics['total_clients'] }}</div>
                <div class="metric-label">Total Clients</div>
            </div>
            <div class="metric">
                <div class="metric-value success">{{ $client_analytics['new_clients'] }}</div>
                <div class="metric-label">New Clients</div>
            </div>
            <div class="metric">
                <div class="metric-value">{{ $client_analytics['returning_clients'] }}</div>
                <div class="metric-label">Returning Clients</div>
            </div>
        </div>

        @if(count($client_analytics['top_clients']) > 0)
        <h3 style="margin-top: 20px; margin-bottom: 10px;">Top Clients</h3>
        <table class="table">
            <thead>
                <tr>
                    <th>Client Name</th>
                    <th style="text-align: center;">Bookings</th>
                    <th style="text-align: right;">Total Spent</th>
                </tr>
            </thead>
            <tbody>
                @foreach($client_analytics['top_clients'] as $client)
                <tr>
                    <td>{{ $client['name'] }}</td>
                    <td style="text-align: center;">{{ $client['booking_count'] }}</td>
                    <td style="text-align: right;" class="success">KES {{ number_format($client['total_spent'], 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
    </div>

    <!-- Financial Breakdown -->
    <div class="section">
        <h2 class="section-title">Financial Breakdown</h2>
        <div class="metrics-grid">
            <div class="metric">
                <div class="metric-value success">KES {{ number_format($financial_breakdown['total_revenue'], 2) }}</div>
                <div class="metric-label">Completed Revenue</div>
            </div>
            <div class="metric">
                <div class="metric-value warning">KES {{ number_format($financial_breakdown['pending_amount'], 2) }}</div>
                <div class="metric-label">Pending Payments</div>
            </div>
            <div class="metric">
                <div class="metric-value danger">KES {{ number_format($financial_breakdown['failed_amount'], 2) }}</div>
                <div class="metric-label">Failed Payments</div>
            </div>
            <div class="metric">
                <div class="metric-value">{{ $financial_breakdown['transaction_count'] }}</div>
                <div class="metric-label">Total Transactions</div>
            </div>
        </div>

        <h3 style="margin-top: 20px; margin-bottom: 10px;">Revenue by Payment Method</h3>
        <table class="table">
            <thead>
                <tr>
                    <th>Payment Method</th>
                    <th style="text-align: right;">Revenue</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Cash</td>
                    <td style="text-align: right;" class="success">KES {{ number_format($financial_breakdown['by_method']['cash'], 2) }}</td>
                </tr>
                <tr>
                    <td>M-Pesa</td>
                    <td style="text-align: right;" class="success">KES {{ number_format($financial_breakdown['by_method']['mpesa'], 2) }}</td>
                </tr>
                <tr>
                    <td>Card</td>
                    <td style="text-align: right;" class="success">KES {{ number_format($financial_breakdown['by_method']['card'], 2) }}</td>
                </tr>
                <tr>
                    <td>Bank Transfer</td>
                    <td style="text-align: right;" class="success">KES {{ number_format($financial_breakdown['by_method']['bank_transfer'], 2) }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="footer">
        <p>Report generated by Wellness Spa Admin System</p>
        <p>Data exported on {{ now()->format('F j, Y \a\t g:i A') }}</p>
    </div>
</body>
</html>