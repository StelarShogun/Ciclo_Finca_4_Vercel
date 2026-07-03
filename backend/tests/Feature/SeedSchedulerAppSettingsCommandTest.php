<?php

namespace Tests\Feature;

use App\Models\AppSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeedSchedulerAppSettingsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_seed_command_inserts_scheduler_keys_without_overwriting(): void
    {
        AppSetting::setSchedulerLastHeartbeatAt('2026-05-23 10:00:00');

        $this->artisan('cf4:seed-scheduler-app-settings')->assertSuccessful();

        $this->assertDatabaseHas('app_settings', [
            'key' => AppSetting::KEY_SCHEDULER_LAST_HEARTBEAT_AT,
            'value' => '2026-05-23 10:00:00',
        ]);

        foreach (AppSetting::schedulerMonitoringKeys() as $key) {
            $this->assertDatabaseHas('app_settings', ['key' => $key]);
        }

        $expectedCount = count(AppSetting::schedulerMonitoringKeys());
        $this->assertSame(
            $expectedCount,
            AppSetting::query()->where('key', 'like', 'scheduler_%')->count()
        );
    }

    public function test_seed_command_dry_run_does_not_insert(): void
    {
        $before = AppSetting::query()->where('key', 'like', 'scheduler_%')->count();

        $this->artisan('cf4:seed-scheduler-app-settings --dry-run')->assertSuccessful();

        $this->assertSame(
            $before,
            AppSetting::query()->where('key', 'like', 'scheduler_%')->count()
        );
    }
}
