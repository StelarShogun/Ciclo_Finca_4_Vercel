<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Adds ready_at to track exactly when an order entered "ready_to_pickup".
// This timestamp is the authoritative source for the 3-day auto-cancellation window,
// avoiding false resets that would occur if updated_at were used instead.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->timestamp('ready_at')
                ->nullable()
                ->after('status')
                ->comment('Set when status transitions to ready_to_pickup. Used for auto-cancellation.');
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn('ready_at');
        });
    }
};
