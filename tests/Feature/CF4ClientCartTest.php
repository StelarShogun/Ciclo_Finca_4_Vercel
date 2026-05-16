<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Session;
use Tests\TestCase;

class CF4ClientCartTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        try {
            parent::setUp();

            $driver = Schema::getConnection()->getDriverName();
            if ($driver !== 'mysql') {
                $this->markTestSkipped('CF4 carrito/checkout requiere MySQL para el esquema en inglés.');
            }

            foreach (['client_table', 'products', 'sales', 'sale_items'] as $table) {
                if (! Schema::hasTable($table)) {
                    $this->markTestSkipped('Tabla requerida no existe: '.$table);
                }
            }

            // Asegurar que pedidos recientes cuenten como "no expirados" en ventas.
            Config::set('sales.order_expiration_days', 30);
        } catch (\Throwable $e) {
            $this->markTestSkipped('Base de datos no disponible para tests: '.$e->getMessage());
        }
    }

    public function test_client_cart_add_update_remove_and_empty_state(): void
    {
        $client = Client::create([
            'name' => 'Cliente',
            'first_surname' => 'Test',
            'second_surname' => null,
            'gmail' => 'cliente-cart@example.com',
            'password' => bcrypt('password'),
            'provider' => 'local',
        ]);

        $product = Product::create([
            'category_id' => null,
            'supplier_id' => null,
            'name' => 'Producto CF4',
            'description' => null,
            'image' => 'default.png',
            'sale_price' => 90,
            'purchase_price' => 10,
            'stock_current' => 10,
            'stock_minimum' => 1,
            'status' => 'active',
        ]);

        $this->actingAs($client, 'clients');

        $addRes = $this->postJson(route('clients.cart.add'), [
            'product_id' => $product->product_id,
            'quantity' => 2,
        ]);
        $addRes->assertStatus(200);
        $this->assertTrue($addRes->json('success'));
        $this->assertEquals(180, $addRes->json('cart_total'));

        $updateRes = $this->putJson(route('clients.cart.update'), [
            'product_id' => $product->product_id,
            'quantity' => 1,
        ]);
        $updateRes->assertStatus(200);
        $this->assertTrue($updateRes->json('success'));
        $this->assertEquals(90, $updateRes->json('cart_total'));
        $this->assertSame(1, $updateRes->json('quantity_applied'));
        $this->assertFalse($updateRes->json('stock_clamped'));
        $this->assertEquals(90.0, $updateRes->json('line_subtotal'));

        $clampRes = $this->putJson(route('clients.cart.update'), [
            'product_id' => $product->product_id,
            'quantity' => 99,
        ]);
        $clampRes->assertStatus(200);
        $this->assertTrue($clampRes->json('success'));
        $this->assertSame(10, $clampRes->json('quantity_applied'));
        $this->assertTrue($clampRes->json('stock_clamped'));
        $this->assertEquals(900.0, $clampRes->json('line_subtotal'));

        $removeRes = $this->deleteJson(route('clients.cart.remove', $product->product_id));
        $removeRes->assertStatus(200);
        $this->assertTrue($removeRes->json('success'));
        $this->assertEquals(0, $removeRes->json('cart_total'));

        // CF4: eliminar cuando el carrito está vacío.
        $removeEmptyRes = $this->deleteJson(route('clients.cart.remove', $product->product_id));
        $removeEmptyRes->assertStatus(400);
        $this->assertFalse($removeEmptyRes->json('success'));
        $this->assertEquals('El carrito está vacío', $removeEmptyRes->json('message'));

        $cartViewRes = $this->get(route('clients.cart'));
        $cartViewRes->assertStatus(200);
        $cartViewRes->assertSee('Tu carrito está vacío', false);
        $cartViewRes->assertSee('cart-empty-icon', false);
        $cartViewRes->assertSee('fa-cart-shopping', false);
    }

    public function test_checkout_creates_pending_web_cart_sale_and_clears_session_cart(): void
    {
        $client = Client::create([
            'name' => 'Cliente',
            'first_surname' => 'Checkout',
            'second_surname' => null,
            'gmail' => 'cliente-checkout@example.com',
            'password' => bcrypt('password'),
            'provider' => 'local',
        ]);

        $product1 = Product::create([
            'category_id' => null,
            'supplier_id' => null,
            'name' => 'Producto 1 CF4',
            'description' => null,
            'image' => 'default.png',
            'sale_price' => 50,
            'purchase_price' => 10,
            'stock_current' => 10,
            'stock_minimum' => 1,
            'status' => 'active',
        ]);

        $product2 = Product::create([
            'category_id' => null,
            'supplier_id' => null,
            'name' => 'Producto 2 CF4',
            'description' => null,
            'image' => 'default.png',
            'sale_price' => 40,
            'purchase_price' => 10,
            'stock_current' => 10,
            'stock_minimum' => 1,
            'status' => 'active',
        ]);

        $this->actingAs($client, 'clients');

        $this->postJson(route('clients.cart.add'), [
            'product_id' => $product1->product_id,
            'quantity' => 1,
        ]);
        $this->postJson(route('clients.cart.add'), [
            'product_id' => $product2->product_id,
            'quantity' => 1,
        ]);

        $checkoutRes = $this->postJson(route('clients.cart.checkout'), [
            'payment_method' => 'cash',
        ]);
        $checkoutRes->assertStatus(200);
        $this->assertTrue($checkoutRes->json('success'));

        $saleId = $checkoutRes->json('sale_id');
        $this->assertNotEmpty($saleId);

        $sale = Sale::findOrFail($saleId);
        $this->assertEquals('pending', $sale->status);
        $this->assertEquals('web_cart', $sale->order_source);
        $this->assertEquals($client->user_id, $sale->client_id);
        $this->assertNotNull($sale->client_id);

        $this->assertEquals(90, $sale->total);

        $items = $sale->saleItems()->get();
        $this->assertCount(2, $items);

        $line1 = $items->firstWhere('product_id', $product1->product_id);
        $line2 = $items->firstWhere('product_id', $product2->product_id);
        $this->assertInstanceOf(SaleItem::class, $line1);
        $this->assertInstanceOf(SaleItem::class, $line2);
        $this->assertEquals(50, (float) $line1->unit_price);
        $this->assertEquals(40, (float) $line2->unit_price);

        $cartViewRes = $this->get(route('clients.cart'));
        $cartViewRes->assertStatus(200);
        $cartViewRes->assertSee('Tu carrito está vacío', false);
        $cartViewRes->assertSee('cart-empty-title', false);
        $cartViewRes->assertSee('fa-cart-shopping', false);
    }

    public function test_add_to_cart_rejects_out_of_stock_with_message_producto_agotado(): void
    {
        $client = Client::create([
            'name' => 'Cliente',
            'first_surname' => 'Agotado',
            'second_surname' => null,
            'gmail' => 'cliente-agotado@example.com',
            'password' => bcrypt('password'),
            'provider' => 'local',
        ]);

        $product = Product::create([
            'category_id' => null,
            'supplier_id' => null,
            'name' => 'Producto sin stock',
            'description' => null,
            'image' => 'default.png',
            'sale_price' => 100,
            'purchase_price' => 10,
            'stock_current' => 0,
            'stock_minimum' => 1,
            'status' => 'out_of_stock',
        ]);

        $this->actingAs($client, 'clients');

        $res = $this->postJson(route('clients.cart.add'), [
            'product_id' => $product->product_id,
            'quantity' => 1,
        ]);

        $res->assertStatus(400);
        $this->assertFalse($res->json('success'));
        $this->assertSame(Product::MSG_CLIENT_AGOTADO, $res->json('message'));
    }

    public function test_product_page_redirects_to_canonical_slug_url(): void
    {
        $product = Product::create([
            'category_id' => null,
            'supplier_id' => null,
            'name' => 'Bicicleta Test SEO',
            'description' => null,
            'image' => 'default.png',
            'sale_price' => 100,
            'purchase_price' => 50,
            'stock_current' => 3,
            'stock_minimum' => 1,
            'status' => 'active',
        ]);

        $canonical = $product->clientProductUrl();
        $path = parse_url($canonical, PHP_URL_PATH) ?? '/';

        $this->get('/product/'.$product->product_id)->assertRedirect($canonical);
        $this->get($path)->assertStatus(200);
    }

    public function test_checkout_persists_quantity_greater_than_one_in_sale_items(): void
    {
        $client = Client::create([
            'name' => 'Cliente',
            'first_surname' => 'MultiQty',
            'second_surname' => null,
            'gmail' => 'cliente-multiqty@example.com',
            'password' => bcrypt('password'),
            'provider' => 'local',
        ]);

        $product = Product::create([
            'category_id' => null,
            'supplier_id' => null,
            'name' => 'Giant Escape 3 Test',
            'description' => null,
            'image' => 'default.png',
            'sale_price' => 250_000,
            'purchase_price' => 100_000,
            'stock_current' => 15,
            'stock_minimum' => 1,
            'status' => 'active',
        ]);

        $this->actingAs($client, 'clients');

        $this->postJson(route('clients.cart.add'), [
            'product_id' => $product->product_id,
            'quantity' => 11,
        ])->assertStatus(200);

        $checkoutRes = $this->postJson(route('clients.cart.checkout'), [
            'payment_method' => 'transfer',
        ]);
        $checkoutRes->assertStatus(200);
        $this->assertTrue($checkoutRes->json('success'));

        $saleId = $checkoutRes->json('sale_id');
        $sale = Sale::findOrFail($saleId);
        $this->assertEquals(11 * 250_000, (float) $sale->total);
        $this->assertSame('transfer', $sale->payment_method);

        $line = SaleItem::where('sale_id', $saleId)->where('product_id', $product->product_id)->first();
        $this->assertNotNull($line);
        $this->assertSame(11, (int) $line->quantity);
        $this->assertEquals(250_000.0, (float) $line->unit_price);

        $product->refresh();
        $this->assertSame(4, (int) $product->stock_current);

        $this->assertSame([], Session::get('cart', []));
    }

    public function test_cart_render_does_not_overwrite_session_quantities(): void
    {
        $client = Client::create([
            'name' => 'Cliente',
            'first_surname' => 'Sesion',
            'second_surname' => null,
            'gmail' => 'cliente-sesion@example.com',
            'password' => bcrypt('password'),
            'provider' => 'local',
        ]);

        $product = Product::create([
            'category_id' => null,
            'supplier_id' => null,
            'name' => 'Producto sesión',
            'description' => null,
            'image' => 'default.png',
            'sale_price' => 100,
            'purchase_price' => 10,
            'stock_current' => 15,
            'stock_minimum' => 1,
            'status' => 'active',
        ]);

        $this->actingAs($client, 'clients');

        $inflatedRow = [
            'product_id' => $product->product_id,
            'name' => $product->name,
            'price' => 100.0,
            'quantity' => 11,
            'image' => '',
            'subtotal' => 999,
            'stock_available' => 15,
        ];

        $this->withSession(['cart' => [$inflatedRow]])
            ->get(route('clients.cart'))
            ->assertStatus(200);

        $cart = Session::get('cart', []);
        $this->assertCount(1, $cart);
        $this->assertSame(11, (int) $cart[0]['quantity']);
        $this->assertArrayNotHasKey('subtotal', $cart[0]);
        $this->assertArrayNotHasKey('stock_available', $cart[0]);
        $allowed = ['product_id', 'name', 'price', 'quantity', 'image'];
        foreach (array_keys($cart[0]) as $key) {
            $this->assertContains($key, $allowed);
        }
    }
}
