<?php

namespace Tests\Feature\Api;

use App\Models\AdminUser;
use App\Models\Category;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Supplier;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * API v1 admin reports: auth y previsualización JSON de desempeño de ventas,
 * productos vendidos y ventas por categoría (reusa los Services existentes).
 */
class ReportsApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['sanctum.stateful' => ['localhost', 'localhost:3000', '127.0.0.1']]);
        $this->withHeader('Origin', 'http://localhost:3000');
    }

    private function admin(): AdminUser
    {
        return AdminUser::firstOrCreate(
            ['gmail' => 'rep-admin@example.com'],
            ['name' => 'Rep', 'first_surname' => 'Admin', 'second_surname' => null, 'password' => bcrypt('password123'), 'last_access' => now()],
        );
    }

    private function seedSale(): void
    {
        $cat = Category::create(['name' => 'Bicis Rep']);
        Supplier::firstOrCreate(['name' => 'Sup Rep']);
        $product = Product::factory()->create(['category_id' => $cat->category_id, 'sale_price' => 1000, 'purchase_price' => 500]);

        $sale = Sale::create([
            'invoice_number' => 'INV-REP-1',
            'seller_admin_id' => $this->admin()->user_id,
            'buyer_name' => 'Mostrador',
            'subtotal' => 2000,
            'iva' => 260,
            'discount' => 0,
            'total' => 2260,
            'payment_method' => 'cash',
            'status' => 'completed',
            'sale_date' => Carbon::now(),
        ]);
        SaleItem::create([
            'sale_id' => $sale->sale_id,
            'product_id' => $product->product_id,
            'name' => $product->name,
            'quantity' => 2,
            'unit_price' => 1000,
            'total' => 2000,
        ]);
    }

    public function test_requires_authentication(): void
    {
        $this->getJson('/api/v1/admin/reports/sales-performance?preset=month')->assertStatus(401);
    }

    public function test_sales_performance_returns_metrics(): void
    {
        $this->actingAs($this->admin(), 'admin');
        $this->seedSale();

        $this->getJson('/api/v1/admin/reports/sales-performance?preset=month')
            ->assertOk()
            ->assertJsonStructure(['data' => ['current_metrics' => ['sales_count', 'revenue'], 'previous_metrics', 'comparison']]);
    }

    public function test_product_sales_returns_table(): void
    {
        $this->actingAs($this->admin(), 'admin');
        $this->seedSale();

        $this->getJson('/api/v1/admin/reports/product-sales?period=month')
            ->assertOk()
            ->assertJsonStructure(['data' => ['rows', 'top10', 'pagination']])
            ->assertJsonMissingPath('data.pagination_html');
    }

    public function test_category_sales_returns_chart_data(): void
    {
        $this->actingAs($this->admin(), 'admin');
        $this->seedSale();

        $this->getJson('/api/v1/admin/reports/category-sales?date_range=month')
            ->assertOk()
            ->assertJsonStructure(['data' => ['rows', 'grandTotal', 'totalUnits', 'chartData']]);
    }

    public function test_client_purchases_returns_table(): void
    {
        $this->actingAs($this->admin(), 'admin');
        $this->seedSale();

        $this->getJson('/api/v1/admin/reports/client-purchases?period=30d')
            ->assertOk()
            ->assertJsonStructure(['data' => ['rows', 'pagination']])
            ->assertJsonMissingPath('data.pagination_html');
    }

    public function test_catalog_search_and_movements(): void
    {
        $this->actingAs($this->admin(), 'admin');
        $this->seedSale();

        $this->getJson('/api/v1/admin/reports/catalog-search?period=30d')->assertOk();
        $this->getJson('/api/v1/admin/reports/inventory-movements')
            ->assertOk()
            ->assertJsonStructure(['data' => ['products', 'pagination']]);
    }
}
