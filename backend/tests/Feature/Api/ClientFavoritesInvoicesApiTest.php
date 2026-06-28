<?php

namespace Tests\Feature\Api;

use App\Models\Category;
use App\Models\Client;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Supplier;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * API v1 favoritos (toggle idempotente, lista) y facturas (lista + detalle con
 * pertenencia: un cliente nunca ve factura ajena).
 */
class ClientFavoritesInvoicesApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['sanctum.stateful' => ['localhost', 'localhost:3000', '127.0.0.1']]);
        $this->withHeader('Origin', 'http://localhost:3000');
    }

    private function client(string $email): Client
    {
        return Client::create([
            'name' => 'Cli', 'first_surname' => 'Test', 'second_surname' => null,
            'gmail' => $email, 'password' => bcrypt('password123'),
            'email_verified' => true, 'active' => true, 'provider' => 'local',
        ]);
    }

    private function product(): Product
    {
        $cat = Category::firstOrCreate(['name' => 'Bicis FavInv']);
        Supplier::firstOrCreate(['name' => 'Sup FavInv']);

        return Product::factory()->create([
            'category_id' => $cat->category_id, 'status' => 'active',
            'stock_current' => 5, 'sale_price' => 1000, 'purchase_price' => 500,
        ]);
    }

    private function completedSale(Client $client): Sale
    {
        return Sale::create([
            'invoice_number' => 'INV-FAVINV-'.$client->user_id,
            'client_id' => $client->user_id,
            'subtotal' => 100, 'iva' => 13, 'discount' => 0, 'total' => 113,
            'payment_method' => 'cash', 'status' => 'completed', 'sale_date' => Carbon::now(),
        ]);
    }

    public function test_favorites_require_auth(): void
    {
        $this->getJson('/api/v1/favorites')->assertStatus(401);
    }

    public function test_toggle_and_list_favorites(): void
    {
        $client = $this->client('fav@gmail.com');
        $product = $this->product();
        $this->actingAs($client, 'clients');

        $this->postJson('/api/v1/favorites/toggle', ['product_id' => $product->product_id])->assertOk();
        $this->assertDatabaseHas('favorite_products', ['user_id' => $client->user_id, 'product_id' => $product->product_id]);

        $this->getJson('/api/v1/favorites')
            ->assertOk()
            ->assertJsonStructure(['data' => ['favorites', 'pagination']]);

        // Toggle de nuevo quita (idempotente por user+product).
        $this->postJson('/api/v1/favorites/toggle', ['product_id' => $product->product_id])->assertOk();
        $this->assertDatabaseMissing('favorite_products', ['user_id' => $client->user_id, 'product_id' => $product->product_id]);
    }

    public function test_invoices_index_and_show(): void
    {
        $client = $this->client('inv@gmail.com');
        $sale = $this->completedSale($client);
        $this->actingAs($client, 'clients');

        $this->getJson('/api/v1/invoices')
            ->assertOk()
            ->assertJsonStructure(['data' => ['orders', 'pagination', 'tab']]);

        $this->getJson("/api/v1/invoices/{$sale->sale_id}")
            ->assertOk()
            ->assertJsonStructure(['data' => ['items', 'totals', 'invoiceNumber']]);
    }

    public function test_cannot_view_other_clients_invoice(): void
    {
        $owner = $this->client('owner@gmail.com');
        $sale = $this->completedSale($owner);

        $intruder = $this->client('intruder@gmail.com');
        $this->actingAs($intruder, 'clients');

        $this->getJson("/api/v1/invoices/{$sale->sale_id}")->assertStatus(404);
    }
}
