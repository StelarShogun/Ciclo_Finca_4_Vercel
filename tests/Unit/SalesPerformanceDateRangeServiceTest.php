<?php

namespace Tests\Unit;

use App\Services\SalesPerformanceDateRangeService;
use Carbon\CarbonImmutable;
use Tests\TestCase;

class SalesPerformanceDateRangeServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    public function test_resolve_today_returns_current_day_and_previous_day(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-04-16 10:30:00'));

        $service = new SalesPerformanceDateRangeService();
        $resolved = $service->resolve(['preset' => 'today']);

        $this->assertSame('today', $resolved['preset']);
        $this->assertSame('2026-04-16', $resolved['from']);
        $this->assertSame('2026-04-16', $resolved['to']);
        $this->assertSame('2026-04-16 00:00:00', $resolved['current_start']->format('Y-m-d H:i:s'));
        $this->assertSame('2026-04-16 23:59:59', $resolved['current_end']->format('Y-m-d H:i:s'));
        $this->assertSame('2026-04-15 00:00:00', $resolved['previous_start']->format('Y-m-d H:i:s'));
        $this->assertSame('2026-04-15 23:59:59', $resolved['previous_end']->format('Y-m-d H:i:s'));
    }

    public function test_resolve_custom_returns_equivalent_previous_period(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-04-16 10:30:00'));

        $service = new SalesPerformanceDateRangeService();
        $resolved = $service->resolve([
            'preset' => 'custom',
            'from' => '2026-04-10',
            'to' => '2026-04-12',
        ]);

        $this->assertSame('custom', $resolved['preset']);
        $this->assertSame('2026-04-10', $resolved['from']);
        $this->assertSame('2026-04-12', $resolved['to']);
        $this->assertSame('2026-04-10 00:00:00', $resolved['current_start']->format('Y-m-d H:i:s'));
        $this->assertSame('2026-04-12 23:59:59', $resolved['current_end']->format('Y-m-d H:i:s'));
        $this->assertSame('2026-04-07 00:00:00', $resolved['previous_start']->format('Y-m-d H:i:s'));
        $this->assertSame('2026-04-09 23:59:59', $resolved['previous_end']->format('Y-m-d H:i:s'));
    }
}
