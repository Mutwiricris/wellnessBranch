<?php

namespace App\Domain\Staff\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

interface StaffScheduleRepositoryInterface
{
    /**
     * Find a schedule by ID
     */
    public function find(int $id): ?Model;

    /**
     * Find a schedule by ID or fail
     */
    public function findOrFail(int $id): Model;

    /**
     * Create a new schedule
     */
    public function create(array $data): Model;

    /**
     * Update a schedule
     */
    public function update(int $id, array $data): bool;

    /**
     * Delete a schedule
     */
    public function delete(int $id): bool;

    /**
     * Get schedules for a staff member
     */
    public function getByStaff(int $staffId, ?string $startDate = null, ?string $endDate = null): Collection;

    /**
     * Get schedules for a branch
     */
    public function getByBranch(int $branchId, ?string $startDate = null, ?string $endDate = null): Collection;

    /**
     * Get schedule for specific date
     */
    public function getForDate(int $staffId, string $date): ?StaffSchedule;

    /**
     * Get schedules for date range
     */
    public function getForDateRange(int $staffId, string $startDate, string $endDate): Collection;

    /**
     * Get available staff schedules for date
     */
    public function getAvailableForDate(int $branchId, string $date): Collection;

    /**
     * Check if staff is available at specific time
     */
    public function isStaffAvailable(int $staffId, string $date, string $startTime, string $endTime): bool;

    /**
     * Get upcoming schedules for staff
     */
    public function getUpcoming(int $staffId, int $days = 7): Collection;

    /**
     * Bulk create schedules
     */
    public function bulkCreate(array $schedules): bool;
}
