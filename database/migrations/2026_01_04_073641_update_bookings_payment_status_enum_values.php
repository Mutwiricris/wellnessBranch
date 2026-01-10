<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update the payment_status enum to include all PaymentStatus enum values
        DB::statement("ALTER TABLE bookings MODIFY COLUMN payment_status ENUM('pending', 'processing', 'completed', 'paid', 'failed', 'refunded', 'partially_refunded', 'partial') NOT NULL DEFAULT 'pending'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to the original enum values
        DB::statement("ALTER TABLE bookings MODIFY COLUMN payment_status ENUM('pending', 'paid', 'partial', 'refunded') NOT NULL DEFAULT 'pending'");
    }
};
