<?php

namespace App\Domain\POS\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface PosTransactionRepositoryInterface
{
    /**
     * Find a transaction by ID
     */
    public function find(int $id): ?Model;

    /**
     * Find a transaction by ID or fail
     */
    public function findOrFail(int $id): Model;

    /**
     * Find transaction by transaction number
     */
    public function findByTransactionNumber(string $transactionNumber);

    /**
     * Create a new transaction
     */
    public function create(array $data): Model;

    /**
     * Update a transaction
     */
    public function update(int $id, array $data): bool;

    /**
     * Delete a transaction
     */
    public function delete(int $id): bool;

    /**
     * Get transactions for branch
     */
    public function getForBranch(int $branchId, array $filters = []): Collection;

    /**
     * Get completed transactions
     */
    public function getCompleted(int $branchId): Collection;

    /**
     * Get today's transactions
     */
    public function getToday(int $branchId): Collection;

    /**
     * Get transactions by date range
     */
    public function getByDateRange(int $branchId, string $startDate, string $endDate): Collection;

    /**
     * Get transactions by payment method
     */
    public function getByPaymentMethod(int $branchId, string $paymentMethod): Collection;

    /**
     * Get paginated transactions
     */
    public function getPaginated(int $branchId, int $perPage = 15, array $filters = []): LengthAwarePaginator;

    /**
     * Get daily summary
     */
    public function getDailySummary(int $branchId, string $date): array;
}
