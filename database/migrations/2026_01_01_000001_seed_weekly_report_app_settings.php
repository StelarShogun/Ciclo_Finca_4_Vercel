<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Inserts the three weekly-report AppSetting rows with sensible defaults
 * if they do not already exist.  Using updateOrInsert so re-running the
 * migration is safe and never overwrites values the administrator already saved.
 */
return new class extends Migration
{
    public function up(): void
    {
        $defaults = [
            'weekly_report_day'        => '1',    // Monday (0 = Sun … 6 = Sat)
            'weekly_report_hour'       => '8',    // 08:00
            'weekly_report_minute'     => '0',    // 00
            'weekly_report_recipients' => '[]',   // empty list — admin must fill this in
        ];

        foreach ($defaults as $key => $value) {
            DB::table('app_settings')->updateOrInsert(
                ['key' => $key],
                ['key' => $key, 'value' => $value, 'created_at' => now(), 'updated_at' => now()]
            );
        }
    }

    public function down(): void
    {
        DB::table('app_settings')->whereIn('key', [
            'weekly_report_recipients',
            'weekly_report_day',
            'weekly_report_hour',
            'weekly_report_minute',
        ])->delete();
    }
};
