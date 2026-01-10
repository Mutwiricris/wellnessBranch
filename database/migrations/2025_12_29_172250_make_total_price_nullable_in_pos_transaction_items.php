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
        // Give total_price a default value of 0
        \DB::statement('ALTER TABLE `pos_transaction_items` MODIFY `total_price` DECIMAL(10,2) NULL DEFAULT 0');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        \DB::statement('ALTER TABLE `pos_transaction_items` MODIFY `total_price` DECIMAL(10,2) NOT NULL');
    }
};
