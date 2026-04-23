<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\Client;
use App\Models\Sale;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * CF4-33 — historial de compras por cliente (CP33-01 a CP33-04).
 *
 * Requiere MySQL y tablas sales, client_table.
 */
class ClientPurchaseHistoryReportTest extends TestCase
{
    use RefreshDatabase;

    protected AdminUser $adminUser;

    protected function setUp(): void
    {
        try {
            parent::setUp();
        } catch (\Throwable $e) {
            $this->markTestSkipped('Base de datos no disponible: '.$e->getMessage());
        }
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            $this->markTestSkipped('ClientPurchaseHistoryReportTest requiere MySQL.');
        }
        if (! Schema::hasTable('sales') || ! Schema::hasTable('client_table')) {
            $this->markTestSkipped('Faltan tablas necesarias.');
        }

        Carbon::setTestNow(Carbon::parse('2026-08-20 14:00:00', config('app.timezone')));

        $this->adminUser = AdminUser::create([
            'name' => 'Admin',
            'first_surname' => 'CF33',
            'second_surname' => null,
            'gmail' => 'admin-cf33@example.com',
            'password' => bcrypt('password'),
            'last_access' => now(),
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_guest_cannot_access_client_purchases_table(): void
    {
        $this->getJson(route('admin.reports.client-purchases.table', [
            'period' => '30d',
            'sort' => 'total_purchased',
            'dir' => 'desc',
        ]))->assertUnauthorized();
    }

    public function test_guest_cannot_access_client_purchases_show(): void
    {
        $this->get(route('admin.reports.client-purchases.show', ['client' => 1]))
            ->assertRedirect();
    }

    /** CP33-01 — totales correctos al buscar por correo. */
    public function test_cp33_01_table_totals_match_sales_for_email_search(): void
    {
        $client = Client::create([
            'name' => 'María',
            'first_surname' => 'Frecuente',
            'second_surname' => null,
            'gmail' => 'maria.frecuente.cf33@example.com',
            'password' => bcrypt('password'),
            'provider' => 'local',
        ]);

        foreach ([100.0, 200.0, 100.0] as $i => $total) {
            Sale::create([
                'invoice_number' => 'INV-CF33-A-'.str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT),
                'client_id' => $client->user_id,
                'seller_admin_id' => $this->adminUser->user_id,
                'subtotal' => $total,
                'iva' => 0,
                'discount' => 0,
                'total' => $total,
                'payment_method' => 'cash',
                'payment_reference' => null,
                'status' => 'completed',
                'notes' => null,
                'sale_date' => now()->subDays(5),
                'buyer_name' => null,
                'buyer_email' => null,
                'order_source' => 'web_cart',
            ]);
        }

        $response = $this->actingAs($this->adminUser, 'admin')
            ->getJson(route('admin.reports.client-purchases.table', [
                'period' => '30d',
                'sort' => 'total_purchased',
                'dir' => 'desc',
                'q' => 'maria.frecuente.cf33@',
            ]));

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $rows = $response->json('rows');
        $this->assertCount(1, $rows);
        $this->assertEquals(400, $rows[0]['total_purchased']);
        $this->assertSame(3, $rows[0]['orders_count']);
        $this->assertEqualsWithDelta(133.33, (float) $rows[0]['avg_ticket'], 0.02);
    }

    /** CP33-02 — ordenar por ticket promedio. */
    public function test_cp33_02_sort_by_avg_ticket_desc(): void
    {
        $low = Client::create([
            'name' => 'Low',
            'first_surname' => 'Avg',
            'second_surname' => null,
            'gmail' => 'low-avg-cf33@example.com',
            'password' => bcrypt('password'),
            'provider' => 'local',
        ]);
        $high = Client::create([
            'name' => 'High',
            'first_surname' => 'Avg',
            'second_surname' => null,
            'gmail' => 'high-avg-cf33@example.com',
            'password' => bcrypt('password'),
            'provider' => 'local',
        ]);

        Sale::create([
            'invoice_number' => 'INV-CF33-B-001',
            'client_id' => $low->user_id,
            'seller_admin_id' => $this->adminUser->user_id,
            'subtotal' => 100,
            'iva' => 0,
            'discount' => 0,
            'total' => 100,
            'payment_method' => 'cash',
            'payment_reference' => null,
            'status' => 'completed',
            'notes' => null,
            'sale_date' => now()->subDays(2),
            'buyer_name' => null,
            'buyer_email' => null,
            'order_source' => 'web_cart',
        ]);
        Sale::create([
            'invoice_number' => 'INV-CF33-B-002',
            'client_id' => $low->user_id,
            'seller_admin_id' => $this->adminUser->user_id,
            'subtotal' => 100,
            'iva' => 0,
            'discount' => 0,
            'total' => 100,
            'payment_method' => 'cash',
            'payment_reference' => null,
            'status' => 'completed',
            'notes' => null,
            'sale_date' => now()->subDays(1),
            'buyer_name' => null,
            'buyer_email' => null,
            'order_source' => 'web_cart',
        ]);

        Sale::create([
            'invoice_number' => 'INV-CF33-B-003',
            'client_id' => $high->user_id,
            'seller_admin_id' => $this->adminUser->user_id,
            'subtotal' => 500,
            'iva' => 0,
            'discount' => 0,
            'total' => 500,
            'payment_method' => 'cash',
            'payment_reference' => null,
            'status' => 'completed',
            'notes' => null,
            'sale_date' => now()->subDays(3),
            'buyer_name' => null,
            'buyer_email' => null,
            'order_source' => 'web_cart',
        ]);

        $response = $this->actingAs($this->adminUser, 'admin')
            ->getJson(route('admin.reports.client-purchases.table', [
                'period' => '30d',
                'sort' => 'avg_ticket',
                'dir' => 'desc',
            ]));

        $response->assertOk();
        $rows = $response->json('rows');
        $this->assertCount(2, $rows);
        $this->assertSame($high->user_id, $rows[0]['client_id']);
        $this->assertEquals(500, $rows[0]['avg_ticket']);
        $this->assertSame($low->user_id, $rows[1]['client_id']);
        $this->assertEquals(100, $rows[1]['avg_ticket']);
    }

    /** CP33-03 — detalle de órdenes del periodo. */
    public function test_cp33_03_client_orders_lists_completed_sales_in_period(): void
    {
        $client = Client::create([
            'name' => 'Detalle',
            'first_surname' => 'Cliente',
            'second_surname' => null,
            'gmail' => 'detalle-cf33@example.com',
            'password' => bcrypt('password'),
            'provider' => 'local',
        ]);

        Sale::create([
            'invoice_number' => 'INV-CF33-C-001',
            'client_id' => $client->user_id,
            'seller_admin_id' => $this->adminUser->user_id,
            'subtotal' => 50,
            'iva' => 0,
            'discount' => 0,
            'total' => 50,
            'payment_method' => 'cash',
            'payment_reference' => null,
            'status' => 'completed',
            'notes' => null,
            'sale_date' => now()->subDays(4),
            'buyer_name' => null,
            'buyer_email' => null,
            'order_source' => 'web_cart',
        ]);

        $this->actingAs($this->adminUser, 'admin')
            ->getJson(route('admin.reports.client-purchases.orders', [
                'client' => $client->user_id,
                'period' => '30d',
            ]))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('orders.0.invoice_number', 'INV-CF33-C-001')
            ->assertJsonPath('orders.0.total', 50);
    }

    /** CP33-04 — cliente sin compras en el periodo no aparece. */
    public function test_cp33_04_client_without_sales_in_period_not_in_results(): void
    {
        $withSales = Client::create([
            'name' => 'Con',
            'first_surname' => 'Compras',
            'second_surname' => null,
            'gmail' => 'con-compras-cf33@example.com',
            'password' => bcrypt('password'),
            'provider' => 'local',
        ]);
        Client::create([
            'name' => 'Sin',
            'first_surname' => 'Compras',
            'second_surname' => null,
            'gmail' => 'sin-compras-cf33@example.com',
            'password' => bcrypt('password'),
            'provider' => 'local',
        ]);

        Sale::create([
            'invoice_number' => 'INV-CF33-D-001',
            'client_id' => $withSales->user_id,
            'seller_admin_id' => $this->adminUser->user_id,
            'subtotal' => 10,
            'iva' => 0,
            'discount' => 0,
            'total' => 10,
            'payment_method' => 'cash',
            'payment_reference' => null,
            'status' => 'completed',
            'notes' => null,
            'sale_date' => now()->subDays(1),
            'buyer_name' => null,
            'buyer_email' => null,
            'order_source' => 'web_cart',
        ]);

        $response = $this->actingAs($this->adminUser, 'admin')
            ->getJson(route('admin.reports.client-purchases.table', [
                'period' => '30d',
                'sort' => 'total_purchased',
                'dir' => 'desc',
                'q' => 'sin-compras-cf33@example.com',
            ]));

        $response->assertOk();
        $this->assertCount(0, $response->json('rows'));
    }

    /** Búsqueda: cadenas con apariencia de SQL/HTML no rompen la petición (consulta parametrizada + JSON seguro). */
    public function test_table_search_malicious_looking_string_returns_ok_without_crash(): void
    {
        $payload = "'; DROP TABLE sales; -- <script>alert(1)</script>";

        $response = $this->actingAs($this->adminUser, 'admin')
            ->getJson(route('admin.reports.client-purchases.table', [
                'period' => '30d',
                'sort' => 'total_purchased',
                'dir' => 'desc',
                'q' => $payload,
            ]));

        $response->assertOk()->assertJsonPath('success', true);
        $this->assertIsArray($response->json('rows'));
        $this->assertSame($payload, $response->json('q'));
    }
}
