<?php

namespace Tests\Feature;

use App\Models\AppSetting;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SchedulerHeartbeatCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_scheduler_heartbeat_writes_last_heartbeat_at_to_app_settings(): void
    {
        $now = Carbon::parse('2026-05-23 14:30:00');
        Carbon::setTestNow($now);

        $this->artisan('scheduler:heartbeat')->assertSuccessful();

        $expected = $now->toDateTimeString();

        $this->assertDatabaseHas('app_settings', [
            'key' => AppSetting::KEY_SCHEDULER_LAST_HEARTBEAT_AT,
            'value' => $expected,
        ]);

        $this->assertSame($expected, AppSetting::getSchedulerLastHeartbeatAt());
    }
}
