<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CF4106CatalogSuggestionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        try {
            parent::setUp();

            $driver = Schema::getConnection()->getDriverName();
            if ($driver !== 'mysql') {
                $this->markTestSkipped('CF4-106 requiere MySQL para el esquema.');
            }

            foreach (['products', 'categories'] as $table) {
                if (! Schema::hasTable($table)) {
                    $this->markTestSkipped("Falta la tabla requerida ({$table}).");
                }
            }
        } catch (\Throwable $e) {
            $this->markTestSkipped('Base de datos no disponible para tests: '.$e->getMessage());
        }
    }

    public function test_search_shorter_than_2_chars_returns_empty_suggestions(): void
    {
        $resp = $this->get('/api/products/suggestions?search=a');
        $resp->assertOk();
        $resp->assertJson([
            'suggestions' => [],
        ]);
    }

    public function test_sku_derivation_resolves_product_id_for_bk_sku_and_numeric(): void
    {
        $cat = Category::create([
            'name' => 'CF4-106 Cat',
            'description' => null,
            'parent_category_id' => null,
        ]);

        $p = Product::create([
            'category_id' => $cat->category_id,
            'supplier_id' => null,
            'name' => 'Producto SKU Derivado',
            'description' => null,
            'image' => 'default.png',
            'sale_price' => 1000,
            'purchase_price' => 100,
            'stock_current' => 2,
            'stock_minimum' => 1,
            'status' => 'active',
            'is_featured' => false,
        ]);

        $id = (int) $p->product_id;
        $sku = Product::skuFromId($id);

        $respSku = $this->get('/api/products/suggestions?search='.urlencode($sku));
        $respSku->assertOk();
        $respSku->assertJsonPath('suggestions.0.id', $id);
        $respSku->assertJsonPath('suggestions.0.match_type', 'sku');

        $respNum = $this->get('/api/products/suggestions?search='.urlencode((string) $id));
        $respNum->assertOk();
        $respNum->assertJsonPath('suggestions.0.id', $id);
        $respNum->assertJsonPath('suggestions.0.match_type', 'sku');
    }

    public function test_name_match_is_ranked_before_category_match(): void
    {
        $catAlpha = Category::create([
            'name' => 'Alpha Category',
            'description' => null,
            'parent_category_id' => null,
        ]);

        $catOther = Category::create([
            'name' => 'Other Category',
            'description' => null,
            'parent_category_id' => null,
        ]);

        $pName = Product::create([
            'category_id' => $catOther->category_id,
            'supplier_id' => null,
            'name' => 'Alpha Bike',
            'description' => null,
            'image' => 'default.png',
            'sale_price' => 1000,
            'purchase_price' => 100,
            'stock_current' => 2,
            'stock_minimum' => 1,
            'status' => 'active',
            'is_featured' => false,
        ]);

        $pCategory = Product::create([
            'category_id' => $catAlpha->category_id,
            'supplier_id' => null,
            'name' => 'Bike Only',
            'description' => null,
            'image' => 'default.png',
            'sale_price' => 1100,
            'purchase_price' => 110,
            'stock_current' => 2,
            'stock_minimum' => 1,
            'status' => 'active',
            'is_featured' => false,
        ]);

        $resp = $this->get('/api/products/suggestions?search=Alpha');
        $resp->assertOk();

        $payload = $resp->json();
        $this->assertIsArray($payload);
        $this->assertIsArray($payload['suggestions'] ?? null);

        $suggestions = $payload['suggestions'];

        $ids = array_map(fn ($s) => (int) ($s['id'] ?? 0), $suggestions);
        $this->assertContains((int) $pName->product_id, $ids);
        $this->assertContains((int) $pCategory->product_id, $ids);

        $firstProduct = null;
        foreach ($suggestions as $s) {
            if (($s['type'] ?? null) === 'product') {
                $firstProduct = $s;
                break;
            }
        }

        $this->assertNotNull($firstProduct);
        $this->assertSame((int) $pName->product_id, (int) ($firstProduct['id'] ?? 0));
        $this->assertSame('name', (string) ($firstProduct['match_type'] ?? ''));
    }

    public function test_endpoint_only_returns_active_in_client_store_products(): void
    {
        $cat = Category::create([
            'name' => 'CF4-106 Active Filter',
            'description' => null,
            'parent_category_id' => null,
        ]);

        $active = Product::create([
            'category_id' => $cat->category_id,
            'supplier_id' => null,
            'name' => 'Activo CF4-106',
            'description' => null,
            'image' => 'default.png',
            'sale_price' => 1000,
            'purchase_price' => 100,
            'stock_current' => 2,
            'stock_minimum' => 1,
            'status' => 'active',
            'is_featured' => false,
        ]);

        $inactive = Product::create([
            'category_id' => $cat->category_id,
            'supplier_id' => null,
            'name' => 'Inactivo CF4-106',
            'description' => null,
            'image' => 'default.png',
            'sale_price' => 1000,
            'purchase_price' => 100,
            'stock_current' => 2,
            'stock_minimum' => 1,
            'status' => 'inactive',
            'is_featured' => false,
        ]);

        $resp = $this->get('/api/products/suggestions?search=CF4-106');
        $resp->assertOk();

        $ids = array_map(
            fn ($s) => (int) ($s['id'] ?? 0),
            (array) ($resp->json('suggestions') ?? [])
        );

        $this->assertContains((int) $active->product_id, $ids);
        $this->assertNotContains((int) $inactive->product_id, $ids);
    }
}
