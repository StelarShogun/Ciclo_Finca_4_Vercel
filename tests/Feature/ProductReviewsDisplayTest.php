<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ProductReviewsDisplayTest extends TestCase
{
    private string $runSuffix;

    protected function setUp(): void
    {
        try {
            parent::setUp();
            $this->runSuffix = (string) now()->format('YmdHisv').'-'.bin2hex(random_bytes(3));

            if (Schema::getConnection()->getDriverName() !== 'mysql') {
                $this->markTestSkipped('ProductReviewsDisplayTest requires MySQL for the current schema.');
            }

            foreach (['client_table', 'products', 'sales', 'sale_items', 'product_reviews'] as $table) {
                if (! Schema::hasTable($table)) {
                    $this->markTestSkipped('Required table missing: '.$table);
                }
            }
        } catch (\Throwable $e) {
            $this->markTestSkipped('Database not available: '.$e->getMessage());
        }
    }

    public function test_guest_sees_no_valoraciones_message_when_product_has_no_public_reviews(): void
    {
        $product = $this->seedProduct('Producto Sin Reseñas Públicas');

        $url = route('clients.product', [
            'id' => $product->product_id,
            'slug' => $product->clientPublicSlug(),
        ]);

        $response = $this->get($url);
        $response->assertOk();
        $response->assertSee('Aún no hay valoraciones disponibles', false);
    }

    public function test_guest_sees_average_distribution_and_reviews_without_login(): void
    {
        $product = $this->seedProduct('Producto Con Reseñas Visibles');
        $clientA = $this->seedClient('guest-rev-a@example.com');
        $clientB = $this->seedClient('guest-rev-b@example.com');
        $this->createCompletedSaleWithItem($clientA, $product);
        $this->createCompletedSaleWithItem($clientB, $product);

        ProductReview::query()->updateOrCreate(
            ['client_id' => $clientA->user_id, 'product_id' => $product->product_id],
            ['stars' => 4]
        );
        ProductReview::query()->updateOrCreate(
            ['client_id' => $clientB->user_id, 'product_id' => $product->product_id],
            ['stars' => 2]
        );

        $url = route('clients.product', [
            'id' => $product->product_id,
            'slug' => $product->clientPublicSlug(),
        ]);

        $response = $this->get($url);
        $response->assertOk();
        $response->assertSee('3.0', false);
        $response->assertSee('2 valoraciones con reseña', false);
        $response->assertSee('product-star-distribution', false);
        $response->assertSee('Compra verificada', false);
    }

    public function test_reviews_sort_stars_high_lists_higher_rating_first(): void
    {
        $product = $this->seedProduct('Producto Orden Estrellas');
        $clientLow = $this->seedClient('sort-low@example.com', 'ZZZLowStars');
        $clientHigh = $this->seedClient('sort-high@example.com', 'AAAHighStars');
        $this->createCompletedSaleWithItem($clientLow, $product);
        $this->createCompletedSaleWithItem($clientHigh, $product);

        ProductReview::query()->updateOrCreate(
            ['client_id' => $clientLow->user_id, 'product_id' => $product->product_id],
            ['stars' => 2]
        );
        ProductReview::query()->updateOrCreate(
            ['client_id' => $clientHigh->user_id, 'product_id' => $product->product_id],
            ['stars' => 5]
        );

        $url = route('clients.product', [
            'id' => $product->product_id,
            'slug' => $product->clientPublicSlug(),
            'reviews_sort' => 'stars_high',
        ]);

        $html = $this->get($url)->assertOk()->getContent();
        $posHighName = strpos($html, 'AAAHighStars');
        $posLowName = strpos($html, 'ZZZLowStars');
        $this->assertNotFalse($posHighName);
        $this->assertNotFalse($posLowName);
        $this->assertLessThan($posHighName, $posLowName, 'AAAHighStars (5★) debe aparecer antes en el documento que ZZZLowStars (2★).');
    }

    public function test_pagination_appears_when_more_than_ten_other_reviews(): void
    {
        $product = $this->seedProduct('Producto Paginación Reseñas');

        for ($i = 0; $i < 11; $i++) {
            $client = $this->seedClient("pag-rev-{$i}@example.com");
            $this->createCompletedSaleWithItem($client, $product);
            ProductReview::query()->updateOrCreate(
                ['client_id' => $client->user_id, 'product_id' => $product->product_id],
                ['stars' => 3]
            );
        }

        $url = route('clients.product', [
            'id' => $product->product_id,
            'slug' => $product->clientPublicSlug(),
        ]);

        $response = $this->get($url);
        $response->assertOk();
        $response->assertSee('Siguiente', false);
        $response->assertSee('de 11', false);
        $this->assertMatchesRegularExpression('/1\D10\D+de\s+11/u', $response->getContent());
    }

    public function test_authenticated_client_sees_highlighted_own_review(): void
    {
        $product = $this->seedProduct('Producto Mi Reseña Destacada');
        $owner = $this->seedClient('owner-highlight@example.com');
        $this->createCompletedSaleWithItem($owner, $product);
        ProductReview::query()->updateOrCreate(
            ['client_id' => $owner->user_id, 'product_id' => $product->product_id],
            ['stars' => 5]
        );

        $other = $this->seedClient('other-highlight@example.com');
        $this->createCompletedSaleWithItem($other, $product);
        ProductReview::query()->updateOrCreate(
            ['client_id' => $other->user_id, 'product_id' => $product->product_id],
            ['stars' => 4]
        );

        $url = route('clients.product', [
            'id' => $product->product_id,
            'slug' => $product->clientPublicSlug(),
        ]);

        $response = $this->actingAs($owner, 'clients')->get($url);
        $response->assertOk();
        $response->assertSee('Tu reseña', false);
        $response->assertSee('product-review-item--mine', false);
    }

    private function seedClient(string $email, ?string $displayName = null): Client
    {
        [$local, $domain] = explode('@', $email, 2);

        return Client::create([
            'name' => $displayName ?? 'Cliente',
            'first_surname' => 'Resena',
            'second_surname' => null,
            'gmail' => $local.'-'.$this->runSuffix.'@'.$domain,
            'password' => bcrypt('password'),
            'provider' => 'local',
        ]);
    }

    private function seedProduct(string $name): Product
    {
        return Product::create([
            'category_id' => null,
            'supplier_id' => null,
            'name' => $name,
            'description' => null,
            'image' => 'default.png',
            'sale_price' => 120,
            'purchase_price' => 50,
            'stock_current' => 10,
            'stock_minimum' => 1,
            'status' => 'active',
        ]);
    }

    private function createCompletedSaleWithItem(Client $client, Product $product): void
    {
        $sale = Sale::create([
            'invoice_number' => 'CF4-'.str_pad((string) random_int(1000, 9999), 4, '0', STR_PAD_LEFT).'-'.substr((string) microtime(true), -3),
            'client_id' => $client->user_id,
            'sale_date' => now(),
            'payment_method' => 'cash',
            'status' => 'completed',
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
