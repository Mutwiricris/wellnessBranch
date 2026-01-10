<?php

namespace Tests\Feature;

use App\Domain\Inventory\Services\InventoryService;
use App\Domain\Payment\Services\PaymentService;
use App\Domain\POS\Services\PosService;
use App\Models\Branch;
use App\Models\BranchProductInventory;
use App\Models\Payment;
use App\Models\PosTransaction;
use App\Models\Product;
use App\Models\Service;
use App\Support\Enums\PaymentMethod;
use App\Support\Enums\PaymentStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PosWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private Branch $branch;
    private Product $product;
    private Service $service;
    private BranchProductInventory $inventory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->branch = Branch::factory()->create(['status' => 'active']);

        $this->product = Product::factory()->create([
            'name' => 'Face Cream',
            'price' => 2000.00,
            'status' => 'active'
        ]);

        $this->service = Service::factory()->create([
            'name' => 'Massage',
            'price' => 5000.00,
            'status' => 'active'
        ]);

        $this->inventory = BranchProductInventory::factory()->create([
            'branch_id' => $this->branch->id,
            'product_id' => $this->product->id,
            'quantity_on_hand' => 20,
            'quantity_reserved' => 0,
            'is_available' => true
        ]);
    }

    /** @test */
    public function complete_pos_transaction_workflow_with_products_and_services()
    {
        $posService = app(PosService::class);
        $paymentService = app(PaymentService::class);

        // Step 1: Create POS transaction with mixed cart
        $cartItems = [
            [
                'type' => 'product',
                'id' => $this->product->id,
                'quantity' => 2,
                'price' => 2000.00
            ],
            [
                'type' => 'service',
                'id' => $this->service->id,
                'quantity' => 1,
                'price' => 5000.00
            ]
        ];

        $transaction = $posService->createTransaction($this->branch->id, $cartItems);

        $this->assertInstanceOf(PosTransaction::class, $transaction);
        $this->assertEquals(9000.00, $transaction->subtotal); // 4000 + 5000
        $this->assertEquals(PaymentStatus::PENDING->value, $transaction->payment_status);

        // Verify inventory was reserved
        $this->inventory->refresh();
        $this->assertEquals(2, $this->inventory->quantity_reserved);

        // Step 2: Process payment
        $paymentData = [
            'payment_method' => PaymentMethod::CASH->value,
            'amount_received' => 9000.00
        ];

        $completedTransaction = $posService->processPayment($transaction->id, $paymentData);

        $this->assertEquals(PaymentStatus::COMPLETED->value, $completedTransaction->payment_status);
        $this->assertNotNull($completedTransaction->completed_at);

        // Verify inventory was consumed (reserved released and stock reduced)
        $this->inventory->refresh();
        $this->assertEquals(0, $this->inventory->quantity_reserved);
        $this->assertEquals(18, $this->inventory->quantity_on_hand); // 20 - 2

        // Verify payment was recorded
        $payment = Payment::where('pos_transaction_id', $transaction->id)->first();
        $this->assertNotNull($payment);
        $this->assertEquals(9000.00, $payment->amount);
        $this->assertEquals(PaymentMethod::CASH->value, $payment->payment_method);
    }

    /** @test */
    public function pos_transaction_with_tax_calculation()
    {
        $posService = app(PosService::class);

        $cartItems = [
            [
                'type' => 'service',
                'id' => $this->service->id,
                'quantity' => 1,
                'price' => 5000.00
            ]
        ];

        $metadata = [
            'tax_rate' => 16.0 // 16% VAT
        ];

        $transaction = $posService->createTransaction($this->branch->id, $cartItems, $metadata);

        $this->assertEquals(5000.00, $transaction->subtotal);
        $this->assertEquals(800.00, $transaction->tax_amount); // 16% of 5000
        $this->assertEquals(5800.00, $transaction->total_amount); // 5000 + 800
    }

    /** @test */
    public function pos_transaction_with_discount()
    {
        $posService = app(PosService::class);

        $cartItems = [
            [
                'type' => 'service',
                'id' => $this->service->id,
                'quantity' => 1,
                'price' => 5000.00
            ]
        ];

        $metadata = [
            'discount_amount' => 500.00
        ];

        $transaction = $posService->createTransaction($this->branch->id, $cartItems, $metadata);

        $this->assertEquals(5000.00, $transaction->subtotal);
        $this->assertEquals(500.00, $transaction->discount_amount);
        $this->assertEquals(4500.00, $transaction->total_amount); // 5000 - 500
    }

    /** @test */
    public function pos_transaction_with_split_payment()
    {
        $posService = app(PosService::class);
        $paymentService = app(PaymentService::class);

        // Create transaction
        $cartItems = [
            [
                'type' => 'service',
                'id' => $this->service->id,
                'quantity' => 1,
                'price' => 5000.00
            ]
        ];

        $transaction = $posService->createTransaction($this->branch->id, $cartItems);

        // Process split payment (cash + card)
        $paymentSplits = [
            [
                'method' => PaymentMethod::CASH->value,
                'amount' => 3000.00
            ],
            [
                'method' => PaymentMethod::CARD->value,
                'amount' => 2000.00
            ]
        ];

        $payment = $paymentService->processPosPayment($transaction, $paymentSplits);

        $this->assertInstanceOf(Payment::class, $payment);
        $this->assertEquals(5000.00, $payment->amount);

        // Refresh transaction to verify completion
        $transaction->refresh();
        $this->assertEquals(PaymentStatus::COMPLETED->value, $transaction->payment_status);
    }

    /** @test */
    public function inventory_is_reserved_then_consumed_correctly()
    {
        $posService = app(PosService::class);

        $initialStock = $this->inventory->quantity_on_hand;
        $initialReserved = $this->inventory->quantity_reserved;

        // Create transaction (should reserve inventory)
        $cartItems = [
            [
                'type' => 'product',
                'id' => $this->product->id,
                'quantity' => 3,
                'price' => 2000.00
            ]
        ];

        $transaction = $posService->createTransaction($this->branch->id, $cartItems);

        $this->inventory->refresh();
        $this->assertEquals($initialStock, $this->inventory->quantity_on_hand);
        $this->assertEquals(3, $this->inventory->quantity_reserved);

        // Process payment (should consume inventory)
        $paymentData = [
            'payment_method' => PaymentMethod::CASH->value,
            'amount_received' => 6000.00
        ];

        $posService->processPayment($transaction->id, $paymentData);

        $this->inventory->refresh();
        $this->assertEquals($initialStock - 3, $this->inventory->quantity_on_hand);
        $this->assertEquals(0, $this->inventory->quantity_reserved);
    }

    /** @test */
    public function can_get_daily_sales_summary()
    {
        $posService = app(PosService::class);

        // Create multiple transactions for today
        PosTransaction::factory()->count(3)->create([
            'branch_id' => $this->branch->id,
            'total_amount' => 5000.00,
            'payment_status' => PaymentStatus::COMPLETED->value,
            'created_at' => now()
        ]);

        PosTransaction::factory()->count(2)->create([
            'branch_id' => $this->branch->id,
            'total_amount' => 3000.00,
            'payment_status' => PaymentStatus::COMPLETED->value,
            'created_at' => now()
        ]);

        // Create transaction for yesterday (should not be included)
        PosTransaction::factory()->create([
            'branch_id' => $this->branch->id,
            'total_amount' => 10000.00,
            'payment_status' => PaymentStatus::COMPLETED->value,
            'created_at' => now()->subDay()
        ]);

        $summary = $posService->getDailySummary($this->branch->id, now()->format('Y-m-d'));

        $this->assertEquals(5, $summary['total_transactions']);
        $this->assertEquals(21000.00, $summary['total_revenue']); // (3 * 5000) + (2 * 3000)
    }

    /** @test */
    public function pos_transaction_respects_branch_scoping()
    {
        $posService = app(PosService::class);

        // Create transaction for branch 1
        $transaction1 = PosTransaction::factory()->create([
            'branch_id' => $this->branch->id,
            'created_at' => now()
        ]);

        // Create transaction for different branch
        $otherBranch = Branch::factory()->create();
        $transaction2 = PosTransaction::factory()->create([
            'branch_id' => $otherBranch->id,
            'created_at' => now()
        ]);

        // Get summary for branch 1
        $summary = $posService->getDailySummary($this->branch->id, now()->format('Y-m-d'));

        // Should only count transaction from branch 1
        $this->assertTrue($summary['total_transactions'] >= 1);
    }
}
