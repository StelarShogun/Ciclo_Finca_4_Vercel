<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\Client;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class CF4160OrderStatusResponseTimeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Config::set('mail.default', 'smtp');
        Config::set('mail.mailers.smtp.host', '127.0.0.1');
        Config::set('mail.mailers.smtp.port', 65535);
        Config::set('mail.mailers.smtp.timeout', 120);
    }

    public function test_mark_ready_to_pickup_returns_within_two_seconds_without_waiting_for_mail(): void
    {
        Notification::fake();

        $admin = AdminUser::create([
            'name' => 'Admin',
            'first_surname' => 'Speed',
            'second_surname' => null,
            'gmail' => 'admin-cf4160-speed@example.com',
            'password' => bcrypt('password'),
            'last_access' => now(),
        ]);

        $client = Client::create([
            'name' => 'Cliente',
            'first_surname' => 'Speed',
            'second_surname' => null,
            'gmail' => 'cliente-cf4160-speed@example.com',
            'password' => bcrypt('password'),
            'provider' => 'local',
        ]);

        $product = Product::create([
            'category_id' => null,
            'supplier_id' => null,
            'name' => 'Producto speed',
            'sale_price' => 100,
            'purchase_price' => 50,
            'stock_current' => 5,
            'stock_minimum' => 1,
            'status' => 'active',
        ]);

        $sale = Sale::create([
            'invoice_number' => 'CF4-SPEED-01',
            'client_id' => $client->user_id,
            'sale_date' => now(),
            'payment_method' => 'cash',
            'status' => 'pending',
            'subtotal' => 100,
            'iva' => 0,
            'discount' => 0,
            'total' => 100,
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

        $startedAt = microtime(true);

        $response = $this->actingAs($admin, 'admin')
            ->postJson("/api/v1/admin/sales/{$sale->sale_id}/ready");

        $elapsed = microtime(true) - $startedAt;

        $response->assertOk();
        $this->assertLessThan(2.0, $elapsed, 'Ready-to-pickup should respond in under 2 seconds.');
        $this->assertSame('ready_to_pickup', $sale->fresh()->status);
    }

    public function test_complete_order_returns_within_two_seconds_without_waiting_for_mail(): void
    {
        Notification::fake();

        $admin = AdminUser::create([
            'name' => 'Admin',
            'first_surname' => 'Complete',
            'second_surname' => null,
            'gmail' => 'admin-cf4160-complete@example.com',
            'password' => bcrypt('password'),
            'last_access' => now(),
        ]);

        $client = Client::create([
            'name' => 'Cliente',
            'first_surname' => 'Complete',
            'second_surname' => null,
            'gmail' => 'cliente-cf4160-complete@example.com',
            'password' => bcrypt('password'),
            'provider' => 'local',
        ]);

        $product = Product::create([
            'category_id' => null,
            'supplier_id' => null,
            'name' => 'Producto complete',
            'sale_price' => 100,
            'purchase_price' => 50,
            'stock_current' => 5,
            'stock_minimum' => 1,
            'status' => 'active',
        ]);

        $sale = Sale::create([
            'invoice_number' => 'CF4-SPEED-02',
            'client_id' => $client->user_id,
            'sale_date' => now(),
            'payment_method' => 'cash',
            'status' => 'ready_to_pickup',
            'ready_at' => now(),
            'subtotal' => 100,
            'iva' => 0,
            'discount' => 0,
            'total' => 100,
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

        $startedAt = microtime(true);

        $response = $this->actingAs($admin, 'admin')
            ->postJson("/api/v1/admin/sales/{$sale->sale_id}/complete");

        $elapsed = microtime(true) - $startedAt;

        $response->assertOk();
        $this->assertLessThan(2.0, $elapsed, 'Order complete should respond in under 2 seconds.');
        $this->assertSame('completed', $sale->fresh()->status);
    }
}
