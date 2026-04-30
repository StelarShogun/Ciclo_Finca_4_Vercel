<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\FavoriteProduct;
use App\Models\Product;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * CF4-75 B — Client favorites (API + persistence).
 *
 * Manual / exploratory cases (UI) not fully automated here:
 * - Guest favorite click redirects to login (handled in JS / catalogFavoriteConfig).
 * - Catalog blade shows heart state without reload (handled in clients-page.js + events).
 * - Empty state copy in drawer (header Blade + clients-users.js).
 *
 * | ID | Caso de prueba | Tipo | Automatizado en |
 * |----|----------------|------|-----------------|
 * | F-01 | Invitado no puede listar favoritos (GET /favorites) | Seguridad | test_guest_cannot_access_favorites_index |
 * | F-02 | Invitado no puede alternar favorito (POST /favorites/toggle) | Seguridad | test_guest_cannot_toggle_favorite |
 * | F-03 | Cliente autenticado obtiene lista vacía con success | API | test_authenticated_favorites_index_returns_empty_array |
 * | F-04 | Cliente agrega favorito y aparece en la lista | Funcional | test_authenticated_toggle_add_then_list_contains_product |
 * | F-05 | Cliente quita favorito y desaparece de la lista | Funcional | test_authenticated_toggle_remove_then_list_excludes_product |
 * | F-06 | Ciclo completo add → list → remove → list (sin RefreshDatabase) | Integración | test_favorites_full_toggle_cycle_without_database_reset |
 * | F-07 | No duplicados: la BD rechaza segunda fila mismo usuario/producto | Regla negocio | test_database_rejects_duplicate_favorite_row |
 * | F-08 | product_id inválido rechazado (validación) | Validación | test_toggle_rejects_invalid_product_id |
 * | F-09 | Varios productos: ambos aparecen en la lista tras toggle | Funcional | test_two_products_both_listed_in_favorites |
 */
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

    private function createIsolatedClient(): Client
    {
        return Client::create([
            'name' => 'Favorite',
            'first_surname' => 'Tester',
            'second_surname' => null,
            'gmail' => 'favorite-list-'.uniqid('', true).'@example.com',
            'password' => bcrypt('password'),
            'provider' => 'local',
        ]);
    }

    private function createIsolatedProduct(string $name = 'Producto Favorito Test'): Product
    {
        return Product::create([
            'category_id' => null,
            'supplier_id' => null,
            'name' => $name,
            'description' => 'Producto para probar favoritos',
            'image' => 'default.png',
            'sale_price' => 1000,
            'purchase_price' => 1000,
            'stock_current' => 10,
            'stock_minimum' => 1,
            'status' => 'active',
        ]);
    }

    private function cleanupFavoriteClientAndProduct(Client $client, Product $product): void
    {
        FavoriteProduct::query()
            ->where('user_id', $client->user_id)
            ->where('product_id', $product->product_id)
            ->delete();

        $product->delete();
        $client->delete();
    }

    public function test_guest_cannot_access_favorites_index(): void
    {
        $this->getJson(route('clients.favorites.index'))->assertUnauthorized();
    }

    public function test_guest_cannot_toggle_favorite(): void
    {
        $product = $this->createIsolatedProduct('Guest toggle guard');

        try {
            $this->postJson(route('clients.favorites.toggle'), [
                'product_id' => $product->product_id,
            ])->assertUnauthorized();
        } finally {
            $product->delete();
        }
    }

    public function test_authenticated_favorites_index_returns_empty_array(): void
    {
        $client = $this->createIsolatedClient();

        try {
            $this->actingAs($client, 'clients');

            $response = $this->getJson(route('clients.favorites.index'));
            $response->assertOk();
            $this->assertTrue($response->json('success'));
            $this->assertSame([], $response->json('favorites'));
        } finally {
            $client->delete();
        }
    }

    public function test_authenticated_toggle_add_then_list_contains_product(): void
    {
        $client = $this->createIsolatedClient();
        $product = $this->createIsolatedProduct();

        try {
            $this->actingAs($client, 'clients');

            $this->postJson(route('clients.favorites.toggle'), [
                'product_id' => $product->product_id,
            ])->assertOk()->assertJson(['success' => true, 'is_favorite' => true]);

            $list = $this->getJson(route('clients.favorites.index'));
            $list->assertOk();
            $rows = array_filter($list->json('favorites', []), fn (array $row) => (int) $row['product_id'] === (int) $product->product_id);
            $this->assertCount(1, $rows);
            $first = array_values($rows)[0];
            $this->assertSame($product->name, $first['name']);
        } finally {
            $this->cleanupFavoriteClientAndProduct($client, $product);
        }
    }

    public function test_authenticated_toggle_remove_then_list_excludes_product(): void
    {
        $client = $this->createIsolatedClient();
        $product = $this->createIsolatedProduct();

        try {
            $this->actingAs($client, 'clients');

            $this->postJson(route('clients.favorites.toggle'), [
                'product_id' => $product->product_id,
            ])->assertOk();

            $this->postJson(route('clients.favorites.toggle'), [
                'product_id' => $product->product_id,
            ])->assertOk()->assertJson(['success' => true, 'is_favorite' => false]);

            $list = $this->getJson(route('clients.favorites.index'));
            $list->assertOk();
            $this->assertEmpty(
                array_filter($list->json('favorites', []), fn (array $row) => (int) $row['product_id'] === (int) $product->product_id)
            );
        } finally {
            $this->cleanupFavoriteClientAndProduct($client, $product);
        }
    }

    public function test_favorites_full_toggle_cycle_without_database_reset(): void
    {
        $client = $this->createIsolatedClient();
        $product = $this->createIsolatedProduct();

        try {
            $this->actingAs($client, 'clients');

            $initialList = $this->getJson(route('clients.favorites.index'));
            $initialList->assertOk();
            $this->assertTrue($initialList->json('success'));

            $this->postJson(route('clients.favorites.toggle'), [
                'product_id' => $product->product_id,
            ])->assertOk()->assertJson(['is_favorite' => true]);

            $listAfterOn = $this->getJson(route('clients.favorites.index'));
            $listAfterOn->assertOk();
            $this->assertNotEmpty(
                array_filter($listAfterOn->json('favorites', []), fn (array $row) => (int) $row['product_id'] === (int) $product->product_id)
            );

            $this->postJson(route('clients.favorites.toggle'), [
                'product_id' => $product->product_id,
            ])->assertOk()->assertJson(['is_favorite' => false]);

            $listAfterOff = $this->getJson(route('clients.favorites.index'));
            $listAfterOff->assertOk();
            $this->assertEmpty(
                array_filter($listAfterOff->json('favorites', []), fn (array $row) => (int) $row['product_id'] === (int) $product->product_id)
            );
        } finally {
            $this->cleanupFavoriteClientAndProduct($client, $product);
        }
    }

    public function test_database_rejects_duplicate_favorite_row(): void
    {
        $client = $this->createIsolatedClient();
        $product = $this->createIsolatedProduct();

        try {
            $this->actingAs($client, 'clients');

            $this->postJson(route('clients.favorites.toggle'), [
                'product_id' => $product->product_id,
            ])->assertOk()->assertJson(['is_favorite' => true]);

            $this->assertSame(1, FavoriteProduct::query()
                ->where('user_id', $client->user_id)
                ->where('product_id', $product->product_id)
                ->count());

            $this->expectException(\Illuminate\Database\QueryException::class);

            FavoriteProduct::create([
                'user_id' => $client->user_id,
                'product_id' => $product->product_id,
            ]);
        } finally {
            $this->cleanupFavoriteClientAndProduct($client, $product);
        }
    }

    public function test_toggle_rejects_invalid_product_id(): void
    {
        $client = $this->createIsolatedClient();

        try {
            $this->actingAs($client, 'clients');

            $this->postJson(route('clients.favorites.toggle'), [
                'product_id' => 999999999,
            ])->assertUnprocessable();
        } finally {
            $client->delete();
        }
    }

    public function test_two_products_both_listed_in_favorites(): void
    {
        $client = $this->createIsolatedClient();
        $productA = $this->createIsolatedProduct('Fav A');
        $productB = $this->createIsolatedProduct('Fav B');

        try {
            $this->actingAs($client, 'clients');

            $this->postJson(route('clients.favorites.toggle'), ['product_id' => $productA->product_id])->assertOk();
            $this->postJson(route('clients.favorites.toggle'), ['product_id' => $productB->product_id])->assertOk();

            $list = $this->getJson(route('clients.favorites.index'));
            $list->assertOk();
            $ids = collect($list->json('favorites', []))->pluck('product_id')->map(fn ($id) => (int) $id)->all();

            $this->assertContains((int) $productA->product_id, $ids);
            $this->assertContains((int) $productB->product_id, $ids);
            $this->assertGreaterThanOrEqual(2, count($ids));
        } finally {
            FavoriteProduct::query()
                ->where('user_id', $client->user_id)
                ->whereIn('product_id', [$productA->product_id, $productB->product_id])
                ->delete();
            $productA->delete();
            $productB->delete();
            $client->delete();
        }
    }
}
