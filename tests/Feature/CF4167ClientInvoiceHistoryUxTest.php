<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\Client;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Notifications\OrderCompletedNotification;
use App\Notifications\OrderReadyToPickupNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class CF4167ClientInvoiceHistoryUxTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Config::set('mail.default', 'array');
        Config::set('sales.ready_to_pickup_expiration_hours', 72);
    }

    public function test_mark_ready_to_pickup_notification_links_to_facturas_tab(): void
    {
        [$admin, $client, $sale] = $this->seedReadyToPickupScenario('pending');

        $this->actingAs($admin, 'admin');

        $this->patchJson(route('admin.orders.ready-to-pickup', $sale->sale_id))
            ->assertOk()
            ->assertJsonPath('success', true);

        $row = $client->fresh()->notifications()->first();
        $this->assertNotNull($row);
        $this->assertSame(OrderReadyToPickupNotification::class, $row->type);

        $data = $row->data;
        $this->assertNotEmpty($data['message'] ?? null);
        $this->assertStringStartsWith('/', (string) ($data['action_url'] ?? ''));
        $this->assertStringContainsString('facturas', (string) $data['action_url']);
        $this->assertSame('Ver en Facturas', $data['action_label'] ?? null);
    }

    public function test_complete_order_sets_unseen_history_and_sends_completed_notification(): void
    {
        [$admin, $client, $sale] = $this->seedReadyToPickupScenario('ready_to_pickup');
        $sale->update(['ready_at' => now()]);

        $this->actingAs($admin, 'admin');

        $this->postJson(route('sales.complete', $sale->sale_id))
            ->assertOk()
            ->assertJsonPath('success', true);

        $sale->refresh();
        $this->assertSame('completed', $sale->status);
        $this->assertNull($sale->client_history_seen_at);
        $this->assertGreaterThan(0, Sale::countUnseenInClientHistory((int) $client->user_id));

        $completedNotification = $client->fresh()->notifications()
            ->where('type', OrderCompletedNotification::class)
            ->first();
        $this->assertNotNull($completedNotification);

        $data = $completedNotification->data;
        $this->assertNotEmpty($data['message'] ?? null);
        $this->assertStringContainsString('historial', (string) ($data['action_url'] ?? ''));
        $this->assertSame('Ver en Historial de compras', $data['action_label'] ?? null);
    }

    public function test_visiting_historial_tab_clears_unseen_history_badge(): void
    {
        [$admin, $client, $sale] = $this->seedReadyToPickupScenario('ready_to_pickup');
        $sale->update([
            'ready_at' => now(),
            'status' => 'completed',
            'client_history_seen_at' => null,
        ]);

        $this->assertGreaterThan(0, Sale::countUnseenInClientHistory((int) $client->user_id));

        $this->actingAs($client, 'clients')
            ->get(route('clients.invoices', ['tab' => 'historial']))
            ->assertOk();

        $this->assertSame(0, Sale::countUnseenInClientHistory((int) $client->user_id));
        $this->assertNotNull($sale->fresh()->client_history_seen_at);
    }

    public function test_invoices_heartbeat_returns_active_count_and_unseen_history(): void
    {
        [$admin, $client, $sale] = $this->seedReadyToPickupScenario('ready_to_pickup');
        $sale->update([
            'ready_at' => now(),
            'status' => 'completed',
            'client_history_seen_at' => null,
        ]);

        Sale::create([
            'invoice_number' => 'CF4-8802',
            'client_id' => $client->user_id,
            'sale_date' => now(),
            'payment_method' => 'cash',
            'status' => 'pending',
            'subtotal' => 50,
            'iva' => 0,
            'discount' => 0,
            'total' => 50,
            'order_source' => 'web_cart',
            'notes' => null,
        ]);

        $response = $this->actingAs($client, 'clients')
            ->getJson(route('clients.invoices.heartbeat'));

        $response->assertOk()
            ->assertJsonPath('count', 1)
            ->assertJsonPath('unseen_history', 1)
            ->assertJsonStructure(['revision']);
    }

    public function test_invoices_heartbeat_revision_changes_when_status_updates(): void
    {
        [, $client, $sale] = $this->seedReadyToPickupScenario('pending');

        $before = $this->actingAs($client, 'clients')
            ->getJson(route('clients.invoices.heartbeat'))
            ->assertOk()
            ->json('revision');

        $this->assertNotEmpty($before);

        $sale->update([
            'status' => 'ready_to_pickup',
            'ready_at' => now(),
        ]);

        $after = $this->actingAs($client, 'clients')
            ->getJson(route('clients.invoices.heartbeat'))
            ->assertOk()
            ->json('revision');

        $this->assertNotSame($before, $after);
    }

    /**
     * @return array{0: AdminUser, 1: Client, 2: Sale}
     */
    private function seedReadyToPickupScenario(string $status): array
    {
        $admin = AdminUser::create([
            'name' => 'Admin',
            'first_surname' => 'CF4167',
            'second_surname' => null,
            'gmail' => 'admin-cf4167-'.uniqid().'@example.com',
            'password' => bcrypt('password'),
            'last_access' => now(),
        ]);

        $client = Client::create([
            'name' => 'Cliente',
            'first_surname' => 'Historial',
            'second_surname' => null,
            'gmail' => 'cliente-cf4167-'.uniqid().'@example.com',
            'password' => bcrypt('password'),
            'provider' => 'local',
        ]);

        $product = Product::create([
            'category_id' => null,
            'supplier_id' => null,
            'name' => 'Producto CF4167',
            'description' => null,
            'image' => 'default.png',
            'sale_price' => 100,
            'purchase_price' => 50,
            'stock_current' => 5,
            'stock_minimum' => 1,
            'status' => 'active',
        ]);

        $sale = Sale::create([
            'invoice_number' => 'CF4-'.random_int(8800, 8899),
            'client_id' => $client->user_id,
            'sale_date' => now(),
            'payment_method' => 'cash',
            'status' => $status === 'ready_to_pickup' ? 'ready_to_pickup' : 'pending',
            'ready_at' => $status === 'ready_to_pickup' ? now() : null,
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

        return [$admin, $client, $sale];
    }
}
