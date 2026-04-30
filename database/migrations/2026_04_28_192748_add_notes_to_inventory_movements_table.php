<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Adds a human-readable notes column to inventory_movements (CA-03).
// This stores the standardized reason text, e.g. "Recepción de pedido de proveedor".
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_movements', function (Blueprint $table) {
            // Nullable so existing records are not broken.
            $table->string('notes')->nullable()->after('reference_id');
        });
    }

    public function down(): void
    {
        Schema::table('inventory_movements', function (Blueprint $table) {
            $table->dropColumn('notes');
        });
    }
};