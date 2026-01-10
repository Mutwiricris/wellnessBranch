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
        Schema::table('pos_transactions', function (Blueprint $table) {
            // Add missing columns
            if (!Schema::hasColumn('pos_transactions', 'payment_status')) {
                $table->string('payment_status', 20)->default('pending')->after('total_amount');
            }
            if (!Schema::hasColumn('pos_transactions', 'notes')) {
                $table->text('notes')->nullable()->after('payment_method');
            }
            if (!Schema::hasColumn('pos_transactions', 'booking_id')) {
                $table->foreignId('booking_id')->nullable()->after('client_id')->constrained('bookings')->nullOnDelete();
            }
            if (!Schema::hasColumn('pos_transactions', 'tip_amount')) {
                $table->decimal('tip_amount', 10, 2)->default(0)->after('tax_amount');
            }
            if (!Schema::hasColumn('pos_transactions', 'payment_details')) {
                $table->json('payment_details')->nullable()->after('payment_method');
            }
            if (!Schema::hasColumn('pos_transactions', 'mpesa_transaction_id')) {
                $table->string('mpesa_transaction_id')->nullable()->after('payment_details');
            }
            if (!Schema::hasColumn('pos_transactions', 'receipt_number')) {
                $table->string('receipt_number')->nullable()->after('transaction_number');
            }
            if (!Schema::hasColumn('pos_transactions', 'receipt_sent')) {
                $table->boolean('receipt_sent')->default(false)->after('receipt_number');
            }
            if (!Schema::hasColumn('pos_transactions', 'customer_info')) {
                $table->json('customer_info')->nullable()->after('client_id');
            }
            if (!Schema::hasColumn('pos_transactions', 'completed_at')) {
                $table->timestamp('completed_at')->nullable()->after('payment_status');
            }
            if (!Schema::hasColumn('pos_transactions', 'coupon_discount_amount')) {
                $table->decimal('coupon_discount_amount', 10, 2)->default(0)->after('discount_amount');
            }
            if (!Schema::hasColumn('pos_transactions', 'voucher_discount_amount')) {
                $table->decimal('voucher_discount_amount', 10, 2)->default(0)->after('coupon_discount_amount');
            }
            if (!Schema::hasColumn('pos_transactions', 'loyalty_points_used')) {
                $table->integer('loyalty_points_used')->default(0)->after('voucher_discount_amount');
            }
            if (!Schema::hasColumn('pos_transactions', 'loyalty_points_earned')) {
                $table->integer('loyalty_points_earned')->default(0)->after('loyalty_points_used');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pos_transactions', function (Blueprint $table) {
            $table->dropColumn([
                'payment_status',
                'notes',
                'tip_amount',
                'payment_details',
                'mpesa_transaction_id',
                'receipt_number',
                'receipt_sent',
                'customer_info',
                'completed_at',
                'coupon_discount_amount',
                'voucher_discount_amount',
                'loyalty_points_used',
                'loyalty_points_earned'
            ]);
            if (Schema::hasColumn('pos_transactions', 'booking_id')) {
                $table->dropForeign(['booking_id']);
                $table->dropColumn('booking_id');
            }
        });
    }
};
