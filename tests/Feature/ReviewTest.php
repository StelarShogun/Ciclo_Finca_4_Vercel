<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\Client;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Tests\Support\InteractsWithMysqlTestDatabase;
use Tests\TestCase;

class ReviewTest extends TestCase
{
    use InteractsWithMysqlTestDatabase;
    use RefreshDatabase;

    private string $runSuffix;

    protected function setUp(): void
    {
        try {
            parent::setUp();
            $this->runSuffix = (string) now()->format('YmdHisv').'-'.bin2hex(random_bytes(3));

            $this->skipUnlessMysqlTestDatabase([
                'admins',
                'client_table',
                'products',
                'sales',
                'sale_items',
                'product_reviews',
            ]);

            Config::set('sales.order_expiration_days', 30);
        } catch (\Throwable $e) {
            $this->markTestSkipped('Base de datos no disponible para tests: '.$e->getMessage());
        }
    }

    public function test_admin_confirm_order_creates_review_placeholder_for_each_product(): void
    {
        [$webClient, $adminUser] = $this->seedAdminContext();
        $client = $this->seedClient('cliente-review-confirm@example.com');
        $productA = $this->seedProduct('Producto Review A');
        $productB = $this->seedProduct('Producto Review B');

        $sale = Sale::create([
            'invoice_number' => 'CF4-'.random_int(1000, 9999),
            'client_id' => $client->user_id,
            'seller_admin_id' => null,
            'sale_date' => now(),
            'payment_method' => 'cash',
            'status' => 'ready_to_pickup',
            'ready_at' => now(),
            'subtotal' => 200,
            'iva' => 0,
            'discount' => 0,
            'total' => 200,
            'notes' => null,
            'order_source' => 'web_cart',
        ]);

        SaleItem::create([
            'sale_id' => $sale->sale_id,
            'product_id' => $productA->product_id,
            'quantity' => 1,
            'unit_price' => 100,
            'unit_discount' => 0,
            'total' => 100,
        ]);
        SaleItem::create([
            'sale_id' => $sale->sale_id,
            'product_id' => $productB->product_id,
            'quantity' => 1,
            'unit_price' => 100,
            'unit_discount' => 0,
            'total' => 100,
        ]);

        Auth::guard('web')->login($webClient);
        Auth::guard('admin')->login($adminUser);

        $response = $this->postJson(route('sales.complete', $sale->sale_id));
        $response->assertStatus(200)->assertJsonPath('success', true);

        $this->assertDatabaseHas('product_reviews', [
            'client_id' => $client->user_id,
            'product_id' => $productA->product_id,
            'stars' => null,
        ]);
        $this->assertDatabaseHas('product_reviews', [
            'client_id' => $client->user_id,
            'product_id' => $productB->product_id,
            'stars' => null,
        ]);
    }

    public function test_client_can_create_and_update_single_review_for_purchased_product(): void
    {
        $client = $this->seedClient('cliente-review-single@example.com');
        $product = $this->seedProduct('Producto Review Single');
        $this->createCompletedSaleWithItem($client, $product);

        $this->actingAs($client, 'clients');

        $first = $this->post(route('clients.products.review.store', ['product' => $product->product_id]), [
            'stars' => 5,
        ]);
        $first->assertRedirect();
        $first->assertSessionHas('status', 'Tu reseña se guardó correctamente.');

        $this->assertDatabaseHas('product_reviews', [
            'client_id' => $client->user_id,
            'product_id' => $product->product_id,
            'stars' => 5,
        ]);

        $second = $this->post(route('clients.products.review.store', ['product' => $product->product_id]), [
            'stars' => 3,
        ]);
        $second->assertRedirect();

        $this->assertSame(1, DB::table('product_reviews')
            ->where('client_id', $client->user_id)
            ->where('product_id', $product->product_id)
            ->count());
        $this->assertDatabaseHas('product_reviews', [
            'client_id' => $client->user_id,
            'product_id' => $product->product_id,
            'stars' => 3,
        ]);
    }

    public function test_client_cannot_review_non_purchased_product(): void
    {
        $client = $this->seedClient('cliente-review-denied@example.com');
        $product = $this->seedProduct('Producto No Comprado');

        $this->actingAs($client, 'clients');

        $response = $this->postJson(route('clients.products.review.store', ['product' => $product->product_id]), [
            'stars' => 4,
        ]);

        $response->assertStatus(403);
        $this->assertSame(0, DB::table('product_reviews')
            ->where('client_id', $client->user_id)
            ->where('product_id', $product->product_id)
            ->count());
    }

    public function test_review_stars_are_required_and_must_be_between_one_and_five(): void
    {
        $client = $this->seedClient('cliente-review-validation@example.com');
        $product = $this->seedProduct('Producto Validation');
        $this->createCompletedSaleWithItem($client, $product);

        $this->actingAs($client, 'clients');

        $missingStars = $this->postJson(route('clients.products.review.store', ['product' => $product->product_id]), []);
        $missingStars->assertStatus(422)->assertJsonValidationErrors(['stars']);

        $invalidRange = $this->postJson(route('clients.products.review.store', ['product' => $product->product_id]), [
            'stars' => 6,
        ]);
        $invalidRange->assertStatus(422)->assertJsonValidationErrors(['stars']);
    }

    public function test_client_can_save_multiple_reviews_from_history_modal_batch(): void
    {
        $client = $this->seedClient('cliente-review-batch-main@example.com');
        $productA = $this->seedProduct('Producto Batch A');
        $productB = $this->seedProduct('Producto Batch B');
        $this->createCompletedSaleWithItem($client, $productA);
        $this->createCompletedSaleWithItem($client, $productB);

        $this->actingAs($client, 'clients');

        $response = $this->postJson(route('clients.products.review.batch'), [
            'reviews' => [
                ['product_id' => $productA->product_id, 'stars' => 5],
                ['product_id' => $productB->product_id, 'stars' => 2],
            ],
        ]);

        $response->assertStatus(200)->assertJsonPath('message', 'Reseñas guardadas correctamente.');
        $this->assertDatabaseHas('product_reviews', [
            'client_id' => $client->user_id,
            'product_id' => $productA->product_id,
            'stars' => 5,
        ]);
        $this->assertDatabaseHas('product_reviews', [
            'client_id' => $client->user_id,
            'product_id' => $productB->product_id,
            'stars' => 2,
        ]);
    }

    public function test_history_view_contains_pending_review_products_for_modal(): void
    {
        $client = $this->seedClient('cliente-review-history@example.com');
        $product = $this->seedProduct('Producto Historial');
        $this->createCompletedSaleWithItem($client, $product);

        $this->actingAs($client, 'clients');
        $this->postJson(route('clients.products.review.batch'), [
            'reviews' => [['product_id' => $product->product_id, 'stars' => 4]],
        ])->assertStatus(200);

        $response = $this->get(route('clients.invoices', ['tab' => 'historial']));
        $response->assertStatus(200);
        $response->assertSee('const pendingProducts = [];', false);
    }

    private function seedAdminContext(): array
    {
        $webClient = Client::create([
            'name' => 'Admin',
            'first_surname' => 'Web',
            'second_surname' => null,
            'gmail' => 'admin-web-review-'.$this->runSuffix.'@example.com',
            'password' => bcrypt('password'),
            'provider' => 'local',
        ]);

        $adminUser = AdminUser::create([
            'name' => 'Admin',
            'first_surname' => 'Reviewer',
            'second_surname' => null,
            'gmail' => 'admin-review-guard-'.$this->runSuffix.'@example.com',
            'password' => bcrypt('password'),
            'last_access' => now(),
        ]);

        return [$webClient, $adminUser];
    }

    private function seedClient(string $email): Client
    {
        return Client::create([
            'name' => 'Cliente',
            'first_surname' => 'Resena',
            'second_surname' => null,
            'gmail' => $this->withRunSuffix($email),
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

    private function withRunSuffix(string $email): string
    {
        [$local, $domain] = explode('@', $email, 2);

        return $local.'-'.$this->runSuffix.'@'.$domain;
    }
}
