<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Receipt - {{ $transaction['transaction_number'] }}</title>
    <style>
        body {
            margin: 0;
            padding: 20px;
            background: #f0f2f5;
            font-family: 'Courier New', Courier, monospace;
            display: flex;
            justify-content: center;
            align-items: flex-start;
        }
        
        .thermal-receipt {
            background: #FFFFFF;
            color: #000000;
            padding: 24px;
            border-radius: 4px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            border: 1px solid #E0E0E0;
            width: 100%;
            max-width: 320px;
            font-size: 11px;
            line-height: 1.4;
            box-sizing: border-box;
        }
        
        .thermal-receipt-divider {
            border-top: 1px dashed #000;
            margin: 12px 0;
        }
        
        .thermal-receipt-header {
            text-align: center;
            margin-bottom: 16px;
        }
        
        .thermal-receipt-header h2 {
            font-size: 16px;
            font-weight: bold;
            margin: 0 0 4px 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .thermal-receipt-header p {
            font-size: 9px;
            margin: 2px 0;
            color: #000000;
        }
        
        .thermal-receipt-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 6px;
        }
        
        .thermal-receipt-item-name {
            flex-grow: 1;
            max-width: 70%;
            word-break: break-word;
        }
        
        .thermal-receipt-item-total {
            font-weight: bold;
            white-space: nowrap;
            margin-left: 10px;
        }
        
        .thermal-receipt-total-row {
            display: flex;
            justify-content: space-between;
            font-size: 13px;
            font-weight: bold;
            margin-top: 6px;
            padding-top: 6px;
            border-top: 1px dashed #000;
        }
        
        .thermal-receipt-footer {
            text-align: center;
            margin-top: 16px;
            font-size: 9px;
        }
        
        .thermal-receipt-barcode {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            margin-top: 16px;
            gap: 4px;
        }
        
        .thermal-receipt-barcode svg {
            width: 140px;
            height: 30px;
        }
        
        .thermal-receipt-barcode span {
            font-size: 8px;
            letter-spacing: 1px;
        }
        
        .print-btn-container {
            text-align: center;
            margin-top: 20px;
        }
        
        .print-btn {
            background: #4F46E5;
            color: #ffffff;
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            font-weight: bold;
        }

        @media print {
            body {
                background: transparent;
                padding: 0;
            }
            .print-btn-container {
                display: none;
            }
            .thermal-receipt {
                width: 72mm !important;
                max-width: 100% !important;
                box-shadow: none !important;
                border: none !important;
                padding: 10px 0 !important;
                margin: 0 !important;
            }
        }
    </style>
</head>
<body>
    <div>
        <div class="thermal-receipt">
            <div class="thermal-receipt-header">
                <h2>{{ $branch }}</h2>
                <p>Wellness & Spa Center</p>
                <p>Date: {{ \Carbon\Carbon::parse($transaction['created_at'])->format('d/m/Y H:i') }}</p>
                <p>Receipt No: {{ $transaction['transaction_number'] }}</p>
            </div>

            <div class="thermal-receipt-divider"></div>

            <div class="thermal-receipt-items">
                @foreach ($items as $item)
                    <div class="thermal-receipt-row">
                        <span class="thermal-receipt-item-name">
                            {{ $item['quantity'] }}x {{ $item['name'] }}
                        </span>
                        <span class="thermal-receipt-item-total">
                            KES {{ number_format($item['total_price'], 2) }}
                        </span>
                    </div>
                @endforeach
            </div>

            <div class="thermal-receipt-divider"></div>

            <div class="thermal-receipt-totals">
                @php
                    $subtotal = collect($items)->sum('total_price');
                    $tax = $subtotal * 0.16;
                    $total = $subtotal + $tax;
                @endphp
                <div class="thermal-receipt-row">
                    <span>Subtotal</span>
                    <span>KES {{ number_format($subtotal, 2) }}</span>
                </div>
                <div class="thermal-receipt-row">
                    <span>Sales Tax (16%)</span>
                    <span>KES {{ number_format($tax, 2) }}</span>
                </div>
                <div class="thermal-receipt-total-row">
                    <span>TOTAL PAID</span>
                    <span>KES {{ number_format($total, 2) }}</span>
                </div>
            </div>

            <div class="thermal-receipt-divider"></div>

            <div class="thermal-receipt-footer">
                <p>Payment Method: <span style="text-transform: uppercase; font-weight: bold;">{{ $transaction['payment_method'] }}</span></p>
                <p>Operator: {{ $staff }}</p>
                <p style="font-weight: bold; margin-top: 10px; font-style: italic;">Thank you for visiting!</p>
            </div>

            <div class="thermal-receipt-barcode">
                <svg class="barcode-svg" viewBox="0 0 100 20">
                    <rect x="5" y="0" width="2" height="20" fill="black"/>
                    <rect x="8" y="0" width="1" height="20" fill="black"/>
                    <rect x="11" y="0" width="3" height="20" fill="black"/>
                    <rect x="16" y="0" width="1" height="20" fill="black"/>
                    <rect x="18" y="0" width="2" height="20" fill="black"/>
                    <rect x="22" y="0" width="4" height="20" fill="black"/>
                    <rect x="28" y="0" width="1" height="20" fill="black"/>
                    <rect x="30" y="0" width="2" height="20" fill="black"/>
                    <rect x="34" y="0" width="1" height="20" fill="black"/>
                    <rect x="36" y="0" width="3" height="20" fill="black"/>
                    <rect x="41" y="0" width="2" height="20" fill="black"/>
                    <rect x="45" y="0" width="1" height="20" fill="black"/>
                    <rect x="48" y="0" width="4" height="20" fill="black"/>
                    <rect x="54" y="0" width="2" height="20" fill="black"/>
                    <rect x="57" y="0" width="1" height="20" fill="black"/>
                    <rect x="60" y="0" width="3" height="20" fill="black"/>
                    <rect x="65" y="0" width="1" height="20" fill="black"/>
                    <rect x="68" y="0" width="2" height="20" fill="black"/>
                    <rect x="72" y="0" width="4" height="20" fill="black"/>
                    <rect x="78" y="0" width="1" height="20" fill="black"/>
                    <rect x="80" y="0" width="2" height="20" fill="black"/>
                    <rect x="84" y="0" width="3" height="20" fill="black"/>
                    <rect x="89" y="0" width="1" height="20" fill="black"/>
                    <rect x="92" y="0" width="2" height="20" fill="black"/>
                </svg>
                <span>{{ $transaction['transaction_number'] }}</span>
            </div>
        </div>
        
        <div class="print-btn-container">
            <button onclick="window.print()" class="print-btn">Print Receipt</button>
        </div>
    </div>
    
    <script>
        window.addEventListener('load', function() {
            setTimeout(function() {
                window.print();
            }, 500);
        });
    </script>
</body>
</html>
