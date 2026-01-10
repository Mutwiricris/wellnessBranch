<?php

namespace Tests\Unit\Domain\Booking;

use App\Domain\Booking\Repositories\BookingRepository;
use App\Models\Booking;
use App\Models\Branch;
use App\Support\Enums\BookingStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private BookingRepository $repository;
    private Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();

        $this->branch = Branch::factory()->create(['status' => 'active']);
        $this->repository = app(BookingRepository::class);
    }

    /** @test */
    public function it_can_find_booking_by_id()
    {
        $booking = Booking::factory()->create(['branch_id' => $this->branch->id]);

        $found = $this->repository->find($booking->id);

        $this->assertInstanceOf(Booking::class, $found);
        $this->assertEquals($booking->id, $found->id);
    }

    /** @test */
    public function it_can_create_booking()
    {
        $data = [
            'branch_id' => $this->branch->id,
            'client_id' => 1,
            'service_id' => 1,
            'staff_id' => 1,
            'booking_reference' => 'TEST123',
            'appointment_date' => now()->addDays(3),
            'start_time' => now()->addDays(3)->setTime(10, 0),
            'end_time' => now()->addDays(3)->setTime(11, 0),
            'total_amount' => 5000.00,
            'status' => BookingStatus::PENDING->value
        ];

        $booking = $this->repository->create($data);

        $this->assertInstanceOf(Booking::class, $booking);
        $this->assertEquals($this->branch->id, $booking->branch_id);
        $this->assertEquals('TEST123', $booking->booking_reference);
    }

    /** @test */
    public function it_can_update_booking()
    {
        $booking = Booking::factory()->create([
            'branch_id' => $this->branch->id,
            'status' => BookingStatus::PENDING->value
        ]);

        $updated = $this->repository->update($booking->id, [
            'status' => BookingStatus::CONFIRMED->value
        ]);

        $this->assertEquals(BookingStatus::CONFIRMED->value, $updated->status);
    }

    /** @test */
    public function it_can_get_bookings_for_branch()
    {
        Booking::factory()->count(5)->create(['branch_id' => $this->branch->id]);

        // Create booking for different branch (should not be included due to HasBranchScope)
        $otherBranch = Branch::factory()->create();
        Booking::factory()->create(['branch_id' => $otherBranch->id]);

        $bookings = $this->repository->getForBranch($this->branch->id);

        $this->assertCount(5, $bookings);
    }

    /** @test */
    public function it_can_filter_bookings_by_status()
    {
        Booking::factory()->count(3)->create([
            'branch_id' => $this->branch->id,
            'status' => BookingStatus::CONFIRMED->value
        ]);

        Booking::factory()->count(2)->create([
            'branch_id' => $this->branch->id,
            'status' => BookingStatus::PENDING->value
        ]);

        $confirmedBookings = $this->repository->getForBranch($this->branch->id, [
            'status' => BookingStatus::CONFIRMED->value
        ]);

        $this->assertCount(3, $confirmedBookings);
    }

    /** @test */
    public function it_can_get_upcoming_bookings()
    {
        Booking::factory()->count(3)->create([
            'branch_id' => $this->branch->id,
            'appointment_date' => now()->addDays(2),
            'status' => BookingStatus::CONFIRMED->value
        ]);

        // Past booking
        Booking::factory()->create([
            'branch_id' => $this->branch->id,
            'appointment_date' => now()->subDays(2),
            'status' => BookingStatus::COMPLETED->value
        ]);

        $upcoming = $this->repository->getUpcoming($this->branch->id);

        $this->assertCount(3, $upcoming);
    }

    /** @test */
    public function it_can_get_todays_bookings()
    {
        Booking::factory()->count(4)->create([
            'branch_id' => $this->branch->id,
            'appointment_date' => now(),
            'status' => BookingStatus::CONFIRMED->value
        ]);

        // Tomorrow's booking
        Booking::factory()->create([
            'branch_id' => $this->branch->id,
            'appointment_date' => now()->addDay(),
            'status' => BookingStatus::CONFIRMED->value
        ]);

        $today = $this->repository->getToday($this->branch->id);

        $this->assertCount(4, $today);
    }

    /** @test */
    public function it_can_find_booking_by_reference()
    {
        $booking = Booking::factory()->create([
            'branch_id' => $this->branch->id,
            'booking_reference' => 'UNIQUE123'
        ]);

        $found = $this->repository->findByReference('UNIQUE123');

        $this->assertInstanceOf(Booking::class, $found);
        $this->assertEquals($booking->id, $found->id);
    }

    /** @test */
    public function it_returns_null_when_booking_not_found_by_reference()
    {
        $found = $this->repository->findByReference('NONEXISTENT');

        $this->assertNull($found);
    }
}
