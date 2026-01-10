<?php

namespace App\Domain\Booking\Repositories;

use App\Models\Booking;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface BookingRepositoryInterface
{
    /**
     * Find a booking by ID
     */
    public function find(int $id): ?Model;

    /**
     * Find a booking by ID or fail
     */
    public function findOrFail(int $id): Model;

    /**
     * Create a new booking
     */
    public function create(array $data): Model;

    /**
     * Update a booking
     */
    public function update(int $id, array $data): bool;

    /**
     * Delete a booking
     */
    public function delete(int $id): bool;

    /**
     * Get all bookings for a branch
     */
    public function getByBranch(int $branchId, array $filters = []): Collection;

    /**
     * Get upcoming bookings for a branch
     */
    public function getUpcomingByBranch(int $branchId, int $limit = 10): Collection;

    /**
     * Get bookings by date range
     */
    public function getByDateRange(int $branchId, string $startDate, string $endDate): Collection;

    /**
     * Get bookings for a specific date
     */
    public function getByDate(int $branchId, string $date): Collection;

    /**
     * Get bookings for a specific staff member
     */
    public function getByStaff(int $staffId, string $date): Collection;

    /**
     * Get bookings for a specific client
     */
    public function getByClient(int $clientId): Collection;

    /**
     * Get today's bookings for a branch
     */
    public function getTodayBookings(int $branchId): Collection;

    /**
     * Get bookings by status
     */
    public function getByStatus(int $branchId, string $status): Collection;

    /**
     * Get paginated bookings
     */
    public function getPaginated(int $branchId, int $perPage = 15, array $filters = []): LengthAwarePaginator;

    /**
     * Check if a time slot is available
     */
    public function isTimeSlotAvailable(int $staffId, string $date, string $startTime, string $endTime, ?int $excludeBookingId = null): bool;

    /**
     * Get bookings count for a branch
     */
    public function countByBranch(int $branchId, array $filters = []): int;
}
