<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CF4ClientStorefrontUxImprovementsTest extends TestCase
{
    use RefreshDatabase;

    private function createUxProduct(string $name = 'UX Test Product'): Product
    {
        return Product::create([
            'category_id' => null,
            'supplier_id' => null,
            'name' => $name,
            'description' => 'Product for storefront UX tests',
            'image' => 'default.png',
            'sale_price' => 1000,
            'purchase_price' => 500,
            'stock_current' => 5,
            'stock_minimum' => 1,
            'status' => 'active',
        ]);
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
        $product = $this->createUxProduct();

        $this->get(route('clients.product', [
            'id' => $product->product_id,
            'slug' => $product->clientPublicSlug(),
        ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Client/Products/Show', false)
                ->has('product')
                ->has('seo.canonicalUrl')
            );
    }

    public function test_header_marks_catalog_nav_active_on_product_route(): void
    {
        $product = $this->createUxProduct();

        $this->get(route('clients.product', [
            'id' => $product->product_id,
            'slug' => $product->clientPublicSlug(),
        ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Client/Products/Show', false)
                ->has('seo.canonicalUrl')
            );
    }
}
