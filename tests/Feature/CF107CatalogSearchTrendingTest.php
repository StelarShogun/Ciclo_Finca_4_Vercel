<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/** CF4-107 — Catálogo: tendencias de búsqueda agregadas en dropdown público. */
class CF107CatalogSearchTrendingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        try {
            parent::setUp();
        } catch (\Throwable $e) {
            $this->markTestSkipped('Base de datos no disponible: '.$e->getMessage());
        }

        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            $this->markTestSkipped('CF107CatalogSearchTrendingTest requiere MySQL.');
        }

        foreach (['products', 'catalog_product_search_events', 'categories', 'suppliers'] as $table) {
            if (! Schema::hasTable($table)) {
                $this->markTestSkipped('Tabla requerida no existe: '.$table);
            }
        }

        Carbon::setTestNow(Carbon::parse('2026-06-20 12:00:00', 'UTC'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    /**
     * @return array{0: Category, 1: Supplier}
     */
    private function seedCategoryAndSupplier(): array
    {
        $category = Category::create([
            'name' => 'Cat CF107',
            'description' => null,
            'parent_category_id' => null,
        ]);

        $supplier = Supplier::create([
            'name' => 'Sup CF107',
            'primary_contact' => 'Contact',
            'phone' => '0000',
            'email' => 'sup-cf107@example.com',
            'address' => 'Addr',
            'delivery_time' => 1,
            'rating' => 5.0,
            'status' => 'active',
        ]);

        return [$category, $supplier];
    }

    public function test_search_trending_returns_products_and_terms_within_limit(): void
    {
        [$category, $supplier] = $this->seedCategoryAndSupplier();

        $hot = Product::create([
            'category_id' => $category->category_id,
            'supplier_id' => $supplier->supplier_id,
            'name' => 'CF107 Hot Product',
            'description' => 'D',
            'image' => 'default.png',
            'sale_price' => 100,
            'purchase_price' => 50,
            'stock_current' => 5,
            'stock_minimum' => 1,
            'status' => 'active',
        ]);

        $warm = Product::create([
            'category_id' => $category->category_id,
            'supplier_id' => $supplier->supplier_id,
            'name' => 'CF107 Warm Product',
            'description' => 'D',
            'image' => 'default.png',
            'sale_price' => 200,
            'purchase_price' => 80,
            'stock_current' => 5,
            'stock_minimum' => 1,
            'status' => 'active',
        ]);

        $inactive = Product::create([
            'category_id' => $category->category_id,
            'supplier_id' => $supplier->supplier_id,
            'name' => 'CF107 Inactive',
            'description' => 'D',
            'image' => 'default.png',
            'sale_price' => 300,
            'purchase_price' => 100,
            'stock_current' => 5,
            'stock_minimum' => 1,
            'status' => 'inactive',
        ]);

        $now = now();
        foreach (range(1, 5) as $_i) {
            DB::table('catalog_product_search_events')->insert([
                'product_id' => $hot->product_id,
                'query_normalized' => 'bicicleta',
                'created_at' => $now,
            ]);
        }
        foreach (range(1, 2) as $_i) {
            DB::table('catalog_product_search_events')->insert([
                'product_id' => $warm->product_id,
                'query_normalized' => 'tractor',
                'created_at' => $now,
            ]);
        }

        foreach (range(1, 4) as $_i) {
            DB::table('catalog_product_search_events')->insert([
                'product_id' => $inactive->product_id,
                'query_normalized' => 'z-inactivo',
                'created_at' => $now,
            ]);
        }

        $res = $this->getJson(route('api.catalog.search-trending', ['limit' => 2]));

        $res->assertOk();
        $res->assertJsonPath('period.key', '30d');

        $ids = collect($res->json('products'))->pluck('id')->all();
        $this->assertCount(2, $ids);
        $this->assertSame($hot->product_id, $ids[0]);
        $this->assertSame($warm->product_id, $ids[1]);
        $this->assertNotContains($inactive->product_id, $ids);

        $termLabels = collect($res->json('terms'))->pluck('name')->all();
        $this->assertContains('bicicleta', $termLabels);
        $this->assertContains('z-inactivo', $termLabels);
        $this->assertLessThanOrEqual(2, count($termLabels));

        foreach ($res->json('products') as $row) {
            $this->assertArrayHasKey('url', $row);
            $this->assertStringContainsString('/product/', $row['url']);
        }

        foreach ($res->json('terms') as $termRow) {
            $this->assertStringContainsString('search=', $termRow['url']);
        }
    }

    public function test_search_trending_fallback_when_no_telemetry_but_catalog_has_products(): void
    {
        [$category, $supplier] = $this->seedCategoryAndSupplier();

        Product::create([
            'category_id' => $category->category_id,
            'supplier_id' => $supplier->supplier_id,
            'name' => 'CF107 Solo destacado',
            'description' => 'D',
            'image' => 'default.png',
            'sale_price' => 10,
            'purchase_price' => 5,
            'stock_current' => 5,
            'stock_minimum' => 1,
            'status' => 'active',
        ]);

        $res = $this->getJson(route('api.catalog.search-trending'));

        $res->assertOk()
            ->assertJsonPath('is_fallback', true)
            ->assertJsonPath('terms', []);

        $this->assertGreaterThanOrEqual(1, count($res->json('products')));
        $this->assertSame('featured', $res->json('products.0.match_type'));
    }
}
