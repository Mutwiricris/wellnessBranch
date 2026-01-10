<?php

namespace App\Domain\Payment\Repositories;

use App\Models\Payment;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface PaymentRepositoryInterface
{
    /**
     * Find a payment by ID
     */
    public function find(int $id): ?Model;

    /**
     * Find a payment by ID or fail
     */
    public function findOrFail(int $id): Model;

    /**
     * Create a new payment
     */
    public function create(array $data): Model;

    /**
     * Update a payment
     */
    public function update(int $id, array $data): bool;

    /**
     * Get payments for a branch
     */
    public function getByBranch(int $branchId, array $filters = []): Collection;

    /**
     * Get payments by booking
     */
    public function getByBooking(int $bookingId): Collection;

    /**
     * Get payments by payment method
     */
    public function getByPaymentMethod(int $branchId, string $paymentMethod): Collection;

    /**
     * Get payments by status
     */
    public function getByStatus(int $branchId, string $status): Collection;

    /**
     * Get today's payments
     */
    public function getTodayPayments(int $branchId): Collection;

    /**
     * Get payments by date range
     */
    public function getByDateRange(int $branchId, string $startDate, string $endDate): Collection;

    /**
     * Get paginated payments
     */
    public function getPaginated(int $branchId, int $perPage = 15, array $filters = []): LengthAwarePaginator;

    /**
     * Get total revenue for branch
     */
    public function getTotalRevenue(int $branchId, ?string $startDate = null, ?string $endDate = null): float;

    /**
     * Get revenue by payment method
     */
    public function getRevenueByPaymentMethod(int $branchId, ?string $startDate = null, ?string $endDate = null): array;

    /**
     * Count payments
     */
    public function countByBranch(int $branchId, array $filters = []): int;
}
