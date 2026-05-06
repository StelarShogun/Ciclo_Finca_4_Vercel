<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            ALTER TABLE orders
            MODIFY COLUMN state ENUM(
                'draft',
                'pending',
                'confirmed',
                'delivered',
                'cancelled'
            ) NOT NULL DEFAULT 'draft'
        ");
    }

    public function down(): void
    {
        DB::statement("
            ALTER TABLE orders
            MODIFY COLUMN state ENUM(
                'draft',
                'pending',
                'confirmed',
                'in_transit',
                'partial_received',
                'delivered',
                'cancelled'
            ) NOT NULL DEFAULT 'draft'
        ");
    }
};
