<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\FavoriteProduct;
use App\Models\Product;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class FavoriteListTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        try {
            foreach (['client_table', 'products', 'favorite_products'] as $table) {
                if (! Schema::hasTable($table)) {
                    $this->markTestSkipped('Tabla requerida no existe: '.$table);
                }
            }
        } catch (\Throwable $e) {
            $this->markTestSkipped('Base de datos no disponible para tests: '.$e->getMessage());
        }
    }

    public function test_favorites_list_and_toggle_work_without_resetting_database(): void
    {
        // Create isolated records for the test only (no database reset traits).
        $client = Client::create([
            'name' => 'Favorite',
            'first_surname' => 'Tester',
            'second_surname' => null,
            'gmail' => 'favorite-list-'.uniqid().'@example.com',
            'password' => bcrypt('password'),
            'provider' => 'local',
        ]);

        $product = Product::create([
            'category_id' => null,
            'supplier_id' => null,
            'name' => 'Producto Favorito Test',
            'description' => 'Producto para probar favoritos',
            'image' => 'default.png',
            'sale_price' => 1000,
            'purchase_price' => 1000,
            'stock_current' => 10,
            'stock_minimum' => 1,
            'status' => 'active',
        ]);

        $this->actingAs($client, 'clients');

        try {
            $initialList = $this->getJson(route('clients.favorites.index'));
            $initialList->assertStatus(200);
            $this->assertTrue($initialList->json('success'));

            $toggleOn = $this->postJson(route('clients.favorites.toggle'), [
                'product_id' => $product->product_id,
            ]);
            $toggleOn->assertStatus(200);
            $this->assertTrue($toggleOn->json('success'));
            $this->assertTrue($toggleOn->json('is_favorite'));

            $listAfterOn = $this->getJson(route('clients.favorites.index'));
            $listAfterOn->assertStatus(200);
            $this->assertTrue($listAfterOn->json('success'));
            $this->assertNotEmpty(
                array_filter($listAfterOn->json('favorites', []), fn (array $row) => (int) $row['product_id'] === (int) $product->product_id)
            );

            $toggleOff = $this->postJson(route('clients.favorites.toggle'), [
                'product_id' => $product->product_id,
            ]);
            $toggleOff->assertStatus(200);
            $this->assertTrue($toggleOff->json('success'));
            $this->assertFalse($toggleOff->json('is_favorite'));

            $listAfterOff = $this->getJson(route('clients.favorites.index'));
            $listAfterOff->assertStatus(200);
            $this->assertTrue($listAfterOff->json('success'));
            $this->assertEmpty(
                array_filter($listAfterOff->json('favorites', []), fn (array $row) => (int) $row['product_id'] === (int) $product->product_id)
            );
        } finally {
            // Explicit cleanup of created rows so the test never pollutes shared DBs.
            FavoriteProduct::query()
                ->where('user_id', $client->user_id)
                ->where('product_id', $product->product_id)
                ->delete();

            $product->delete();
            $client->delete();
        }
    }
}
