<?php

namespace Tests\Feature\Api;

use App\Models\Category;
use App\Models\Product;
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

        $this->getJson("/api/v1/products/{$product->product_id}")
            ->assertOk()
            ->assertJsonStructure(['data' => ['product' => ['id', 'name', 'priceFormatted', 'carouselSlides', 'canBuy'], 'specs', 'reviews', 'relatedProducts', 'taxonomy']])
            ->assertJsonPath('data.product.id', (int) $product->product_id)
            ->assertJsonPath('data.product.name', 'Trek Marlin Test');
    }

    public function test_missing_product_returns_404(): void
    {
        $this->getJson('/api/v1/products/999999')->assertStatus(404);
    }
}
