<?php

namespace Tests\Feature\Api;

use App\Models\Category;
use App\Models\Client;
use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * API v1 checkout del cliente: requiere sesión, crea venta, descuenta stock y
 * vacía el carrito. Stock con locks/transacción en CheckoutCart.
 */
class ClientCheckoutApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['sanctum.stateful' => ['localhost', 'localhost:3000', '127.0.0.1']]);
        $this->withHeader('Origin', 'http://localhost:3000');
    }

    private function client(): Client
    {
        return Client::create([
            'name' => 'Compradora', 'first_surname' => 'Test', 'second_surname' => null,
            'gmail' => 'compradora@gmail.com', 'password' => bcrypt('password123'),
            'email_verified' => true, 'active' => true, 'provider' => 'local',
        ]);
    }

    private function product(int $stock = 5): Product
    {
        $cat = Category::firstOrCreate(['name' => 'Bicis Checkout']);
        Supplier::firstOrCreate(['name' => 'Sup Checkout']);

        return Product::factory()->create([
            'category_id' => $cat->category_id, 'status' => 'active',
            'stock_current' => $stock, 'sale_price' => 1000, 'purchase_price' => 500,
        ]);
    }

    public function test_checkout_requires_authentication(): void
    {
        $this->postJson('/api/v1/cart/checkout', ['payment_method' => 'cash'])->assertStatus(401);
    }

    public function test_checkout_creates_sale_and_decrements_stock(): void
    {
        $client = $this->client();
        $product = $this->product(5);
        $this->actingAs($client, 'clients');

        $this->postJson('/api/v1/cart/add', ['product_id' => $product->public_id, 'quantity' => 2])->assertOk();

        $this->postJson('/api/v1/cart/checkout', ['payment_method' => 'cash'])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['sale_id', 'invoice_number']);

        $this->assertSame(3, $product->fresh()->stock_current);
        $this->assertDatabaseHas('sales', ['client_id' => $client->user_id]);

        // Carrito vacío tras el checkout.
        $this->getJson('/api/v1/cart')->assertJsonPath('data.total', 0);
    }

    public function test_checkout_empty_cart_fails(): void
    {
        $this->actingAs($this->client(), 'clients');

        $this->postJson('/api/v1/cart/checkout', ['payment_method' => 'cash'])
            ->assertStatus(400)
            ->assertJsonPath('success', false);
    }
}
