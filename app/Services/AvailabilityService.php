<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Service;
use App\Models\Staff;
use App\Models\Branch;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class AvailabilityService
{
    /**
     * Get available dates for a service
     */
    public function getAvailableDates(int $serviceId, int $branchId, int $daysAhead = 30): Collection
    {
        $service = Service::findOrFail($serviceId);
        $availableDates = collect();
        $startDate = now()->startOfDay();
        $maxAdvanceBookingDays = $service->max_advance_booking_days ?? 60;
        
        // Limit days ahead to service's maximum advance booking days
        $daysAhead = min($daysAhead, $maxAdvanceBookingDays);
        
        for ($i = 0; $i < $daysAhead * 2; $i++) { // Check double to account for excluded days
            $date = $startDate->copy()->addDays($i);
            
            // Skip past dates and Sundays (assuming spa is closed on Sundays)
            if ($date->gte(now()->startOfDay()) && $date->dayOfWeek !== 0) {
                $availableDates->push([
                    'date' => $date->format('Y-m-d'),
                    'formatted' => $date->format('M j'),
                    'day_name' => $date->format('l'),
                    'is_today' => $date->isToday(),
                    'is_tomorrow' => $date->isTomorrow(),
                    'year' => $date->format('Y'),
                    'has_availability' => $this->hasAvailabilityOnDate($date, $serviceId, $branchId)
                ]);
            }
            
            // Stop when we have enough available dates
            if ($availableDates->count() >= $daysAhead) {
                break;
            }
        }
        
        return $availableDates;
    }
    
    /**
     * Get available time slots for a specific date
     */
    public function getAvailableTimeSlots(string $date, int $serviceId, int $branchId, ?int $staffId = null): Collection
    {
        $service = Service::findOrFail($serviceId);
        $serviceDuration = $service->duration ?? 60; // Default 60 minutes
        $bufferTime = $service->buffer_time_minutes ?? 15; // Buffer between appointments
        
        // Spa operating hours: 9 AM to 6 PM
        $startHour = 9;
        $endHour = 18;
        $slotInterval = 30; // 30-minute intervals
        
        $timeSlots = collect();
        $requestDate = Carbon::parse($date);
        
        // Get existing bookings for this date and branch
        $existingBookings = Booking::where('appointment_date', $date)
            ->where('branch_id', $branchId)
            ->where('status', '!=', 'cancelled')
            ->when($staffId, function($query) use ($staffId) {
                return $query->where('staff_id', $staffId);
            })
            ->get();
        
        // Get available staff for this service if no specific staff selected
        $availableStaff = collect();
        if (!$staffId) {
            $availableStaff = Staff::where('status', 'active')
                ->whereHas('branches', function($query) use ($branchId) {
                    $query->where('branch_id', $branchId)->where('status', 'active');
                })
                ->whereHas('services', function($query) use ($serviceId) {
                    $query->where('service_id', $serviceId)->where('status', 'active');
                })
                ->get();
        } else {
            // If staff is selected, ensure we can still generate slots even if they're busy
            // We'll check individual slot availability below
        }
        
        // Generate time slots - SIMPLE VERSION
        for ($hour = $startHour; $hour < $endHour; $hour++) {
            for ($minute = 0; $minute < 60; $minute += $slotInterval) {
                $slotTime = sprintf('%02d:%02d', $hour, $minute);
                $slotDateTime = Carbon::parse($date . ' ' . $slotTime);
                $slotEndTime = $slotDateTime->copy()->addMinutes($serviceDuration);
                
                // Skip if slot would extend beyond operating hours
                if ($slotEndTime->hour >= $endHour) {
                    continue;
                }
                
                // Start with available = true, then check if booked
                $isAvailable = true;
                $assignedStaff = null;
                
                // Simple booking check - is this time slot already booked?
                $conflict = $existingBookings->first(function($booking) use ($slotDateTime, $slotEndTime, $staffId) {
                    // If we have a specific staff, only check conflicts for that staff
                    if ($staffId && $booking->staff_id != $staffId) return false;
                    
                    $bookingStart = Carbon::parse($booking->appointment_date . ' ' . $booking->start_time);
                    $bookingEnd = Carbon::parse($booking->appointment_date . ' ' . $booking->end_time);
                    
                    return ($slotDateTime < $bookingEnd && $slotEndTime > $bookingStart);
                });
                
                $isAvailable = !$conflict;
                
                // If specific staff selected, use them
                if ($staffId) {
                    $assignedStaff = Staff::find($staffId);
                } elseif ($availableStaff->count() > 0) {
                    // Auto-assign first available staff
                    $assignedStaff = $availableStaff->first();
                }
                
                // Simple reason
                $reason = $isAvailable ? 'Available' : 'Booked';
                
                $timeSlots->push([
                    'time' => $slotTime,
                    'end_time' => $slotEndTime->format('H:i'),
                    'available' => $isAvailable,
                    'is_past' => false,
                    'disabled' => false, // Never disable, let user try to book
                    'staff_id' => $assignedStaff ? $assignedStaff->id : null,
                    'staff_name' => $assignedStaff ? $assignedStaff->name : null,
                    'formatted_time' => $slotDateTime->format('g:i A'),
                    'reason' => $reason,
                    'is_today' => $requestDate->isToday()
                ]);
            }
        }
        
        return $timeSlots;
    }
    
    /**
     * Get all qualified staff for a service (always available for selection)
     */
    public function getAvailableStaffForService(int $serviceId, int $branchId, string $date): Collection
    {
        return Staff::where('status', 'active')
            ->whereHas('branches', function($query) use ($branchId) {
                $query->where('branch_id', $branchId)->where('status', 'active');
            })
            ->whereHas('services', function($query) use ($serviceId) {
                $query->where('service_id', $serviceId)->where('status', 'active');
            })
            ->with(['services' => function($query) use ($serviceId) {
                $query->where('service_id', $serviceId);
            }])
            ->get()
            ->map(function($staff) use ($date, $serviceId, $branchId) {
                // Get workload info for display purposes only
                $todayBookings = Booking::where('staff_id', $staff->id)
                    ->where('appointment_date', $date)
                    ->where('status', '!=', 'cancelled')
                    ->count();
                
                // Calculate available slots for information only (doesn't affect selection)
                $availableSlots = $this->getAvailableTimeSlots($date, $serviceId, $branchId, $staff->id)
                    ->where('available', true)
                    ->count();
                    
                return [
                    'id' => $staff->id,
                    'name' => $staff->name,
                    'first_name' => $staff->first_name,
                    'last_name' => $staff->last_name,
                    'specialization' => $staff->specialization ?? 'General',
                    'available_slots' => $availableSlots,
                    'scheduled_bookings' => $todayBookings,
                    'is_available' => true, // All qualified staff are always selectable
                    'workload_status' => $this->getWorkloadStatus($todayBookings),
                    'display_label' => $this->formatStaffDisplayLabel($staff, $availableSlots, $todayBookings)
                ];
            })
            ->sortBy('name'); // Sort by name instead of availability
    }
    
    /**
     * Get workload status for staff member
     */
    private function getWorkloadStatus(int $bookingCount): string
    {
        if ($bookingCount == 0) return 'Free';
        if ($bookingCount <= 2) return 'Light';
        if ($bookingCount <= 4) return 'Moderate';
        if ($bookingCount <= 6) return 'Busy';
        return 'Fully Booked';
    }
    
    /**
     * Format staff display label with availability info
     */
    private function formatStaffDisplayLabel($staff, int $availableSlots, int $bookingCount): string
    {
        $label = $staff->name;
        
        if ($staff->specialization && $staff->specialization !== 'General') {
            $label .= ' - ' . $staff->specialization;
        }
        
        // Show workload information for reference, but don't restrict selection
        $workloadStatus = $this->getWorkloadStatus($bookingCount);
        if ($workloadStatus !== 'Free') {
            $label .= " - {$workloadStatus}";
        }
        
        // Optionally show available slot count for information
        if ($availableSlots > 0) {
            $label .= " ({$availableSlots} slots available)";
        }
        
        return $label;
    }
    
    /**
     * Auto-assign best available staff member for a specific time slot
     */
    public function getBestAvailableStaff(int $serviceId, int $branchId, string $date, ?string $time = null): ?array
    {
        // Get all qualified staff for this service
        $qualifiedStaff = Staff::where('status', 'active')
            ->whereHas('branches', function($query) use ($branchId) {
                $query->where('branch_id', $branchId)->where('status', 'active');
            })
            ->whereHas('services', function($query) use ($serviceId) {
                $query->where('service_id', $serviceId)->where('status', 'active');
            })
            ->get();
            
        if ($qualifiedStaff->isEmpty()) {
            return null;
        }
        
        // If specific time is provided, check availability for that exact slot
        if ($time) {
            $service = Service::findOrFail($serviceId);
            $serviceDuration = $service->duration ?? 60;
            $slotDateTime = Carbon::parse($date . ' ' . $time);
            $slotEndTime = $slotDateTime->copy()->addMinutes($serviceDuration);
            
            // Get existing bookings for this date and time range
            $conflictingBookings = Booking::where('appointment_date', $date)
                ->where('branch_id', $branchId)
                ->where('status', '!=', 'cancelled')
                ->get();
            
            $availableStaffForSlot = collect();
            
            foreach ($qualifiedStaff as $staff) {
                // Check if this staff member has a conflict at the requested time
                $hasConflict = $conflictingBookings->first(function($booking) use ($slotDateTime, $slotEndTime, $staff) {
                    if ($booking->staff_id != $staff->id) return false;
                    
                    $bookingStart = Carbon::parse($booking->appointment_date . ' ' . $booking->start_time);
                    $bookingEnd = Carbon::parse($booking->appointment_date . ' ' . $booking->end_time);
                    
                    return ($slotDateTime < $bookingEnd && $slotEndTime > $bookingStart);
                });
                
                if (!$hasConflict) {
                    // Get today's booking count for workload balancing
                    $todayBookings = Booking::where('staff_id', $staff->id)
                        ->where('appointment_date', $date)
                        ->where('branch_id', $branchId)
                        ->where('status', '!=', 'cancelled')
                        ->count();
                    
                    $availableStaffForSlot->push([
                        'id' => $staff->id,
                        'name' => $staff->name,
                        'first_name' => $staff->first_name,
                        'last_name' => $staff->last_name,
                        'specialization' => $staff->specialization ?? 'General',
                        'scheduled_bookings' => $todayBookings,
                        'workload_status' => $this->getWorkloadStatus($todayBookings),
                        'is_available' => true
                    ]);
                }
            }
            
            if ($availableStaffForSlot->isEmpty()) {
                return null;
            }
            
            // Return staff member with least bookings for the day (balanced workload)
            return $availableStaffForSlot->sortBy('scheduled_bookings')->first();
        }
        
        // If no specific time provided, return staff with least bookings for the day
        $staffWithWorkload = $qualifiedStaff->map(function($staff) use ($date, $branchId) {
            $todayBookings = Booking::where('staff_id', $staff->id)
                ->where('appointment_date', $date)
                ->where('branch_id', $branchId)
                ->where('status', '!=', 'cancelled')
                ->count();
            
            return [
                'id' => $staff->id,
                'name' => $staff->name,
                'first_name' => $staff->first_name,
                'last_name' => $staff->last_name,
                'specialization' => $staff->specialization ?? 'General',
                'scheduled_bookings' => $todayBookings,
                'workload_status' => $this->getWorkloadStatus($todayBookings),
                'is_available' => true
            ];
        })->sortBy('scheduled_bookings');
        
        return $staffWithWorkload->first();
    }
    
    /**
     * Check if a specific time slot is available (with proper staff assignment logic)
     */
    public function isSpecificTimeSlotAvailable(string $date, ?string $time, int $serviceId, int $branchId, ?int $staffId = null): bool
    {
        if (empty($time)) {
            return false;
        }
        
        $service = Service::findOrFail($serviceId);
        $serviceDuration = $service->duration ?? 60;
        
        $slotDateTime = Carbon::parse($date . ' ' . $time);
        $slotEndTime = $slotDateTime->copy()->addMinutes($serviceDuration);
        
        // Simple check - is this time slot already booked?
        $conflictingBooking = Booking::where('appointment_date', $date)
            ->where('branch_id', $branchId)
            ->where('status', '!=', 'cancelled')
            ->when($staffId, function($query) use ($staffId) {
                return $query->where('staff_id', $staffId);
            })
            ->where(function($query) use ($slotDateTime, $slotEndTime) {
                $query->whereRaw('TIME(start_time) < ? AND TIME(end_time) > ?', [
                    $slotEndTime->format('H:i:s'),
                    $slotDateTime->format('H:i:s')
                ]);
            })
            ->exists();
            
        return !$conflictingBooking;
    }
    
    /**
     * Check if there's any availability on a specific date
     */
    private function hasAvailabilityOnDate(Carbon $date, int $serviceId, int $branchId): bool
    {
        $timeSlots = $this->getAvailableTimeSlots($date->format('Y-m-d'), $serviceId, $branchId);
        return $timeSlots->where('available', true)->count() > 0;
    }
    
    /**
     * Get next available appointment slot
     */
    public function getNextAvailableSlot(int $serviceId, int $branchId, ?int $staffId = null): ?array
    {
        $dates = $this->getAvailableDates($serviceId, $branchId, 14); // Check next 2 weeks
        
        foreach ($dates as $dateInfo) {
            $timeSlots = $this->getAvailableTimeSlots($dateInfo['date'], $serviceId, $branchId, $staffId);
            $availableSlot = $timeSlots->where('available', true)->first();
            
            if ($availableSlot) {
                return [
                    'date' => $dateInfo['date'],
                    'formatted_date' => $dateInfo['formatted'],
                    'time' => $availableSlot['time'],
                    'formatted_time' => $availableSlot['formatted_time'],
                    'staff_id' => $availableSlot['staff_id'],
                    'staff_name' => $availableSlot['staff_name']
                ];
            }
        }
        
        return null;
    }
}