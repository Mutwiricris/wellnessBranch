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
        // Add transaction_date if it doesn't exist
        if (!Schema::hasColumn('pos_transactions', 'transaction_date')) {
            Schema::table('pos_transactions', function (Blueprint $table) {
                $table->date('transaction_date')->nullable()->after('branch_id');
            });
        }

        // Make transaction_date nullable and set default
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
