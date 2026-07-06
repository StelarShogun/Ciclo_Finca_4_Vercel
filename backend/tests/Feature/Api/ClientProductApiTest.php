<?php

namespace Tests\Feature\Api;

use App\Models\Category;
use App\Models\Client;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * API v1 detalle de producto público: estructura del payload (reusa
 * BuildProductDetailPage) y 404.
 */
class ClientProductApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['sanctum.stateful' => ['localhost', 'localhost:3000', '127.0.0.1']]);
        $this->withHeader('Origin', 'http://localhost:3000');
    }

    private function product(): Product
    {
        $cat = Category::firstOrCreate(['name' => 'Bicis Detalle']);
        Supplier::firstOrCreate(['name' => 'Sup Detalle']);

        return Product::factory()->create([
            'category_id' => $cat->category_id,
            'name' => 'Trek Marlin Test',
            'status' => 'active',
            'stock_current' => 4,
            'sale_price' => 250000,
            'purchase_price' => 180000,
        ]);
    }

    public function test_detail_is_public_and_structured(): void
    {
        $product = $this->product();

        $this->getJson("/api/v1/products/{$product->public_id}")
            ->assertOk()
            ->assertJsonStructure(['data' => ['product' => ['id', 'name', 'priceFormatted', 'carouselSlides', 'canBuy'], 'specs', 'reviews', 'relatedProducts', 'taxonomy']])
            ->assertJsonPath('data.product.id', (string) $product->public_id)
            ->assertJsonPath('data.product.name', 'Trek Marlin Test');
    }

    public function test_missing_product_returns_404(): void
    {
        $this->getJson('/api/v1/products/999999')->assertStatus(404);
        $this->getJson('/api/v1/products/01JZZZZZZZZZZZZZZZZZZZZZZZ')->assertStatus(404);
    }

    public function test_review_requires_auth(): void
    {
        $product = $this->product();
        $this->postJson("/api/v1/products/{$product->public_id}/reviews", ['stars' => 5])->assertStatus(401);
    }

    public function test_review_blocked_without_purchase(): void
    {
        $product = $this->product();
        $client = Client::create([
            'name' => 'Sin', 'first_surname' => 'Compra', 'second_surname' => null,
            'gmail' => 'sincompra@gmail.com', 'password' => bcrypt('x'),
            'email_verified' => true, 'active' => true, 'provider' => 'local',
        ]);
        $this->actingAs($client, 'clients');

        $this->postJson("/api/v1/products/{$product->public_id}/reviews", ['stars' => 4])
            ->assertStatus(403);
    }

    public function test_review_saved_after_purchase(): void
    {
        $product = $this->product();
        $client = Client::create([
            'name' => 'Con', 'first_surname' => 'Compra', 'second_surname' => null,
            'gmail' => 'concompra@gmail.com', 'password' => bcrypt('x'),
            'email_verified' => true, 'active' => true, 'provider' => 'local',
        ]);
        $sale = Sale::create([
            'invoice_number' => 'INV-REV-1', 'client_id' => $client->user_id,
            'subtotal' => 100, 'iva' => 13, 'discount' => 0, 'total' => 113,
            'payment_method' => 'cash', 'status' => 'completed', 'sale_date' => now(),
        ]);
        SaleItem::create([
            'sale_id' => $sale->sale_id, 'product_id' => $product->product_id,
            'name' => $product->name, 'quantity' => 1, 'unit_price' => 100, 'total' => 100,
        ]);
        $this->actingAs($client, 'clients');

        $this->postJson("/api/v1/products/{$product->public_id}/reviews", ['stars' => 5])
            ->assertOk()->assertJsonPath('success', true);

        $this->assertDatabaseHas('product_reviews', [
            'product_id' => $product->product_id, 'client_id' => $client->user_id, 'stars' => 5,
        ]);
    }
}
