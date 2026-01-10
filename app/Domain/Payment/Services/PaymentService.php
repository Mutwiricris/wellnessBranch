<?php

namespace App\Domain\Payment\Services;

use App\Domain\Payment\Repositories\PaymentRepositoryInterface;
use App\Models\Payment;
use App\Models\Booking;
use App\Support\Enums\PaymentStatus;
use App\Support\Enums\PaymentMethod;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class PaymentService
{
    public function __construct(
        private PaymentRepositoryInterface $paymentRepository
    ) {}

    /**
     * Create a new payment for a booking
     */
    public function createPayment(array $data): Payment
    {
        DB::beginTransaction();

        try {
            // Validate booking exists
            $booking = Booking::findOrFail($data['booking_id']);

            // Set initial status if not provided
            if (!isset($data['status'])) {
                $data['status'] = $this->determineInitialStatus($data['payment_method']);
            }

            // Calculate processing fee if applicable
            if (isset($data['payment_method'])) {
                $paymentMethod = PaymentMethod::from($data['payment_method']);
                $processingFee = $data['amount'] * $paymentMethod->processingFeePercentage();
                $data['processing_fee'] = $processingFee;
                $data['net_amount'] = $data['amount'] - $processingFee;
            }

            // Set processed timestamp if completed
            if ($data['status'] === PaymentStatus::COMPLETED->value) {
                $data['processed_at'] = now();
            }

            // Create payment
            $payment = $this->paymentRepository->create($data);

            // Update booking payment status
            if ($data['status'] === PaymentStatus::COMPLETED->value) {
                $booking->update([
                    'payment_status' => PaymentStatus::COMPLETED->value,
                ]);
            }

            DB::commit();

            // TODO: Send payment confirmation notification
            // event(new PaymentCreated($payment));

            return $payment->load(['booking', 'booking.client']);

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Update payment status
     */
    public function updatePaymentStatus(int $paymentId, string $status, ?array $additionalData = []): Payment
    {
        DB::beginTransaction();

        try {
            $payment = $this->paymentRepository->findOrFail($paymentId);

            $updateData = array_merge(['status' => $status], $additionalData);

            // Set processed timestamp if completing
            if ($status === PaymentStatus::COMPLETED->value && !$payment->processed_at) {
                $updateData['processed_at'] = now();
            }

            $this->paymentRepository->update($paymentId, $updateData);

            // Update booking payment status
            if ($status === PaymentStatus::COMPLETED->value) {
                $payment->booking->update([
                    'payment_status' => PaymentStatus::COMPLETED->value,
                ]);
            } elseif ($status === PaymentStatus::FAILED->value) {
                $payment->booking->update([
                    'payment_status' => PaymentStatus::FAILED->value,
                ]);
            }

            DB::commit();

            return $this->paymentRepository->findOrFail($paymentId);

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Process a refund
     */
    public function processRefund(int $paymentId, ?float $refundAmount = null, ?string $refundReason = null): Payment
    {
        DB::beginTransaction();

        try {
            $payment = $this->paymentRepository->findOrFail($paymentId);

            // Validate payment can be refunded
            if ($payment->status !== PaymentStatus::COMPLETED->value) {
                throw new Exception('Only completed payments can be refunded');
            }

            // Determine refund amount
            $refundAmount = $refundAmount ?? $payment->amount;

            if ($refundAmount > $payment->amount) {
                throw new Exception('Refund amount cannot exceed payment amount');
            }

            // Determine new status
            $newStatus = ($refundAmount >= $payment->amount)
                ? PaymentStatus::REFUNDED->value
                : PaymentStatus::PARTIALLY_REFUNDED->value;

            // Update payment
            $this->paymentRepository->update($paymentId, [
                'status' => $newStatus,
                'refund_amount' => $refundAmount,
                'refund_reason' => $refundReason,
                'refunded_at' => now(),
            ]);

            // Update booking payment status
            $payment->booking->update([
                'payment_status' => $newStatus,
            ]);

            DB::commit();

            // TODO: Process actual refund via payment gateway
            // TODO: Send refund notification
            // event(new PaymentRefunded($payment, $refundAmount));

            return $this->paymentRepository->findOrFail($paymentId);

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Record M-Pesa payment
     */
    public function recordMpesaPayment(int $bookingId, array $mpesaData): Payment
    {
        return $this->createPayment([
            'booking_id' => $bookingId,
            'branch_id' => Booking::findOrFail($bookingId)->branch_id,
            'amount' => $mpesaData['amount'],
            'payment_method' => PaymentMethod::MPESA->value,
            'status' => PaymentStatus::COMPLETED->value,
            'transaction_reference' => $mpesaData['transaction_id'] ?? null,
            'mpesa_checkout_request_id' => $mpesaData['checkout_request_id'] ?? null,
            'mpesa_transaction_id' => $mpesaData['transaction_id'] ?? null,
            'gateway_response' => $mpesaData,
        ]);
    }

    /**
     * Record card payment
     */
    public function recordCardPayment(int $bookingId, array $cardData): Payment
    {
        return $this->createPayment([
            'booking_id' => $bookingId,
            'branch_id' => Booking::findOrFail($bookingId)->branch_id,
            'amount' => $cardData['amount'],
            'payment_method' => PaymentMethod::CARD->value,
            'status' => PaymentStatus::COMPLETED->value,
            'transaction_reference' => $cardData['transaction_id'] ?? null,
            'card_last_four' => $cardData['card_last_four'] ?? null,
            'card_brand' => $cardData['card_brand'] ?? null,
            'authorization_code' => $cardData['authorization_code'] ?? null,
            'gateway_response' => $cardData,
        ]);
    }

    /**
     * Record cash payment
     */
    public function recordCashPayment(int $bookingId, float $amount): Payment
    {
        return $this->createPayment([
            'booking_id' => $bookingId,
            'branch_id' => Booking::findOrFail($bookingId)->branch_id,
            'amount' => $amount,
            'payment_method' => PaymentMethod::CASH->value,
            'status' => PaymentStatus::COMPLETED->value,
        ]);
    }

    /**
     * Get payments for a branch
     */
    public function getBranchPayments(int $branchId, array $filters = []): Collection
    {
        return $this->paymentRepository->getByBranch($branchId, $filters);
    }

    /**
     * Get today's payments
     */
    public function getTodayPayments(int $branchId): Collection
    {
        return $this->paymentRepository->getTodayPayments($branchId);
    }

    /**
     * Get payment statistics
     */
    public function getPaymentStatistics(int $branchId, ?string $startDate = null, ?string $endDate = null): array
    {
        $startDate = $startDate ?? now()->startOfMonth()->toDateString();
        $endDate = $endDate ?? now()->endOfMonth()->toDateString();

        $totalRevenue = $this->paymentRepository->getTotalRevenue($branchId, $startDate, $endDate);
        $revenueByMethod = $this->paymentRepository->getRevenueByPaymentMethod($branchId, $startDate, $endDate);

        return [
            'total_revenue' => $totalRevenue,
            'revenue_by_method' => $revenueByMethod,
            'total_transactions' => $this->paymentRepository->countByBranch($branchId, [
                'date_from' => $startDate,
                'date_to' => $endDate,
            ]),
            'completed' => $this->paymentRepository->countByBranch($branchId, [
                'status' => [PaymentStatus::COMPLETED->value],
                'date_from' => $startDate,
                'date_to' => $endDate,
            ]),
            'pending' => $this->paymentRepository->countByBranch($branchId, [
                'status' => [PaymentStatus::PENDING->value],
                'date_from' => $startDate,
                'date_to' => $endDate,
            ]),
            'failed' => $this->paymentRepository->countByBranch($branchId, [
                'status' => [PaymentStatus::FAILED->value],
                'date_from' => $startDate,
                'date_to' => $endDate,
            ]),
            'refunded' => $this->paymentRepository->countByBranch($branchId, [
                'status' => [PaymentStatus::REFUNDED->value, PaymentStatus::PARTIALLY_REFUNDED->value],
                'date_from' => $startDate,
                'date_to' => $endDate,
            ]),
        ];
    }

    /**
     * Determine initial payment status based on method
     */
    private function determineInitialStatus(string $paymentMethod): string
    {
        $method = PaymentMethod::from($paymentMethod);

        // Instant payment methods are immediately completed
        if ($method->isInstant()) {
            return PaymentStatus::COMPLETED->value;
        }

        // Online payment methods need processing
        if ($method->requiresOnlineProcessing()) {
            return PaymentStatus::PROCESSING->value;
        }

        return PaymentStatus::PENDING->value;
    }

    /**
     * Validate payment reference uniqueness
     */
    public function isTransactionReferenceUnique(string $reference): bool
    {
        return Payment::where('transaction_reference', $reference)->doesntExist();
    }

    /**
     * Process POS payment
     */
    public function processPosPayment($transaction, array $paymentData): Payment
    {
        DB::beginTransaction();

        try {
            $paymentMethod = $paymentData['payment_method'];
            $amount = $paymentData['amount'];

            // Handle different payment methods
            if ($paymentMethod === 'mpesa') {
                return $this->processMpesaPayment($transaction, $paymentData);
            } elseif ($paymentMethod === 'card') {
                return $this->processCardPayment($transaction, $paymentData);
            } else {
                // Cash, Bank Transfer, etc. - instant completion
                $payment = $this->paymentRepository->create([
                    'branch_id' => $transaction->branch_id,
                    'pos_transaction_id' => $transaction->id,
                    'amount' => $amount,
                    'payment_method' => $paymentMethod,
                    'status' => PaymentStatus::COMPLETED->value,
                    'processed_at' => now(),
                ]);

                DB::commit();
                return $payment;
            }
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Process M-Pesa payment for POS
     */
    private function processMpesaPayment($transaction, array $paymentData): Payment
    {
        $mpesaService = app(MpesaService::class);

        // Validate phone number
        if (!isset($paymentData['mpesa_phone']) || !$mpesaService->validatePhoneNumber($paymentData['mpesa_phone'])) {
            throw new Exception('Invalid M-Pesa phone number');
        }

        // Initiate STK Push
        $stkResponse = $mpesaService->stkPush(
            $paymentData['mpesa_phone'],
            $paymentData['amount'],
            "POS-{$transaction->transaction_number}",
            "Payment for transaction {$transaction->transaction_number}"
        );

        if (!$stkResponse['success']) {
            throw new Exception($stkResponse['error'] ?? 'Failed to initiate M-Pesa payment');
        }

        // Create payment record with pending status
        $payment = $this->paymentRepository->create([
            'branch_id' => $transaction->branch_id,
            'pos_transaction_id' => $transaction->id,
            'amount' => $paymentData['amount'],
            'payment_method' => PaymentMethod::MPESA->value,
            'status' => PaymentStatus::PROCESSING->value,
            'mpesa_checkout_request_id' => $stkResponse['checkout_request_id'],
            'transaction_reference' => $stkResponse['merchant_request_id'],
            'gateway_response' => $stkResponse,
        ]);

        DB::commit();

        return $payment;
    }

    /**
     * Process Card payment for POS
     */
    private function processCardPayment($transaction, array $paymentData): Payment
    {
        // TODO: Integrate with actual card payment gateway (Stripe, Paystack, etc.)
        // For now, we'll simulate a successful card payment

        // In production, you would:
        // 1. Initialize payment with gateway
        // 2. Get authorization URL or payment intent
        // 3. Create payment record with processing status
        // 4. Return payment with gateway details for frontend to complete

        // Simulated card payment
        $payment = $this->paymentRepository->create([
            'branch_id' => $transaction->branch_id,
            'pos_transaction_id' => $transaction->id,
            'amount' => $paymentData['amount'],
            'payment_method' => PaymentMethod::CARD->value,
            'status' => PaymentStatus::PROCESSING->value,
            'transaction_reference' => 'CARD-' . uniqid(),
            'card_last_four' => $paymentData['card_last_four'] ?? '****',
            'gateway_response' => [
                'message' => 'Card payment initiated',
                'simulated' => true,
            ],
        ]);

        // In a real scenario, the payment would be completed via webhook/callback
        // For demo purposes, we'll mark it as completed immediately
        $this->updatePaymentStatus($payment->id, PaymentStatus::COMPLETED->value, [
            'processed_at' => now(),
            'authorization_code' => 'AUTH-' . uniqid(),
        ]);

        DB::commit();

        return $this->paymentRepository->findOrFail($payment->id);
    }

    /**
     * Complete M-Pesa payment from callback
     */
    public function completeMpesaPayment(string $checkoutRequestId, array $callbackData): ?Payment
    {
        DB::beginTransaction();

        try {
            $payment = Payment::where('mpesa_checkout_request_id', $checkoutRequestId)->first();

            if (!$payment) {
                Log::warning('Payment not found for M-Pesa callback', [
                    'checkout_request_id' => $checkoutRequestId,
                ]);
                return null;
            }

            if ($callbackData['success']) {
                $this->updatePaymentStatus($payment->id, PaymentStatus::COMPLETED->value, [
                    'mpesa_transaction_id' => $callbackData['mpesa_receipt_number'],
                    'processed_at' => now(),
                    'gateway_response' => $callbackData,
                ]);
            } else {
                $this->updatePaymentStatus($payment->id, PaymentStatus::FAILED->value, [
                    'gateway_response' => $callbackData,
                    'failure_reason' => $callbackData['result_desc'],
                ]);
            }

            DB::commit();

            return $this->paymentRepository->findOrFail($payment->id);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Failed to complete M-Pesa payment: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Refund payment
     */
    public function refundPayment(int $paymentId, float $amount): Payment
    {
        return $this->processRefund($paymentId, $amount);
    }
}
