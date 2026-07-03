<?php

namespace Tests\Feature\Api;

use App\Models\Category;
use App\Models\Client;
use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * API v1 carrito: requiere sesión de cliente (como la app vieja). El SPA
 * referencia productos por su ID público (ULID); los internos se rechazan
 * en la URL y se aceptan solo en el body por compatibilidad con el web viejo.
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

    private function client(string $gmail = 'carrito@gmail.com'): Client
    {
        return Client::create([
            'name' => 'Carrito', 'first_surname' => 'Test', 'second_surname' => null,
            'gmail' => $gmail, 'password' => bcrypt('password123'),
            'email_verified' => true, 'active' => true, 'provider' => 'local',
        ]);
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

    public function test_cart_requires_client_session(): void
    {
        $this->getJson('/api/v1/cart')->assertStatus(401);
        $this->postJson('/api/v1/cart/add', ['product_id' => 'x', 'quantity' => 1])->assertStatus(401);
    }

    public function test_cart_starts_empty(): void
    {
        $this->actingAs($this->client(), 'clients');

        $this->getJson('/api/v1/cart')
            ->assertOk()
            ->assertJsonStructure(['data' => ['items', 'total', 'totalFormatted']])
            ->assertJsonPath('data.total', 0);
    }

    public function test_add_update_remove_flow_with_public_ids(): void
    {
        $this->actingAs($this->client(), 'clients');
        $product = $this->product();

        $this->postJson('/api/v1/cart/add', ['product_id' => $product->public_id, 'quantity' => 2])
            ->assertOk();

        // El payload expone el ID público, nunca el autoincremental.
        $this->getJson('/api/v1/cart')
            ->assertOk()
            ->assertJsonPath('data.items.0.productId', $product->public_id)
            ->assertJsonPath('data.items.0.quantity', 2)
            ->assertJsonPath('data.total', 2000);

        $this->putJson('/api/v1/cart/update', ['product_id' => $product->public_id, 'quantity' => 3])
            ->assertOk();
        $this->getJson('/api/v1/cart')->assertJsonPath('data.total', 3000);

        $this->deleteJson("/api/v1/cart/remove/{$product->public_id}")->assertOk();
        $this->getJson('/api/v1/cart')->assertJsonPath('data.total', 0);
    }

    public function test_remove_rejects_internal_numeric_id(): void
    {
        $this->actingAs($this->client(), 'clients');
        $product = $this->product();
        $this->postJson('/api/v1/cart/add', ['product_id' => $product->public_id, 'quantity' => 1])->assertOk();

        $this->deleteJson("/api/v1/cart/remove/{$product->product_id}")->assertStatus(404);
        $this->getJson('/api/v1/cart')->assertJsonPath('data.total', 1000);
    }

    public function test_clear_empties_cart(): void
    {
        $this->actingAs($this->client(), 'clients');
        $product = $this->product();
        $this->postJson('/api/v1/cart/add', ['product_id' => $product->public_id, 'quantity' => 1])->assertOk();

        $this->deleteJson('/api/v1/cart/clear')->assertOk();
        $this->getJson('/api/v1/cart')->assertJsonPath('data.total', 0);
    }

    public function test_add_validates_product(): void
    {
        $this->actingAs($this->client(), 'clients');

        $this->postJson('/api/v1/cart/add', ['product_id' => '01JZZZZZZZZZZZZZZZZZZZZZZZ', 'quantity' => 1])
            ->assertStatus(422);
    }
}
