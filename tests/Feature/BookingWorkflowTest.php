<?php

namespace Tests\Feature;

use App\Domain\Booking\DTOs\CreateBookingDTO;
use App\Domain\Booking\Services\BookingService;
use App\Domain\Payment\Services\PaymentService;
use App\Models\Booking;
use App\Models\Branch;
use App\Models\Payment;
use App\Models\Service;
use App\Models\Staff;
use App\Models\User;
use App\Support\Enums\BookingStatus;
use App\Support\Enums\PaymentMethod;
use App\Support\Enums\PaymentStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private Branch $branch;
    private User $client;
    private Service $service;
    private Staff $staff;

    protected function setUp(): void
    {
        parent::setUp();

        $this->branch = Branch::factory()->create(['status' => 'active']);
        $this->client = User::factory()->create(['user_type' => 'user']);
        $this->service = Service::factory()->create([
            'name' => 'Massage',
            'price' => 5000.00,
            'duration_minutes' => 60,
            'status' => 'active'
        ]);
        $this->staff = Staff::factory()->create([
            'name' => 'Therapist',
            'specialization' => 'massage'
        ]);
    }

    /** @test */
    public function complete_booking_workflow_from_creation_to_completion()
    {
        $bookingService = app(BookingService::class);
        $paymentService = app(PaymentService::class);

        // Step 1: Create a booking
        $dto = new CreateBookingDTO(
            branchId: $this->branch->id,
            clientId: $this->client->id,
            serviceId: $this->service->id,
            staffId: $this->staff->id,
            appointmentDate: now()->addDays(3)->format('Y-m-d'),
            startTime: '10:00',
            endTime: '11:00',
            totalAmount: 5000.00
        );

        $booking = $bookingService->createBooking($dto);

        $this->assertInstanceOf(Booking::class, $booking);
        $this->assertEquals(BookingStatus::PENDING->value, $booking->status);
        $this->assertEquals(PaymentStatus::PENDING->value, $booking->payment_status);

        // Step 2: Process payment
        $paymentData = [
            'payment_method' => PaymentMethod::CASH->value,
            'amount' => 5000.00,
        ];

        $payment = $paymentService->processBookingPayment($booking, $paymentData);

        $this->assertInstanceOf(Payment::class, $payment);
        $this->assertEquals(PaymentStatus::COMPLETED->value, $payment->status);

        // Refresh booking to check payment status updated
        $booking->refresh();
        $this->assertEquals(PaymentStatus::PAID->value, $booking->payment_status);

        // Step 3: Confirm the booking
        $confirmedBooking = $bookingService->confirmBooking($booking->id);

        $this->assertEquals(BookingStatus::CONFIRMED->value, $confirmedBooking->status);
        $this->assertNotNull($confirmedBooking->confirmed_at);

        // Step 4: Complete the booking
        $completedBooking = $bookingService->completeBooking($booking->id);

        $this->assertEquals(BookingStatus::COMPLETED->value, $completedBooking->status);
        $this->assertNotNull($completedBooking->completed_at);

        // Verify the complete workflow state
        $finalBooking = Booking::find($booking->id);
        $this->assertEquals(BookingStatus::COMPLETED->value, $finalBooking->status);
        $this->assertEquals(PaymentStatus::PAID->value, $finalBooking->payment_status);
        $this->assertNotNull($finalBooking->confirmed_at);
        $this->assertNotNull($finalBooking->completed_at);
    }

    /** @test */
    public function can_cancel_confirmed_booking()
    {
        $bookingService = app(BookingService::class);

        // Create and confirm a booking
        $dto = new CreateBookingDTO(
            branchId: $this->branch->id,
            clientId: $this->client->id,
            serviceId: $this->service->id,
            staffId: $this->staff->id,
            appointmentDate: now()->addDays(3)->format('Y-m-d'),
            startTime: '10:00',
            endTime: '11:00',
            totalAmount: 5000.00
        );

        $booking = $bookingService->createBooking($dto);
        $confirmedBooking = $bookingService->confirmBooking($booking->id);

        // Cancel the booking
        $cancelledBooking = $bookingService->cancelBooking(
            $confirmedBooking->id,
            'Client requested cancellation'
        );

        $this->assertEquals(BookingStatus::CANCELLED->value, $cancelledBooking->status);
        $this->assertEquals('Client requested cancellation', $cancelledBooking->cancellation_reason);
        $this->assertNotNull($cancelledBooking->cancelled_at);
    }

    /** @test */
    public function booking_respects_branch_scoping()
    {
        $bookingService = app(BookingService::class);

        // Create booking for branch 1
        $booking1 = Booking::factory()->create([
            'branch_id' => $this->branch->id
        ]);

        // Create booking for different branch
        $otherBranch = Branch::factory()->create();
        $booking2 = Booking::factory()->create([
            'branch_id' => $otherBranch->id
        ]);

        // Get bookings for branch 1
        $branch1Bookings = $bookingService->getUpcomingBookingsForBranch($this->branch->id);

        // Should only contain booking from branch 1
        $this->assertTrue($branch1Bookings->contains($booking1));
        $this->assertFalse($branch1Bookings->contains($booking2));
    }

    /** @test */
    public function can_process_multiple_payment_methods()
    {
        $bookingService = app(BookingService::class);
        $paymentService = app(PaymentService::class);

        // Create booking
        $dto = new CreateBookingDTO(
            branchId: $this->branch->id,
            clientId: $this->client->id,
            serviceId: $this->service->id,
            staffId: $this->staff->id,
            appointmentDate: now()->addDays(3)->format('Y-m-d'),
            startTime: '10:00',
            endTime: '11:00',
            totalAmount: 5000.00
        );

        $booking = $bookingService->createBooking($dto);

        // Test different payment methods
        $paymentMethods = [
            PaymentMethod::CASH,
            PaymentMethod::CARD,
            PaymentMethod::BANK_TRANSFER
        ];

        foreach ($paymentMethods as $method) {
            $paymentData = [
                'payment_method' => $method->value,
                'amount' => 5000.00,
            ];

            $payment = $paymentService->recordPayment(
                branchId: $this->branch->id,
                amount: 5000.00,
                paymentMethod: $method->value,
                bookingId: $booking->id
            );

            $this->assertEquals($method->value, $payment->payment_method);
            $this->assertEquals(PaymentStatus::COMPLETED->value, $payment->status);
        }
    }
}
