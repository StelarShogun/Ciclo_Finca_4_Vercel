<?php

namespace Tests\Feature\Api;

use App\Models\AdminUser;
use App\Models\Sale;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * API v1 admin encargos (pedidos web): auth y listado filtrado a web_cart con
 * conteo de pendientes.
 */
class OrdersApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['sanctum.stateful' => ['localhost', 'localhost:3000', '127.0.0.1']]);
        $this->withHeader('Origin', 'http://localhost:3000');
    }

    private function admin(): AdminUser
    {
        return AdminUser::firstOrCreate(
            ['gmail' => 'orders-admin@example.com'],
            ['name' => 'Ord', 'first_surname' => 'Admin', 'second_surname' => null, 'password' => bcrypt('password123'), 'last_access' => now()],
        );
    }

    private function webOrder(string $status): Sale
    {
        return Sale::create([
            'invoice_number' => 'WEB-'.uniqid(),
            'buyer_name' => 'Cliente Web',
            'subtotal' => 100, 'iva' => 13, 'discount' => 0, 'total' => 113,
            'payment_method' => 'cash', 'status' => $status, 'order_source' => 'web_cart',
            'sale_date' => Carbon::now(),
        ]);
    }

    public function test_requires_authentication(): void
    {
        $this->getJson('/api/v1/admin/orders')->assertStatus(401);
    }

    public function test_lists_web_orders_with_pending_count(): void
    {
        $this->actingAs($this->admin(), 'admin');
        $this->webOrder('pending');
        $this->webOrder('ready_to_pickup');

        $this->getJson('/api/v1/admin/orders')
            ->assertOk()
            ->assertJsonStructure(['data' => ['orders', 'pagination', 'pendingWebOrdersCount', 'filters']])
            ->assertJsonPath('data.pendingWebOrdersCount', 1);
    }

    public function test_filters_by_status(): void
    {
        $this->actingAs($this->admin(), 'admin');
        $this->webOrder('pending');
        $this->webOrder('ready_to_pickup');

        $res = $this->getJson('/api/v1/admin/orders?status=ready_to_pickup')->assertOk();
        $statuses = collect($res->json('data.orders'))->pluck('status')->unique()->values();
        $this->assertEquals(['ready_to_pickup'], $statuses->all());
    }
}
