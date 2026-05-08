<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\Client;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Notifications\OrderReadyToPickupNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class OrderReadyToPickupNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        try {
            parent::setUp();
        } catch (\Throwable $e) {
            $this->markTestSkipped('Base de datos no disponible para tests: '.$e->getMessage());
        }

        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            $this->markTestSkipped('Este test requiere MySQL.');
        }

        foreach (['sales', 'sale_items', 'client_table', 'admins', 'notifications', 'products'] as $table) {
            if (! Schema::hasTable($table)) {
                $this->markTestSkipped('Tabla requerida no existe: '.$table);
            }
        }

        Config::set('mail.default', 'array');
        Config::set('sales.ready_to_pickup_expiration_hours', 72);
    }

    public function test_mark_ready_to_pickup_creates_database_notification(): void
    {
        $admin = AdminUser::create([
            'name' => 'Admin',
            'first_surname' => 'Ready',
            'second_surname' => null,
            'gmail' => 'admin-ready-pickup@example.com',
            'password' => bcrypt('password'),
            'last_access' => now(),
        ]);

        $client = Client::create([
            'name' => 'Cliente',
            'first_surname' => 'Pickup',
            'second_surname' => null,
            'gmail' => 'cliente-ready@example.com',
            'password' => bcrypt('password'),
            'provider' => 'local',
        ]);

        $product = Product::create([
            'category_id' => null,
            'supplier_id' => null,
            'name' => 'Producto ready',
            'description' => null,
            'image' => 'default.png',
            'sale_price' => 100,
            'purchase_price' => 50,
            'stock_current' => 5,
            'stock_minimum' => 1,
            'status' => 'active',
        ]);

        $sale = Sale::create([
            'invoice_number' => 'CF4-8801',
            'client_id' => $client->user_id,
            'sale_date' => now(),
            'payment_method' => 'cash',
            'status' => 'pending',
            'subtotal' => 100,
            'iva' => 0,
            'discount' => 0,
            'total' => 100,
            'order_source' => 'web_cart',
            'notes' => null,
        ]);

        SaleItem::create([
            'sale_id' => $sale->sale_id,
            'product_id' => $product->product_id,
            'quantity' => 1,
            'unit_price' => 100,
            'unit_discount' => 0,
            'total' => 100,
        ]);

        $this->actingAs($admin, 'admin');

        $response = $this->patchJson(route('admin.orders.ready-to-pickup', $sale->sale_id));
        $response->assertOk();
        $this->assertTrue($response->json('success'));

        $this->assertDatabaseHas('notifications', [
            'notifiable_type' => Client::class,
            'notifiable_id' => $client->user_id,
            'type' => OrderReadyToPickupNotification::class,
        ]);

        $sale->refresh();
        $this->assertSame('ready_to_pickup', $sale->status);
        $this->assertNotNull($sale->ready_at);
    }
}
