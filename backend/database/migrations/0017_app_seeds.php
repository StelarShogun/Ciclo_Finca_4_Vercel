<?php

use App\Models\AppSetting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $weeklyDefaults = [
            'weekly_report_day' => '1',
            'weekly_report_hour' => '8',
            'weekly_report_minute' => '0',
            'weekly_report_recipients' => '[]',
        ];

        foreach ($weeklyDefaults as $key => $value) {
            DB::table('app_settings')->updateOrInsert(
                ['key' => $key],
                ['key' => $key, 'value' => $value, 'created_at' => now(), 'updated_at' => now()]
            );
        }

        DB::table('app_settings')->updateOrInsert(
            ['key' => 'supplier_order_default_delivery_days'],
            ['value' => '7', 'created_at' => now(), 'updated_at' => now()]
        );

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
        DB::table('app_settings')->whereIn('key', [
            'weekly_report_recipients',
            'weekly_report_day',
            'weekly_report_hour',
            'weekly_report_minute',
            'supplier_order_default_delivery_days',
        ])->delete();
    }
};
