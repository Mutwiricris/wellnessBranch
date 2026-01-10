<?php

namespace App\Domain\POS\Services;

use App\Domain\POS\Repositories\PosTransactionRepositoryInterface;
use App\Domain\Inventory\Services\InventoryService;
use App\Domain\Payment\Services\PaymentService;
use App\Models\PosTransaction;
use Illuminate\Support\Facades\DB;
use Exception;

class PosService
{
    public function __construct(
        private PosTransactionRepositoryInterface $repository,
        private InventoryService $inventoryService,
        private PaymentService $paymentService
    ) {}

    /**
     * Create a new POS transaction
     */
    public function createTransaction(int $branchId, array $cartItems, array $metadata = []): PosTransaction
    {
        DB::beginTransaction();
        try {
            // Calculate totals
            $subtotal = 0;
            $taxAmount = 0;
            $items = [];

            foreach ($cartItems as $item) {
                $itemSubtotal = $item['unit_price'] * $item['quantity'];
                $itemTax = $itemSubtotal * ($item['tax_rate'] ?? 0) / 100;

                $subtotal += $itemSubtotal;
                $taxAmount += $itemTax;

                $items[] = [
                    'item_type' => $item['item_type'],
                    'item_id' => $item['item_id'],
                    'product_id' => $item['product_id'] ?? null,
                    'service_id' => $item['service_id'] ?? null,
                    'product_variant_id' => $item['product_variant_id'] ?? null,
                    'name' => $item['name'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'discount_amount' => $item['discount_amount'] ?? 0,
                    'tax_rate' => $item['tax_rate'] ?? 0,
                    'tax_amount' => $itemTax,
                    'total_amount' => $itemSubtotal + $itemTax - ($item['discount_amount'] ?? 0),
                    'staff_id' => $item['staff_id'] ?? null,
                    'notes' => $item['notes'] ?? null,
                ];

                // Reserve inventory for products
                if ($item['item_type'] === 'product' && isset($item['product_id'])) {
                    $this->inventoryService->reserveInventory(
                        $item['product_id'],
                        $branchId,
                        $item['quantity']
                    );
                }
            }

            $discountAmount = $metadata['discount_amount'] ?? 0;
            $totalAmount = $subtotal + $taxAmount - $discountAmount;

            // Create transaction
            $transactionData = [
                'branch_id' => $branchId,
                'customer_id' => $metadata['customer_id'] ?? null,
                'staff_id' => $metadata['staff_id'] ?? null, // Nullable - for tracking service provider
                'transaction_type' => $metadata['transaction_type'] ?? 'sale',
                'status' => 'pending',
                'payment_status' => 'unpaid',
                'payment_method' => $metadata['payment_method'] ?? null,
                'subtotal' => $subtotal,
                'discount_amount' => $discountAmount,
                'discount_type' => $metadata['discount_type'] ?? null,
                'tax_amount' => $taxAmount,
                'total_amount' => $totalAmount,
                'amount_paid' => 0,
                'amount_due' => $totalAmount,
                'notes' => $metadata['notes'] ?? null,
                'items' => $items,
            ];

            $transaction = $this->repository->create($transactionData);

            DB::commit();
            return $transaction;
        } catch (Exception $e) {
            DB::rollBack();

            // Release reserved inventory on failure
            foreach ($cartItems as $item) {
                if ($item['item_type'] === 'product' && isset($item['product_id'])) {
                    try {
                        $this->inventoryService->releaseReservation(
                            $item['product_id'],
                            $branchId,
                            $item['quantity']
                        );
                    } catch (Exception $releaseException) {
                        // Log but don't throw - we're already in error state
                    }
                }
            }

            throw $e;
        }
    }

    /**
     * Process payment for a transaction
     */
    public function processPayment(int $transactionId, array $paymentData): PosTransaction
    {
        DB::beginTransaction();
        try {
            $transaction = $this->repository->findOrFail($transactionId);

            if ($transaction->status === 'completed') {
                throw new Exception('Transaction is already completed');
            }

            if ($transaction->status === 'cancelled') {
                throw new Exception('Cannot process payment for cancelled transaction');
            }

            // Handle split payments
            if (isset($paymentData['payments']) && is_array($paymentData['payments'])) {
                $totalPaid = 0;

                foreach ($paymentData['payments'] as $payment) {
                    $paymentRecord = $this->paymentService->processPosPayment(
                        $transaction,
                        $payment
                    );
                    $totalPaid += $paymentRecord->amount;
                }

                $amountPaid = $transaction->amount_paid + $totalPaid;
            } else {
                // Single payment
                $paymentRecord = $this->paymentService->processPosPayment(
                    $transaction,
                    $paymentData
                );
                $amountPaid = $transaction->amount_paid + $paymentRecord->amount;
            }

            // Update transaction payment status
            $amountDue = $transaction->total_amount - $amountPaid;
            $paymentStatus = $this->determinePaymentStatus($amountPaid, $transaction->total_amount);

            $this->repository->update($transactionId, [
                'amount_paid' => $amountPaid,
                'amount_due' => max(0, $amountDue),
                'payment_status' => $paymentStatus,
                'payment_method' => $paymentData['payment_method'] ?? $transaction->payment_method,
                'status' => $paymentStatus === 'paid' ? 'completed' : 'pending',
            ]);

            // If fully paid, consume inventory
            if ($paymentStatus === 'paid') {
                foreach ($transaction->items as $item) {
                    if ($item->isProduct()) {
                        $this->inventoryService->consumeInventory(
                            $item->product_id,
                            $transaction->branch_id,
                            $item->quantity,
                            'sale',
                            $transactionId,
                            $transaction->staff_id
                        );
                    }
                }
            }

            DB::commit();
            return $this->repository->findOrFail($transactionId);
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Cancel a transaction
     */
    public function cancelTransaction(int $transactionId, string $reason = null): PosTransaction
    {
        DB::beginTransaction();
        try {
            $transaction = $this->repository->findOrFail($transactionId);

            if ($transaction->status === 'completed') {
                throw new Exception('Cannot cancel a completed transaction. Use refund instead.');
            }

            // Release reserved inventory
            foreach ($transaction->items as $item) {
                if ($item->isProduct()) {
                    $this->inventoryService->releaseReservation(
                        $item->product_id,
                        $transaction->branch_id,
                        $item->quantity
                    );
                }
            }

            $this->repository->update($transactionId, [
                'status' => 'cancelled',
                'notes' => $transaction->notes . "\nCancelled: " . ($reason ?? 'No reason provided'),
            ]);

            DB::commit();
            return $this->repository->findOrFail($transactionId);
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Refund a transaction
     */
    public function refundTransaction(int $transactionId, float $refundAmount, string $reason = null): PosTransaction
    {
        DB::beginTransaction();
        try {
            $transaction = $this->repository->findOrFail($transactionId);

            if ($transaction->status !== 'completed') {
                throw new Exception('Can only refund completed transactions');
            }

            if ($refundAmount > $transaction->amount_paid) {
                throw new Exception('Refund amount cannot exceed amount paid');
            }

            // Process refund through payment service
            $this->paymentService->refundPayment(
                $transaction->payments()->latest()->first()->id,
                $refundAmount
            );

            // Return inventory
            foreach ($transaction->items as $item) {
                if ($item->isProduct()) {
                    $this->inventoryService->adjustStock(
                        $item->product_id,
                        $transaction->branch_id,
                        $item->quantity,
                        'return',
                        $transactionId,
                        $transaction->staff_id
                    );
                }
            }

            $this->repository->update($transactionId, [
                'status' => 'refunded',
                'notes' => $transaction->notes . "\nRefunded: KES " . number_format($refundAmount, 2) . " - " . ($reason ?? 'No reason provided'),
            ]);

            DB::commit();
            return $this->repository->findOrFail($transactionId);
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Generate receipt for transaction
     */
    public function generateReceipt(int $transactionId): array
    {
        $transaction = $this->repository->findOrFail($transactionId);

        return [
            'transaction_number' => $transaction->transaction_number,
            'date' => $transaction->created_at->format('Y-m-d H:i:s'),
            'branch' => $transaction->branch->name ?? 'N/A',
            'customer' => $transaction->customer->name ?? 'Walk-in Customer',
            'staff' => $transaction->staff->name ?? 'N/A',
            'items' => $transaction->items->map(fn($item) => [
                'name' => $item->name,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'discount' => $item->discount_amount,
                'tax' => $item->tax_amount,
                'total' => $item->total_amount,
            ])->toArray(),
            'subtotal' => $transaction->subtotal,
            'discount' => $transaction->discount_amount,
            'tax' => $transaction->tax_amount,
            'total' => $transaction->total_amount,
            'amount_paid' => $transaction->amount_paid,
            'amount_due' => $transaction->amount_due,
            'payment_method' => $transaction->payment_method,
            'payment_status' => $transaction->payment_status,
            'status' => $transaction->status,
        ];
    }

    /**
     * Get daily summary
     */
    public function getDailySummary(int $branchId, string $date): array
    {
        return $this->repository->getDailySummary($branchId, $date);
    }

    /**
     * Get today's transactions
     */
    public function getTodayTransactions(int $branchId)
    {
        return $this->repository->getToday($branchId);
    }

    /**
     * Get pending transactions
     */
    public function getPendingTransactions(int $branchId)
    {
        return $this->repository->getPending($branchId);
    }

    /**
     * Get transactions with outstanding balance
     */
    public function getOutstandingTransactions(int $branchId)
    {
        return $this->repository->getWithOutstandingBalance($branchId);
    }

    /**
     * Determine payment status based on amounts
     */
    private function determinePaymentStatus(float $amountPaid, float $totalAmount): string
    {
        if ($amountPaid == 0) {
            return 'unpaid';
        } elseif ($amountPaid >= $totalAmount) {
            return 'paid';
        } elseif ($amountPaid > $totalAmount) {
            return 'overpaid';
        } else {
            return 'partial';
        }
    }
}
