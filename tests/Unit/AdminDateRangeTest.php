<?php

namespace Tests\Unit;

use App\Support\AdminDateRange;
use Carbon\Carbon;
use Illuminate\Support\Carbon as SupportCarbon;
use Tests\TestCase;

class AdminDateRangeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['app.timezone' => 'America/Costa_Rica']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        SupportCarbon::setTestNow();
        parent::tearDown();
    }

    public function test_today_bounds_use_app_timezone(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-05-22 23:58:00', 'America/Costa_Rica'));

        [$start, $end] = AdminDateRange::bounds(AdminDateRange::PRESET_TODAY);

        $this->assertSame('America/Costa_Rica', $start->timezone->getName());
        $this->assertSame('2026-05-22 00:00:00', $start->format('Y-m-d H:i:s'));
        $this->assertSame('2026-05-22 23:59:59', $end->format('Y-m-d H:i:s'));
    }

    public function test_today_bounds_do_not_include_next_calendar_day(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-05-22 23:58:59', 'America/Costa_Rica'));

        [, $end] = AdminDateRange::bounds(AdminDateRange::PRESET_TODAY);

        $this->assertSame('2026-05-22', $end->toDateString());
        $this->assertNotSame('2026-05-23', $end->toDateString());
    }
}
