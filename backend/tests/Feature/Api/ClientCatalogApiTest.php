<?php

namespace Tests\Feature\Api;

use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * API v1 catálogo público: estructura del payload, búsqueda y filtro por
 * categoría. Reusa los Services del storefront (BuildCatalogPage).
 */
class ClientCatalogApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['sanctum.stateful' => ['localhost', 'localhost:3000', '127.0.0.1']]);
        $this->withHeader('Origin', 'http://localhost:3000');
    }

    private function product(array $overrides = []): Product
    {
        $cat = Category::firstOrCreate(['name' => 'Bicicletas Cat']);
        Supplier::firstOrCreate(['name' => 'Sup Cat']);

        return Product::factory()->create(array_merge([
            'category_id' => $cat->category_id,
            'status' => 'active',
            'stock_current' => 5,
            'sale_price' => 1000,
            'purchase_price' => 500,
        ], $overrides));
    }

    public function test_catalog_is_public(): void
    {
        $this->product(['name' => 'Bici Pública']);

        $this->getJson('/api/v1/catalog')
            ->assertOk()
            ->assertJsonStructure(['data' => ['products', 'pagination', 'categories', 'brands', 'filters', 'summary']]);
    }

    public function test_catalog_search_filters(): void
    {
        $this->product(['name' => 'Casco Rojo Catalogo']);
        $this->product(['name' => 'Bicicleta Verde Catalogo']);

        $res = $this->getJson('/api/v1/catalog?search=Casco')->assertOk();
        $names = collect($res->json('data.products'))->pluck('name');
        $this->assertTrue($names->contains('Casco Rojo Catalogo'));
        $this->assertFalse($names->contains('Bicicleta Verde Catalogo'));
    }

    public function test_heartbeat_returns_version(): void
    {
        $this->getJson('/api/v1/catalog/heartbeat')
            ->assertOk()
            ->assertJsonStructure(['version']);
    }
}
