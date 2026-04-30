<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ProductReviewByPurchaseTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        try {
            parent::setUp();

            $driver = Schema::getConnection()->getDriverName();
            if ($driver !== 'mysql') {
                $this->markTestSkipped('Reseñas de producto requieren MySQL para el esquema actual.');
            }

            foreach (['client_table', 'products', 'sales', 'sale_items', 'product_reviews'] as $table) {
                if (! Schema::hasTable($table)) {
                    $this->markTestSkipped('Tabla requerida no existe: '.$table);
                }
            }
        } catch (\Throwable $e) {
            $this->markTestSkipped('Base de datos no disponible para tests: '.$e->getMessage());
        }
    }

    public function test_client_with_completed_purchase_can_create_and_update_single_review(): void
    {
        [$client, $product] = $this->seedClientAndProduct();
        $this->createSaleWithItem($client, $product, 'completed');

        $this->actingAs($client, 'clients');

        $this->post(route('clients.products.review.store', ['product' => $product->product_id]), [
            'stars' => 5,
        ])->assertRedirect();

        $this->assertDatabaseHas('product_reviews', [
            'client_id' => $client->user_id,
            'product_id' => $product->product_id,
            'stars' => 5,
        ]);

        $this->post(route('clients.products.review.store', ['product' => $product->product_id]), [
            'stars' => 3,
        ])->assertRedirect();

        $this->assertDatabaseCount('product_reviews', 1);
        $this->assertDatabaseHas('product_reviews', [
            'client_id' => $client->user_id,
            'product_id' => $product->product_id,
            'stars' => 3,
        ]);
    }

    public function test_client_without_completed_purchase_cannot_review_product(): void
    {
        [$client, $product] = $this->seedClientAndProduct();
        $this->createSaleWithItem($client, $product, 'pending');

        $this->actingAs($client, 'clients');

        $this->postJson(route('clients.products.review.store', ['product' => $product->product_id]), [
            'stars' => 4,
        ])->assertStatus(403);

        $this->assertDatabaseCount('product_reviews', 0);
    }

    public function test_review_validation_rejects_stars_out_of_range(): void
    {
        [$client, $product] = $this->seedClientAndProduct();
        $this->createSaleWithItem($client, $product, 'completed');

        $this->actingAs($client, 'clients');

        $this->postJson(route('clients.products.review.store', ['product' => $product->product_id]), [
            'stars' => 6,
        ])->assertStatus(422)->assertJsonValidationErrors(['stars']);
    }

    private function seedClientAndProduct(): array
    {
        $client = Client::create([
            'name' => 'Cliente',
            'first_surname' => 'Resena',
            'second_surname' => null,
            'gmail' => 'cliente-review@example.com',
            'password' => bcrypt('password'),
            'provider' => 'local',
        ]);

        $product = Product::create([
            'category_id' => null,
            'supplier_id' => null,
            'name' => 'Producto reseña',
            'description' => null,
            'image' => 'default.png',
            'sale_price' => 120,
            'purchase_price' => 50,
            'stock_current' => 10,
            'stock_minimum' => 1,
            'status' => 'active',
        ]);

        return [$client, $product];
    }

    private function createSaleWithItem(Client $client, Product $product, string $status): void
    {
        $sale = Sale::create([
            'invoice_number' => 'CF4-9999',
            'client_id' => $client->user_id,
            'sale_date' => now(),
            'payment_method' => 'cash',
            'status' => $status,
            'order_source' => 'web_cart',
            'subtotal' => 120,
            'iva' => 0,
            'discount' => 0,
            'total' => 120,
            'notes' => null,
        ]);

        SaleItem::create([
            'sale_id' => $sale->sale_id,
            'product_id' => $product->product_id,
            'quantity' => 1,
            'unit_price' => 120,
            'unit_discount' => 0,
            'total' => 120,
        ]);
    }
}
