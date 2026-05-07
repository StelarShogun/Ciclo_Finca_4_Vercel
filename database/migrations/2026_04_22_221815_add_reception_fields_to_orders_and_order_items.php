<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

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
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

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
