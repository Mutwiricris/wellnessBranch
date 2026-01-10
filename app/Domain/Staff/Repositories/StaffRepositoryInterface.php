<?php

namespace App\Domain\Staff\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface StaffRepositoryInterface
{
    /**
     * Find a staff member by ID
     */
    public function find(int $id): ?Model;

    /**
     * Find a staff member by ID or fail
     */
    public function findOrFail(int $id): Model;

    /**
     * Create a new staff member
     */
    public function create(array $data): Model;

    /**
     * Update a staff member
     */
    public function update(int $id, array $data): bool;

    /**
     * Delete a staff member
     */
    public function delete(int $id): bool;

    /**
     * Get all staff members for a branch
     */
    public function getByBranch(int $branchId, array $filters = []): Collection;

    /**
     * Get active staff members
     */
    public function getActive(int $branchId): Collection;

    /**
     * Get staff members by service
     */
    public function getByService(int $serviceId): Collection;

    /**
     * Get staff members available for a time slot
     */
    public function getAvailableForTimeSlot(int $branchId, string $date, string $startTime, string $endTime): Collection;

    /**
     * Get paginated staff members
     */
    public function getPaginated(int $branchId, int $perPage = 15, array $filters = []): LengthAwarePaginator;

    /**
     * Get staff performance metrics
     */
    public function getPerformanceMetrics(int $staffId, ?string $startDate = null, ?string $endDate = null): array;

    /**
     * Get staff working at specific branch
     */
    public function getWorkingAtBranch(int $branchId): Collection;

    /**
     * Search staff by name, email, or phone
     */
    public function search(string $query, int $branchId): Collection;
}
