<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Removes the notes column from inventory_movements.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_movements', function (Blueprint $table) {
            // Drops the obsolete notes column.
            $table->dropColumn('notes');
        });
    }

    public function down(): void
    {
        Schema::table('inventory_movements', function (Blueprint $table) {
            // Restores the notes column after reference_id on rollback.
            $table->text('notes')->nullable()->after('reference_id');
        });
    }
};
