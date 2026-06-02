<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Client;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ClientCartImagePlaceholderTest extends TestCase
{
    use RefreshDatabase;

    public function test_cart_shows_category_icon_placeholder_for_product_without_image(): void
    {
        $client = Client::create([
            'name' => 'Cart',
            'first_surname' => 'Placeholder',
            'second_surname' => null,
            'gmail' => 'cart-placeholder@example.com',
            'password' => bcrypt('password'),
            'provider' => 'local',
        ]);
        $root = Category::create([
            'name' => 'Bicicletas',
            'parent_category_id' => null,
        ]);
        $sub = Category::create([
            'name' => 'MTB',
            'parent_category_id' => $root->category_id,
        ]);
        $product = Product::create([
            'category_id' => $sub->category_id,
            'supplier_id' => null,
            'name' => 'Bike Cart Sin Foto',
            'description' => 'Test',
            'image' => 'default.png',
            'sale_price' => 100000,
            'purchase_price' => 50000,
            'stock_current' => 5,
            'stock_minimum' => 1,
            'status' => 'active',
        ]);

        $this->actingAs($client, 'clients')
            ->postJson(route('clients.cart.add'), [
                'product_id' => $product->product_id,
                'quantity' => 1,
            ])
            ->assertOk();

        $this->actingAs($client, 'clients')
            ->get(route('clients.cart'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Client/Cart/Index', false)
                ->has('items', 1)
                ->where('items.0.image.usesPlaceholder', true)
                ->where('items.0.image.placeholderIconClass', fn (string $icon) => str_contains($icon, 'fa-bicycle'))
                ->where('items.0.image.fallback', fn (string $fallback) => str_contains($fallback, 'default.png'))
            );
    }
}
