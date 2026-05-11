<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('order_items')) {
            return;
        }

        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        // FULLTEXT enables fast search over product names in supplier orders.
        // Best-effort: ignore if already exists or engine doesn't support it.
        try {
            DB::statement('ALTER TABLE `order_items` ADD FULLTEXT `ft_order_items_name` (`name`)');
        } catch (Throwable $e) {
            // ignore
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('order_items')) {
            return;
        }

        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        try {
            DB::statement('ALTER TABLE `order_items` DROP INDEX `ft_order_items_name`');
        } catch (Throwable $e) {
            // ignore
        }
    }
};
