<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\Client;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Notifications\OrderCancelledNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class OrderCancellationNotificationTest extends TestCase
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
            $this->markTestSkipped('Estos tests requieren MySQL.');
        }

        foreach (['sales', 'sale_items', 'client_table', 'admins', 'notifications', 'order_notification_logs'] as $table) {
            if (! Schema::hasTable($table)) {
                $this->markTestSkipped('Tabla requerida no existe: '.$table);
            }
        }

        Config::set('mail.default', 'array');
        Config::set('sales.order_expiration_days', 30);
    }

    public function test_automatic_expiration_cancellation_creates_notification_and_logs(): void
    {
        $client = Client::create([
            'name' => 'Cliente',
            'first_surname' => 'Auto',
            'second_surname' => null,
            'gmail' => 'cliente-auto@example.com',
            'password' => bcrypt('password'),
            'provider' => 'local',
        ]);

        $sale = Sale::create([
            'invoice_number' => 'CF4-9991',
            'client_id' => $client->user_id,
            'sale_date' => now()->subDays(31),
            'payment_method' => 'cash',
            'status' => 'pending',
            'subtotal' => 1000,
            'iva' => 0,
            'discount' => 0,
            'total' => 1000,
            'order_source' => 'web_cart',
            'notes' => null,
        ]);

        $this->artisan('sales:delete-expired')->assertSuccessful();

        $sale->refresh();
        $this->assertSame('cancelled', $sale->status);
        $this->assertStringContainsString('Cancelado automáticamente por vencimiento del plazo', (string) $sale->notes);

        $this->assertDatabaseHas('notifications', [
            'notifiable_type' => Client::class,
            'notifiable_id' => $client->user_id,
            'type' => OrderCancelledNotification::class,
        ]);

        $this->assertDatabaseHas('order_notification_logs', [
            'sale_id' => $sale->sale_id,
            'client_id' => $client->user_id,
            'channel' => 'mail',
            'status' => 'sent',
            'reason' => 'Cancelado automáticamente por vencimiento del plazo',
        ]);

        $this->assertDatabaseHas('order_notification_logs', [
            'sale_id' => $sale->sale_id,
            'client_id' => $client->user_id,
            'channel' => 'database',
            'status' => 'sent',
            'reason' => 'Cancelado automáticamente por vencimiento del plazo',
        ]);
    }

    public function test_manual_admin_cancellation_creates_notification_and_logs(): void
    {
        $webClient = Client::create([
            'name' => 'Admin',
            'first_surname' => 'Web',
            'second_surname' => null,
            'gmail' => 'admin-web-manual@example.com',
            'password' => bcrypt('password'),
            'provider' => 'local',
        ]);

        $adminUser = AdminUser::create([
            'name' => 'Admin',
            'first_surname' => 'Manual',
            'second_surname' => null,
            'gmail' => 'admin-manual@example.com',
            'password' => bcrypt('password'),
            'last_access' => now(),
        ]);

        $client = Client::create([
            'name' => 'Cliente',
            'first_surname' => 'Manual',
            'second_surname' => null,
            'gmail' => 'cliente-manual@example.com',
            'password' => bcrypt('password'),
            'provider' => 'local',
        ]);

        $product = Product::create([
            'category_id' => null,
            'supplier_id' => null,
            'name' => 'Producto HU Cancel',
            'description' => null,
            'image' => 'default.png',
            'sale_price' => 300,
            'purchase_price' => 100,
            'stock_current' => 5,
            'stock_minimum' => 1,
            'status' => 'active',
        ]);

        $sale = Sale::create([
            'invoice_number' => 'CF4-9992',
            'client_id' => $client->user_id,
            'sale_date' => now()->subDays(3),
            'payment_method' => 'cash',
            'status' => 'pending',
            'subtotal' => 300,
            'iva' => 0,
            'discount' => 0,
            'total' => 300,
            'order_source' => 'web_cart',
            'notes' => null,
        ]);

        SaleItem::create([
            'sale_id' => $sale->sale_id,
            'product_id' => $product->product_id,
            'quantity' => 1,
            'unit_price' => 300,
            'unit_discount' => 0,
            'total' => 300,
        ]);

        Auth::guard('web')->login($webClient);
        Auth::guard('admin')->login($adminUser);

        $response = $this->postJson(route('sales.cancel', $sale->sale_id));
        $response->assertStatus(200)->assertJsonPath('success', true);

        $sale->refresh();
        $this->assertSame('cancelled', $sale->status);
        $this->assertStringContainsString('Cancelado por administración', (string) $sale->notes);

        $this->assertDatabaseHas('notifications', [
            'notifiable_type' => Client::class,
            'notifiable_id' => $client->user_id,
            'type' => OrderCancelledNotification::class,
        ]);

        $this->assertDatabaseHas('order_notification_logs', [
            'sale_id' => $sale->sale_id,
            'client_id' => $client->user_id,
            'channel' => 'mail',
            'status' => 'sent',
            'reason' => 'Cancelado por administración',
        ]);

        $this->assertDatabaseHas('order_notification_logs', [
            'sale_id' => $sale->sale_id,
            'client_id' => $client->user_id,
            'channel' => 'database',
            'status' => 'sent',
            'reason' => 'Cancelado por administración',
        ]);
    }
}
