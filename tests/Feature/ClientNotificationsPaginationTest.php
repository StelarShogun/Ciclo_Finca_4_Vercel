<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Notifications\OrderReadyToPickupNotification;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * CF4-138 — Client notifications list pagination.
 */
class ClientNotificationsPaginationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            $this->markTestSkipped('Este test requiere MySQL.');
        }

        try {
            foreach (['sales', 'sale_items', 'client_table', 'notifications', 'products'] as $table) {
                if (! Schema::hasTable($table)) {
                    $this->markTestSkipped('Tabla requerida no existe: '.$table);
                }
            }
        } catch (\Throwable $e) {
            $this->markTestSkipped('Base de datos no disponible para tests: '.$e->getMessage());
        }

        Config::set('mail.default', 'array');
    }

    private function createClientWithNotifications(int $notificationCount): Client
    {
        $client = Client::create([
            'name' => 'Notif',
            'first_surname' => 'Pager',
            'second_surname' => null,
            'gmail' => 'notif-pager-'.uniqid('', true).'@example.com',
            'password' => bcrypt('password'),
            'provider' => 'local',
        ]);

        $product = Product::create([
            'category_id' => null,
            'supplier_id' => null,
            'name' => 'Producto notif paginación',
            'description' => null,
            'image' => 'default.png',
            'sale_price' => 100,
            'purchase_price' => 50,
            'stock_current' => 5,
            'stock_minimum' => 1,
            'status' => 'active',
        ]);

        for ($i = 0; $i < $notificationCount; $i++) {
            $sale = Sale::create([
                'invoice_number' => 'NT-PG-'.uniqid('', true),
                'client_id' => $client->user_id,
                'sale_date' => now()->subMinutes($notificationCount - $i),
                'payment_method' => 'cash',
                'status' => 'ready_to_pickup',
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

            $client->notify(new OrderReadyToPickupNotification($sale));
        }

        return $client;
    }

    private function cleanupClient(Client $client): void
    {
        Sale::query()->where('client_id', $client->user_id)->delete();
        $client->notifications()->delete();
        $client->delete();
    }

    public function test_notifications_page_uses_shared_pagination_component(): void
    {
        $client = $this->createClientWithNotifications(12);

        try {
            $this->actingAs($client, 'clients');

            $this->get(route('clients.notifications', ['per_page' => 10]))
                ->assertOk()
                ->assertInertia(fn (Assert $page) => $page
                    ->component('Client/Notifications/Index', false)
                    ->where('pagination.total', 12)
                    ->where('pagination.from', 1)
                    ->where('pagination.to', 10)
                    ->where('pagination.lastPage', 2)
                );

            $this->get(route('clients.notifications', ['per_page' => 10, 'page' => 2]))
                ->assertOk()
                ->assertInertia(fn (Assert $page) => $page
                    ->component('Client/Notifications/Index', false)
                    ->where('pagination.from', 11)
                    ->where('pagination.to', 12)
                );
        } finally {
            $this->cleanupClient($client);
        }
    }
}
