<?php

namespace Tests\Feature;

use App\Http\Requests\Admin\Orders\UpdateOrderSettingsRequest;
use App\Models\AppSetting;
use App\Models\Sale;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class OrderExpirationSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_ready_to_pickup_expiration_uses_config_default(): void
    {
        Config::set('sales.ready_to_pickup_expiration_hours', 72);

        $this->assertSame(72, Sale::getReadyToPickupExpirationHours());
    }

    public function test_ready_to_pickup_expiration_uses_database_hours(): void
    {
        Config::set('sales.ready_to_pickup_expiration_hours', 72);

        AppSetting::setReadyToPickupExpirationHours(168);

        $this->assertSame(168, Sale::getReadyToPickupExpirationHours());
        $this->assertDatabaseHas('app_settings', [
            'key' => AppSetting::KEY_READY_TO_PICKUP_EXPIRATION_HOURS,
            'value' => '168',
        ]);
    }

    public function test_ready_to_pickup_expiration_falls_back_from_legacy_days(): void
    {
        Config::set('sales.ready_to_pickup_expiration_hours', 72);

        AppSetting::setReadyToPickupExpirationDays(4);

        $this->assertSame(96, Sale::getReadyToPickupExpirationHours());
    }

    public function test_order_settings_request_rejects_invalid_hours(): void
    {
        $rules = (new UpdateOrderSettingsRequest)->rules();

        $this->assertTrue(Validator::make(['ready_to_pickup_expiration_hours' => 1], $rules)->passes());
        $this->assertFalse(Validator::make(['ready_to_pickup_expiration_hours' => 0], $rules)->passes());
        $this->assertFalse(Validator::make(['ready_to_pickup_expiration_hours' => -3], $rules)->passes());
        $this->assertFalse(Validator::make(['ready_to_pickup_expiration_hours' => 'not-a-number'], $rules)->passes());
    }
}
