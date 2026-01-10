<?php

namespace App\Domain\POS\Repositories;

use App\Domain\Shared\Repositories\BaseRepository;
use App\Models\PosTransaction;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class PosTransactionRepository extends BaseRepository implements PosTransactionRepositoryInterface
{
    /**
     * Resolve the model instance for this repository
     */
    protected function resolveModel(): \Illuminate\Database\Eloquent\Model
    {
        return new PosTransaction();
    }

    /**
     * Find a transaction by ID
     */
    public function find(int $id): ?\Illuminate\Database\Eloquent\Model
    {
        return $this->model->newQuery()
            ->with(['items', 'customer', 'staff', 'branch', 'payments'])
            ->find($id);
    }

    /**
     * Find a transaction by ID or fail
     */
    public function findOrFail(int $id): \Illuminate\Database\Eloquent\Model
    {
        return $this->model->newQuery()
            ->with(['items', 'customer', 'staff', 'branch', 'payments'])
            ->findOrFail($id);
    }

    /**
     * Find transaction by transaction number
     */
    public function findByTransactionNumber(string $transactionNumber)
    {
        return $this->model->newQuery()
            ->where('transaction_number', $transactionNumber)
            ->with(['items', 'customer', 'staff', 'branch', 'payments'])
            ->first();
    }

    /**
     * Create a new transaction
     */
    public function create(array $data): \Illuminate\Database\Eloquent\Model
    {
        return DB::transaction(function () use ($data) {
            $transaction = $this->model->create($data);

            if (isset($data['items']) && is_array($data['items'])) {
                foreach ($data['items'] as $item) {
                    $transaction->items()->create($item);
                }
            }

            return $transaction->load(['items', 'customer', 'staff', 'branch']);
        });
    }

    /**
     * Update a transaction
     */
    public function update(int $id, array $data): bool
    {
        $transaction = $this->findOrFail($id);
        return $transaction->update($data);
    }

    /**
     * Delete a transaction
     */
    public function delete(int $id): bool
    {
        $transaction = $this->findOrFail($id);
        return $transaction->delete();
    }

    /**
     * Get transactions for branch
     */
    public function getForBranch(int $branchId, array $filters = []): Collection
    {
        $query = $this->model->newQuery()
            ->where('branch_id', $branchId)
            ->with(['items', 'customer', 'staff', 'payments']);

        // Apply filters
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['payment_status'])) {
            $query->where('payment_status', $filters['payment_status']);
        }

        if (isset($filters['payment_method'])) {
            $query->where('payment_method', $filters['payment_method']);
        }

        if (isset($filters['transaction_type'])) {
            $query->where('transaction_type', $filters['transaction_type']);
        }

        if (isset($filters['customer_id'])) {
            $query->where('customer_id', $filters['customer_id']);
        }

        if (isset($filters['staff_id'])) {
            $query->where('staff_id', $filters['staff_id']);
        }

        if (isset($filters['start_date']) && isset($filters['end_date'])) {
            $query->whereBetween('created_at', [$filters['start_date'], $filters['end_date']]);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * Get completed transactions
     */
    public function getCompleted(int $branchId): Collection
    {
        return $this->model->newQuery()
            ->where('branch_id', $branchId)
            ->where('status', 'completed')
            ->with(['items', 'customer', 'staff', 'payments'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get today's transactions
     */
    public function getToday(int $branchId): Collection
    {
        return $this->model->newQuery()
            ->where('branch_id', $branchId)
            ->whereDate('created_at', today())
            ->with(['items', 'customer', 'staff', 'payments'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get transactions by date range
     */
    public function getByDateRange(int $branchId, string $startDate, string $endDate): Collection
    {
        return $this->model->newQuery()
            ->where('branch_id', $branchId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->with(['items', 'customer', 'staff', 'payments'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get transactions by payment method
     */
    public function getByPaymentMethod(int $branchId, string $paymentMethod): Collection
    {
        return $this->model->newQuery()
            ->where('branch_id', $branchId)
            ->where('payment_method', $paymentMethod)
            ->with(['items', 'customer', 'staff', 'payments'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get paginated transactions
     */
    public function getPaginated(int $branchId, int $perPage = 15, array $filters = []): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = $this->model->newQuery()
            ->where('branch_id', $branchId)
            ->with(['items', 'customer', 'staff', 'payments']);

        // Apply filters
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['payment_status'])) {
            $query->where('payment_status', $filters['payment_status']);
        }

        if (isset($filters['payment_method'])) {
            $query->where('payment_method', $filters['payment_method']);
        }

        if (isset($filters['start_date']) && isset($filters['end_date'])) {
            $query->whereBetween('created_at', [$filters['start_date'], $filters['end_date']]);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * Get daily summary
     */
    public function getDailySummary(int $branchId, string $date): array
    {
        $transactions = $this->model->newQuery()
            ->where('branch_id', $branchId)
            ->whereDate('created_at', $date)
            ->with(['items', 'payments'])
            ->get();

        $completed = $transactions->where('status', 'completed');
        $cancelled = $transactions->where('status', 'cancelled');
        $refunded = $transactions->where('status', 'refunded');

        // Group by payment method
        $byPaymentMethod = $completed->groupBy('payment_method');
        $paymentMethodBreakdown = [];

        foreach ($byPaymentMethod as $method => $methodTransactions) {
            $paymentMethodBreakdown[$method] = [
                'count' => $methodTransactions->count(),
                'total' => $methodTransactions->sum('total_amount'),
                'paid' => $methodTransactions->sum('amount_paid'),
            ];
        }

        // Group by transaction type
        $byType = $completed->groupBy('transaction_type');
        $typeBreakdown = [];

        foreach ($byType as $type => $typeTransactions) {
            $typeBreakdown[$type] = [
                'count' => $typeTransactions->count(),
                'total' => $typeTransactions->sum('total_amount'),
            ];
        }

        return [
            'date' => $date,
            'branch_id' => $branchId,
            'total_transactions' => $transactions->count(),
            'completed_transactions' => $completed->count(),
            'cancelled_transactions' => $cancelled->count(),
            'refunded_transactions' => $refunded->count(),
            'gross_sales' => $completed->sum('subtotal'),
            'discounts' => $completed->sum('discount_amount'),
            'tax' => $completed->sum('tax_amount'),
            'net_sales' => $completed->sum('total_amount'),
            'amount_paid' => $completed->sum('amount_paid'),
            'amount_due' => $completed->sum('amount_due'),
            'refunded_amount' => $refunded->sum('total_amount'),
            'payment_method_breakdown' => $paymentMethodBreakdown,
            'transaction_type_breakdown' => $typeBreakdown,
            'items_sold' => $completed->sum(fn($t) => $t->items->sum('quantity')),
            'average_transaction_value' => $completed->count() > 0
                ? $completed->sum('total_amount') / $completed->count()
                : 0,
        ];
    }

    /**
     * Get pending transactions
     */
    public function getPending(int $branchId): Collection
    {
        return $this->model->newQuery()
            ->where('branch_id', $branchId)
            ->where('status', 'pending')
            ->with(['items', 'customer', 'staff'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get transactions with outstanding balance
     */
    public function getWithOutstandingBalance(int $branchId): Collection
    {
        return $this->model->newQuery()
            ->where('branch_id', $branchId)
            ->where('payment_status', 'partial')
            ->where('amount_due', '>', 0)
            ->with(['items', 'customer', 'staff', 'payments'])
            ->orderBy('created_at', 'desc')
            ->get();
    }
}
