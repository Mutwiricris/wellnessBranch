<?php

namespace App\Http\Controllers;

use App\Services\AvailabilityService;
use Illuminate\Http\Request;

class AvailabilityController extends Controller
{
    protected AvailabilityService $availabilityService;

    public function __construct(AvailabilityService $availabilityService)
    {
        $this->availabilityService = $availabilityService;
    }

    /**
     * Get available dates for a service
     */
    public function getDates(Request $request)
    {
        $request->validate([
            'service_id' => 'required|exists:services,id',
            'branch_id' => 'required|exists:branches,id',
            'days_ahead' => 'nullable|integer|min:1|max:90',
        ]);

        $dates = $this->availabilityService->getAvailableDates(
            $request->service_id,
            $request->branch_id,
            $request->days_ahead ?? 30
        );

        return response()->json($dates);
    }

    /**
     * Get available time slots for a specific date
     */
    public function getTimeSlots(Request $request)
    {
        try {
            $request->validate([
                'date' => 'required|date|after_or_equal:today',
                'service_id' => 'required|exists:services,id',
                'branch_id' => 'required|exists:branches,id',
                'staff_id' => 'nullable|exists:staff,id',
            ]);

            \Log::info('=== API: Getting time slots ===', [
                'date' => $request->date,
                'service_id' => $request->service_id,
                'branch_id' => $request->branch_id,
                'staff_id' => $request->staff_id,
                'current_time' => \Carbon\Carbon::now('Africa/Nairobi')->format('Y-m-d H:i:s T')
            ]);

            $timeSlots = $this->availabilityService->getAvailableTimeSlots(
                $request->date,
                $request->service_id,
                $request->branch_id,
                $request->staff_id
            );

            \Log::info('Time slots retrieved via API', [
                'total_slots' => $timeSlots->count(),
                'available_slots' => $timeSlots->where('available', true)->count()
            ]);

            return response()->json([
                'success' => true,
                'data' => $timeSlots,
                'meta' => [
                    'total_slots' => $timeSlots->count(),
                    'available_slots' => $timeSlots->where('available', true)->count(),
                    'date' => $request->date,
                    'timezone' => 'Africa/Nairobi'
                ]
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid request parameters',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('API: Time slots loading failed', [
                'error' => $e->getMessage(),
                'request_data' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Unable to load time slots. Please try again.'
            ], 500);
        }
    }

    /**
     * Get available staff for a service
     */
    public function getStaff(Request $request)
    {
        $request->validate([
            'service_id' => 'required|exists:services,id',
            'branch_id' => 'required|exists:branches,id',
            'date' => 'required|date|after_or_equal:today',
        ]);

        $staff = $this->availabilityService->getAvailableStaffForService(
            $request->service_id,
            $request->branch_id,
            $request->date
        );

        return response()->json($staff);
    }

    /**
     * Check if a specific time slot is available
     */
    public function checkTimeSlot(Request $request)
    {
        try {
            $request->validate([
                'date' => 'required|date|after_or_equal:today',
                'time' => 'required|date_format:H:i',
                'service_id' => 'required|exists:services,id',
                'branch_id' => 'required|exists:branches,id',
                'staff_id' => 'nullable|exists:staff,id',
            ]);

            \Log::info('=== API: Checking specific time slot ===', [
                'date' => $request->date,
                'time' => $request->time,
                'service_id' => $request->service_id,
                'branch_id' => $request->branch_id,
                'staff_id' => $request->staff_id,
                'current_time' => \Carbon\Carbon::now('Africa/Nairobi')->format('Y-m-d H:i:s T')
            ]);

            $available = $this->availabilityService->isSpecificTimeSlotAvailable(
                $request->date,
                $request->time,
                $request->service_id,
                $request->branch_id,
                $request->staff_id
            );

            \Log::info('API: Time slot availability check result', [
                'available' => $available,
                'date' => $request->date,
                'time' => $request->time
            ]);

            // If not available and no staff specified, try to get auto-assignment suggestion
            $staffSuggestion = null;
            if (!$available && !$request->staff_id) {
                $bestStaff = $this->availabilityService->getBestAvailableStaff(
                    $request->service_id,
                    $request->branch_id,
                    $request->date,
                    $request->time
                );
                
                if ($bestStaff) {
                    $staffSuggestion = [
                        'id' => $bestStaff['id'],
                        'name' => $bestStaff['name'],
                        'message' => "This time slot is available with {$bestStaff['name']}"
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'available' => $available,
                'message' => $available ? 'Time slot is available' : 'Time slot is not available',
                'staff_suggestion' => $staffSuggestion,
                'meta' => [
                    'date' => $request->date,
                    'time' => $request->time,
                    'timezone' => 'Africa/Nairobi',
                    'checked_at' => \Carbon\Carbon::now('Africa/Nairobi')->format('Y-m-d H:i:s T')
                ]
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid request parameters',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('API: Time slot availability check failed', [
                'error' => $e->getMessage(),
                'request_data' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Unable to check time slot availability. Please try again.',
                'available' => false
            ], 500);
        }
    }
}