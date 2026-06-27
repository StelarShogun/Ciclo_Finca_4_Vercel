<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Notifications\OrderCancelledNotification;
use App\Notifications\OrderCompletedNotification;
use App\Notifications\OrderReadyToPickupNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class ClientNotificationsMarkReadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Config::set('mail.default', 'array');
    }

    public function test_notifications_page_marks_unread_as_read(): void
    {
        $client = Client::create([
            'name' => 'Cliente',
            'first_surname' => 'Leido',
            'second_surname' => null,
            'gmail' => 'cliente-leido@example.com',
            'password' => bcrypt('password'),
            'provider' => 'local',
        ]);

        $product = Product::create([
            'category_id' => null,
            'supplier_id' => null,
            'name' => 'Producto',
            'description' => null,
            'image' => 'default.png',
            'sale_price' => 100,
            'purchase_price' => 50,
            'stock_current' => 5,
            'stock_minimum' => 1,
            'status' => 'active',
        ]);

        $sale = Sale::create([
            'invoice_number' => 'CF4-7701',
            'client_id' => $client->user_id,
            'sale_date' => now(),
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

        $this->assertSame(1, $client->unreadNotifications()->count());

        $response = $this->actingAs($client, 'clients')->get(route('clients.notifications'));
        $response->assertOk();

        $client->refresh();
        $this->assertSame(0, $client->unreadNotifications()->count());
        $this->assertNotNull($client->notifications()->first()->read_at);
    }

    public function test_client_can_mark_own_notification_as_read(): void
    {
        $client = Client::create([
            'name' => 'Cliente',
            'first_surname' => 'Owner',
            'second_surname' => null,
            'gmail' => 'cliente-notification-owner@example.com',
            'password' => bcrypt('password'),
            'provider' => 'local',
        ]);

        $notificationId = (string) Str::uuid();
        DB::table('notifications')->insert([
            'id' => $notificationId,
            'type' => 'test',
            'notifiable_type' => Client::class,
            'notifiable_id' => $client->user_id,
            'data' => json_encode(['message' => 'Prueba']),
            'read_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($client, 'clients')
            ->postJson(route('clients.notifications.read', ['notification' => $notificationId]))
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertNotNull(DB::table('notifications')->where('id', $notificationId)->value('read_at'));
    }

    public function test_client_cannot_mark_other_client_notification_as_read(): void
    {
        $client = Client::create([
            'name' => 'Cliente',
            'first_surname' => 'Owner',
            'second_surname' => null,
            'gmail' => 'cliente-notification-owner-2@example.com',
            'password' => bcrypt('password'),
            'provider' => 'local',
        ]);

        $otherClient = Client::create([
            'name' => 'Otro',
            'first_surname' => 'Cliente',
            'second_surname' => null,
            'gmail' => 'cliente-notification-other@example.com',
            'password' => bcrypt('password'),
            'provider' => 'local',
        ]);

        $notificationId = (string) Str::uuid();
        DB::table('notifications')->insert([
            'id' => $notificationId,
            'type' => 'test',
            'notifiable_type' => Client::class,
            'notifiable_id' => $otherClient->user_id,
            'data' => json_encode(['message' => 'Prueba']),
            'read_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($client, 'clients')
            ->postJson(route('clients.notifications.read', ['notification' => $notificationId]))
            ->assertOk()
            ->assertJson(['success' => false]);

        $this->assertNull(DB::table('notifications')->where('id', $notificationId)->value('read_at'));
    }

    public function test_client_mark_all_read_only_updates_own_notifications(): void
    {
        $client = Client::create([
            'name' => 'Cliente',
            'first_surname' => 'All',
            'second_surname' => null,
            'gmail' => 'cliente-notification-all@example.com',
            'password' => bcrypt('password'),
            'provider' => 'local',
        ]);

        $otherClient = Client::create([
            'name' => 'Otro',
            'first_surname' => 'All',
            'second_surname' => null,
            'gmail' => 'cliente-notification-all-other@example.com',
            'password' => bcrypt('password'),
            'provider' => 'local',
        ]);

        $ownNotificationId = (string) Str::uuid();
        $otherNotificationId = (string) Str::uuid();
        DB::table('notifications')->insert([
            [
                'id' => $ownNotificationId,
                'type' => 'test',
                'notifiable_type' => Client::class,
                'notifiable_id' => $client->user_id,
                'data' => json_encode(['message' => 'Propia']),
                'read_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => $otherNotificationId,
                'type' => 'test',
                'notifiable_type' => Client::class,
                'notifiable_id' => $otherClient->user_id,
                'data' => json_encode(['message' => 'Ajena']),
                'read_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $this->actingAs($client, 'clients')
            ->postJson(route('clients.notifications.read-all'))
            ->assertOk()
            ->assertJson(['success' => true, 'updated' => 1]);

        $this->assertNotNull(DB::table('notifications')->where('id', $ownNotificationId)->value('read_at'));
        $this->assertNull(DB::table('notifications')->where('id', $otherNotificationId)->value('read_at'));
    }

    public function test_notifications_heartbeat_returns_ready_to_pickup_toast_payload(): void
    {
        $client = Client::create([
            'name' => 'Cliente',
            'first_surname' => 'Toast',
            'second_surname' => null,
            'gmail' => 'cliente-toast@example.com',
            'password' => bcrypt('password'),
            'provider' => 'local',
        ]);

        $product = Product::create([
            'category_id' => null,
            'supplier_id' => null,
            'name' => 'Producto',
            'description' => null,
            'image' => 'default.png',
            'sale_price' => 100,
            'purchase_price' => 50,
            'stock_current' => 5,
            'stock_minimum' => 1,
            'status' => 'active',
        ]);

        $sale = Sale::create([
            'invoice_number' => 'CF4-7702',
            'client_id' => $client->user_id,
            'sale_date' => now(),
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

        $response = $this->actingAs($client, 'clients')->getJson(route('clients.notifications.heartbeat'));

        $response->assertOk()
            ->assertJsonPath('unread_count', 1)
            ->assertJsonCount(1, 'toasts')
            ->assertJsonPath('toasts.0.kind', 'ready_to_pickup')
            ->assertJsonPath('toasts.0.title', '¡Listo para recoger!')
            ->assertJsonStructure([
                'unread_count',
                'toasts' => [
                    ['id', 'kind', 'title', 'message', 'action_url', 'action_label'],
                ],
            ]);

        $message = (string) $response->json('toasts.0.message');
        $this->assertStringContainsString('CF4-7702', $message);
        $this->assertStringContainsString('listo para recoger', $message);
        $this->assertNotEmpty($response->json('toasts.0.action_url'));
    }

    public function test_notifications_heartbeat_returns_completed_notification(): void
    {
        $client = Client::create([
            'name' => 'Cliente',
            'first_surname' => 'Completado',
            'second_surname' => null,
            'gmail' => 'cliente-completed@example.com',
            'password' => bcrypt('password'),
            'provider' => 'local',
        ]);

        $product = Product::create([
            'category_id' => null, 'supplier_id' => null,
            'name' => 'Producto', 'description' => null, 'image' => 'default.png',
            'sale_price' => 100, 'purchase_price' => 50, 'stock_current' => 5,
            'stock_minimum' => 1, 'status' => 'active',
        ]);

        $sale = Sale::create([
            'invoice_number' => 'CF4-7703',
            'client_id' => $client->user_id,
            'sale_date' => now(),
            'payment_method' => 'cash',
            'status' => 'completed',
            'subtotal' => 100, 'iva' => 0, 'discount' => 0, 'total' => 100,
            'order_source' => 'web_cart', 'notes' => null,
        ]);

        SaleItem::create([
            'sale_id' => $sale->sale_id, 'product_id' => $product->product_id,
            'quantity' => 1, 'unit_price' => 100, 'unit_discount' => 0, 'total' => 100,
        ]);

        $client->notify(new OrderCompletedNotification($sale));

        $response = $this->actingAs($client, 'clients')->getJson(route('clients.notifications.heartbeat'));

        $response->assertOk()
            ->assertJsonPath('toasts.0.kind', 'completed')
            ->assertJsonPath('toasts.0.title', '¡Pedido confirmado!')
            ->assertJsonStructure(['invoice_count', 'unseen_history', 'revision']);

        $this->assertStringContainsString('CF4-7703', (string) $response->json('toasts.0.message'));
    }

    public function test_notifications_heartbeat_returns_cancelled_notification(): void
    {
        $client = Client::create([
            'name' => 'Cliente',
            'first_surname' => 'Cancelado',
            'second_surname' => null,
            'gmail' => 'cliente-cancelado@example.com',
            'password' => bcrypt('password'),
            'provider' => 'local',
        ]);

        $product = Product::create([
            'category_id' => null, 'supplier_id' => null,
            'name' => 'Producto', 'description' => null, 'image' => 'default.png',
            'sale_price' => 100, 'purchase_price' => 50, 'stock_current' => 5,
            'stock_minimum' => 1, 'status' => 'active',
        ]);

        $sale = Sale::create([
            'invoice_number' => 'CF4-7704',
            'client_id' => $client->user_id,
            'sale_date' => now(),
            'payment_method' => 'cash',
            'status' => 'cancelled',
            'subtotal' => 100, 'iva' => 0, 'discount' => 0, 'total' => 100,
            'order_source' => 'web_cart', 'notes' => null,
        ]);

        SaleItem::create([
            'sale_id' => $sale->sale_id, 'product_id' => $product->product_id,
            'quantity' => 1, 'unit_price' => 100, 'unit_discount' => 0, 'total' => 100,
        ]);

        $client->notify(new OrderCancelledNotification(
            $sale,
            'Pedido vencido',
            now(),
        ));

        $response = $this->actingAs($client, 'clients')->getJson(route('clients.notifications.heartbeat'));

        $response->assertOk()
            ->assertJsonPath('toasts.0.kind', 'cancelled')
            ->assertJsonPath('toasts.0.title', 'Pedido cancelado');

        $this->assertStringContainsString('CF4-7704', (string) $response->json('toasts.0.message'));
        $this->assertStringContainsString('canceladas', (string) $response->json('toasts.0.action_url'));
    }
}
