<?php

namespace App\Domain\Staff\Repositories;

use App\Domain\Shared\Repositories\BaseRepository;
use App\Models\StaffSchedule;
use Illuminate\Database\Eloquent\Collection;

class StaffScheduleRepository extends BaseRepository implements StaffScheduleRepositoryInterface
{
    /**
     * Resolve the StaffSchedule model
     */
    protected function resolveModel(): StaffSchedule
    {
        return new StaffSchedule();
    }

    /**
     * Get schedules for a staff member
     */
    public function getByStaff(int $staffId, ?string $startDate = null, ?string $endDate = null): Collection
    {
        $query = $this->query()->where('staff_id', $staffId);

        if ($startDate && $endDate) {
            $query->whereBetween('date', [$startDate, $endDate]);
        } elseif ($startDate) {
            $query->where('date', '>=', $startDate);
        }

        return $query->orderBy('date')
            ->orderBy('start_time')
            ->get();
    }

    /**
     * Get schedules for a branch
     */
    public function getByBranch(int $branchId, ?string $startDate = null, ?string $endDate = null): Collection
    {
        $query = $this->query()->where('branch_id', $branchId);

        if ($startDate && $endDate) {
            $query->whereBetween('date', [$startDate, $endDate]);
        } elseif ($startDate) {
            $query->where('date', '>=', $startDate);
        }

        return $query->with('staff')
            ->orderBy('date')
            ->orderBy('start_time')
            ->get();
    }

    /**
     * Get schedule for specific date
     */
    public function getForDate(int $staffId, string $date): ?StaffSchedule
    {
        return $this->query()
            ->where('staff_id', $staffId)
            ->where('date', $date)
            ->first();
    }

    /**
     * Get schedules for date range
     */
    public function getForDateRange(int $staffId, string $startDate, string $endDate): Collection
    {
        return $this->query()
            ->where('staff_id', $staffId)
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date')
            ->get();
    }

    /**
     * Get available staff schedules for date
     */
    public function getAvailableForDate(int $branchId, string $date): Collection
    {
        return $this->query()
            ->where('branch_id', $branchId)
            ->where('date', $date)
            ->where('is_available', true)
            ->with('staff')
            ->orderBy('start_time')
            ->get();
    }

    /**
     * Check if staff is available at specific time
     */
    public function isStaffAvailable(int $staffId, string $date, string $startTime, string $endTime): bool
    {
        $schedule = $this->getForDate($staffId, $date);

        if (!$schedule || !$schedule->is_available) {
            return false;
        }

        // Check if requested time is within schedule
        if ($startTime < $schedule->start_time || $endTime > $schedule->end_time) {
            return false;
        }

        // Check if time conflicts with break
        if ($schedule->hasBreak()) {
            $breakStart = $schedule->break_start->format('H:i');
            $breakEnd = $schedule->break_end->format('H:i');

            // Check if requested time overlaps with break
            if (($startTime >= $breakStart && $startTime < $breakEnd) ||
                ($endTime > $breakStart && $endTime <= $breakEnd) ||
                ($startTime <= $breakStart && $endTime >= $breakEnd)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get upcoming schedules for staff
     */
    public function getUpcoming(int $staffId, int $days = 7): Collection
    {
        $startDate = now()->toDateString();
        $endDate = now()->addDays($days)->toDateString();

        return $this->getForDateRange($staffId, $startDate, $endDate);
    }

    /**
     * Bulk create schedules
     */
    public function bulkCreate(array $schedules): bool
    {
        try {
            foreach ($schedules as $scheduleData) {
                $this->create($scheduleData);
            }
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
