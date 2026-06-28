<?php

namespace Tests\Feature\Api;

use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * API v1 carrito (invitado, por sesión): agregar, listar, actualizar, quitar,
 * vaciar. Reusa las Actions y el CartManager.
 */
class ClientCartApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['sanctum.stateful' => ['localhost', 'localhost:3000', '127.0.0.1']]);
        $this->withHeader('Origin', 'http://localhost:3000');
    }

    private function product(int $stock = 10): Product
    {
        $cat = Category::firstOrCreate(['name' => 'Bicis Cart']);
        Supplier::firstOrCreate(['name' => 'Sup Cart']);

        return Product::factory()->create([
            'category_id' => $cat->category_id,
            'status' => 'active',
            'stock_current' => $stock,
            'sale_price' => 1000,
            'purchase_price' => 500,
        ]);
    }

    public function test_cart_starts_empty(): void
    {
        $this->getJson('/api/v1/cart')
            ->assertOk()
            ->assertJsonStructure(['data' => ['items', 'total', 'totalFormatted']])
            ->assertJsonPath('data.total', 0);
    }

    public function test_add_update_remove_flow(): void
    {
        $product = $this->product();

        $this->postJson('/api/v1/cart/add', ['product_id' => $product->product_id, 'quantity' => 2])
            ->assertOk();

        $this->getJson('/api/v1/cart')
            ->assertOk()
            ->assertJsonPath('data.items.0.productId', (int) $product->product_id)
            ->assertJsonPath('data.items.0.quantity', 2)
            ->assertJsonPath('data.total', 2000);

        $this->putJson('/api/v1/cart/update', ['product_id' => $product->product_id, 'quantity' => 3])
            ->assertOk();
        $this->getJson('/api/v1/cart')->assertJsonPath('data.total', 3000);

        $this->deleteJson("/api/v1/cart/remove/{$product->product_id}")->assertOk();
        $this->getJson('/api/v1/cart')->assertJsonPath('data.total', 0);
    }

    public function test_clear_empties_cart(): void
    {
        $product = $this->product();
        $this->postJson('/api/v1/cart/add', ['product_id' => $product->product_id, 'quantity' => 1])->assertOk();

        $this->deleteJson('/api/v1/cart/clear')->assertOk();
        $this->getJson('/api/v1/cart')->assertJsonPath('data.total', 0);
    }

    public function test_add_validates_product(): void
    {
        $this->postJson('/api/v1/cart/add', ['product_id' => 999999, 'quantity' => 1])
            ->assertStatus(422);
    }
}
