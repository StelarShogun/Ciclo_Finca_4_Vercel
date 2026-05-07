<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (! Schema::hasColumn('orders', 'po_number')) {
                $table->string('po_number', 32)->nullable()->after('num_order');
            }
            if (! Schema::hasColumn('orders', 'estimated_delivery_date')) {
                $table->date('estimated_delivery_date')->nullable()->after('date');
            }
        });

        // Add unique index separately (MySQL requires index name)
        if (Schema::hasColumn('orders', 'po_number')) {
            try {
                Schema::table('orders', function (Blueprint $table) {
                    $table->unique('po_number', 'uq_orders_po_number');
                });
            } catch (Throwable $e) {
                // Ignore if index already exists
            }
        }

        // Add state 'draft' and default to draft.
        // MySQL: update the enum column definition.
        if (Schema::hasColumn('orders', 'state')) {
            if (Schema::getConnection()->getDriverName() === 'mysql') {
                DB::statement(
                    "ALTER TABLE `orders` MODIFY COLUMN `state` ENUM('draft','pending','confirmed','delivered','cancelled') NOT NULL DEFAULT 'draft'"
                );
            }

            // Backfill existing records: keep current values if valid, otherwise set to pending.
            DB::statement(
                "UPDATE `orders` SET `state` = 'pending' WHERE `state` IS NULL OR `state` = ''"
            );
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('orders')) {
            // revert enum (best-effort)
            try {
                if (Schema::getConnection()->getDriverName() === 'mysql') {
                    DB::statement(
                        "ALTER TABLE `orders` MODIFY COLUMN `state` ENUM('pending','confirmed','delivered','cancelled') NOT NULL DEFAULT 'pending'"
                    );
                }
            } catch (Throwable $e) {
                // ignore
            }

            Schema::table('orders', function (Blueprint $table) {
                if (Schema::hasColumn('orders', 'po_number')) {
                    try {
                        $table->dropUnique('uq_orders_po_number');
                    } catch (Throwable $e) {
                        // ignore
                    }
                    $table->dropColumn('po_number');
                }
                if (Schema::hasColumn('orders', 'estimated_delivery_date')) {
                    $table->dropColumn('estimated_delivery_date');
                }
            });
        }
    }
};
