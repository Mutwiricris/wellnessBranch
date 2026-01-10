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
        // Make transaction_date nullable
        \DB::statement('ALTER TABLE `pos_transactions` MODIFY `transaction_date` DATE NULL DEFAULT (CURRENT_DATE)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        \DB::statement('ALTER TABLE `pos_transactions` MODIFY `transaction_date` DATE NOT NULL');
    }
};
