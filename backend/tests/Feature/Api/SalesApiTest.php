<?php

namespace Tests\Feature\Api;

use App\Models\AdminUser;
use App\Models\Category;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Supplier;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * API v1 admin sales: auth, historial (index/heartbeat), detalle y un caso de
 * ciclo de vida (cancelar una venta confirmada reintegra stock). Las Actions
 * llevan las transacciones de stock; aquí se valida el contrato del endpoint.
 */
class SalesApiTest extends TestCase
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
            ['gmail' => 'sales-admin@example.com'],
            ['name' => 'Sales', 'first_surname' => 'Admin', 'second_surname' => null, 'password' => bcrypt('password123'), 'last_access' => now()],
        );
    }

    private function completedSale(int $qty = 2, string $status = 'completed'): array
    {
        Category::firstOrCreate(['name' => 'Cat Sale']);
        Supplier::firstOrCreate(['name' => 'Sup Sale']);
        $product = Product::factory()->create(['stock_current' => 10]);

        $sale = Sale::create([
            'invoice_number' => 'INV'.now()->format('Ymd').mt_rand(1000, 9999),
            'seller_admin_id' => $this->admin()->user_id,
            'buyer_name' => 'Mostrador',
            'subtotal' => 100,
            'iva' => 13,
            'discount' => 0,
            'total' => 113,
            'payment_method' => 'cash',
            'status' => $status,
            'sale_date' => Carbon::now(),
        ]);
        SaleItem::create([
            'sale_id' => $sale->sale_id,
            'product_id' => $product->product_id,
            'quantity' => $qty,
            'unit_price' => 50,
            'total' => 100,
        ]);

        return [$sale, $product];
    }

    public function test_requires_authentication(): void
    {
        $this->getJson('/api/v1/admin/sales')->assertStatus(401);
        $this->getJson('/api/v1/admin/sales/1')->assertStatus(401);
    }

    public function test_index_returns_history_payload(): void
    {
        $this->actingAs($this->admin(), 'admin');
        [$sale] = $this->completedSale();

        $this->getJson('/api/v1/admin/sales?status=all&date_range=month')
            ->assertOk()
            ->assertJsonStructure(['data' => ['sales', 'pagination', 'kpis', 'salesStatusUi', 'filters']])
            ->assertJsonPath('data.sales.0.sale_id', (int) $sale->sale_id);
    }

    public function test_heartbeat_returns_counts(): void
    {
        $this->actingAs($this->admin(), 'admin');

        $this->getJson('/api/v1/admin/sales/heartbeat?since=0')
            ->assertOk()
            ->assertJsonStructure(['hasNew', 'newCount', 'latestSaleId', 'pendingCount']);
    }

    public function test_show_returns_sale_detail(): void
    {
        $this->actingAs($this->admin(), 'admin');
        [$sale] = $this->completedSale();

        $this->getJson("/api/v1/admin/sales/{$sale->sale_id}")
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('sale.sale_id', (int) $sale->sale_id);
    }

    public function test_cancel_ready_sale_restocks(): void
    {
        $this->actingAs($this->admin(), 'admin');
        [$sale, $product] = $this->completedSale(2, 'ready_to_pickup');
        $before = $product->fresh()->stock_current;

        $this->postJson("/api/v1/admin/sales/{$sale->sale_id}/cancel", ['reason' => 'Prueba de cancelación'])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSame('cancelled', $sale->fresh()->status);
        $this->assertSame($before + 2, $product->fresh()->stock_current);
    }

    public function test_cannot_cancel_completed_sale(): void
    {
        $this->actingAs($this->admin(), 'admin');
        [$sale] = $this->completedSale();

        // Una venta confirmada no se rechaza: se usa devolución.
        $this->postJson("/api/v1/admin/sales/{$sale->sale_id}/cancel", ['reason' => 'Intento inválido'])
            ->assertStatus(400)
            ->assertJsonPath('success', false);
    }

    public function test_cancel_requires_reason(): void
    {
        $this->actingAs($this->admin(), 'admin');
        [$sale] = $this->completedSale();

        $this->postJson("/api/v1/admin/sales/{$sale->sale_id}/cancel", ['reason' => ''])
            ->assertStatus(422)
            ->assertJsonValidationErrors('reason');
    }
}
