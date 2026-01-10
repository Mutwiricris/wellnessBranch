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
        Schema::table('pos_transaction_items', function (Blueprint $table) {
            // Add missing columns
            if (!Schema::hasColumn('pos_transaction_items', 'item_type')) {
                $table->string('item_type', 20)->after('pos_transaction_id'); // 'product' or 'service'
            }
            if (!Schema::hasColumn('pos_transaction_items', 'item_id')) {
                $table->unsignedBigInteger('item_id')->after('item_type'); // product_id or service_id
            }
            if (!Schema::hasColumn('pos_transaction_items', 'item_name')) {
                $table->string('item_name')->nullable()->after('item_id');
            }
            if (!Schema::hasColumn('pos_transaction_items', 'item_description')) {
                $table->text('item_description')->nullable()->after('item_name');
            }
            if (!Schema::hasColumn('pos_transaction_items', 'total_price')) {
                $table->decimal('total_price', 10, 2)->after('discount_amount');
            }
            if (!Schema::hasColumn('pos_transaction_items', 'assigned_staff_id')) {
                $table->foreignId('assigned_staff_id')->nullable()->after('total_price')->constrained('staff')->nullOnDelete();
            }
            if (!Schema::hasColumn('pos_transaction_items', 'duration_minutes')) {
                $table->integer('duration_minutes')->nullable()->after('assigned_staff_id');
            }
            if (!Schema::hasColumn('pos_transaction_items', 'service_start_time')) {
                $table->dateTime('service_start_time')->nullable()->after('duration_minutes');
            }
            if (!Schema::hasColumn('pos_transaction_items', 'service_end_time')) {
                $table->dateTime('service_end_time')->nullable()->after('service_start_time');
            }
            if (!Schema::hasColumn('pos_transaction_items', 'sku')) {
                $table->string('sku')->nullable()->after('service_end_time');
            }
            if (!Schema::hasColumn('pos_transaction_items', 'cost_price')) {
                $table->decimal('cost_price', 10, 2)->nullable()->after('sku');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pos_transaction_items', function (Blueprint $table) {
            $columns = [
                'item_type', 'item_id', 'item_name', 'item_description',
                'total_price', 'duration_minutes',
                'service_start_time', 'service_end_time', 'sku', 'cost_price'
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('pos_transaction_items', $column)) {
                    $table->dropColumn($column);
                }
            }

            if (Schema::hasColumn('pos_transaction_items', 'assigned_staff_id')) {
                $table->dropForeign(['assigned_staff_id']);
                $table->dropColumn('assigned_staff_id');
            }
        });
    }
};
