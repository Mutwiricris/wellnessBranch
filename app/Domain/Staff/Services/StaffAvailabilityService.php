<?php

namespace App\Domain\Staff\Services;

use App\Domain\Staff\Repositories\StaffRepositoryInterface;
use App\Domain\Staff\Repositories\StaffScheduleRepositoryInterface;
use App\Models\Staff;
use App\Models\StaffSchedule;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Exception;

class StaffAvailabilityService
{
    public function __construct(
        private StaffRepositoryInterface $staffRepository,
        private StaffScheduleRepositoryInterface $scheduleRepository
    ) {}

    /**
     * Create a schedule for staff
     */
    public function createSchedule(array $data): StaffSchedule
    {
        // Check if schedule already exists for this date
        $existingSchedule = $this->scheduleRepository->getForDate($data['staff_id'], $data['date']);

        if ($existingSchedule) {
            throw new Exception('Schedule already exists for this date. Please update the existing schedule.');
        }

        return $this->scheduleRepository->create($data);
    }

    /**
     * Update a schedule
     */
    public function updateSchedule(int $scheduleId, array $data): StaffSchedule
    {
        $this->scheduleRepository->update($scheduleId, $data);
        return $this->scheduleRepository->findOrFail($scheduleId);
    }

    /**
     * Delete a schedule
     */
    public function deleteSchedule(int $scheduleId): bool
    {
        return $this->scheduleRepository->delete($scheduleId);
    }

    /**
     * Get schedules for staff
     */
    public function getStaffSchedules(int $staffId, ?string $startDate = null, ?string $endDate = null): Collection
    {
        return $this->scheduleRepository->getByStaff($staffId, $startDate, $endDate);
    }

    /**
     * Get schedules for branch
     */
    public function getBranchSchedules(int $branchId, ?string $startDate = null, ?string $endDate = null): Collection
    {
        return $this->scheduleRepository->getByBranch($branchId, $startDate, $endDate);
    }

    /**
     * Check if staff is available for a time slot
     */
    public function isStaffAvailableForTimeSlot(int $staffId, string $date, string $startTime, string $endTime): bool
    {
        // Check schedule availability
        if (!$this->scheduleRepository->isStaffAvailable($staffId, $date, $startTime, $endTime)) {
            return false;
        }

        // Check for conflicting bookings
        $staff = $this->staffRepository->findOrFail($staffId);

        $hasConflict = $staff->bookings()
            ->where('appointment_date', $date)
            ->whereIn('status', ['pending', 'confirmed', 'in_progress'])
            ->where(function ($query) use ($startTime, $endTime) {
                // Check for time overlap
                $query->whereBetween('start_time', [$startTime, $endTime])
                    ->orWhereBetween('end_time', [$startTime, $endTime])
                    ->orWhere(function ($q) use ($startTime, $endTime) {
                        $q->where('start_time', '<=', $startTime)
                            ->where('end_time', '>=', $endTime);
                    });
            })
            ->exists();

        return !$hasConflict;
    }

    /**
     * Get available staff for a time slot
     */
    public function getAvailableStaff(int $branchId, string $date, string $startTime, string $endTime, ?int $serviceId = null): Collection
    {
        $availableStaff = $this->staffRepository->getAvailableForTimeSlot($branchId, $date, $startTime, $endTime);

        // Filter by service if provided
        if ($serviceId) {
            $availableStaff = $availableStaff->filter(function ($staff) use ($serviceId) {
                return $staff->services->contains('id', $serviceId);
            });
        }

        return $availableStaff;
    }

    /**
     * Get available time slots for staff on a date
     */
    public function getAvailableTimeSlots(int $staffId, string $date, int $durationMinutes = 60): array
    {
        $schedule = $this->scheduleRepository->getForDate($staffId, $date);

        if (!$schedule || !$schedule->is_available) {
            return [];
        }

        $staff = $this->staffRepository->findOrFail($staffId);

        // Get existing bookings for the day
        $bookings = $staff->bookings()
            ->where('appointment_date', $date)
            ->whereIn('status', ['pending', 'confirmed', 'in_progress'])
            ->orderBy('start_time')
            ->get(['start_time', 'end_time']);

        // Generate time slots
        $slots = [];
        $workingPeriods = $schedule->working_hours;

        foreach ($workingPeriods as $period) {
            $currentTime = \Carbon\Carbon::parse($period['start']);
            $endTime = \Carbon\Carbon::parse($period['end']);

            while ($currentTime->copy()->addMinutes($durationMinutes) <= $endTime) {
                $slotStart = $currentTime->format('H:i');
                $slotEnd = $currentTime->copy()->addMinutes($durationMinutes)->format('H:i');

                // Check if slot conflicts with any booking
                $hasConflict = $bookings->contains(function ($booking) use ($slotStart, $slotEnd) {
                    $bookingStart = \Carbon\Carbon::parse($booking->start_time)->format('H:i');
                    $bookingEnd = \Carbon\Carbon::parse($booking->end_time)->format('H:i');

                    return ($slotStart >= $bookingStart && $slotStart < $bookingEnd) ||
                           ($slotEnd > $bookingStart && $slotEnd <= $bookingEnd) ||
                           ($slotStart <= $bookingStart && $slotEnd >= $bookingEnd);
                });

                if (!$hasConflict) {
                    $slots[] = [
                        'start_time' => $slotStart,
                        'end_time' => $slotEnd,
                        'available' => true,
                    ];
                }

                $currentTime->addMinutes($durationMinutes);
            }
        }

        return $slots;
    }

    /**
     * Bulk create schedules for staff
     */
    public function bulkCreateSchedules(int $staffId, int $branchId, array $scheduleData): bool
    {
        DB::beginTransaction();

        try {
            $schedules = [];

            foreach ($scheduleData as $data) {
                $schedules[] = array_merge($data, [
                    'staff_id' => $staffId,
                    'branch_id' => $branchId,
                ]);
            }

            $result = $this->scheduleRepository->bulkCreate($schedules);

            DB::commit();

            return $result;

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Mark staff as unavailable for a date
     */
    public function markAsUnavailable(int $staffId, string $date, ?string $reason = null): StaffSchedule
    {
        $schedule = $this->scheduleRepository->getForDate($staffId, $date);

        if (!$schedule) {
            throw new Exception('No schedule found for this date');
        }

        $this->scheduleRepository->update($schedule->id, [
            'is_available' => false,
            'notes' => $reason,
        ]);

        return $this->scheduleRepository->findOrFail($schedule->id);
    }

    /**
     * Mark staff as available for a date
     */
    public function markAsAvailable(int $staffId, string $date): StaffSchedule
    {
        $schedule = $this->scheduleRepository->getForDate($staffId, $date);

        if (!$schedule) {
            throw new Exception('No schedule found for this date');
        }

        $this->scheduleRepository->update($schedule->id, [
            'is_available' => true,
        ]);

        return $this->scheduleRepository->findOrFail($schedule->id);
    }

    /**
     * Get upcoming schedules for staff
     */
    public function getUpcomingSchedules(int $staffId, int $days = 7): Collection
    {
        return $this->scheduleRepository->getUpcoming($staffId, $days);
    }

    /**
     * Get schedule statistics for staff
     */
    public function getScheduleStatistics(int $staffId, string $startDate, string $endDate): array
    {
        $schedules = $this->scheduleRepository->getForDateRange($staffId, $startDate, $endDate);

        $totalDays = $schedules->count();
        $availableDays = $schedules->where('is_available', true)->count();
        $totalWorkingMinutes = $schedules->sum('total_working_minutes');

        return [
            'total_scheduled_days' => $totalDays,
            'available_days' => $availableDays,
            'unavailable_days' => $totalDays - $availableDays,
            'total_working_hours' => round($totalWorkingMinutes / 60, 2),
            'average_daily_hours' => $totalDays > 0 ? round(($totalWorkingMinutes / 60) / $totalDays, 2) : 0,
        ];
    }
}
