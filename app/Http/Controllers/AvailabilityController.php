<?php

namespace App\Http\Controllers;

use App\Services\AvailabilityService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AvailabilityController extends Controller
{
    public function __construct(
        protected AvailabilityService $availabilityService
    ) {}

    /**
     * Get available dates for a service
     */
    public function getDates(Request $request): JsonResponse
    {
        $request->validate([
            'service_id' => 'required|exists:services,id',
            'branch_id' => 'required|exists:branches,id',
            'days' => 'nullable|integer|min:1|max:90',
        ]);

        $dates = $this->availabilityService->getAvailableDates(
            $request->service_id,
            $request->branch_id,
            $request->days ?? 30
        );

        return response()->json($dates);
    }

    /**
     * Get available time slots for a date and service
     */
    public function getTimeSlots(Request $request): JsonResponse
    {
        $request->validate([
            'date' => 'required|date_format:Y-m-d',
            'service_id' => 'required|exists:services,id',
            'branch_id' => 'required|exists:branches,id',
            'staff_id' => 'nullable|exists:staff,id',
        ]);

        $slots = $this->availabilityService->getAvailableTimeSlots(
            $request->date,
            $request->service_id,
            $request->branch_id,
            $request->staff_id
        );

        return response()->json($slots);
    }

    /**
     * Get available staff for a date and time slot
     */
    public function getStaff(Request $request): JsonResponse
    {
        $request->validate([
            'date' => 'required|date_format:Y-m-d',
            'start_time' => 'required|date_format:H:i',
            'service_id' => 'required|exists:services,id',
            'branch_id' => 'required|exists:branches,id',
        ]);

        $staff = $this->availabilityService->getAvailableStaff(
            $request->date,
            $request->start_time,
            $request->service_id,
            $request->branch_id
        );

        return response()->json($staff);
    }

    /**
     * Check if a specific time slot is available
     */
    public function checkTimeSlot(Request $request): JsonResponse
    {
        $request->validate([
            'date' => 'required|date_format:Y-m-d',
            'start_time' => 'required|date_format:H:i',
            'service_id' => 'required|exists:services,id',
            'branch_id' => 'required|exists:branches,id',
            'staff_id' => 'nullable|exists:staff,id',
        ]);

        $slots = $this->availabilityService->getAvailableTimeSlots(
            $request->date,
            $request->service_id,
            $request->branch_id,
            $request->staff_id
        );

        $isAvailable = isset($slots[$request->start_time]);

        return response()->json([
            'available' => $isAvailable,
        ]);
    }
}
