<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Client;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

        $response = $this->actingAs($client, 'clients')->get(route('clients.cart'));
        $response->assertOk();
        $response->assertSee('product-media-placeholder--cart', false);
        $response->assertSee('fa-bicycle', false);
        $response->assertDontSee('default.png', false);
    }
}
