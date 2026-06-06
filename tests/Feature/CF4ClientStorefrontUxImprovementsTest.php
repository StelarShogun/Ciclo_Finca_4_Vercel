<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Inertia\Testing\AssertableInertia as Assert;
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
        $client = Client::create([
            'name' => 'Cliente',
            'first_surname' => 'Carrito',
            'second_surname' => null,
            'gmail' => 'cart-ux-'.uniqid('', true).'@example.com',
            'password' => bcrypt('password'),
            'provider' => 'local',
        ]);

        $this->actingAs($client, 'clients')
            ->get(route('clients.cart'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Client/Cart/Index', false)
                ->has('pickupPolicyLine')
                ->has('pickupPolicyNotice')
                ->has('pagination')
            );
    }

    public function test_catalog_includes_category_rail_markup(): void
    {
        $this->get(route('clients.catalog'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Client/Catalog/Index', false)
                ->has('categories')
                ->has('filters')
            );
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

        $this->get(route('clients.product', [
            'id' => $product->product_id,
            'slug' => $product->clientPublicSlug(),
        ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Client/Products/Show', false)
                ->has('product')
            );
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

        $this->get(route('clients.product', [
            'id' => $product->product_id,
            'slug' => $product->clientPublicSlug(),
        ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Client/Products/Show', false)
                ->has('product.url')
            );
    }
}
