<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('sales', 'status')) {
            return;
        }

        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::statement(
            "ALTER TABLE `sales` MODIFY COLUMN `status` ENUM('pending','ready_to_pickup','completed','cancelled','refunded') NOT NULL DEFAULT 'pending'"
        );
    }

    public function down(): void
    {
        if (! Schema::hasColumn('sales', 'status')) {
            return;
        }

        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::statement(
            "ALTER TABLE `sales` MODIFY COLUMN `status` ENUM('pending','completed','cancelled','refunded') NOT NULL DEFAULT 'pending'"
        );
    }
};
