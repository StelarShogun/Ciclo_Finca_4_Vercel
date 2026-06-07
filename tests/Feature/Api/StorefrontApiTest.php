<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Public storefront API endpoints — runs in CI with PHPUnit (Seguimiento 8).
 */
class StorefrontApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_health_endpoint_returns_ok(): void
    {
        $this->get('/up')->assertOk();
    }

    public function test_product_suggestions_returns_empty_for_short_search(): void
    {
        $response = $this->getJson('/api/products/suggestions?search=a');

        $response->assertOk();
        $response->assertJson([
            'suggestions' => [],
        ]);
    }

    public function test_catalog_heartbeat_returns_version_key(): void
    {
        $response = $this->getJson(route('api.catalog.heartbeat'));

        $response->assertOk();
        $response->assertJsonStructure(['version']);
        $this->assertIsString($response->json('version'));
    }

    public function test_search_trending_returns_expected_json_shape(): void
    {
        $response = $this->getJson(route('api.catalog.search-trending', ['limit' => 5]));

        $response->assertOk();
        $response->assertJsonStructure([
            'period' => ['key', 'label_es'],
            'products',
            'terms',
        ]);
    }
}
