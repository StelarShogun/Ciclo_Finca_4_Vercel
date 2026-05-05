<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\AppSetting;
use App\Models\Client;
use App\Models\Sale;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class OrderExpirationSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function createAdmin(): AdminUser
    {
        return AdminUser::create([
            'name' => 'Admin',
            'first_surname' => 'Test',
            'second_surname' => null,
            'gmail' => 'admin-expiry-settings@example.com',
            'password' => bcrypt('password'),
            'last_access' => now(),
        ]);
    }

    public function test_guest_cannot_access_orders_page(): void
    {
        $this->get(route('admin.orders.index'))
            ->assertRedirect(route('admin.login'));
    }

    public function test_admin_orders_page_includes_order_expiration_modal(): void
    {
        Config::set('sales.order_expiration_days', 30);

        $admin = $this->createAdmin();
        $this->actingAs($admin, 'admin');

        $this->get(route('admin.orders.index'))
            ->assertStatus(200)
            ->assertSee('order-expiration-modal', false)
            ->assertSee('Plazo para cancelación automática', false);
    }

    public function test_admin_can_save_order_expiration_days_via_put(): void
    {
        Config::set('sales.order_expiration_days', 30);
        Config::set('sales.ready_to_pickup_expiration_days', 3);

        $admin = $this->createAdmin();
        $this->actingAs($admin, 'admin');

        $put = $this->from(route('admin.orders.index'))
            ->put(route('admin.orders.settings.order-expiration.update'), [
                'ready_to_pickup_expiration_days' => 7,
            ]);
        $put->assertRedirect(route('admin.orders.index'));
        $put->assertSessionHas('status');

        $this->assertDatabaseHas('app_settings', [
            'key' => AppSetting::KEY_READY_TO_PICKUP_EXPIRATION_DAYS,
            'value' => '7',
        ]);

        $this->get(route('admin.orders.index'))
            ->assertStatus(200)
            ->assertSee('value="7"', false);

        $this->assertSame(7, Sale::getReadyToPickupExpirationDays());
    }

    public function test_put_with_accept_json_returns_json_payload(): void
    {
        Config::set('sales.order_expiration_days', 30);
        Config::set('sales.ready_to_pickup_expiration_days', 3);

        $admin = $this->createAdmin();
        $this->actingAs($admin, 'admin');

        $response = $this->putJson(route('admin.orders.settings.order-expiration.update'), [
            'ready_to_pickup_expiration_days' => 12,
        ]);

        $response->assertOk()
            ->assertJsonPath('ready_to_pickup_expiration_days', 12)
            ->assertJsonStructure(['message', 'ready_to_pickup_expiration_days']);
    }

    public function test_validation_rejects_zero_and_keeps_previous_value(): void
    {
        Config::set('sales.order_expiration_days', 30);
        Config::set('sales.ready_to_pickup_expiration_days', 3);

        $admin = $this->createAdmin();
        $this->actingAs($admin, 'admin');

        $this->put(route('admin.orders.settings.order-expiration.update'), [
            'ready_to_pickup_expiration_days' => 14,
        ])->assertSessionHasNoErrors();

        $response = $this->from(route('admin.orders.index'))
            ->put(route('admin.orders.settings.order-expiration.update'), [
                'ready_to_pickup_expiration_days' => 0,
            ]);

        $response->assertSessionHasErrors('ready_to_pickup_expiration_days');

        $this->assertDatabaseHas('app_settings', [
            'key' => AppSetting::KEY_READY_TO_PICKUP_EXPIRATION_DAYS,
            'value' => '14',
        ]);
    }

    public function test_validation_rejects_negative_or_non_numeric(): void
    {
        Config::set('sales.order_expiration_days', 30);
        Config::set('sales.ready_to_pickup_expiration_days', 3);

        $admin = $this->createAdmin();
        $this->actingAs($admin, 'admin');

        $this->put(route('admin.orders.settings.order-expiration.update'), [
            'ready_to_pickup_expiration_days' => 5,
        ])->assertSessionHasNoErrors();

        $this->from(route('admin.orders.index'))
            ->put(route('admin.orders.settings.order-expiration.update'), [
                'ready_to_pickup_expiration_days' => -3,
            ])
            ->assertSessionHasErrors('ready_to_pickup_expiration_days');

        $this->from(route('admin.orders.index'))
            ->put(route('admin.orders.settings.order-expiration.update'), [
                'ready_to_pickup_expiration_days' => 'not-a-number',
            ])
            ->assertSessionHasErrors('ready_to_pickup_expiration_days');

        $this->assertDatabaseHas('app_settings', [
            'key' => AppSetting::KEY_READY_TO_PICKUP_EXPIRATION_DAYS,
            'value' => '5',
        ]);
    }

    public function test_client_cannot_access_orders_or_update_expiration(): void
    {
        $client = Client::create([
            'name' => 'Cliente',
            'first_surname' => 'Web',
            'second_surname' => null,
            'gmail' => 'cliente-no-admin-expiry@example.com',
            'password' => bcrypt('password'),
            'provider' => 'local',
        ]);

        $this->actingAs($client, 'clients');

        $this->get(route('admin.orders.index'))
            ->assertRedirect(route('admin.login'));

        $this->put(route('admin.orders.settings.order-expiration.update'), [
            'ready_to_pickup_expiration_days' => 99,
        ])->assertRedirect(route('admin.login'));

        $this->assertDatabaseMissing('app_settings', [
            'key' => AppSetting::KEY_READY_TO_PICKUP_EXPIRATION_DAYS,
            'value' => '99',
        ]);
    }
}
