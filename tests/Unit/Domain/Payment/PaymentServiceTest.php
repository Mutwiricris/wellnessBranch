<?php

namespace Tests\Unit\Domain\Payment;

use App\Domain\Payment\Services\PaymentService;
use App\Models\Booking;
use App\Models\Branch;
use App\Models\Payment;
use App\Models\PosTransaction;
use App\Support\Enums\PaymentMethod;
use App\Support\Enums\PaymentStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentServiceTest extends TestCase
{
    use RefreshDatabase;

    private PaymentService $paymentService;
    private Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();

        $this->branch = Branch::factory()->create(['status' => 'active']);
        $this->paymentService = app(PaymentService::class);
    }

    /** @test */
    public function it_can_process_cash_payment_for_booking()
    {
        $booking = Booking::factory()->create([
            'branch_id' => $this->branch->id,
            'total_amount' => 5000.00
        ]);

        $paymentData = [
            'payment_method' => PaymentMethod::CASH->value,
            'amount' => 5000.00,
        ];

        $payment = $this->paymentService->processBookingPayment($booking, $paymentData);

        $this->assertInstanceOf(Payment::class, $payment);
        $this->assertEquals($booking->id, $payment->booking_id);
        $this->assertEquals($this->branch->id, $payment->branch_id);
        $this->assertEquals(5000.00, $payment->amount);
        $this->assertEquals(PaymentMethod::CASH->value, $payment->payment_method);
        $this->assertEquals(PaymentStatus::COMPLETED->value, $payment->status);
    }

    /** @test */
    public function it_can_process_split_payment_for_pos()
    {
        $transaction = PosTransaction::factory()->create([
            'branch_id' => $this->branch->id,
            'total_amount' => 10000.00
        ]);

        $paymentSplits = [
            ['method' => PaymentMethod::CASH->value, 'amount' => 5000.00],
            ['method' => PaymentMethod::CARD->value, 'amount' => 5000.00],
        ];

        $result = $this->paymentService->processPosPayment($transaction, $paymentSplits);

        $this->assertInstanceOf(Payment::class, $result);
        $this->assertEquals($transaction->id, $result->pos_transaction_id);
        $this->assertEquals(10000.00, $result->amount);
    }

    /** @test */
    public function it_can_record_payment()
    {
        $booking = Booking::factory()->create([
            'branch_id' => $this->branch->id,
            'total_amount' => 5000.00
        ]);

        $payment = $this->paymentService->recordPayment(
            branchId: $this->branch->id,
            amount: 5000.00,
            paymentMethod: PaymentMethod::CASH->value,
            bookingId: $booking->id
        );

        $this->assertInstanceOf(Payment::class, $payment);
        $this->assertEquals($this->branch->id, $payment->branch_id);
        $this->assertEquals(5000.00, $payment->amount);
        $this->assertNotNull($payment->payment_reference);
    }

    /** @test */
    public function it_generates_unique_payment_reference()
    {
        $booking = Booking::factory()->create([
            'branch_id' => $this->branch->id,
            'total_amount' => 5000.00
        ]);

        $payment1 = $this->paymentService->recordPayment(
            branchId: $this->branch->id,
            amount: 5000.00,
            paymentMethod: PaymentMethod::CASH->value,
            bookingId: $booking->id
        );

        $payment2 = $this->paymentService->recordPayment(
            branchId: $this->branch->id,
            amount: 5000.00,
            paymentMethod: PaymentMethod::CASH->value,
            bookingId: $booking->id
        );

        $this->assertNotEquals($payment1->payment_reference, $payment2->payment_reference);
    }

    /** @test */
    public function it_can_get_payments_for_branch()
    {
        Payment::factory()->count(5)->create([
            'branch_id' => $this->branch->id
        ]);

        // Create payment for different branch (should not be included)
        $otherBranch = Branch::factory()->create();
        Payment::factory()->create([
            'branch_id' => $otherBranch->id
        ]);

        $payments = $this->paymentService->getPaymentsByBranch($this->branch->id);

        $this->assertCount(5, $payments);
    }

    /** @test */
    public function it_can_filter_payments_by_method()
    {
        Payment::factory()->count(3)->create([
            'branch_id' => $this->branch->id,
            'payment_method' => PaymentMethod::CASH->value
        ]);

        Payment::factory()->count(2)->create([
            'branch_id' => $this->branch->id,
            'payment_method' => PaymentMethod::CARD->value
        ]);

        $cashPayments = $this->paymentService->getPaymentsByBranch(
            $this->branch->id,
            ['payment_method' => PaymentMethod::CASH->value]
        );

        $this->assertCount(3, $cashPayments);
    }
}
