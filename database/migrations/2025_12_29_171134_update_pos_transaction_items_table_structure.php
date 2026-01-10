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
        // Make product_id nullable
        \DB::statement('ALTER TABLE `pos_transaction_items` MODIFY `product_id` BIGINT UNSIGNED NULL');

        // Rename 'total' to 'total_price' if 'total' exists and 'total_price' doesn't
        if (Schema::hasColumn('pos_transaction_items', 'total') && !Schema::hasColumn('pos_transaction_items', 'total_price')) {
            Schema::table('pos_transaction_items', function (Blueprint $table) {
                $table->renameColumn('total', 'total_price');
            });
        }

        // Rename 'subtotal' to match if needed
        if (Schema::hasColumn('pos_transaction_items', 'subtotal')) {
            Schema::table('pos_transaction_items', function (Blueprint $table) {
                $table->dropColumn('subtotal');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        \DB::statement('ALTER TABLE `pos_transaction_items` MODIFY `product_id` BIGINT UNSIGNED NOT NULL');

        if (Schema::hasColumn('pos_transaction_items', 'total_price')) {
            Schema::table('pos_transaction_items', function (Blueprint $table) {
                $table->renameColumn('total_price', 'total');
            });
        }
    }
};
