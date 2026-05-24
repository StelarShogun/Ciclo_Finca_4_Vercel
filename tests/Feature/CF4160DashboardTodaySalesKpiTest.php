<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\Client;
use App\Models\Sale;
use App\Support\AdminDateRange;
use App\Support\DashboardTodaySales;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon as SupportCarbon;
use Illuminate\Support\Facades\Cache;
use Tests\Support\InteractsWithMysqlTestDatabase;
use Tests\TestCase;

class CF4160DashboardTodaySalesKpiTest extends TestCase
{
    use InteractsWithMysqlTestDatabase;
    use RefreshDatabase;

    private AdminUser $admin;

    protected function setUp(): void
    {
        try {
            parent::setUp();
        } catch (\Throwable $e) {
            $this->markTestSkipped('Base de datos no disponible: '.$e->getMessage());
        }

        $this->skipUnlessMysqlTestDatabase(['sales', 'admins', 'client_table']);

        config(['app.timezone' => 'America/Costa_Rica']);
        Carbon::setTestNow(Carbon::parse('2026-05-22 15:00:00', 'America/Costa_Rica'));
        SupportCarbon::setTestNow(Carbon::parse('2026-05-22 15:00:00', 'America/Costa_Rica'));

        $this->admin = AdminUser::firstOrCreate(
            ['gmail' => 'admin-cf4160@example.com'],
            [
                'name' => 'Admin',
                'first_surname' => 'CF4160',
                'second_surname' => null,
                'password' => bcrypt('password'),
                'last_access' => now(),
            ]
        );
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        SupportCarbon::setTestNow();
        parent::tearDown();
    }

    public function test_today_kpi_sums_two_walk_in_sales_from_today(): void
    {
        $this->createWalkInSale('INV-CF160-A', 1500.50, AdminDateRange::now()->utc());
        $this->createWalkInSale('INV-CF160-B', 2499.50, AdminDateRange::now()->copy()->subHours(2)->utc());

        $this->assertSame(4000.0, DashboardTodaySales::sumToday());
    }

    public function test_yesterday_walk_in_sales_are_excluded_from_today_kpi(): void
    {
        $this->createWalkInSale('INV-CF160-TODAY', 1000, AdminDateRange::now()->utc());
        $this->createWalkInSale(
            'INV-CF160-YDAY',
            5000,
            AdminDateRange::now()->copy()->subDay()->setTime(10, 0)->utc(),
        );

        $this->assertSame(1000.0, DashboardTodaySales::sumToday());
    }

    public function test_pending_web_order_is_excluded_from_today_kpi(): void
    {
        $client = $this->createClient('cliente-cf160-pending@example.com');

        $this->createWalkInSale('INV-CF160-WALK', 800, AdminDateRange::now()->utc());

        Sale::create([
            'invoice_number' => 'INV-CF160-PENDING',
            'client_id' => $client->user_id,
            'seller_admin_id' => $this->admin->user_id,
            'subtotal' => 3000,
            'iva' => 0,
            'discount' => 0,
            'total' => 3000,
            'payment_method' => 'cash',
            'status' => 'pending',
            'sale_date' => AdminDateRange::now()->utc(),
            'order_source' => 'web_cart',
        ]);

        $this->assertSame(800.0, DashboardTodaySales::sumToday());
    }

    public function test_web_order_confirmed_today_is_included_even_if_placed_yesterday(): void
    {
        $client = $this->createClient('cliente-cf160-web@example.com');

        $this->createWalkInSale('INV-CF160-WALK', 500, AdminDateRange::now()->utc());

        $placedYesterday = AdminDateRange::now()->copy()->subDay()->setTime(18, 0)->utc();
        $confirmedToday = AdminDateRange::now()->copy()->setTime(14, 30)->utc();

        Sale::query()->create([
            'invoice_number' => 'INV-CF160-WEB',
            'client_id' => $client->user_id,
            'seller_admin_id' => $this->admin->user_id,
            'subtotal' => 2200,
            'iva' => 0,
            'discount' => 0,
            'total' => 2200,
            'payment_method' => 'cash',
            'status' => 'completed',
            'sale_date' => $placedYesterday,
            'order_source' => 'web_cart',
            'updated_at' => $confirmedToday,
            'created_at' => $placedYesterday,
        ]);

        $this->assertSame(2700.0, DashboardTodaySales::sumToday());
    }

    public function test_dashboard_data_endpoint_returns_formatted_today_sales_total(): void
    {
        $this->createWalkInSale('INV-CF160-API-1', 1200, AdminDateRange::now()->utc());
        $this->createWalkInSale('INV-CF160-API-2', 800, AdminDateRange::now()->utc());

        $response = $this->actingAs($this->admin, 'admin')
            ->getJson(route('dashboard.data'));

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('todaySales', 2000);
        $response->assertJsonStructure(['salesTrend']);
    }

    public function test_dashboard_page_shows_today_sales_with_currency_symbol(): void
    {
        Cache::flush();

        $this->createWalkInSale('INV-CF160-VIEW', 12500, AdminDateRange::now()->utc());

        $response = $this->actingAs($this->admin, 'admin')->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('id="today-sales"', false);
        $response->assertSee('₡12.500', false);
    }

    private function createClient(string $email): Client
    {
        return Client::create([
            'name' => 'Cliente',
            'first_surname' => 'CF4160',
            'second_surname' => null,
            'gmail' => $email,
            'password' => bcrypt('password'),
            'provider' => 'local',
        ]);
    }

    private function createWalkInSale(string $invoiceNumber, float $total, Carbon $saleDateUtc): Sale
    {
        return Sale::create([
            'invoice_number' => $invoiceNumber,
            'client_id' => null,
            'seller_admin_id' => $this->admin->user_id,
            'subtotal' => $total,
            'iva' => 0,
            'discount' => 0,
            'total' => $total,
            'payment_method' => 'cash',
            'status' => 'completed',
            'sale_date' => $saleDateUtc,
            'order_source' => 'walk_in',
        ]);
    }
}
