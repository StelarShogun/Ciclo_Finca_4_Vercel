<?php

namespace Tests\Unit;

use App\Support\AdminDateRange;
use Carbon\Carbon;
use Illuminate\Support\Carbon as SupportCarbon;
use Tests\TestCase;

class AdminDateRangeTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();
        SupportCarbon::setTestNow();
        parent::tearDown();
    }

    public function test_today_bounds_use_app_timezone(): void
    {
        config(['app.timezone' => 'America/Costa_Rica']);
        Carbon::setTestNow(Carbon::parse('2026-05-22 23:58:00', 'America/Costa_Rica'));
        SupportCarbon::setTestNow(Carbon::parse('2026-05-22 23:58:00', 'America/Costa_Rica'));

        [$start, $end] = AdminDateRange::boundsForUtcColumn(AdminDateRange::PRESET_TODAY);

        $this->assertSame('2026-05-22 06:00:00', $start->format('Y-m-d H:i:s'));
        $this->assertSame('2026-05-23 05:59:59', $end->format('Y-m-d H:i:s'));
    }
}
