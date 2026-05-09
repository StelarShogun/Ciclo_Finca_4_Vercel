<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Notifications\ProductReviewReminderNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ProductReviewReminderNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            $this->markTestSkipped('Este test requiere MySQL.');
        }

        foreach (['sales', 'sale_items', 'client_table', 'notifications', 'products'] as $table) {
            if (! Schema::hasTable($table)) {
                $this->markTestSkipped('Tabla requerida no existe: '.$table);
            }
        }

        Config::set('mail.default', 'array');
    }

    public function test_product_review_reminder_persists_database_notification_with_action_url(): void
    {
        $client = Client::create([
            'name' => 'Cliente',
            'first_surname' => 'Reseña',
            'second_surname' => null,
            'gmail' => 'cliente-resena@example.com',
            'password' => bcrypt('password'),
            'provider' => 'local',
        ]);

        $product = Product::create([
            'category_id' => null,
            'supplier_id' => null,
            'name' => 'Producto reseña',
            'description' => null,
            'image' => 'default.png',
            'sale_price' => 100,
            'purchase_price' => 50,
            'stock_current' => 5,
            'stock_minimum' => 1,
            'status' => 'active',
        ]);

        $sale = Sale::create([
            'invoice_number' => 'CF4-9901',
            'client_id' => $client->user_id,
            'sale_date' => now(),
            'payment_method' => 'cash',
            'status' => 'completed',
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

        $client->notify(new ProductReviewReminderNotification($sale));

        $this->assertDatabaseHas('notifications', [
            'notifiable_type' => Client::class,
            'notifiable_id' => $client->user_id,
            'type' => ProductReviewReminderNotification::class,
        ]);

        $row = $client->notifications()->first();
        $this->assertNotNull($row);
        $data = $row->data;
        $this->assertArrayHasKey('action_url', $data);
        $this->assertStringContainsString('historial', (string) $data['action_url']);
        $this->assertSame('Ir al historial de compras', $data['action_label']);
    }
}
