<?php

namespace Tests\Unit\Services;

use App\Models\AppSetting;
use App\Models\Sale;
use App\Services\Shared\Sales\OrderExpirationPolicy;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class OrderExpirationPolicyTest extends TestCase
{
    private OrderExpirationPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::setDefaultDriver('array');
        Cache::flush();
        Config::set('app.timezone', 'UTC');
        Config::set('sales.order_expiration_days', 30);
        Config::set('sales.ready_to_pickup_expiration_hours', 72);
        date_default_timezone_set('UTC');
        Carbon::setTestNow(Carbon::parse('2026-06-15 12:00:00', 'UTC'));

        $this->policy = app(OrderExpirationPolicy::class);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_uses_cached_effective_values_before_config_fallbacks(): void
    {
        $this->assertSame(30, $this->policy->orderExpirationDays());
        $this->assertSame(72, $this->policy->readyToPickupExpirationHours());

        Cache::put(AppSetting::cacheKeyOrderExpirationDays(), 12, 3600);
        Cache::put(AppSetting::cacheKeyReadyToPickupExpirationHours(), 48, 3600);

        $this->assertSame(12, $this->policy->orderExpirationDays());
        $this->assertSame(48, $this->policy->readyToPickupExpirationHours());
    }

    public function test_expires_at_pickup_expiry_and_cutoff_are_calculated_from_policy(): void
    {
        Cache::put(AppSetting::cacheKeyOrderExpirationDays(), 10, 3600);
        Cache::put(AppSetting::cacheKeyReadyToPickupExpirationHours(), 24, 3600);

        $sale = new Sale([
            'sale_date' => Carbon::parse('2026-06-01 08:00:00', 'UTC'),
            'ready_at' => Carbon::parse('2026-06-14 11:00:00', 'UTC'),
        ]);

        $this->assertSame('2026-06-11 08:00:00', $this->policy->expiresAt($sale)->format('Y-m-d H:i:s'));
        $this->assertSame('2026-06-15 11:00:00', $this->policy->pickupExpiresAt($sale)?->format('Y-m-d H:i:s'));
        $this->assertTrue($this->policy->isPickupExpired($sale));
        $this->assertSame('2026-06-14 12:00:00', $this->policy->readyToPickupCutoff()->format('Y-m-d H:i:s'));
    }

    public function test_pickup_time_remaining_labels_match_existing_contract(): void
    {
        $sale = new Sale(['status' => 'ready_to_pickup']);
        $sale->ready_at = null;
        $this->assertSame('', $this->policy->pickupTimeRemainingLabel($sale));

        Cache::put(AppSetting::cacheKeyReadyToPickupExpirationHours(), 72, 3600);

        $sale->ready_at = Carbon::parse('2026-06-15 11:00:00', 'UTC');
        $this->assertSame('2 días restantes', $this->policy->pickupTimeRemainingLabel($sale));

        $sale->ready_at = Carbon::parse('2026-06-12 12:00:00', 'UTC');
        $this->assertSame('Vencido', $this->policy->pickupTimeRemainingLabel($sale));
    }
}
