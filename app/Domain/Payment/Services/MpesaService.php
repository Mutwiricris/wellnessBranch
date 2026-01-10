<?php

namespace App\Domain\Payment\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class MpesaService
{
    private string $consumerKey;
    private string $consumerSecret;
    private string $shortcode;
    private string $passkey;
    private string $callbackUrl;
    private string $baseUrl;

    public function __construct()
    {
        $this->consumerKey = config('services.mpesa.consumer_key', '');
        $this->consumerSecret = config('services.mpesa.consumer_secret', '');
        $this->shortcode = config('services.mpesa.shortcode', '');
        $this->passkey = config('services.mpesa.passkey', '');

        // Build callback URL at runtime to avoid url() helper during config loading
        $callbackPath = config('services.mpesa.callback_url', '/api/mpesa/callback');
        $this->callbackUrl = str_starts_with($callbackPath, 'http')
            ? $callbackPath
            : url($callbackPath);

        $this->baseUrl = config('services.mpesa.environment') === 'production'
            ? 'https://api.safaricom.co.ke'
            : 'https://sandbox.safaricom.co.ke';
    }

    /**
     * Get OAuth access token
     */
    public function getAccessToken(): string
    {
        try {
            $response = Http::withBasicAuth($this->consumerKey, $this->consumerSecret)
                ->get("{$this->baseUrl}/oauth/v1/generate?grant_type=client_credentials");

            if ($response->successful()) {
                return $response->json('access_token');
            }

            throw new Exception('Failed to get M-Pesa access token: ' . $response->body());
        } catch (Exception $e) {
            Log::error('M-Pesa Access Token Error: ' . $e->getMessage());
            throw new Exception('Failed to authenticate with M-Pesa: ' . $e->getMessage());
        }
    }

    /**
     * Initiate STK Push (Lipa Na M-Pesa)
     */
    public function stkPush(string $phoneNumber, float $amount, string $accountReference, string $description = 'Payment'): array
    {
        try {
            $accessToken = $this->getAccessToken();
            $timestamp = now()->format('YmdHis');
            $password = base64_encode($this->shortcode . $this->passkey . $timestamp);

            // Format phone number (remove leading 0 if present, add 254)
            $phoneNumber = $this->formatPhoneNumber($phoneNumber);

            $payload = [
                'BusinessShortCode' => $this->shortcode,
                'Password' => $password,
                'Timestamp' => $timestamp,
                'TransactionType' => 'CustomerPayBillOnline',
                'Amount' => (int) ceil($amount), // M-Pesa doesn't accept decimals
                'PartyA' => $phoneNumber,
                'PartyB' => $this->shortcode,
                'PhoneNumber' => $phoneNumber,
                'CallBackURL' => $this->callbackUrl,
                'AccountReference' => $accountReference,
                'TransactionDesc' => $description,
            ];

            $response = Http::withToken($accessToken)
                ->post("{$this->baseUrl}/mpesa/stkpush/v1/processrequest", $payload);

            if ($response->successful()) {
                $data = $response->json();

                // Check if request was successful
                if (isset($data['ResponseCode']) && $data['ResponseCode'] === '0') {
                    return [
                        'success' => true,
                        'checkout_request_id' => $data['CheckoutRequestID'],
                        'merchant_request_id' => $data['MerchantRequestID'],
                        'response_code' => $data['ResponseCode'],
                        'response_description' => $data['ResponseDescription'],
                        'customer_message' => $data['CustomerMessage'] ?? 'Payment request sent to your phone',
                    ];
                }

                return [
                    'success' => false,
                    'error' => $data['errorMessage'] ?? $data['ResponseDescription'] ?? 'Failed to initiate payment',
                ];
            }

            throw new Exception('STK Push request failed: ' . $response->body());
        } catch (Exception $e) {
            Log::error('M-Pesa STK Push Error: ' . $e->getMessage(), [
                'phone' => $phoneNumber,
                'amount' => $amount,
            ]);

            return [
                'success' => false,
                'error' => 'Failed to initiate M-Pesa payment: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Query STK Push transaction status
     */
    public function stkPushQuery(string $checkoutRequestId): array
    {
        try {
            $accessToken = $this->getAccessToken();
            $timestamp = now()->format('YmdHis');
            $password = base64_encode($this->shortcode . $this->passkey . $timestamp);

            $payload = [
                'BusinessShortCode' => $this->shortcode,
                'Password' => $password,
                'Timestamp' => $timestamp,
                'CheckoutRequestID' => $checkoutRequestId,
            ];

            $response = Http::withToken($accessToken)
                ->post("{$this->baseUrl}/mpesa/stkpushquery/v1/query", $payload);

            if ($response->successful()) {
                $data = $response->json();

                return [
                    'success' => true,
                    'result_code' => $data['ResultCode'] ?? null,
                    'result_desc' => $data['ResultDesc'] ?? null,
                    'data' => $data,
                ];
            }

            throw new Exception('STK Push query failed: ' . $response->body());
        } catch (Exception $e) {
            Log::error('M-Pesa STK Push Query Error: ' . $e->getMessage());

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Process M-Pesa callback
     */
    public function processCallback(array $callbackData): array
    {
        try {
            $resultCode = $callbackData['Body']['stkCallback']['ResultCode'] ?? null;
            $resultDesc = $callbackData['Body']['stkCallback']['ResultDesc'] ?? null;
            $checkoutRequestId = $callbackData['Body']['stkCallback']['CheckoutRequestID'] ?? null;
            $merchantRequestId = $callbackData['Body']['stkCallback']['MerchantRequestID'] ?? null;

            // Success
            if ($resultCode === 0) {
                $callbackMetadata = $callbackData['Body']['stkCallback']['CallbackMetadata']['Item'] ?? [];

                $metadata = [];
                foreach ($callbackMetadata as $item) {
                    $metadata[$item['Name']] = $item['Value'] ?? null;
                }

                return [
                    'success' => true,
                    'checkout_request_id' => $checkoutRequestId,
                    'merchant_request_id' => $merchantRequestId,
                    'amount' => $metadata['Amount'] ?? null,
                    'mpesa_receipt_number' => $metadata['MpesaReceiptNumber'] ?? null,
                    'transaction_date' => $metadata['TransactionDate'] ?? null,
                    'phone_number' => $metadata['PhoneNumber'] ?? null,
                    'result_desc' => $resultDesc,
                ];
            }

            // Failed or cancelled
            return [
                'success' => false,
                'checkout_request_id' => $checkoutRequestId,
                'merchant_request_id' => $merchantRequestId,
                'result_code' => $resultCode,
                'result_desc' => $resultDesc,
                'error' => $resultDesc,
            ];
        } catch (Exception $e) {
            Log::error('M-Pesa Callback Processing Error: ' . $e->getMessage(), [
                'callback_data' => $callbackData,
            ]);

            return [
                'success' => false,
                'error' => 'Failed to process M-Pesa callback: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Format phone number to Kenyan format (254XXXXXXXXX)
     */
    private function formatPhoneNumber(string $phone): string
    {
        // Remove any spaces, dashes, or other characters
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // Remove leading + if present
        $phone = ltrim($phone, '+');

        // Remove leading 0 if present and add 254
        if (substr($phone, 0, 1) === '0') {
            $phone = '254' . substr($phone, 1);
        }

        // Add 254 if not present
        if (substr($phone, 0, 3) !== '254') {
            $phone = '254' . $phone;
        }

        return $phone;
    }

    /**
     * Validate phone number format
     */
    public function validatePhoneNumber(string $phone): bool
    {
        $formatted = $this->formatPhoneNumber($phone);

        // Valid Kenyan phone numbers: 254XXXXXXXXX (12 digits total)
        return preg_match('/^254[17]\d{8}$/', $formatted);
    }
}
