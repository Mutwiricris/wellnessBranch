<?php

namespace Tests\Unit\Domain\POS;

use App\Domain\POS\Services\PosService;
use App\Models\Branch;
use App\Models\Product;
use App\Models\Service;
use App\Models\PosTransaction;
use App\Models\BranchProductInventory;
use App\Support\Enums\PaymentStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PosServiceTest extends TestCase
{
    use RefreshDatabase;

    private PosService $posService;
    private Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();

        $this->branch = Branch::factory()->create(['status' => 'active']);
        $this->posService = app(PosService::class);
    }

    /** @test */
    public function it_can_create_pos_transaction_with_services()
    {
        $service = Service::factory()->create([
            'name' => 'Massage',
            'price' => 5000.00,
            'status' => 'active'
        ]);

        $cartItems = [
            [
                'type' => 'service',
                'id' => $service->id,
                'quantity' => 1,
                'price' => 5000.00
            ]
        ];

        $transaction = $this->posService->createTransaction($this->branch->id, $cartItems);

        $this->assertInstanceOf(PosTransaction::class, $transaction);
        $this->assertEquals($this->branch->id, $transaction->branch_id);
        $this->assertEquals(5000.00, $transaction->subtotal);
        $this->assertEquals(5000.00, $transaction->total_amount);
        $this->assertNotNull($transaction->transaction_number);
        $this->assertEquals(PaymentStatus::PENDING->value, $transaction->payment_status);
    }

    /** @test */
    public function it_can_create_pos_transaction_with_products()
    {
        $product = Product::factory()->create([
            'name' => 'Face Cream',
            'price' => 2000.00,
            'status' => 'active'
        ]);

        // Create inventory for the product
        BranchProductInventory::factory()->create([
            'branch_id' => $this->branch->id,
            'product_id' => $product->id,
            'quantity_on_hand' => 10,
            'is_available' => true
        ]);

        $cartItems = [
            [
                'type' => 'product',
                'id' => $product->id,
                'quantity' => 2,
                'price' => 2000.00
            ]
        ];

        $transaction = $this->posService->createTransaction($this->branch->id, $cartItems);

        $this->assertInstanceOf(PosTransaction::class, $transaction);
        $this->assertEquals(4000.00, $transaction->subtotal);
        $this->assertEquals(4000.00, $transaction->total_amount);
        $this->assertCount(1, $transaction->items);
    }

    /** @test */
    public function it_reserves_inventory_when_creating_transaction()
    {
        $product = Product::factory()->create([
            'name' => 'Face Cream',
            'price' => 2000.00,
            'status' => 'active'
        ]);

        $inventory = BranchProductInventory::factory()->create([
            'branch_id' => $this->branch->id,
            'product_id' => $product->id,
            'quantity_on_hand' => 10,
            'quantity_reserved' => 0,
            'is_available' => true
        ]);

        $cartItems = [
            [
                'type' => 'product',
                'id' => $product->id,
                'quantity' => 2,
                'price' => 2000.00
            ]
        ];

        $transaction = $this->posService->createTransaction($this->branch->id, $cartItems);

        $inventory->refresh();
        $this->assertEquals(2, $inventory->quantity_reserved);
    }

    /** @test */
    public function it_can_process_cash_payment()
    {
        $transaction = PosTransaction::factory()->create([
            'branch_id' => $this->branch->id,
            'total_amount' => 5000.00,
            'payment_status' => PaymentStatus::PENDING->value
        ]);

        $paymentData = [
            'payment_method' => 'cash',
            'amount_received' => 5000.00
        ];

        $completed = $this->posService->processPayment($transaction->id, $paymentData);

        $this->assertEquals(PaymentStatus::COMPLETED->value, $completed->payment_status);
        $this->assertNotNull($completed->completed_at);
    }

    /** @test */
    public function it_can_calculate_totals_with_tax()
    {
        $service = Service::factory()->create([
            'name' => 'Massage',
            'price' => 5000.00,
            'status' => 'active'
        ]);

        $cartItems = [
            [
                'type' => 'service',
                'id' => $service->id,
                'quantity' => 1,
                'price' => 5000.00
            ]
        ];

        $metadata = [
            'tax_rate' => 16.0
        ];

        $transaction = $this->posService->createTransaction($this->branch->id, $cartItems, $metadata);

        $this->assertEquals(5000.00, $transaction->subtotal);
        $this->assertEquals(800.00, $transaction->tax_amount);
        $this->assertEquals(5800.00, $transaction->total_amount);
    }

    /** @test */
    public function it_generates_unique_transaction_number()
    {
        $service = Service::factory()->create(['price' => 5000.00, 'status' => 'active']);

        $cartItems = [
            [
                'type' => 'service',
                'id' => $service->id,
                'quantity' => 1,
                'price' => 5000.00
            ]
        ];

        $transaction1 = $this->posService->createTransaction($this->branch->id, $cartItems);
        $transaction2 = $this->posService->createTransaction($this->branch->id, $cartItems);

        $this->assertNotEquals($transaction1->transaction_number, $transaction2->transaction_number);
    }

    /** @test */
    public function it_can_get_daily_summary()
    {
        // Create transactions for today
        PosTransaction::factory()->count(3)->create([
            'branch_id' => $this->branch->id,
            'total_amount' => 5000.00,
            'payment_status' => PaymentStatus::COMPLETED->value,
            'created_at' => now()
        ]);

        // Create transaction for yesterday (should not be included)
        PosTransaction::factory()->create([
            'branch_id' => $this->branch->id,
            'total_amount' => 5000.00,
            'payment_status' => PaymentStatus::COMPLETED->value,
            'created_at' => now()->subDay()
        ]);

        $summary = $this->posService->getDailySummary($this->branch->id, now()->format('Y-m-d'));

        $this->assertEquals(3, $summary['total_transactions']);
        $this->assertEquals(15000.00, $summary['total_revenue']);
    }
}
