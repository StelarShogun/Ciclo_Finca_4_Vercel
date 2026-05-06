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

        // Fix: totals can exceed DECIMAL(12,2) for large orders.
        DB::statement('ALTER TABLE `orders` MODIFY COLUMN `total` DECIMAL(15,2) NOT NULL DEFAULT 0');

        // Keep line totals consistent (best-effort).
        if (Schema::hasTable('order_items')) {
            DB::statement('ALTER TABLE `order_items` MODIFY COLUMN `unit_price` DECIMAL(15,2) NOT NULL DEFAULT 0');
            DB::statement('ALTER TABLE `order_items` MODIFY COLUMN `total` DECIMAL(15,2) NOT NULL DEFAULT 0');
        }
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        // Best-effort rollback.
        DB::statement('ALTER TABLE `orders` MODIFY COLUMN `total` DECIMAL(12,2) NOT NULL DEFAULT 0');

        if (Schema::hasTable('order_items')) {
            DB::statement('ALTER TABLE `order_items` MODIFY COLUMN `unit_price` DECIMAL(12,2) NOT NULL DEFAULT 0');
            DB::statement('ALTER TABLE `order_items` MODIFY COLUMN `total` DECIMAL(12,2) NOT NULL DEFAULT 0');
        }
    }
};
