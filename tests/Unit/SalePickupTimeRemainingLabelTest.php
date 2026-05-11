<?php

namespace Tests\Unit;

use App\Models\AppSetting;
use App\Models\Sale;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class SalePickupTimeRemainingLabelTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Config::set('sales.ready_to_pickup_expiration_hours', 72);
        Cache::flush();
        Cache::put(AppSetting::cacheKeyReadyToPickupExpirationHours(), 72, 3600);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_ready_at_null_returns_empty_string(): void
    {
        $sale = new Sale(['status' => 'ready_to_pickup']);
        $sale->ready_at = null;

        $this->assertSame('', $sale->pickup_time_remaining_label);
    }

    public function test_labels_for_fixed_now_and_ready_at(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-05-08 12:00:00', 'UTC'));

        $sale = new Sale(['status' => 'ready_to_pickup']);
        $sale->ready_at = Carbon::parse('2026-05-08 11:00:00', 'UTC');
        $this->assertSame('2 días restantes', $sale->pickup_time_remaining_label);

        $sale->ready_at = Carbon::parse('2026-05-07 11:00:00', 'UTC');
        $this->assertSame('1 día restante', $sale->pickup_time_remaining_label);

        $sale->ready_at = Carbon::parse('2026-05-06 11:00:00', 'UTC');
        $this->assertSame('23 horas restantes', $sale->pickup_time_remaining_label);

        $sale->ready_at = Carbon::parse('2026-05-05 13:00:00', 'UTC');
        $this->assertSame('1 hora restante', $sale->pickup_time_remaining_label);

        $sale->ready_at = Carbon::parse('2026-05-05 12:00:00', 'UTC');
        $this->assertSame('Vencido', $sale->pickup_time_remaining_label);
    }
}
