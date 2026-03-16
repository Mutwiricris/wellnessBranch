<?php

namespace App\Domain\Booking\Repositories;

use App\Domain\Shared\Repositories\BaseRepository;
use App\Models\Booking;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class BookingRepository extends BaseRepository implements BookingRepositoryInterface
{

    protected function resolveModel(): Booking
    {
        return new Booking();
    }

    /**
     * Get all bookings for a branch with optional filters
     */
    public function getByBranch(int $branchId, array $filters = []): Collection
    {
        $query = $this->query()->where('branch_id', $branchId);

        // Apply filters
        if (isset($filters['status'])) {
            $query->whereIn('status', (array) $filters['status']);
        }

        if (isset($filters['payment_status'])) {
            $query->where('payment_status', $filters['payment_status']);
        }

        if (isset($filters['service_id'])) {
            $query->where('service_id', $filters['service_id']);
        }

        if (isset($filters['staff_id'])) {
            $query->where('staff_id', $filters['staff_id']);
        }

        if (isset($filters['client_id'])) {
            $query->where('client_id', $filters['client_id']);
        }

        if (isset($filters['date_from'])) {
            $query->where('appointment_date', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('appointment_date', '<=', $filters['date_to']);
        }

        return $query->with(['client', 'service', 'staff', 'payment', 'branch'])
            ->orderBy('appointment_date', 'desc')
            ->orderBy('start_time', 'desc')
            ->get();
    }

    /**
     * Get upcoming bookings for a branch
     */
    public function getUpcomingByBranch(int $branchId, int $limit = 10): Collection
    {
        return $this->query()
            ->where('branch_id', $branchId)
            ->where('appointment_date', '>=', now()->toDateString())
            ->whereIn('status', ['pending', 'confirmed'])
            ->orderBy('appointment_date')
            ->orderBy('start_time')
            ->limit($limit)
            ->with(['client', 'service', 'staff'])
            ->get();
    }

    /**
     * Get bookings by date range
     */
    public function getByDateRange(int $branchId, string $startDate, string $endDate): Collection
    {
        return $this->query()
            ->where('branch_id', $branchId)
            ->whereBetween('appointment_date', [$startDate, $endDate])
            ->orderBy('appointment_date')
            ->orderBy('start_time')
            ->with(['client', 'service', 'staff', 'payment'])
            ->get();
    }

    /**
     * Get bookings for a specific date
     */
    public function getByDate(int $branchId, string $date): Collection
    {
        return $this->query()
            ->where('branch_id', $branchId)
            ->where('appointment_date', $date)
            ->orderBy('start_time')
            ->with(['client', 'service', 'staff', 'payment'])
            ->get();
    }

    /**
     * Get bookings for a specific staff member
     */
    public function getByStaff(int $staffId, string $date): Collection
    {
        return $this->query()
            ->where('staff_id', $staffId)
            ->where('appointment_date', $date)
            ->whereIn('status', ['confirmed', 'in_progress'])
            ->orderBy('start_time')
            ->with(['client', 'service'])
            ->get();
    }

    /**
     * Get bookings for a specific client
     */
    public function getByClient(int $clientId): Collection
    {
        return $this->query()
            ->where('client_id', $clientId)
            ->orderBy('appointment_date', 'desc')
            ->orderBy('start_time', 'desc')
            ->with(['service', 'staff', 'branch', 'payment'])
            ->get();
    }

    /**
     * Get today's bookings for a branch
     */
    public function getTodayBookings(int $branchId): Collection
    {
        return $this->getByDate($branchId, now()->toDateString());
    }

    /**
     * Get bookings by status
     */
    public function getByStatus(int $branchId, string $status): Collection
    {
        return $this->query()
            ->where('branch_id', $branchId)
            ->where('status', $status)
            ->orderBy('appointment_date', 'desc')
            ->orderBy('start_time', 'desc')
            ->with(['client', 'service', 'staff', 'payment'])
            ->get();
    }

    /**
     * Get paginated bookings
     */
    public function getPaginated(int $branchId, int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        $query = $this->query()->where('branch_id', $branchId);

        // Apply same filters as getByBranch
        if (isset($filters['status'])) {
            $query->whereIn('status', (array) $filters['status']);
        }

        if (isset($filters['payment_status'])) {
            $query->where('payment_status', $filters['payment_status']);
        }

        if (isset($filters['service_id'])) {
            $query->where('service_id', $filters['service_id']);
        }

        if (isset($filters['staff_id'])) {
            $query->where('staff_id', $filters['staff_id']);
        }

        if (isset($filters['client_id'])) {
            $query->where('client_id', $filters['client_id']);
        }

        if (isset($filters['date_from'])) {
            $query->where('appointment_date', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('appointment_date', '<=', $filters['date_to']);
        }

        return $query->with(['client', 'service', 'staff', 'payment', 'branch'])
            ->orderBy('appointment_date', 'desc')
            ->orderBy('start_time', 'desc')
            ->paginate($perPage);
    }

    /**
     * Check if a time slot is available for a staff member
     */
    public function isTimeSlotAvailable(
        int $staffId,
        string $date,
        string $startTime,
        string $endTime,
        ?int $excludeBookingId = null
    ): bool {
        $query = $this->query()
            ->where('staff_id', $staffId)
            ->where('appointment_date', $date)
            ->whereIn('status', ['confirmed', 'in_progress'])
            ->where(function ($q) use ($startTime, $endTime) {
                // Check for overlapping time slots
                $q->where(function ($query) use ($startTime, $endTime) {
                    // New booking starts during existing booking
                    $query->where('start_time', '<=', $startTime)
                        ->where('end_time', '>', $startTime);
                })->orWhere(function ($query) use ($startTime, $endTime) {
                    // New booking ends during existing booking
                    $query->where('start_time', '<', $endTime)
                        ->where('end_time', '>=', $endTime);
                })->orWhere(function ($query) use ($startTime, $endTime) {
                    // New booking completely encompasses existing booking
                    $query->where('start_time', '>=', $startTime)
                        ->where('end_time', '<=', $endTime);
                });
            });

        // Exclude current booking if updating
        if ($excludeBookingId) {
            $query->where('id', '!=', $excludeBookingId);
        }

        return $query->count() === 0;
    }

    /**
     * Get bookings count for a branch
     */
    public function countByBranch(int $branchId, array $filters = []): int
    {
        $query = $this->query()->where('branch_id', $branchId);

        if (isset($filters['status'])) {
            $query->whereIn('status', (array) $filters['status']);
        }

        if (isset($filters['date_from'])) {
            $query->where('appointment_date', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('appointment_date', '<=', $filters['date_to']);
        }

        return $query->count();
    }
}
