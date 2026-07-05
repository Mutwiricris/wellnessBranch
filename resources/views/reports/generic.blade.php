<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        body { font-family: 'Helvetica', sans-serif; color: #333; line-height: 1.5; font-size: 12px; }
        .header { text-align: center; border-bottom: 2px solid #db2777; padding-bottom: 20px; margin-bottom: 20px; }
        .logo { font-size: 24px; font-weight: bold; color: #db2777; text-transform: uppercase; letter-spacing: 2px; }
        .report-title { font-size: 18px; margin-top: 10px; color: #1e293b; }
        .meta { margin-bottom: 20px; font-size: 10px; color: #64748b; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th { background-color: #f8fafc; text-align: left; padding: 10px; border-bottom: 1px solid #e2e8f0; color: #475569; text-transform: uppercase; font-size: 10px; }
        td { padding: 10px; border-bottom: 1px solid #f1f5f9; vertical-align: top; }
        .text-right { text-align: right; }
        .footer { position: fixed; bottom: 0; width: 100%; text-align: center; font-size: 9px; color: #94a3b8; padding-top: 10px; border-top: 1px solid #e2e8f0; }
        .badge { padding: 2px 6px; border-radius: 4px; font-size: 9px; font-weight: bold; }
        .badge-success { background-color: #dcfce7; color: #166534; }
        .badge-warning { background-color: #fef9c3; color: #854d0e; }
        .badge-danger { background-color: #fee2e2; color: #991b1b; }
        .total-row { background-color: #f8fafc; font-weight: bold; }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo">Ascend Wellness OS</div>
        <div class="report-title">{{ $title }}</div>
    </div>

    <div class="meta">
        Generated on: {{ now()->format('M d, Y H:i A') }}<br>
        Branch: {{ $branch_name ?? 'All Branches' }}<br>
        Period: {{ $period ?? 'All Time' }}
    </div>

    <table>
        <thead>
            <tr>
                @foreach($headers as $header)
                    <th>{{ $header }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach($data as $row)
                <tr>
                    @foreach($headers as $key => $label)
                        <td>
                            @php
                                $val = data_get($row, $key, '-');
                                // Special formatting for money
                                if (str_contains(strtolower($label), 'amount') || str_contains(strtolower($label), 'revenue') || str_contains(strtolower($label), 'price')) {
                                    $val = number_format((float)$val, 2);
                                }
                            @endphp
                            {{ $val }}
                        </td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
        @if(isset($totals))
            <tfoot>
                <tr class="total-row">
                    @foreach($headers as $key => $label)
                        <td class="{{ str_contains(strtolower($label), 'amount') ? 'text-right' : '' }}">
                            {{ $totals[$key] ?? '' }}
                        </td>
                    @endforeach
                </tr>
            </tfoot>
        @endif
    </table>

    <div class="footer">
        &copy; {{ date('Y') }} Ascend Wellness OS. Confidential Financial Report.
    </div>
</body>
</html>
