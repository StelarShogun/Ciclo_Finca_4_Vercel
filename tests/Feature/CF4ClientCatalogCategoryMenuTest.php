<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CF4ClientCatalogCategoryMenuTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        try {
            parent::setUp();

            $driver = Schema::getConnection()->getDriverName();
            if ($driver !== 'mysql') {
                $this->markTestSkipped('CF4 catálogo cliente requiere MySQL para el esquema.');
            }

            if (! Schema::hasTable('products') || ! Schema::hasTable('categories')) {
                $this->markTestSkipped('Tablas categories/products no existen.');
            }
        } catch (\Throwable $e) {
            $this->markTestSkipped('Base de datos no disponible para tests: '.$e->getMessage());
        }
    }

    public function test_catalog_includes_category_panel_markup(): void
    {
        $response = $this->get(route('clients.catalog'));
        $response->assertStatus(200);
        $response->assertSee('id="catalog-category-panel"', false);
        $response->assertSee('id="catalog-category-trigger"', false);
        $response->assertSee('id="catalog-category-sidebar-toggle"', false);
        $response->assertSee('Categorías', false);
    }

    public function test_catalog_redirects_when_price_filter_is_negative(): void
    {
        $response = $this->from(route('clients.catalog'))
            ->get(route('clients.catalog', ['min_price' => '-5']));
        $response->assertRedirect(route('clients.catalog'));
        $response->assertSessionHasErrors('price_range');
    }

    public function test_home_does_not_include_catalog_category_panel(): void
    {
        $response = $this->get(route('clients.home'));
        $response->assertStatus(200);
        $response->assertDontSee('id="catalog-category-panel"', false);
    }

    public function test_filter_by_parent_category_includes_child_products(): void
    {
        $parent = Category::create([
            'name' => 'CF4 Padre Menú',
            'description' => null,
            'parent_category_id' => null,
        ]);
        $child = Category::create([
            'name' => 'CF4 Hija Menú',
            'description' => null,
            'parent_category_id' => $parent->category_id,
        ]);
        Product::create([
            'category_id' => $child->category_id,
            'supplier_id' => null,
            'name' => 'Producto En Sub CF4',
            'description' => null,
            'image' => 'default.png',
            'sale_price' => 1000,
            'purchase_price' => 100,
            'stock_current' => 2,
            'stock_minimum' => 1,
            'status' => 'active',
            'is_featured' => false,
        ]);

        $response = $this->get(route('clients.catalog', ['category_id' => $parent->category_id]));
        $response->assertStatus(200);
        $response->assertSee('Producto En Sub CF4', false);
    }

    public function test_filter_by_child_category_shows_only_that_child(): void
    {
        $parent = Category::create([
            'name' => 'CF4 Padre 2',
            'description' => null,
            'parent_category_id' => null,
        ]);
        $childA = Category::create([
            'name' => 'CF4 Hija A',
            'description' => null,
            'parent_category_id' => $parent->category_id,
        ]);
        $childB = Category::create([
            'name' => 'CF4 Hija B',
            'description' => null,
            'parent_category_id' => $parent->category_id,
        ]);
        Product::create([
            'category_id' => $childA->category_id,
            'supplier_id' => null,
            'name' => 'Solo en A',
            'description' => null,
            'image' => 'default.png',
            'sale_price' => 500,
            'purchase_price' => 50,
            'stock_current' => 1,
            'stock_minimum' => 1,
            'status' => 'active',
            'is_featured' => false,
        ]);
        Product::create([
            'category_id' => $childB->category_id,
            'supplier_id' => null,
            'name' => 'Solo en B',
            'description' => null,
            'image' => 'default.png',
            'sale_price' => 600,
            'purchase_price' => 60,
            'stock_current' => 1,
            'stock_minimum' => 1,
            'status' => 'active',
            'is_featured' => false,
        ]);

        $rA = $this->get(route('clients.catalog', ['category_id' => $childA->category_id]));
        $rA->assertStatus(200);
        $rA->assertSee('Solo en A', false);
        // El listado paginado debe ser solo 1 producto (spotlight puede listar otros).
        $rA->assertSee('1 productos', false);
    }

    public function test_empty_category_shows_specific_message(): void
    {
        $emptyParent = Category::create([
            'name' => 'CF4 Vacía',
            'description' => null,
            'parent_category_id' => null,
        ]);

        $response = $this->get(route('clients.catalog', ['category_id' => $emptyParent->category_id]));
        $response->assertStatus(200);
        $response->assertSee('No hay productos en esta categoría', false);
    }
}
