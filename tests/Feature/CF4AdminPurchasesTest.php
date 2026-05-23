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

class CF4AdminPurchasesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        try {
            parent::setUp();

            $driver = Schema::getConnection()->getDriverName();
            if ($driver !== 'mysql') {
                $this->markTestSkipped('CF4 admin compras requiere MySQL para el esquema en inglés.');
            }

            foreach (['admins', 'client_table', 'products', 'sales', 'sale_items'] as $table) {
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

    public function test_admin_purchases_table_shows_pending_and_completed_web_cart(): void
    {
        $webClient = Client::create([
            'name' => 'Admin',
            'first_surname' => 'Test',
            'second_surname' => null,
            'gmail' => 'admin-web-cf4@example.com',
            'password' => bcrypt('password'),
            'provider' => 'local',
        ]);

        $adminUser = AdminUser::create([
            'name' => 'Admin',
            'first_surname' => 'Test',
            'second_surname' => null,
            'gmail' => 'admin-cf4-guard@example.com',
            'password' => bcrypt('password'),
            'last_access' => now(),
        ]);

        $client = Client::create([
            'name' => 'Cliente',
            'first_surname' => 'CF4',
            'second_surname' => null,
            'gmail' => 'cliente-admin-purchases@example.com',
            'password' => bcrypt('password'),
            'provider' => 'local',
        ]);

        $product = Product::create([
            'category_id' => null,
            'supplier_id' => null,
            'name' => 'Producto Admin',
            'description' => null,
            'image' => 'default.png',
            'sale_price' => 25,
            'purchase_price' => 5,
            'stock_current' => 20,
            'stock_minimum' => 1,
            'status' => 'active',
        ]);

        $total = 50; // 25 * 2
        $salePending = Sale::create([
            'invoice_number' => 'INV'.now()->format('Ymd').'0010',
            'client_id' => $client->user_id,
            'seller_admin_id' => null,
            'sale_date' => now(),
            'payment_method' => 'cash',
            'status' => 'pending',
            'subtotal' => $total,
            'iva' => 0,
            'discount' => 0,
            'total' => $total,
            'notes' => 'CF4 test pending',
            'order_source' => 'web_cart',
        ]);

        SaleItem::create([
            'sale_id' => $salePending->sale_id,
            'product_id' => $product->product_id,
            'quantity' => 2,
            'unit_price' => 25,
            'unit_discount' => 0,
            'total' => $total,
        ]);

        $saleCompleted = Sale::create([
            'invoice_number' => 'INV'.now()->format('Ymd').'0011',
            'client_id' => $client->user_id,
            'seller_admin_id' => null,
            'sale_date' => now(),
            'payment_method' => 'cash',
            'status' => 'completed',
            'subtotal' => $total,
            'iva' => 0,
            'discount' => 0,
            'total' => $total,
            'notes' => 'CF4 test completed',
            'order_source' => 'web_cart',
        ]);

        SaleItem::create([
            'sale_id' => $saleCompleted->sale_id,
            'product_id' => $product->product_id,
            'quantity' => 2,
            'unit_price' => 25,
            'unit_discount' => 0,
            'total' => $total,
        ]);

        $this->authenticateAdmin($webClient, $adminUser);

        $response = $this->get(route('admin.orders.index'));
        $response->assertStatus(200);

        $response->assertSee('Pendiente', false);
        $response->assertSee($salePending->invoice_number, false);
        $response->assertSee($saleCompleted->invoice_number, false);
        $response->assertSee('cliente-admin-purchases@example.com', false);
        $response->assertSee('Confirmado', false);
        $response->assertSee('Fecha de pedido', false);
        $response->assertSee('Fecha listo para recoger', false);
        $response->assertSee('Fecha de confirmación', false);
    }

    public function test_admin_purchases_heartbeat_detects_new_web_cart_sale(): void
    {
        $webClient = Client::create([
            'name' => 'Admin',
            'first_surname' => 'Test',
            'second_surname' => null,
            'gmail' => 'admin-web-cf4-heartbeat@example.com',
            'password' => bcrypt('password'),
            'provider' => 'local',
        ]);

        $adminUser = AdminUser::create([
            'name' => 'Admin',
            'first_surname' => 'Test',
            'second_surname' => null,
            'gmail' => 'admin-cf4-heartbeat-guard@example.com',
            'password' => bcrypt('password'),
            'last_access' => now(),
        ]);

        $client = Client::create([
            'name' => 'Cliente',
            'first_surname' => 'CF4',
            'second_surname' => null,
            'gmail' => 'cliente-heartbeat@example.com',
            'password' => bcrypt('password'),
            'provider' => 'local',
        ]);

        $product = Product::create([
            'category_id' => null,
            'supplier_id' => null,
            'name' => 'Producto HB',
            'description' => null,
            'image' => 'default.png',
            'sale_price' => 10,
            'purchase_price' => 2,
            'stock_current' => 50,
            'stock_minimum' => 1,
            'status' => 'active',
        ]);

        $total = 10;

        $sale1 = Sale::create([
            'invoice_number' => 'INV'.now()->format('Ymd').'0100',
            'client_id' => $client->user_id,
            'seller_admin_id' => null,
            'sale_date' => now(),
            'payment_method' => 'cash',
            'status' => 'pending',
            'subtotal' => $total,
            'iva' => 0,
            'discount' => 0,
            'total' => $total,
            'notes' => 'CF4 heartbeat 1',
            'order_source' => 'web_cart',
        ]);

        SaleItem::create([
            'sale_id' => $sale1->sale_id,
            'product_id' => $product->product_id,
            'quantity' => 1,
            'unit_price' => 10,
            'unit_discount' => 0,
            'total' => $total,
        ]);

        $this->authenticateAdmin($webClient, $adminUser);

        $heartbeatRes1 = $this->getJson('/sales/history/heartbeat?since='.$sale1->sale_id);
        $heartbeatRes1->assertStatus(200);
        $this->assertFalse($heartbeatRes1->json('hasNew'));
        $this->assertSame(0, $heartbeatRes1->json('newCount'));
        $this->assertArrayHasKey('pendingCount', $heartbeatRes1->json());

        // Crear una nueva compra para que heartbeat detecte cambios.
        $sale2 = Sale::create([
            'invoice_number' => 'INV'.now()->format('Ymd').'0101',
            'client_id' => $client->user_id,
            'seller_admin_id' => null,
            'sale_date' => now(),
            'payment_method' => 'cash',
            'status' => 'pending',
            'subtotal' => $total,
            'iva' => 0,
            'discount' => 0,
            'total' => $total,
            'notes' => 'CF4 heartbeat 2',
            'order_source' => 'web_cart',
        ]);

        SaleItem::create([
            'sale_id' => $sale2->sale_id,
            'product_id' => $product->product_id,
            'quantity' => 1,
            'unit_price' => 10,
            'unit_discount' => 0,
            'total' => $total,
        ]);

        $heartbeatRes2 = $this->getJson('/sales/history/heartbeat?since='.$sale1->sale_id);
        $heartbeatRes2->assertStatus(200);
        $this->assertTrue($heartbeatRes2->json('hasNew'));
        $this->assertSame(1, $heartbeatRes2->json('newCount'));
        $this->assertGreaterThanOrEqual(2, $heartbeatRes2->json('pendingCount'));
    }

    public function test_admin_orders_index_includes_auto_refresh_markup(): void
    {
        $webClient = Client::create([
            'name' => 'Admin',
            'first_surname' => 'Test',
            'second_surname' => null,
            'gmail' => 'admin-orders-refresh@example.com',
            'password' => bcrypt('password'),
            'provider' => 'local',
        ]);

        $adminUser = AdminUser::create([
            'name' => 'Admin',
            'first_surname' => 'Test',
            'second_surname' => null,
            'gmail' => 'admin-orders-refresh-guard@example.com',
            'password' => bcrypt('password'),
            'last_access' => now(),
        ]);

        $this->authenticateAdmin($webClient, $adminUser);

        $response = $this->get(route('admin.orders.index'));
        $response->assertStatus(200);
        $response->assertSee('data-cf4-orders-heartbeat', false);
        $response->assertSee('id="cf4-orders-new-banner"', false);
        $response->assertSee('cf4-latest-purchase-sale-id', false);
        $response->assertSee('data-cf4-orders-pending-badge', false);
    }
}
