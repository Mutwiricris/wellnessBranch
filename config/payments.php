<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Payment Gateway Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for various payment gateways and methods
    |
    */

    'default_currency' => 'KES',

    'card_gateway' => [
        'api_key' => env('CARD_GATEWAY_API_KEY'),
        'secret_key' => env('CARD_GATEWAY_SECRET_KEY'),
        'endpoint' => env('CARD_GATEWAY_ENDPOINT', 'https://api.cardgateway.com/v1'),
        'test_mode' => env('CARD_GATEWAY_TEST_MODE', true),
    ],

    'bank_transfer' => [
        'api_key' => env('BANK_TRANSFER_API_KEY'),
        'endpoint' => env('BANK_TRANSFER_ENDPOINT', 'https://api.banktransfer.com/v1'),
        'supported_banks' => [
            '01' => 'Kenya Commercial Bank (KCB)',
            '02' => 'Standard Chartered Bank',
            '03' => 'Barclays Bank of Kenya',
            '04' => 'Cooperative Bank of Kenya',
            '05' => 'Equity Bank Kenya',
            '06' => 'National Bank of Kenya',
            '07' => 'Commercial Bank of Africa (CBA)',
            '08' => 'NIC Bank',
            '09' => 'Diamond Trust Bank (DTB)',
            '10' => 'I&M Bank',
            '11' => 'Family Bank',
            '12' => 'Stanbic Bank',
            '13' => 'Prime Bank',
            '14' => 'Credit Bank',
            '15' => 'Chase Bank',
            '16' => 'Guardian Bank',
            '17' => 'Bank of Africa',
            '18' => 'Consolidated Bank',
            '19' => 'Victoria Commercial Bank',
            '20' => 'Trans-National Bank',
        ],
    ],

    'mpesa' => [
        'consumer_key' => env('MPESA_CONSUMER_KEY'),
        'consumer_secret' => env('MPESA_CONSUMER_SECRET'),
        'passkey' => env('MPESA_PASSKEY'),
        'shortcode' => env('MPESA_SHORTCODE'),
        'environment' => env('MPESA_ENVIRONMENT', 'sandbox'), // sandbox or production
        'endpoint' => env('MPESA_ENVIRONMENT', 'sandbox') === 'sandbox' 
            ? 'https://sandbox.safaricom.co.ke/mpesa'
            : 'https://api.safaricom.co.ke/mpesa',
        'timeout' => 60, // seconds
    ],

    'payment_methods' => [
        'cash' => [
            'enabled' => true,
            'min_amount' => 1,
            'max_amount' => 100000,
            'processing_fee' => 0,
            'requires_authorization' => false,
        ],
        'mpesa' => [
            'enabled' => true,
            'min_amount' => 1,
            'max_amount' => 70000,
            'processing_fee' => 0, // No fee for M-Pesa
            'requires_authorization' => false,
        ],
        'card' => [
            'enabled' => true,
            'min_amount' => 50,
            'max_amount' => 500000,
            'processing_fee_percentage' => 3.5, // 3.5% processing fee
            'requires_authorization' => true,
            'supported_cards' => ['visa', 'mastercard', 'american_express'],
        ],
        'bank_transfer' => [
            'enabled' => true,
            'min_amount' => 100,
            'max_amount' => 1000000,
            'processing_fee' => 25, // Flat fee of KES 25
            'requires_authorization' => true,
            'processing_time' => '1-3 business days',
        ],
        'gift_voucher' => [
            'enabled' => true,
            'min_amount' => 1,
            'max_amount' => null, // No limit, depends on voucher balance
            'processing_fee' => 0,
            'requires_authorization' => false,
        ],
        'loyalty_points' => [
            'enabled' => true,
            'point_value' => 1, // 1 point = 1 KES
            'min_points' => 10,
            'max_points_per_transaction' => 5000,
            'processing_fee' => 0,
            'requires_authorization' => false,
        ],
    ],

    'receipt_settings' => [
        'include_qr_code' => true,
        'include_payment_breakdown' => true,
        'show_processing_fees' => true,
        'footer_text' => 'Thank you for choosing our services!',
    ],

    'security' => [
        'encrypt_card_data' => true,
        'pci_compliance' => true,
        'require_cvv' => true,
        'require_3d_secure' => env('CARD_REQUIRE_3D_SECURE', false),
        'fraud_detection' => true,
    ],

    'webhooks' => [
        'mpesa_callback' => '/api/webhooks/mpesa',
        'card_callback' => '/api/webhooks/card',
        'bank_transfer_callback' => '/api/webhooks/bank-transfer',
        'verify_signature' => true,
    ],

    'notifications' => [
        'send_payment_confirmations' => true,
        'send_failure_notifications' => true,
        'admin_notification_threshold' => 10000, // Notify admin for payments above this amount
    ],

    'refunds' => [
        'enabled' => true,
        'auto_refund_timeout' => 30, // minutes
        'require_manager_approval' => true,
        'partial_refunds_allowed' => true,
        'refund_processing_fee' => 10, // KES 10 processing fee for refunds
    ],

    'reporting' => [
        'daily_reconciliation' => true,
        'export_formats' => ['pdf', 'excel', 'csv'],
        'retention_period' => 7, // years
    ],
];