<?php

namespace App\Http\Controllers\Api;

use App\Domain\Booking\Services\BookingService;
use App\Domain\Payment\Services\MpesaService;
use App\Domain\Payment\Services\PaymentService;
use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Support\Enums\PaymentStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MpesaWebhookController extends Controller
{
    public function __construct(
        protected MpesaService $mpesaService,
        protected PaymentService $paymentService,
        protected BookingService $bookingService
    ) {}

    /**
     * Handle M-Pesa STK Push callback
     */
    public function handleCallback(Request $request): JsonResponse
    {
        try {
            // Log the raw callback for debugging
            Log::info('M-Pesa Callback Received', [
                'payload' => $request->all()
            ]);

            // Process the callback using MpesaService
            $callbackData = $this->mpesaService->processCallback($request->all());

            // Find payment by checkout request ID
            $payment = Payment::where('mpesa_checkout_request_id', $callbackData['checkout_request_id'])
                ->first();

            if ($payment) {
                // Check if transaction was successful
                if ($callbackData['success'] ?? false) {
                    // Transaction successful - update payment
                    $this->paymentService->updatePaymentStatus(
                        $payment->id,
                        PaymentStatus::COMPLETED->value,
                        [
                            'mpesa_transaction_id' => $callbackData['mpesa_receipt_number'] ?? null,
                            'transaction_reference' => $callbackData['mpesa_receipt_number'] ?? null,
                            'gateway_response' => $callbackData,
                        ]
                    );

                    Log::info('Payment updated to COMPLETED', [
                        'payment_id' => $payment->id,
                        'mpesa_receipt' => $callbackData['mpesa_receipt_number'] ?? null
                    ]);

                    // Auto-confirm booking if payment is completed
                    if ($payment->booking && $payment->booking->status === 'pending') {
                        try {
                            $this->bookingService->confirmBooking($payment->booking_id);

                            Log::info('Booking auto-confirmed after payment', [
                                'booking_id' => $payment->booking_id
                            ]);
                        } catch (\Exception $e) {
                            Log::error('Failed to auto-confirm booking', [
                                'booking_id' => $payment->booking_id,
                                'error' => $e->getMessage()
                            ]);
                        }
                    }
                } else {
                    // Transaction failed - update payment
                    $this->paymentService->updatePaymentStatus(
                        $payment->id,
                        PaymentStatus::FAILED->value,
                        [
                            'gateway_response' => $callbackData,
                            'notes' => $callbackData['result_desc'] ?? 'Transaction failed'
                        ]
                    );

                    Log::info('Payment marked as FAILED', [
                        'payment_id' => $payment->id,
                        'reason' => $callbackData['result_desc'] ?? 'Unknown'
                    ]);
                }
            } else {
                // Check if it is a subscription transaction
                $mpesaTransaction = \App\Models\MpesaTransaction::where('checkout_request_id', $callbackData['checkout_request_id'])->first();

                if (!$mpesaTransaction) {
                    Log::warning('Payment/Transaction not found for M-Pesa callback', [
                        'checkout_request_id' => $callbackData['checkout_request_id']
                    ]);

                    return response()->json([
                        'ResultCode' => 1,
                        'ResultDesc' => 'Payment record not found'
                    ]);
                }

                // Update MpesaTransaction from Safaricom callback body
                $mpesaTransaction->updateFromCallback($request->all());

                if ($mpesaTransaction->isSuccessful()) {
                    if ($mpesaTransaction->subscription_id) {
                        $subscription = \App\Models\Subscription::find($mpesaTransaction->subscription_id);
                        if ($subscription) {
                            \Illuminate\Support\Facades\DB::transaction(function () use ($subscription, $mpesaTransaction, $callbackData) {
                                // Update subscription status to active
                                $subscription->update([
                                    'status' => 'active',
                                    'last_payment_at' => now(),
                                    'next_billing_at' => now()->addMonth(),
                                ]);

                                // Create subscription payment record
                                \App\Models\SubscriptionPayment::create([
                                    'subscription_id' => $subscription->id,
                                    'amount' => $mpesaTransaction->amount,
                                    'payment_method' => 'mpesa',
                                    'transaction_reference' => $callbackData['mpesa_receipt_number'] ?? $mpesaTransaction->merchant_request_id,
                                    'status' => 'completed',
                                    'payment_details' => $callbackData,
                                ]);
                            });

                            Log::info("Subscription {$subscription->id} activated via M-Pesa webhook callback.");
                        }
                    }
                } else {
                    Log::info("MpesaTransaction {$mpesaTransaction->id} failed in webhook callback.");
                }
            }

            // Respond to M-Pesa
            return response()->json([
                'ResultCode' => 0,
                'ResultDesc' => 'Callback processed successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('M-Pesa callback processing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'ResultCode' => 1,
                'ResultDesc' => 'Callback processing failed'
            ], 500);
        }
    }

    /**
     * Handle M-Pesa validation (optional)
     * Called by M-Pesa before processing the transaction
     */
    public function handleValidation(Request $request): JsonResponse
    {
        // Log validation request
        Log::info('M-Pesa Validation Request', [
            'payload' => $request->all()
        ]);

        // Accept all transactions (you can add custom validation here)
        return response()->json([
            'ResultCode' => 0,
            'ResultDesc' => 'Accepted'
        ]);
    }

    /**
     * Handle M-Pesa confirmation (optional)
     * Called by M-Pesa after transaction is processed
     */
    public function handleConfirmation(Request $request): JsonResponse
    {
        // Log confirmation request
        Log::info('M-Pesa Confirmation Request', [
            'payload' => $request->all()
        ]);

        return response()->json([
            'ResultCode' => 0,
            'ResultDesc' => 'Accepted'
        ]);
    }
}
