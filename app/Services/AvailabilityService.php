<?php

namespace App\Services;

use App\Domain\Staff\Services\StaffAvailabilityService;
use App\Domain\Booking\Repositories\BookingRepositoryInterface;
use App\Models\Service;
use App\Models\Staff;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class AvailabilityService
{
    public function __construct(
        protected StaffAvailabilityService $staffAvailabilityService,
        protected BookingRepositoryInterface $bookingRepository
    ) {}

    /**
     * Get available dates for a service and branch
     */
    public function getAvailableDates(int $serviceId, int $branchId, int $days = 30): Collection
    {
        $dates = collect();
        $startDate = now();
        $endDate = now()->addDays($days);
        
        $service = Service::findOrFail($serviceId);
        
        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            $formattedDate = $date->toDateString();
            
            // Get all active staff at this branch who provide this service
            $staffMembers = Staff::whereHas('branches', function ($query) use ($branchId) {
                $query->where('branch_id', $branchId);
            })->whereHas('services', function ($query) use ($serviceId) {
                $query->where('service_id', $serviceId);
            })->where('status', 'active')->get();
            
            $hasAvailability = false;
            foreach ($staffMembers as $staff) {
                $slots = $this->staffAvailabilityService->getAvailableTimeSlots($staff->id, $formattedDate, $service->duration);
                if (!empty($slots)) {
                    $hasAvailability = true;
                    break;
                }
            }
            
            $dates->push([
                'date' => $formattedDate,
                'label' => $date->format('D, M j'),
                'has_availability' => $hasAvailability,
            ]);
        }
        
        return $dates;
    }

    /**
     * Get available time slots for a specific date, service, and branch
     */
    public function getAvailableTimeSlots(string $date, int $serviceId, int $branchId, ?int $staffId = null): Collection
    {
        $service = Service::findOrFail($serviceId);
        
        if ($staffId) {
            $slots = $this->staffAvailabilityService->getAvailableTimeSlots($staffId, $date, $service->duration);
            return collect($slots)->mapWithKeys(function ($slot) {
                return [$slot['start_time'] => $slot['start_time']];
            });
        }
        
        // If no staff specified, get slots from all staff who provide this service at this branch
        $staffMembers = Staff::whereHas('branches', function ($query) use ($branchId) {
            $query->where('branch_id', $branchId);
        })->whereHas('services', function ($query) use ($serviceId) {
            $query->where('service_id', $serviceId);
        })->where('status', 'active')->get();
        
        $allSlots = collect();
        foreach ($staffMembers as $staff) {
            $slots = $this->staffAvailabilityService->getAvailableTimeSlots($staff->id, $date, $service->duration);
            foreach ($slots as $slot) {
                $allSlots->push($slot['start_time']);
            }
        }
        
        return $allSlots->unique()->sort()->mapWithKeys(function ($time) {
            return [$time => $time];
        });
    }

    /**
     * Get available staff for a specific date and time slot
     */
    public function getAvailableStaff(string $date, string $startTime, int $serviceId, int $branchId): Collection
    {
        $service = Service::findOrFail($serviceId);
        $endTime = Carbon::parse($startTime)->addMinutes($service->duration)->format('H:i');
        
        return $this->staffAvailabilityService->getAvailableStaff($branchId, $date, $startTime, $endTime, $serviceId);
    }
}
