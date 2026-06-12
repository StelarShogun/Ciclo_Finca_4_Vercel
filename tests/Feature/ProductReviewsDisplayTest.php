<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\Sale;
use App\Models\SaleItem;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ProductReviewsDisplayTest extends TestCase
{
    private string $runSuffix;

    protected function setUp(): void
    {
        parent::setUp();
        $this->runSuffix = (string) now()->format('YmdHisv').'-'.bin2hex(random_bytes(3));
    }

    public function test_guest_sees_no_valoraciones_message_when_product_has_no_public_reviews(): void
    {
        $product = $this->seedProduct('Producto Sin Reseñas Públicas');

        $this->get($this->productUrl($product))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Client/Products/Show', false)
                ->where('reviews.totalCount', 0)
                ->where('reviews.items', [])
            );
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

        $this->get($this->productUrl($product))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Client/Products/Show', false)
                ->where('reviews.totalCount', 2)
                ->where('reviews.averageStars', fn ($avg) => (float) $avg === 3.0)
                ->has('reviews.items', 2)
                ->where('reviews.items.0.verified', true)
            );
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

        $this->get($this->productUrl($product, ['reviews_sort' => 'stars_high']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Client/Products/Show', false)
                ->where('reviews.sort', 'stars_high')
                ->where('reviews.items.0.stars', 5)
                ->where('reviews.items.0.author', fn (string $author) => str_contains($author, 'AAAHighStars'))
                ->where('reviews.items.1.stars', 2)
                ->where('reviews.items.1.author', fn (string $author) => str_contains($author, 'ZZZLowStars'))
            );
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

        $this->get($this->productUrl($product))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Client/Products/Show', false)
                ->where('reviews.totalCount', 11)
                ->where('reviews.pagination.lastPage', fn (int $lastPage) => $lastPage >= 2)
                ->where('reviews.pagination.total', 11)
            );
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

        $this->actingAs($owner, 'clients')
            ->get($this->productUrl($product))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Client/Products/Show', false)
                ->where('reviews.showMyHighlighted', true)
                ->where('reviews.myHighlighted.mine', true)
                ->where('reviews.myHighlighted.stars', 5)
            );
    }

    /**
     * @param  array<string, mixed>  $query
     */
    private function productUrl(Product $product, array $query = []): string
    {
        return route('clients.product', array_merge([
            'id' => $product->product_id,
            'slug' => $product->clientPublicSlug(),
        ], $query));
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
