<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CF4ClientStorefrontUxImprovementsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        try {
            parent::setUp();

            $driver = Schema::getConnection()->getDriverName();
            if ($driver !== 'mysql') {
                $this->markTestSkipped('Storefront UX tests require MySQL schema.');
            }
        } catch (\Throwable $e) {
            $this->markTestSkipped('Database unavailable: '.$e->getMessage());
        }
    }

    public function test_cart_includes_checkout_progress_markup(): void
    {
        $response = $this->get(route('clients.cart'));

        $response->assertOk();
        $response->assertSee('cf4-checkout-progress', false);
        $response->assertSee('Carrito', false);
        $response->assertSee('Confirmación', false);
    }

    public function test_catalog_includes_category_rail_markup(): void
    {
        $response = $this->get(route('clients.catalog'));

        $response->assertOk();
        $response->assertSee('category-rail', false);
        $response->assertSee('catalog-shell', false);
    }

    public function test_product_page_includes_mobile_back_nav(): void
    {
        if (! Schema::hasTable('products')) {
            $this->markTestSkipped('Products table missing.');
        }

        $product = Product::query()->first();
        if ($product === null) {
            $this->markTestSkipped('No products seeded for product page UX test.');
        }

        $response = $this->get(route('clients.product', [
            'id' => $product->product_id,
            'slug' => $product->clientPublicSlug(),
        ]));

        $response->assertOk();
        $response->assertSee('cf4-mobile-back-nav', false);
        $response->assertSee('product-detail-card', false);
    }

    public function test_header_marks_catalog_nav_active_on_product_route(): void
    {
        if (! Schema::hasTable('products')) {
            $this->markTestSkipped('Products table missing.');
        }

        $product = Product::query()->first();
        if ($product === null) {
            $this->markTestSkipped('No products seeded for header nav test.');
        }

        $response = $this->get(route('clients.product', [
            'id' => $product->product_id,
            'slug' => $product->clientPublicSlug(),
        ]));

        $response->assertOk();
        $response->assertSee('nav-link active', false);
    }
}
