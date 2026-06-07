<?php

namespace Tests\Feature\Api;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Automated API checks for Seguimiento 8 (alternative to manual Postman runs).
 * These run in CI via PHPUnit; use Newman + postman/ for the same collection in CLI.
 */
class StorefrontApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_health_endpoint_returns_ok(): void
    {
        $this->get('/up')
            ->assertOk();
    }

    public function test_product_suggestions_requires_at_least_two_characters(): void
    {
        $this->getJson('/api/products/suggestions?search=a')
            ->assertOk()
            ->assertJson([
                'suggestions' => [],
            ]);
    }

    public function test_product_suggestions_returns_matching_product(): void
    {
        Product::create([
            'category_id' => null,
            'supplier_id' => null,
            'name' => 'API Test Bicicleta',
            'description' => null,
            'image' => 'default.png',
            'sale_price' => 120000,
            'purchase_price' => 40000,
            'stock_current' => 4,
            'stock_minimum' => 1,
            'status' => 'active',
            'is_featured' => false,
        ]);

        $response = $this->getJson('/api/products/suggestions?search=Bicicleta');

        $response->assertOk()
            ->assertJsonPath('suggestions.0.name', 'API Test Bicicleta');
    }

    public function test_catalog_heartbeat_returns_json_payload(): void
    {
        $this->getJson('/api/catalog/heartbeat')
            ->assertOk()
            ->assertJsonStructure([
                'version',
            ]);
    }

    public function test_search_trending_returns_period_metadata(): void
    {
        $this->getJson('/api/catalog/search-trending?period=30d&limit=5')
            ->assertOk()
            ->assertJsonStructure([
                'period' => ['key', 'label_es', 'window_start'],
                'limit',
                'products',
                'terms',
            ]);
    }
}
