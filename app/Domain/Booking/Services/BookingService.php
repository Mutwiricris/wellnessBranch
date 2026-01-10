<?php

namespace App\Domain\Booking\Services;

use App\Domain\Booking\Repositories\BookingRepositoryInterface;
use App\Models\Booking;
use App\Support\Enums\BookingStatus;
use App\Support\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Exception;

class BookingService
{
    public function __construct(
        private BookingRepositoryInterface $bookingRepository
    ) {}

    /**
     * Create a new booking
     */
    public function createBooking(array $data): Booking
    {
        DB::beginTransaction();

        try {
            // Validate time slot availability
            $this->validateTimeSlotAvailability(
                $data['staff_id'],
                $data['appointment_date'],
                $data['start_time'],
                $data['end_time']
            );

            // Set initial statuses
            $data['status'] = BookingStatus::PENDING->value;
            $data['payment_status'] = PaymentStatus::PENDING->value;

            // Generate booking reference if not provided
            if (!isset($data['booking_reference'])) {
                $data['booking_reference'] = $this->generateBookingReference();
            }

            // Create booking
            $booking = $this->bookingRepository->create($data);

            DB::commit();

            // TODO: Dispatch event for notifications
            // event(new BookingCreated($booking));

            return $booking->load(['client', 'service', 'staff', 'branch']);

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Update an existing booking
     */
    public function updateBooking(int $bookingId, array $data): Booking
    {
        DB::beginTransaction();

        try {
            $booking = $this->bookingRepository->findOrFail($bookingId);

            // If changing time/staff, validate availability
            if (isset($data['staff_id']) || isset($data['appointment_date']) ||
                isset($data['start_time']) || isset($data['end_time'])) {

                $this->validateTimeSlotAvailability(
                    $data['staff_id'] ?? $booking->staff_id,
                    $data['appointment_date'] ?? $booking->appointment_date,
                    $data['start_time'] ?? $booking->start_time,
                    $data['end_time'] ?? $booking->end_time,
                    $bookingId // Exclude current booking
                );
            }

            $this->bookingRepository->update($bookingId, $data);

            DB::commit();

            return $this->bookingRepository->findOrFail($bookingId);

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Confirm a booking (requires completed payment)
     */
    public function confirmBooking(int $bookingId): Booking
    {
        $booking = $this->bookingRepository->findOrFail($bookingId);

        // Validate current status
        if ($booking->status !== BookingStatus::PENDING->value) {
            throw new Exception('Only pending bookings can be confirmed');
        }

        // Validate payment
        if ($booking->payment_status !== PaymentStatus::COMPLETED->value) {
            throw new Exception('Booking cannot be confirmed without completed payment');
        }

        // Update status
        $this->bookingRepository->update($bookingId, [
            'status' => BookingStatus::CONFIRMED->value,
            'confirmed_at' => now(),
        ]);

        // TODO: Send confirmation notification
        // event(new BookingConfirmed($booking));

        return $this->bookingRepository->findOrFail($bookingId);
    }

    /**
     * Start a booking (mark as in progress)
     */
    public function startBooking(int $bookingId): Booking
    {
        $booking = $this->bookingRepository->findOrFail($bookingId);

        // Validate current status
        if ($booking->status !== BookingStatus::CONFIRMED->value) {
            throw new Exception('Only confirmed bookings can be started');
        }

        // Update status
        $this->bookingRepository->update($bookingId, [
            'status' => BookingStatus::IN_PROGRESS->value,
            'started_at' => now(),
        ]);

        return $this->bookingRepository->findOrFail($bookingId);
    }

    /**
     * Complete a booking
     */
    public function completeBooking(int $bookingId): Booking
    {
        $booking = $this->bookingRepository->findOrFail($bookingId);

        // Validate current status
        if ($booking->status !== BookingStatus::IN_PROGRESS->value) {
            throw new Exception('Only in-progress bookings can be completed');
        }

        // Validate payment
        if ($booking->payment_status !== PaymentStatus::COMPLETED->value) {
            throw new Exception('Booking cannot be completed without completed payment');
        }

        // Update status
        $this->bookingRepository->update($bookingId, [
            'status' => BookingStatus::COMPLETED->value,
            'completed_at' => now(),
        ]);

        // TODO: Update staff commission, send receipt
        // event(new BookingCompleted($booking));

        return $this->bookingRepository->findOrFail($bookingId);
    }

    /**
     * Cancel a booking
     */
    public function cancelBooking(int $bookingId, string $cancellationReason = null): Booking
    {
        $booking = $this->bookingRepository->findOrFail($bookingId);

        // Validate current status
        if (!in_array($booking->status, [BookingStatus::PENDING->value, BookingStatus::CONFIRMED->value])) {
            throw new Exception('Only pending or confirmed bookings can be cancelled');
        }

        // Update status
        $this->bookingRepository->update($bookingId, [
            'status' => BookingStatus::CANCELLED->value,
            'cancellation_reason' => $cancellationReason,
            'cancelled_at' => now(),
        ]);

        // TODO: Process refund if payment was made
        // event(new BookingCancelled($booking));

        return $this->bookingRepository->findOrFail($bookingId);
    }

    /**
     * Mark booking as no-show
     */
    public function markAsNoShow(int $bookingId): Booking
    {
        $booking = $this->bookingRepository->findOrFail($bookingId);

        // Validate current status
        if ($booking->status !== BookingStatus::CONFIRMED->value) {
            throw new Exception('Only confirmed bookings can be marked as no-show');
        }

        // Update status
        $this->bookingRepository->update($bookingId, [
            'status' => BookingStatus::NO_SHOW->value,
            'no_show_at' => now(),
        ]);

        return $this->bookingRepository->findOrFail($bookingId);
    }

    /**
     * Get upcoming bookings for a branch
     */
    public function getUpcomingBookings(int $branchId, int $limit = 10): Collection
    {
        return $this->bookingRepository->getUpcomingByBranch($branchId, $limit);
    }

    /**
     * Get today's bookings for a branch
     */
    public function getTodayBookings(int $branchId): Collection
    {
        return $this->bookingRepository->getTodayBookings($branchId);
    }

    /**
     * Get bookings by date range
     */
    public function getBookingsByDateRange(int $branchId, string $startDate, string $endDate): Collection
    {
        return $this->bookingRepository->getByDateRange($branchId, $startDate, $endDate);
    }

    /**
     * Get bookings for a specific staff member
     */
    public function getStaffBookings(int $staffId, string $date): Collection
    {
        return $this->bookingRepository->getByStaff($staffId, $date);
    }

    /**
     * Get client booking history
     */
    public function getClientBookings(int $clientId): Collection
    {
        return $this->bookingRepository->getByClient($clientId);
    }

    /**
     * Validate time slot availability
     */
    private function validateTimeSlotAvailability(
        int $staffId,
        string $date,
        string $startTime,
        string $endTime,
        ?int $excludeBookingId = null
    ): void {
        $isAvailable = $this->bookingRepository->isTimeSlotAvailable(
            $staffId,
            $date,
            $startTime,
            $endTime,
            $excludeBookingId
        );

        if (!$isAvailable) {
            throw new Exception('The selected time slot is not available for this staff member');
        }
    }

    /**
     * Generate unique booking reference
     */
    private function generateBookingReference(): string
    {
        $prefix = 'SPA';
        $timestamp = now()->format('ymd');
        $random = strtoupper(substr(md5(uniqid()), 0, 6));

        return $prefix . $timestamp . $random;
    }

    /**
     * Get booking statistics for a branch
     */
    public function getBookingStatistics(int $branchId, ?string $startDate = null, ?string $endDate = null): array
    {
        $startDate = $startDate ?? now()->startOfMonth()->toDateString();
        $endDate = $endDate ?? now()->endOfMonth()->toDateString();

        return [
            'total' => $this->bookingRepository->countByBranch($branchId, [
                'date_from' => $startDate,
                'date_to' => $endDate,
            ]),
            'pending' => $this->bookingRepository->countByBranch($branchId, [
                'status' => [BookingStatus::PENDING->value],
                'date_from' => $startDate,
                'date_to' => $endDate,
            ]),
            'confirmed' => $this->bookingRepository->countByBranch($branchId, [
                'status' => [BookingStatus::CONFIRMED->value],
                'date_from' => $startDate,
                'date_to' => $endDate,
            ]),
            'completed' => $this->bookingRepository->countByBranch($branchId, [
                'status' => [BookingStatus::COMPLETED->value],
                'date_from' => $startDate,
                'date_to' => $endDate,
            ]),
            'cancelled' => $this->bookingRepository->countByBranch($branchId, [
                'status' => [BookingStatus::CANCELLED->value],
                'date_from' => $startDate,
                'date_to' => $endDate,
            ]),
            'no_show' => $this->bookingRepository->countByBranch($branchId, [
                'status' => [BookingStatus::NO_SHOW->value],
                'date_from' => $startDate,
                'date_to' => $endDate,
            ]),
        ];
    }
}
