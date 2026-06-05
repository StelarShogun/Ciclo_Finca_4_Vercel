<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Client;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ClientInvoiceDetailImagePlaceholderTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoice_detail_shows_category_placeholder_for_line_without_image(): void
    {
        $client = Client::create([
            'name' => 'Invoice',
            'first_surname' => 'Placeholder',
            'second_surname' => null,
            'gmail' => 'invoice-placeholder@example.com',
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
            'name' => 'Bike Invoice Sin Foto',
            'description' => 'Test',
            'image' => 'default.png',
            'sale_price' => 1500,
            'purchase_price' => 1000,
            'stock_current' => 5,
            'stock_minimum' => 1,
            'status' => 'active',
        ]);
        $sale = Sale::create([
            'invoice_number' => 'CF4-PL'.substr(uniqid('', true), -5),
            'client_id' => $client->user_id,
            'seller_admin_id' => null,
            'sale_date' => now(),
            'payment_method' => 'cash',
            'status' => 'pending',
            'subtotal' => 1500,
            'iva' => 0,
            'discount' => 0,
            'total' => 1500,
            'order_source' => 'web_cart',
        ]);
        SaleItem::create([
            'sale_id' => $sale->sale_id,
            'product_id' => $product->product_id,
            'quantity' => 1,
            'unit_price' => 1500,
            'unit_discount' => 0,
            'total' => 1500,
        ]);

        $this->actingAs($client, 'clients')
            ->get(route('clients.invoices.show', $sale))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Client/Invoices/Show', false)
                ->has('items', 1)
                ->where('items.0.name', 'Bike Invoice Sin Foto')
                ->where('items.0.image.usesPlaceholder', true)
                ->where('items.0.image.placeholderIconClass', 'fas fa-bicycle')
            );
    }
}
