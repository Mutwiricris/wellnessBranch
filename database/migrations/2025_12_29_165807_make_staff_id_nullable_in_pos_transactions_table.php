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
            // Drop the foreign key constraint
            $table->dropForeign(['staff_id']);
        });

        // Make staff_id nullable
        \DB::statement('ALTER TABLE `pos_transactions` MODIFY `staff_id` BIGINT UNSIGNED NULL');

        Schema::table('pos_transactions', function (Blueprint $table) {
            // Re-add the foreign key with nullable
            $table->foreign('staff_id')->references('id')->on('staff')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pos_transactions', function (Blueprint $table) {
            $table->dropForeign(['staff_id']);
        });

        \DB::statement('ALTER TABLE `pos_transactions` MODIFY `staff_id` BIGINT UNSIGNED NOT NULL');

        Schema::table('pos_transactions', function (Blueprint $table) {
            $table->foreign('staff_id')->references('id')->on('staff')->nullOnDelete();
        });
    }
};
