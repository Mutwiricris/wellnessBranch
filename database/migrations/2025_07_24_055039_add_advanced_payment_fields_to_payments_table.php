<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            // Add missing fields from the original design (only if they don't exist)
            if (!Schema::hasColumn('payments', 'mpesa_transaction_id')) {
                $table->string('mpesa_transaction_id')->nullable()->after('mpesa_checkout_request_id');
            }
            if (!Schema::hasColumn('payments', 'notes')) {
                $table->text('notes')->nullable()->after('processed_at');
            }
            if (!Schema::hasColumn('payments', 'refund_amount')) {
                $table->decimal('refund_amount', 10, 2)->nullable()->after('notes');
            }
            if (!Schema::hasColumn('payments', 'refunded_at')) {
                $table->timestamp('refunded_at')->nullable()->after('refund_amount');
            }
            if (!Schema::hasColumn('payments', 'gateway_response')) {
                $table->json('gateway_response')->nullable()->after('refunded_at');
            }
            if (!Schema::hasColumn('payments', 'card_last_four')) {
                $table->string('card_last_four', 4)->nullable()->after('gateway_response');
            }
            if (!Schema::hasColumn('payments', 'card_brand')) {
                $table->string('card_brand')->nullable()->after('card_last_four');
            }

            // Add new advanced fields
            if (!Schema::hasColumn('payments', 'bank_reference')) {
                $table->string('bank_reference')->nullable()->after('card_brand');
            }
            if (!Schema::hasColumn('payments', 'authorization_code')) {
                $table->string('authorization_code')->nullable()->after('bank_reference');
            }
            if (!Schema::hasColumn('payments', 'payment_channel')) {
                $table->string('payment_channel')->nullable()->after('authorization_code');
            }
            if (!Schema::hasColumn('payments', 'customer_id')) {
                $table->unsignedBigInteger('customer_id')->nullable()->after('payment_channel');
            }
            if (!Schema::hasColumn('payments', 'staff_id')) {
                $table->unsignedBigInteger('staff_id')->nullable()->after('customer_id');
            }
        });

        // Add foreign keys separately to avoid constraint errors
        // Check if foreign keys don't already exist before adding
        $sm = Schema::getConnection()->getDoctrineSchemaManager();
        $indexesFound = $sm->listTableIndexes('payments');

        Schema::table('payments', function (Blueprint $table) use ($indexesFound) {
            if (Schema::hasColumn('payments', 'customer_id') && !isset($indexesFound['payments_customer_id_foreign'])) {
                try {
                    $table->foreign('customer_id')->references('id')->on('users')->onDelete('set null');
                } catch (\Exception $e) {
                    // Foreign key might already exist or fail for other reasons
                }
            }
            if (Schema::hasColumn('payments', 'staff_id') && !isset($indexesFound['payments_staff_id_foreign'])) {
                try {
                    $table->foreign('staff_id')->references('id')->on('staff')->onDelete('set null');
                } catch (\Exception $e) {
                    // Foreign key might already exist or fail for other reasons
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['customer_id']);
            $table->dropForeign(['staff_id']);
            $table->dropColumn([
                'mpesa_transaction_id',
                'notes',
                'refund_amount',
                'refunded_at',
                'gateway_response',
                'card_last_four',
                'card_brand',
                'bank_reference',
                'authorization_code',
                'payment_channel',
                'customer_id',
                'staff_id'
            ]);
        });
    }
};
