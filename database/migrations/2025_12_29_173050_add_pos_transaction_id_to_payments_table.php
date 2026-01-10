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
            if (!Schema::hasColumn('payments', 'pos_transaction_id')) {
                $table->foreignId('pos_transaction_id')->nullable()->after('booking_id')->constrained('pos_transactions')->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (Schema::hasColumn('payments', 'pos_transaction_id')) {
                $table->dropForeign(['pos_transaction_id']);
                $table->dropColumn('pos_transaction_id');
            }
        });
    }
};
