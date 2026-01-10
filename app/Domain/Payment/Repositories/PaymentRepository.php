<?php

namespace App\Domain\Payment\Repositories;

use App\Domain\Shared\Repositories\BaseRepository;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class PaymentRepository extends BaseRepository implements PaymentRepositoryInterface
{
    /**
     * Resolve the Payment model
     */
    protected function resolveModel(): Payment
    {
        return new Payment();
    }

    /**
     * Get payments for a branch with optional filters
     */
    public function getByBranch(int $branchId, array $filters = []): Collection
    {
        $query = $this->query()->where('branch_id', $branchId);

        // Apply filters
        if (isset($filters['status'])) {
            $query->whereIn('status', (array) $filters['status']);
        }

        if (isset($filters['payment_method'])) {
            $query->whereIn('payment_method', (array) $filters['payment_method']);
        }

        if (isset($filters['booking_id'])) {
            $query->where('booking_id', $filters['booking_id']);
        }

        if (isset($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return $query->with(['booking', 'booking.client', 'booking.service', 'branch'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get payments by booking
     */
    public function getByBooking(int $bookingId): Collection
    {
        return $this->query()
            ->where('booking_id', $bookingId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get payments by payment method
     */
    public function getByPaymentMethod(int $branchId, string $paymentMethod): Collection
    {
        return $this->query()
            ->where('branch_id', $branchId)
            ->where('payment_method', $paymentMethod)
            ->orderBy('created_at', 'desc')
            ->with(['booking', 'booking.client'])
            ->get();
    }

    /**
     * Get payments by status
     */
    public function getByStatus(int $branchId, string $status): Collection
    {
        return $this->query()
            ->where('branch_id', $branchId)
            ->where('status', $status)
            ->orderBy('created_at', 'desc')
            ->with(['booking', 'booking.client'])
            ->get();
    }

    /**
     * Get today's payments
     */
    public function getTodayPayments(int $branchId): Collection
    {
        return $this->query()
            ->where('branch_id', $branchId)
            ->whereDate('created_at', now()->toDateString())
            ->orderBy('created_at', 'desc')
            ->with(['booking', 'booking.client', 'booking.service'])
            ->get();
    }

    /**
     * Get payments by date range
     */
    public function getByDateRange(int $branchId, string $startDate, string $endDate): Collection
    {
        return $this->query()
            ->where('branch_id', $branchId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->orderBy('created_at', 'desc')
            ->with(['booking', 'booking.client', 'booking.service'])
            ->get();
    }

    /**
     * Get paginated payments
     */
    public function getPaginated(int $branchId, int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        $query = $this->query()->where('branch_id', $branchId);

        // Apply same filters as getByBranch
        if (isset($filters['status'])) {
            $query->whereIn('status', (array) $filters['status']);
        }

        if (isset($filters['payment_method'])) {
            $query->whereIn('payment_method', (array) $filters['payment_method']);
        }

        if (isset($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return $query->with(['booking', 'booking.client', 'booking.service', 'branch'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get total revenue for branch
     */
    public function getTotalRevenue(int $branchId, ?string $startDate = null, ?string $endDate = null): float
    {
        $query = $this->query()
            ->where('branch_id', $branchId)
            ->where('status', 'completed');

        if ($startDate) {
            $query->whereDate('created_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->whereDate('created_at', '<=', $endDate);
        }

        return (float) $query->sum('amount');
    }

    /**
     * Get revenue by payment method
     */
    public function getRevenueByPaymentMethod(int $branchId, ?string $startDate = null, ?string $endDate = null): array
    {
        $query = $this->query()
            ->where('branch_id', $branchId)
            ->where('status', 'completed');

        if ($startDate) {
            $query->whereDate('created_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->whereDate('created_at', '<=', $endDate);
        }

        return $query->selectRaw('payment_method, SUM(amount) as total')
            ->groupBy('payment_method')
            ->pluck('total', 'payment_method')
            ->toArray();
    }

    /**
     * Count payments
     */
    public function countByBranch(int $branchId, array $filters = []): int
    {
        $query = $this->query()->where('branch_id', $branchId);

        if (isset($filters['status'])) {
            $query->whereIn('status', (array) $filters['status']);
        }

        if (isset($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return $query->count();
    }
}
