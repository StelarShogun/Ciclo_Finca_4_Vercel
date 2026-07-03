<?php

namespace Tests\Feature\Api;

use App\Models\AdminUser;
use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * API v1 admin inventory: auth, listado de stock, ajustes manuales (que generan
 * movimiento) e historial de movimientos por producto.
 */
class InventoryApiTest extends TestCase
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
            ['gmail' => 'inv-admin@example.com'],
            ['name' => 'Inv', 'first_surname' => 'Admin', 'second_surname' => null, 'password' => bcrypt('password123'), 'last_access' => now()],
        );
    }

    private function product(int $stock = 5): Product
    {
        Category::firstOrCreate(['name' => 'Cat Inv']);
        Supplier::firstOrCreate(['name' => 'Sup Inv']);

        return Product::factory()->create(['stock_current' => $stock, 'purchase_price' => 500, 'sale_price' => 1000]);
    }

    public function test_requires_authentication(): void
    {
        $this->getJson('/api/v1/admin/inventory')->assertStatus(401);
    }

    public function test_index_returns_stock_payload(): void
    {
        $this->actingAs($this->admin(), 'admin');
        $this->product();

        $this->getJson('/api/v1/admin/inventory')
            ->assertOk()
            ->assertJsonStructure(['data' => ['products', 'pagination', 'inventorySummary', 'filters']]);
    }

    public function test_add_manual_stock_increments_and_records_movement(): void
    {
        $this->actingAs($this->admin(), 'admin');
        $product = $this->product(5);

        $this->postJson("/api/v1/admin/inventory/{$product->product_id}/add", [
            'quantity' => 4,
            'reason' => 'Reabastecimiento manual',
        ])->assertOk()->assertJsonPath('success', true);

        $this->assertSame(9, $product->fresh()->stock_current);
        $this->assertDatabaseHas('inventory_movements', ['product_id' => $product->product_id, 'type' => 'entrada']);
    }

    public function test_remove_manual_stock_decrements(): void
    {
        $this->actingAs($this->admin(), 'admin');
        $product = $this->product(5);

        $this->postJson("/api/v1/admin/inventory/{$product->product_id}/remove", [
            'quantity' => 2,
            'reason' => 'Merma por daño',
        ])->assertOk()->assertJsonPath('success', true);

        $this->assertSame(3, $product->fresh()->stock_current);
    }

    public function test_add_requires_reason(): void
    {
        $this->actingAs($this->admin(), 'admin');
        $product = $this->product();

        $this->postJson("/api/v1/admin/inventory/{$product->product_id}/add", ['quantity' => 4, 'reason' => ''])
            ->assertStatus(422);
    }

    public function test_movements_history(): void
    {
        $this->actingAs($this->admin(), 'admin');
        $product = $this->product(5);

        $this->postJson("/api/v1/admin/inventory/{$product->product_id}/add", [
            'quantity' => 3,
            'reason' => 'Carga inicial',
        ])->assertOk();

        $this->getJson("/api/v1/admin/inventory/{$product->product_id}/movements")
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('product.product_id', (int) $product->product_id)
            ->assertJsonStructure(['data', 'summary', 'meta']);
    }
}
