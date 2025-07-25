<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\PosTransaction;
use App\Models\PosPaymentSplit;
use App\Models\Booking;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class PaymentService
{
    // Payment gateway configurations
    private array $cardGatewayConfig;
    private array $bankTransferConfig;
    private array $mpesaConfig;

    public function __construct()
    {
        $this->cardGatewayConfig = [
            'api_key' => config('payments.card_gateway.api_key'),
            'secret_key' => config('payments.card_gateway.secret_key'),
            'endpoint' => config('payments.card_gateway.endpoint'),
        ];

        $this->bankTransferConfig = [
            'api_key' => config('payments.bank_transfer.api_key'),
            'endpoint' => config('payments.bank_transfer.endpoint'),
        ];

        $this->mpesaConfig = [
            'consumer_key' => config('payments.mpesa.consumer_key'),
            'consumer_secret' => config('payments.mpesa.consumer_secret'),
            'passkey' => config('payments.mpesa.passkey'),
            'shortcode' => config('payments.mpesa.shortcode'),
            'endpoint' => config('payments.mpesa.endpoint'),
        ];
    }

    /**
     * Process card payment
     */
    public function processCardPayment(array $paymentData): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->cardGatewayConfig['api_key'],
                'Content-Type' => 'application/json',
            ])->post($this->cardGatewayConfig['endpoint'] . '/charge', [
                'amount' => $paymentData['amount'],
                'currency' => 'KES',
                'card_number' => $paymentData['card_number'],
                'expiry_month' => $paymentData['expiry_month'],
                'expiry_year' => $paymentData['expiry_year'],
                'cvv' => $paymentData['cvv'],
                'cardholder_name' => $paymentData['cardholder_name'],
                'description' => $paymentData['description'] ?? 'Payment for services',
                'reference' => $paymentData['reference'],
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => true,
                    'transaction_id' => $data['transaction_id'],
                    'reference' => $data['reference'],
                    'card_last_four' => substr($paymentData['card_number'], -4),
                    'card_brand' => $this->getCardBrand($paymentData['card_number']),
                    'gateway_response' => $data,
                ];
            }

            return [
                'success' => false,
                'error' => $response->json()['message'] ?? 'Card payment failed',
                'gateway_response' => $response->json(),
            ];

        } catch (Exception $e) {
            Log::error('Card payment error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Card payment processing failed',
            ];
        }
    }

    /**
     * Process bank transfer payment
     */
    public function processBankTransferPayment(array $paymentData): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->bankTransferConfig['api_key'],
                'Content-Type' => 'application/json',
            ])->post($this->bankTransferConfig['endpoint'] . '/transfer', [
                'amount' => $paymentData['amount'],
                'account_number' => $paymentData['account_number'],
                'bank_code' => $paymentData['bank_code'],
                'account_name' => $paymentData['account_name'],
                'description' => $paymentData['description'] ?? 'Payment for services',
                'reference' => $paymentData['reference'],
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => true,
                    'transaction_id' => $data['transaction_id'],
                    'reference' => $data['reference'],
                    'account_details' => [
                        'account_number' => $paymentData['account_number'],
                        'bank_code' => $paymentData['bank_code'],
                        'account_name' => $paymentData['account_name'],
                    ],
                    'gateway_response' => $data,
                ];
            }

            return [
                'success' => false,
                'error' => $response->json()['message'] ?? 'Bank transfer failed',
                'gateway_response' => $response->json(),
            ];

        } catch (Exception $e) {
            Log::error('Bank transfer error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Bank transfer processing failed',
            ];
        }
    }

    /**
     * Process M-Pesa STK Push
     */
    public function processMpesaPayment(array $paymentData): array
    {
        try {
            // Get access token
            $token = $this->getMpesaAccessToken();
            if (!$token) {
                return [
                    'success' => false,
                    'error' => 'Failed to get M-Pesa access token',
                ];
            }

            // Generate timestamp and password
            $timestamp = date('YmdHis');
            $password = base64_encode($this->mpesaConfig['shortcode'] . $this->mpesaConfig['passkey'] . $timestamp);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json',
            ])->post($this->mpesaConfig['endpoint'] . '/stkpush/v1/processrequest', [
                'BusinessShortCode' => $this->mpesaConfig['shortcode'],
                'Password' => $password,
                'Timestamp' => $timestamp,
                'TransactionType' => 'CustomerPayBillOnline',
                'Amount' => $paymentData['amount'],
                'PartyA' => $paymentData['phone_number'],
                'PartyB' => $this->mpesaConfig['shortcode'],
                'PhoneNumber' => $paymentData['phone_number'],
                'CallBackURL' => config('app.url') . '/api/mpesa/callback',
                'AccountReference' => $paymentData['reference'],
                'TransactionDesc' => $paymentData['description'] ?? 'Payment for services',
            ]);

            if ($response->successful()) {
                $data = $response->json();
                if ($data['ResponseCode'] === '0') {
                    return [
                        'success' => true,
                        'checkout_request_id' => $data['CheckoutRequestID'],
                        'merchant_request_id' => $data['MerchantRequestID'],
                        'response_description' => $data['ResponseDescription'],
                        'gateway_response' => $data,
                    ];
                }
            }

            return [
                'success' => false,
                'error' => $response->json()['ResponseDescription'] ?? 'M-Pesa payment failed',
                'gateway_response' => $response->json(),
            ];

        } catch (Exception $e) {
            Log::error('M-Pesa payment error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'M-Pesa payment processing failed',
            ];
        }
    }

    /**
     * Process booking payment with enhanced methods
     */
    public function processBookingPayment(Booking $booking, array $paymentData): Payment
    {
        $payment = Payment::create([
            'booking_id' => $booking->id,
            'branch_id' => $booking->branch_id,
            'amount' => $paymentData['amount'],
            'payment_method' => $paymentData['payment_method'],
            'status' => 'pending',
            'transaction_reference' => $paymentData['reference'] ?? $this->generateTransactionReference(),
        ]);

        $result = match ($paymentData['payment_method']) {
            'card' => $this->processCardPayment($paymentData),
            'bank_transfer' => $this->processBankTransferPayment($paymentData),
            'mpesa' => $this->processMpesaPayment($paymentData),
            'cash' => ['success' => true], // Cash is processed immediately
            default => ['success' => false, 'error' => 'Unsupported payment method']
        };

        if ($result['success']) {
            $updateData = [
                'status' => 'completed',
                'processed_at' => now(),
            ];

            // Add method-specific data
            if ($paymentData['payment_method'] === 'card') {
                $updateData['card_last_four'] = $result['card_last_four'];
                $updateData['card_brand'] = $result['card_brand'];
            } elseif ($paymentData['payment_method'] === 'mpesa') {
                $updateData['mpesa_checkout_request_id'] = $result['checkout_request_id'];
            }

            if (isset($result['gateway_response'])) {
                $updateData['gateway_response'] = $result['gateway_response'];
            }

            $payment->update($updateData);

            // Update booking payment status
            $booking->update(['payment_status' => 'completed']);
        } else {
            $payment->update([
                'status' => 'failed',
                'notes' => $result['error'],
                'gateway_response' => $result['gateway_response'] ?? null,
            ]);
        }

        return $payment;
    }

    /**
     * Process POS payment with split payments support
     */
    public function processPosPayment(PosTransaction $transaction, array $paymentSplits): bool
    {
        $totalPaid = 0;
        $allSuccessful = true;

        foreach ($paymentSplits as $splitData) {
            $split = PosPaymentSplit::create([
                'pos_transaction_id' => $transaction->id,
                'payment_method' => $splitData['payment_method'],
                'amount' => $splitData['amount'],
                'status' => 'pending',
            ]);

            $result = match ($splitData['payment_method']) {
                'card' => $this->processCardPayment($splitData),
                'bank_transfer' => $this->processBankTransferPayment($splitData),
                'mpesa' => $this->processMpesaPayment($splitData),
                'cash' => ['success' => true],
                'gift_voucher' => $this->processGiftVoucherPayment($splitData),
                'loyalty_points' => $this->processLoyaltyPointsPayment($splitData),
                default => ['success' => false, 'error' => 'Unsupported payment method']
            };

            if ($result['success']) {
                $split->markAsCompleted($result['reference'] ?? null);
                $split->update([
                    'payment_details' => $result,
                ]);
                $totalPaid += $splitData['amount'];
            } else {
                $split->markAsFailed($result['error']);
                $allSuccessful = false;
            }
        }

        // Update transaction status
        if ($allSuccessful && $totalPaid >= $transaction->total_amount) {
            $transaction->update(['payment_status' => 'completed']);
        } elseif ($totalPaid > 0) {
            $transaction->update(['payment_status' => 'partial']);
        } else {
            $transaction->update(['payment_status' => 'failed']);
        }

        return $allSuccessful;
    }

    /**
     * Process gift voucher payment
     */
    private function processGiftVoucherPayment(array $paymentData): array
    {
        try {
            $voucher = \App\Models\GiftVoucher::where('voucher_code', $paymentData['voucher_code'])->first();
            
            if (!$voucher || !$voucher->isValid()) {
                return [
                    'success' => false,
                    'error' => 'Invalid or expired gift voucher',
                ];
            }

            $discountAmount = $voucher->applyToTransaction($paymentData['amount']);
            
            return [
                'success' => true,
                'discount_amount' => $discountAmount,
                'voucher_code' => $voucher->voucher_code,
                'remaining_balance' => $voucher->remaining_amount,
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Process loyalty points payment
     */
    private function processLoyaltyPointsPayment(array $paymentData): array
    {
        try {
            $customer = \App\Models\User::find($paymentData['customer_id']);
            
            if (!$customer) {
                return [
                    'success' => false,
                    'error' => 'Customer not found',
                ];
            }

            $pointsToUse = min($paymentData['points'], $customer->loyalty_points);
            $discountAmount = min($pointsToUse, $paymentData['amount']); // 1 point = 1 KES

            // Deduct points
            $customer->decrement('loyalty_points', $pointsToUse);

            // Log points usage
            \App\Models\LoyaltyPoint::create([
                'user_id' => $customer->id,
                'transaction_type' => 'redeemed',
                'points' => -$pointsToUse,
                'description' => 'Points redeemed for payment',
            ]);

            return [
                'success' => true,
                'points_used' => $pointsToUse,
                'discount_amount' => $discountAmount,
                'remaining_points' => $customer->loyalty_points,
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get M-Pesa access token
     */
    private function getMpesaAccessToken(): ?string
    {
        try {
            $credentials = base64_encode($this->mpesaConfig['consumer_key'] . ':' . $this->mpesaConfig['consumer_secret']);
            
            $response = Http::withHeaders([
                'Authorization' => 'Basic ' . $credentials,
            ])->get('https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials');

            if ($response->successful()) {
                return $response->json()['access_token'];
            }

            return null;
        } catch (Exception $e) {
            Log::error('M-Pesa token error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Determine card brand from card number
     */
    private function getCardBrand(string $cardNumber): string
    {
        $cardNumber = preg_replace('/\s+/', '', $cardNumber);
        
        if (preg_match('/^4/', $cardNumber)) {
            return 'Visa';
        } elseif (preg_match('/^5[1-5]/', $cardNumber)) {
            return 'Mastercard';
        } elseif (preg_match('/^3[47]/', $cardNumber)) {
            return 'American Express';
        } elseif (preg_match('/^6(?:011|5)/', $cardNumber)) {
            return 'Discover';
        }
        
        return 'Unknown';
    }

    /**
     * Generate unique transaction reference
     */
    private function generateTransactionReference(): string
    {
        return 'TXN' . strtoupper(uniqid()) . time();
    }

    /**
     * Get available payment methods
     */
    public function getAvailablePaymentMethods(): array
    {
        return [
            'cash' => [
                'name' => 'Cash',
                'icon' => 'heroicon-o-banknotes',
                'enabled' => true,
                'requires_processing' => false,
            ],
            'mpesa' => [
                'name' => 'M-Pesa',
                'icon' => 'heroicon-o-device-phone-mobile',
                'enabled' => !empty($this->mpesaConfig['consumer_key']),
                'requires_processing' => true,
            ],
            'card' => [
                'name' => 'Credit/Debit Card',
                'icon' => 'heroicon-o-credit-card',
                'enabled' => !empty($this->cardGatewayConfig['api_key']),
                'requires_processing' => true,
            ],
            'bank_transfer' => [
                'name' => 'Bank Transfer',
                'icon' => 'heroicon-o-building-library',
                'enabled' => !empty($this->bankTransferConfig['api_key']),
                'requires_processing' => true,
            ],
            'gift_voucher' => [
                'name' => 'Gift Voucher',
                'icon' => 'heroicon-o-gift',
                'enabled' => true,
                'requires_processing' => false,
            ],
            'loyalty_points' => [
                'name' => 'Loyalty Points',
                'icon' => 'heroicon-o-star',
                'enabled' => true,
                'requires_processing' => false,
            ],
        ];
    }

    /**
     * Validate payment data based on method
     */
    public function validatePaymentData(string $paymentMethod, array $data): array
    {
        $errors = [];

        switch ($paymentMethod) {
            case 'card':
                if (empty($data['card_number'])) $errors[] = 'Card number is required';
                if (empty($data['expiry_month'])) $errors[] = 'Expiry month is required';
                if (empty($data['expiry_year'])) $errors[] = 'Expiry year is required';
                if (empty($data['cvv'])) $errors[] = 'CVV is required';
                if (empty($data['cardholder_name'])) $errors[] = 'Cardholder name is required';
                break;

            case 'bank_transfer':
                if (empty($data['account_number'])) $errors[] = 'Account number is required';
                if (empty($data['bank_code'])) $errors[] = 'Bank code is required';
                if (empty($data['account_name'])) $errors[] = 'Account name is required';
                break;

            case 'mpesa':
                if (empty($data['phone_number'])) $errors[] = 'Phone number is required';
                if (!preg_match('/^254[0-9]{9}$/', $data['phone_number'])) {
                    $errors[] = 'Invalid phone number format (use 254XXXXXXXXX)';
                }
                break;

            case 'gift_voucher':
                if (empty($data['voucher_code'])) $errors[] = 'Voucher code is required';
                break;

            case 'loyalty_points':
                if (empty($data['customer_id'])) $errors[] = 'Customer ID is required';
                if (empty($data['points']) || $data['points'] <= 0) $errors[] = 'Points must be greater than 0';
                break;
        }

        return $errors;
    }
}