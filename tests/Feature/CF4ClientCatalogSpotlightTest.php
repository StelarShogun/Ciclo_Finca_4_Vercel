<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CF4ClientCatalogSpotlightTest extends TestCase
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

            if (! Schema::hasTable('products')) {
                $this->markTestSkipped('Tabla products no existe.');
            }
        } catch (\Throwable $e) {
            $this->markTestSkipped('Base de datos no disponible para tests: '.$e->getMessage());
        }
    }

    public function test_catalog_shows_spotlight_section_with_featured_product(): void
    {
        $product = Product::create([
            'category_id' => null,
            'supplier_id' => null,
            'name' => 'Producto Destacado CF4-29',
            'description' => null,
            'image' => 'default.png',
            'sale_price' => 1500,
            'purchase_price' => 100,
            'stock_current' => 5,
            'stock_minimum' => 1,
            'status' => 'active',
            'is_featured' => true,
        ]);

        $detailUrl = $product->clientProductUrl();

        $this->get(route('clients.catalog'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Client/Catalog/Index', false)
                ->where('catalogSpotlight.0.kind', 'featured')
                ->where('catalogSpotlight.0.product.name', 'Producto Destacado CF4-29')
                ->where('catalogSpotlight.0.product.isFeatured', true)
                ->where('catalogSpotlight.0.product.priceFormatted', '₡1.500')
                ->where('catalogSpotlight.0.product.url', $detailUrl)
            );
    }

    public function test_catalog_spotlight_shows_novelty_badge_for_recent_non_featured(): void
    {
        Product::create([
            'category_id' => null,
            'supplier_id' => null,
            'name' => 'Solo novedad CF4-29',
            'description' => null,
            'image' => 'default.png',
            'sale_price' => 800,
            'purchase_price' => 50,
            'stock_current' => 3,
            'stock_minimum' => 1,
            'status' => 'active',
            'is_featured' => false,
        ]);

        $this->get(route('clients.catalog'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Client/Catalog/Index', false)
                ->where('catalogSpotlight.0.kind', 'novelty')
                ->where('catalogSpotlight.0.product.name', 'Solo novedad CF4-29')
                ->where('catalogSpotlight.0.product.isFeatured', false)
                ->where('catalogSpotlight.0.product.isNew', true)
            );
    }
}
