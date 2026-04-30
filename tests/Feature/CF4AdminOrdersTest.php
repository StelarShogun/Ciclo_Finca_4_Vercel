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
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CF4AdminOrdersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        try {
            parent::setUp();

            $driver = Schema::getConnection()->getDriverName();
            if ($driver !== 'mysql') {
                $this->markTestSkipped('CF4-21 pedidos admin requiere MySQL para el esquema en inglés.');
            }

            foreach (['admins', 'client_table', 'products', 'sales', 'sale_items', 'product_reviews'] as $table) {
                if (! Schema::hasTable($table)) {
                    $this->markTestSkipped('Tabla requerida no existe: '.$table);
                }
            }

            Config::set('sales.order_expiration_days', 30);
        } catch (\Throwable $e) {
            $this->markTestSkipped('Base de datos no disponible para tests: '.$e->getMessage());
        }
    }

    private function authenticateAdmin(Client $webClient, AdminUser $adminUser): void
    {
        Auth::guard('web')->login($webClient);
        Auth::guard('admin')->login($adminUser);
    }

    public function test_orders_index_lists_web_cart_orders(): void
    {
        $webClient = Client::create([
            'name' => 'Admin',
            'first_surname' => 'Orders',
            'second_surname' => null,
            'gmail' => 'admin-web-orders@example.com',
            'password' => bcrypt('password'),
            'provider' => 'local',
        ]);

        $adminUser = AdminUser::create([
            'name' => 'Admin',
            'first_surname' => 'Orders',
            'second_surname' => null,
            'gmail' => 'admin-orders-guard@example.com',
            'password' => bcrypt('password'),
            'last_access' => now(),
        ]);

        $client = Client::create([
            'name' => 'Cliente',
            'first_surname' => 'Pedido',
            'second_surname' => null,
            'gmail' => 'cliente-pedidos@example.com',
            'password' => bcrypt('password'),
            'provider' => 'local',
        ]);

        $product = Product::create([
            'category_id' => null,
            'supplier_id' => null,
            'name' => 'Producto Pedido CF4',
            'description' => null,
            'image' => 'default.png',
            'sale_price' => 100,
            'purchase_price' => 20,
            'stock_current' => 5,
            'stock_minimum' => 1,
            'status' => 'active',
        ]);

        $inv = 'INV'.now()->format('Ymd').'0999';
        $sale = Sale::create([
            'invoice_number' => $inv,
            'client_id' => $client->user_id,
            'seller_admin_id' => null,
            'sale_date' => now(),
            'payment_method' => 'cash',
            'status' => 'pending',
            'subtotal' => 100,
            'iva' => 0,
            'discount' => 0,
            'total' => 100,
            'notes' => 'CF4-21 test',
            'order_source' => 'web_cart',
        ]);

        SaleItem::create([
            'sale_id' => $sale->sale_id,
            'product_id' => $product->product_id,
            'quantity' => 1,
            'unit_price' => 100,
            'unit_discount' => 0,
            'total' => 100,
        ]);

        $this->authenticateAdmin($webClient, $adminUser);

        $response = $this->get(route('admin.orders.index'));
        $response->assertStatus(200);
        $response->assertSee('Pedidos en línea', false);
        $response->assertSee('Producto Pedido CF4', false);
        $response->assertSee($inv, false);
    }

    public function test_complete_pending_returns_invoice_and_second_complete_fails(): void
    {
        $webClient = Client::create([
            'name' => 'Admin',
            'first_surname' => 'Complete',
            'second_surname' => null,
            'gmail' => 'admin-web-complete@example.com',
            'password' => bcrypt('password'),
            'provider' => 'local',
        ]);

        $adminUser = AdminUser::create([
            'name' => 'Admin',
            'first_surname' => 'Complete',
            'second_surname' => null,
            'gmail' => 'admin-complete-guard@example.com',
            'password' => bcrypt('password'),
            'last_access' => now(),
        ]);

        $client = Client::create([
            'name' => 'C',
            'first_surname' => 'F',
            'second_surname' => null,
            'gmail' => 'cf@example.com',
            'password' => bcrypt('password'),
            'provider' => 'local',
        ]);

        $product = Product::create([
            'category_id' => null,
            'supplier_id' => null,
            'name' => 'P',
            'description' => null,
            'image' => 'default.png',
            'sale_price' => 10,
            'purchase_price' => 2,
            'stock_current' => 5,
            'stock_minimum' => 1,
            'status' => 'active',
        ]);

        $sale = Sale::create([
            'invoice_number' => 'INV'.now()->format('Ymd').'0888',
            'client_id' => $client->user_id,
            'seller_admin_id' => null,
            'sale_date' => now(),
            'payment_method' => 'cash',
            'status' => 'pending',
            'subtotal' => 10,
            'iva' => 0,
            'discount' => 0,
            'total' => 10,
            'notes' => 'x',
            'order_source' => 'web_cart',
        ]);

        SaleItem::create([
            'sale_id' => $sale->sale_id,
            'product_id' => $product->product_id,
            'quantity' => 1,
            'unit_price' => 10,
            'unit_discount' => 0,
            'total' => 10,
        ]);

        $this->authenticateAdmin($webClient, $adminUser);

        $complete = $this->postJson(route('sales.complete', $sale->sale_id));
        $complete->assertStatus(200);
        $complete->assertJsonPath('success', true);
        $complete->assertJsonPath('sale.status', 'completed');
        $this->assertNotEmpty($complete->json('sale.invoice_number'));
        $this->assertDatabaseHas('product_reviews', [
            'client_id' => $client->user_id,
            'product_id' => $product->product_id,
            'stars' => null,
        ]);

        $again = $this->postJson(route('sales.complete', $sale->sale_id));
        $again->assertStatus(400);
        $again->assertJsonPath('success', false);
        $this->assertStringContainsString('confirmado', $again->json('message'));
    }

    public function test_complete_generates_invoice_when_missing(): void
    {
        $webClient = Client::create([
            'name' => 'Admin',
            'first_surname' => 'Inv',
            'second_surname' => null,
            'gmail' => 'admin-web-inv@example.com',
            'password' => bcrypt('password'),
            'provider' => 'local',
        ]);

        $adminUser = AdminUser::create([
            'name' => 'Admin',
            'first_surname' => 'Inv',
            'second_surname' => null,
            'gmail' => 'admin-inv-guard@example.com',
            'password' => bcrypt('password'),
            'last_access' => now(),
        ]);

        $client = Client::create([
            'name' => 'C',
            'first_surname' => 'I',
            'second_surname' => null,
            'gmail' => 'ci@example.com',
            'password' => bcrypt('password'),
            'provider' => 'local',
        ]);

        $product = Product::create([
            'category_id' => null,
            'supplier_id' => null,
            'name' => 'Pi',
            'description' => null,
            'image' => 'default.png',
            'sale_price' => 5,
            'purchase_price' => 1,
            'stock_current' => 3,
            'stock_minimum' => 1,
            'status' => 'active',
        ]);

        $sale = Sale::create([
            'invoice_number' => '',
            'client_id' => $client->user_id,
            'seller_admin_id' => null,
            'sale_date' => now(),
            'payment_method' => 'cash',
            'status' => 'pending',
            'subtotal' => 5,
            'iva' => 0,
            'discount' => 0,
            'total' => 5,
            'notes' => 'sin factura',
            'order_source' => 'web_cart',
        ]);

        SaleItem::create([
            'sale_id' => $sale->sale_id,
            'product_id' => $product->product_id,
            'quantity' => 1,
            'unit_price' => 5,
            'unit_discount' => 0,
            'total' => 5,
        ]);

        $this->authenticateAdmin($webClient, $adminUser);

        $complete = $this->postJson(route('sales.complete', $sale->sale_id));
        $complete->assertStatus(200);
        $inv = $complete->json('sale.invoice_number');
        $this->assertNotEmpty($inv);
        $this->assertMatchesRegularExpression('/^CF4-\d{4}$/', $inv);

        $sale->refresh();
        $this->assertSame($inv, $sale->invoice_number);
        $this->assertSame('completed', $sale->status);
    }
}
