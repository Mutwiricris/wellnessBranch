<?php

namespace App\Domain\Staff\Repositories;

use App\Domain\Shared\Repositories\BaseRepository;
use App\Models\Staff;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class StaffRepository extends BaseRepository implements StaffRepositoryInterface
{
    /**
     * Resolve the Staff model
     */
    protected function resolveModel(): Staff
    {
        return new Staff();
    }

    /**
     * Get all staff members for a branch
     */
    public function getByBranch(int $branchId, array $filters = []): Collection
    {
        $query = $this->model->newQuery()
            ->whereHas('branches', function ($q) use ($branchId) {
                $q->where('branches.id', $branchId);
            });

        // Apply filters
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['service_id'])) {
            $query->whereHas('services', function ($q) use ($filters) {
                $q->where('services.id', $filters['service_id']);
            });
        }

        return $query->with(['branches', 'services'])
            ->orderBy('name')
            ->get();
    }

    /**
     * Get active staff members
     */
    public function getActive(int $branchId): Collection
    {
        return $this->getByBranch($branchId, ['status' => 'active']);
    }

    /**
     * Get staff members by service
     */
    public function getByService(int $serviceId): Collection
    {
        return $this->model->newQuery()
            ->whereHas('services', function ($q) use ($serviceId) {
                $q->where('services.id', $serviceId);
            })
            ->where('status', 'active')
            ->with(['services', 'branches'])
            ->get();
    }

    /**
     * Get staff members available for a time slot
     */
    public function getAvailableForTimeSlot(int $branchId, string $date, string $startTime, string $endTime): Collection
    {
        return $this->model->newQuery()
            ->whereHas('branches', function ($q) use ($branchId) {
                $q->where('branches.id', $branchId);
            })
            ->where('status', 'active')
            ->whereHas('schedules', function ($q) use ($date, $startTime, $endTime) {
                $q->where('date', $date)
                    ->where('is_available', true)
                    ->where('start_time', '<=', $startTime)
                    ->where('end_time', '>=', $endTime);
            })
            ->whereDoesntHave('bookings', function ($q) use ($date, $startTime, $endTime) {
                $q->where('appointment_date', $date)
                    ->whereIn('status', ['pending', 'confirmed', 'in_progress'])
                    ->where(function ($query) use ($startTime, $endTime) {
                        // Check for time overlap
                        $query->whereBetween('start_time', [$startTime, $endTime])
                            ->orWhereBetween('end_time', [$startTime, $endTime])
                            ->orWhere(function ($q) use ($startTime, $endTime) {
                                $q->where('start_time', '<=', $startTime)
                                    ->where('end_time', '>=', $endTime);
                            });
                    });
            })
            ->with(['services', 'branches'])
            ->get();
    }

    /**
     * Get paginated staff members
     */
    public function getPaginated(int $branchId, int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        $query = $this->model->newQuery()
            ->whereHas('branches', function ($q) use ($branchId) {
                $q->where('branches.id', $branchId);
            });

        // Apply same filters as getByBranch
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['service_id'])) {
            $query->whereHas('services', function ($q) use ($filters) {
                $q->where('services.id', $filters['service_id']);
            });
        }

        return $query->with(['branches', 'services'])
            ->orderBy('name')
            ->paginate($perPage);
    }

    /**
     * Get staff performance metrics
     */
    public function getPerformanceMetrics(int $staffId, ?string $startDate = null, ?string $endDate = null): array
    {
        $staff = $this->findOrFail($staffId);

        return [
            'total_bookings' => $staff->getTotalBookings($startDate, $endDate),
            'completed_bookings' => $staff->getCompletedBookings($startDate, $endDate),
            'completion_rate' => $staff->getCompletionRate($startDate, $endDate),
            'total_revenue' => $staff->getTotalRevenue($startDate, $endDate),
            'average_rating' => $staff->getAverageRating(),
            'top_services' => $staff->getTopServices(5),
        ];
    }

    /**
     * Get staff working at specific branch
     */
    public function getWorkingAtBranch(int $branchId): Collection
    {
        return $this->model->newQuery()
            ->whereHas('branches', function ($q) use ($branchId) {
                $q->where('branches.id', $branchId);
            })
            ->with(['branches' => function ($q) use ($branchId) {
                $q->where('branches.id', $branchId);
            }])
            ->orderBy('name')
            ->get();
    }

    /**
     * Search staff by name, email, or phone
     */
    public function search(string $query, int $branchId): Collection
    {
        return $this->model->newQuery()
            ->whereHas('branches', function ($q) use ($branchId) {
                $q->where('branches.id', $branchId);
            })
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('email', 'like', "%{$query}%")
                    ->orWhere('phone', 'like', "%{$query}%");
            })
            ->with(['branches', 'services'])
            ->orderBy('name')
            ->get();
    }
}
