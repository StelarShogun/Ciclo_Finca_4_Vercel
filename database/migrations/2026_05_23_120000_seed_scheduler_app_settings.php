<?php

use App\Models\AppSetting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * CF4-163 — Ensures scheduler monitoring rows exist in app_settings after deploy.
 * Insert-only; never overwrites values written by the running scheduler.
 */
return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        foreach (AppSetting::schedulerMonitoringKeys() as $key) {
            if (DB::table('app_settings')->where('key', $key)->exists()) {
                continue;
            }

            DB::table('app_settings')->insert([
                'key' => $key,
                'value' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        // Rows may already hold production telemetry; do not delete on rollback.
    }
};
