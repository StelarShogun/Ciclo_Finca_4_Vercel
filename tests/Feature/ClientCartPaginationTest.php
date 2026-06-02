<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * CF4-138 — Client cart list pagination (session-backed display slice).
 */
class ClientCartPaginationTest extends TestCase
{
    use RefreshDatabase;

    private function createClient(): Client
    {
        return Client::create([
            'name' => 'Cart',
            'first_surname' => 'Pager',
            'second_surname' => null,
            'gmail' => 'cart-pager-'.uniqid('', true).'@example.com',
            'password' => bcrypt('password'),
            'provider' => 'local',
        ]);
    }

    private function createProduct(string $name): Product
    {
        return Product::create([
            'category_id' => null,
            'supplier_id' => null,
            'name' => $name,
            'description' => null,
            'image' => 'default.png',
            'sale_price' => 100,
            'purchase_price' => 50,
            'stock_current' => 10,
            'stock_minimum' => 1,
            'status' => 'active',
        ]);
    }

    public function test_cart_page_paginates_line_items_and_keeps_full_total(): void
    {
        $client = $this->createClient();
        $this->actingAs($client, 'clients');

        for ($i = 0; $i < 12; $i++) {
            $product = $this->createProduct('Cart Item '.$i);
            $this->postJson(route('clients.cart.add'), [
                'product_id' => $product->product_id,
                'quantity' => 1,
            ])->assertOk();
        }

        $this->get(route('clients.cart', ['per_page' => 10]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Client/Cart/Index', false)
                ->where('pagination.currentPage', 1)
                ->where('pagination.perPage', 10)
                ->where('pagination.total', 12)
                ->where('totalFormatted', '₡1.200')
                ->has('items', 10)
            );

        $this->get(route('clients.cart', ['per_page' => 10, 'page' => 2]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Client/Cart/Index', false)
                ->where('pagination.currentPage', 2)
                ->where('pagination.total', 12)
                ->where('totalFormatted', '₡1.200')
                ->has('items', 2)
            );
    }
}
