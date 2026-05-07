<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (! Schema::hasColumn('orders', 'confirmed_at')) {
                $table->timestamp('confirmed_at')->nullable()->after('state');
            }
            if (! Schema::hasColumn('orders', 'confirmed_by')) {
                $table->unsignedBigInteger('confirmed_by')->nullable()->after('confirmed_at');
            }
        });

        // Add FK separately to avoid issues when columns already exist.
        try {
            Schema::table('orders', function (Blueprint $table) {
                $table->foreign('confirmed_by')
                    ->references('user_id')
                    ->on('admins')
                    ->nullOnDelete();
            });
        } catch (Throwable $e) {
            // Best-effort: ignore if FK already exists or DB doesn't support it.
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('orders')) {
            return;
        }

        // Drop FK best-effort.
        try {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropForeign(['confirmed_by']);
            });
        } catch (Throwable $e) {
            // ignore
        }

        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'confirmed_by')) {
                $table->dropColumn('confirmed_by');
            }
            if (Schema::hasColumn('orders', 'confirmed_at')) {
                $table->dropColumn('confirmed_at');
            }
        });
    }
};
