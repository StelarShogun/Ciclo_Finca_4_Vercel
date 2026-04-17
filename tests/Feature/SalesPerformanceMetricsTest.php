<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\Sale;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * CF4-24 — métricas de ventas por rango (completadas + ingresos + comparativa).
 */
class SalesPerformanceMetricsTest extends TestCase
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
            $this->markTestSkipped('SalesPerformanceMetricsTest requiere MySQL.');
        }
        if (! Schema::hasTable('sales')) {
            $this->markTestSkipped('Falta tabla sales.');
        }

        Carbon::setTestNow(Carbon::parse('2026-06-15 14:00:00', 'UTC'));

        $this->adminUser = AdminUser::create([
            'name' => 'Admin',
            'first_surname' => 'Metrics',
            'second_surname' => null,
            'gmail' => 'admin-metrics-cf24@example.com',
            'password' => bcrypt('password'),
            'last_access' => now(),
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_guest_cannot_access_sales_metrics(): void
    {
        $this->getJson(route('admin.reports.sales.metrics', ['preset' => 'today']))
            ->assertUnauthorized();
    }

    public function test_metrics_today_sums_only_completed_sales_in_window(): void
    {
        Sale::create([
            'invoice_number' => 'INV-CF24-A',
            'customer_id' => null,
            'client_id' => null,
            'seller_id' => null,
            'seller_admin_id' => $this->adminUser->user_id,
            'subtotal' => 100,
            'iva' => 0,
            'discount' => 0,
            'total' => 100,
            'payment_method' => 'cash',
            'payment_reference' => null,
            'status' => 'completed',
            'notes' => null,
            'sale_date' => now(),
            'buyer_name' => null,
            'buyer_email' => null,
            'order_source' => 'walk_in',
        ]);
        Sale::create([
            'invoice_number' => 'INV-CF24-B',
            'customer_id' => null,
            'client_id' => null,
            'seller_id' => null,
            'seller_admin_id' => $this->adminUser->user_id,
            'subtotal' => 50,
            'iva' => 0,
            'discount' => 0,
            'total' => 50,
            'payment_method' => 'cash',
            'payment_reference' => null,
            'status' => 'completed',
            'notes' => null,
            'sale_date' => now(),
            'buyer_name' => null,
            'buyer_email' => null,
            'order_source' => 'walk_in',
        ]);
        Sale::create([
            'invoice_number' => 'INV-CF24-PENDING',
            'customer_id' => null,
            'client_id' => null,
            'seller_id' => null,
            'seller_admin_id' => $this->adminUser->user_id,
            'subtotal' => 999,
            'iva' => 0,
            'discount' => 0,
            'total' => 999,
            'payment_method' => 'cash',
            'payment_reference' => null,
            'status' => 'pending',
            'notes' => null,
            'sale_date' => now(),
            'buyer_name' => null,
            'buyer_email' => null,
            'order_source' => 'walk_in',
        ]);
        Sale::create([
            'invoice_number' => 'INV-CF24-OLD',
            'customer_id' => null,
            'client_id' => null,
            'seller_id' => null,
            'seller_admin_id' => $this->adminUser->user_id,
            'subtotal' => 1000,
            'iva' => 0,
            'discount' => 0,
            'total' => 1000,
            'payment_method' => 'cash',
            'payment_reference' => null,
            'status' => 'completed',
            'notes' => null,
            'sale_date' => now()->subDay(),
            'buyer_name' => null,
            'buyer_email' => null,
            'order_source' => 'walk_in',
        ]);

        $response = $this->actingAs($this->adminUser, 'admin')
            ->getJson(route('admin.reports.sales.metrics', ['preset' => 'today']));

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('current_metrics.sales_count', 2);
        $response->assertJsonPath('current_metrics.revenue', 150);
        $response->assertJsonPath('previous_metrics.sales_count', 1);
        $response->assertJsonPath('previous_metrics.revenue', 1000);
        $response->assertJsonPath('comparison.revenue_change_percent', -85);
        $response->assertJsonPath('comparison.revenue_trend', 'down');
        $response->assertJsonPath('comparison.sales_count_change_percent', 100);
        $response->assertJsonPath('comparison.revenue_percent_not_comparable', false);
    }

    public function test_comparison_marks_revenue_percent_not_comparable_when_prior_revenue_is_zero(): void
    {
        Sale::create([
            'invoice_number' => 'INV-CF24-TODAY',
            'customer_id' => null,
            'client_id' => null,
            'seller_id' => null,
            'seller_admin_id' => $this->adminUser->user_id,
            'subtotal' => 200,
            'iva' => 0,
            'discount' => 0,
            'total' => 200,
            'payment_method' => 'cash',
            'payment_reference' => null,
            'status' => 'completed',
            'notes' => null,
            'sale_date' => now(),
            'buyer_name' => null,
            'buyer_email' => null,
            'order_source' => 'walk_in',
        ]);

        $response = $this->actingAs($this->adminUser, 'admin')
            ->getJson(route('admin.reports.sales.metrics', ['preset' => 'today']));

        $response->assertOk();
        $response->assertJsonPath('previous_metrics.revenue', 0);
        $response->assertJsonPath('current_metrics.revenue', 200);
        $response->assertJsonPath('comparison.revenue_change_percent', null);
        $response->assertJsonPath('comparison.revenue_percent_not_comparable', true);
        $response->assertJsonPath('comparison.revenue_trend', 'up');
    }
}
