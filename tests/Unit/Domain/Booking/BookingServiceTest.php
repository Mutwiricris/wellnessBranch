<?php

namespace Tests\Unit\Domain\Booking;

use App\Domain\Booking\DTOs\CreateBookingDTO;
use App\Domain\Booking\Repositories\BookingRepositoryInterface;
use App\Domain\Booking\Services\BookingService;
use App\Domain\Staff\Services\StaffAvailabilityService;
use App\Models\Booking;
use App\Models\Branch;
use App\Models\Service;
use App\Models\Staff;
use App\Models\User;
use App\Support\Enums\BookingStatus;
use App\Support\Enums\PaymentStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingServiceTest extends TestCase
{
    use RefreshDatabase;

    private BookingService $bookingService;
    private Branch $branch;
    private User $client;
    private Service $service;
    private Staff $staff;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test data
        $this->branch = Branch::factory()->create(['status' => 'active']);
        $this->client = User::factory()->create(['user_type' => 'user']);
        $this->service = Service::factory()->create([
            'name' => 'Test Massage',
            'price' => 5000.00,
            'duration_minutes' => 60,
            'status' => 'active'
        ]);
        $this->staff = Staff::factory()->create([
            'name' => 'Test Therapist',
            'specialization' => 'massage'
        ]);

        $this->bookingService = app(BookingService::class);
    }

    /** @test */
    public function it_can_create_a_booking()
    {
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

        $booking = $this->bookingService->createBooking($dto);

        $this->assertInstanceOf(Booking::class, $booking);
        $this->assertEquals($this->branch->id, $booking->branch_id);
        $this->assertEquals($this->client->id, $booking->client_id);
        $this->assertEquals($this->service->id, $booking->service_id);
        $this->assertEquals($this->staff->id, $booking->staff_id);
        $this->assertEquals(BookingStatus::PENDING->value, $booking->status);
        $this->assertEquals(PaymentStatus::PENDING->value, $booking->payment_status);
        $this->assertNotNull($booking->booking_reference);
    }

    /** @test */
    public function it_can_confirm_a_booking()
    {
        $booking = Booking::factory()->create([
            'branch_id' => $this->branch->id,
            'status' => BookingStatus::PENDING->value,
            'payment_status' => PaymentStatus::PENDING->value
        ]);

        $confirmed = $this->bookingService->confirmBooking($booking->id);

        $this->assertEquals(BookingStatus::CONFIRMED->value, $confirmed->status);
        $this->assertNotNull($confirmed->confirmed_at);
    }

    /** @test */
    public function it_can_cancel_a_booking()
    {
        $booking = Booking::factory()->create([
            'branch_id' => $this->branch->id,
            'status' => BookingStatus::CONFIRMED->value
        ]);

        $cancelled = $this->bookingService->cancelBooking($booking->id, 'Client requested cancellation');

        $this->assertEquals(BookingStatus::CANCELLED->value, $cancelled->status);
        $this->assertEquals('Client requested cancellation', $cancelled->cancellation_reason);
        $this->assertNotNull($cancelled->cancelled_at);
    }

    /** @test */
    public function it_can_complete_a_booking()
    {
        $booking = Booking::factory()->create([
            'branch_id' => $this->branch->id,
            'status' => BookingStatus::CONFIRMED->value
        ]);

        $completed = $this->bookingService->completeBooking($booking->id);

        $this->assertEquals(BookingStatus::COMPLETED->value, $completed->status);
        $this->assertNotNull($completed->completed_at);
    }

    /** @test */
    public function it_can_get_upcoming_bookings_for_branch()
    {
        // Create some bookings
        Booking::factory()->count(3)->create([
            'branch_id' => $this->branch->id,
            'appointment_date' => now()->addDays(2),
            'status' => BookingStatus::CONFIRMED->value
        ]);

        // Create past booking (should not be included)
        Booking::factory()->create([
            'branch_id' => $this->branch->id,
            'appointment_date' => now()->subDays(2),
            'status' => BookingStatus::COMPLETED->value
        ]);

        $upcomingBookings = $this->bookingService->getUpcomingBookingsForBranch($this->branch->id);

        $this->assertCount(3, $upcomingBookings);
    }

    /** @test */
    public function it_generates_unique_booking_reference()
    {
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

        $booking1 = $this->bookingService->createBooking($dto);
        $booking2 = $this->bookingService->createBooking($dto);

        $this->assertNotEquals($booking1->booking_reference, $booking2->booking_reference);
    }
}
