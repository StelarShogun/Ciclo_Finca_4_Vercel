<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('suppliers')) {
            return;
        }

        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        // FULLTEXT enables fast search over supplier names.
        try {
            DB::statement('ALTER TABLE `suppliers` ADD FULLTEXT `ft_suppliers_name` (`name`)');
        } catch (Throwable $e) {
            // ignore
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('suppliers')) {
            return;
        }

        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        try {
            DB::statement('ALTER TABLE `suppliers` DROP INDEX `ft_suppliers_name`');
        } catch (Throwable $e) {
            // ignore
        }
    }
};
