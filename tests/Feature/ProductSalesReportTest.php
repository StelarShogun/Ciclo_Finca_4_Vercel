<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\Category;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Supplier;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * CF4-30 — reporte productos más vendidos (CP30-01 a CP30-03).
 *
 * Requiere MySQL y migraciones (tablas sales, sale_items, products). Ver SalesOrderExpiryTest.
 */
class ProductSalesReportTest extends TestCase
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
            $this->markTestSkipped('ProductSalesReportTest requiere MySQL.');
        }
        if (! Schema::hasTable('sales') || ! Schema::hasTable('sale_items') || ! Schema::hasTable('products')) {
            $this->markTestSkipped('Faltan tablas de ventas o productos.');
        }

        Carbon::setTestNow(Carbon::parse('2026-06-10 12:00:00', 'UTC'));

        $this->adminUser = AdminUser::create([
            'name' => 'Admin',
            'first_surname' => 'Report',
            'second_surname' => null,
            'gmail' => 'admin-reports-cf30@example.com',
            'password' => bcrypt('password'),
            'last_access' => now(),
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    protected function seedFifteenProductsWithSales(): void
    {
        $category = Category::create([
            'name' => 'Cat CF30',
            'description' => null,
            'parent_category_id' => null,
        ]);
        $supplier = Supplier::create([
            'name' => 'Sup CF30',
            'primary_contact' => 'Contact',
            'phone' => '0000',
            'email' => 'sup-cf30@example.com',
            'address' => 'Addr',
            'delivery_time' => 1,
            'rating' => 5.0,
            'status' => 'active',
        ]);

        $products = [];
        for ($i = 0; $i < 15; $i++) {
            $suffix = $i === 7 ? 'RepTest-UNIQUE-XYZ-marker' : "RepTest-Prod-{$i}";
            $products[] = Product::create([
                'category_id' => $category->category_id,
                'supplier_id' => $supplier->supplier_id,
                'name' => "Producto {$suffix}",
                'description' => 'd',
                'purchase_price' => 10,
                'sale_price' => 100,
                'stock_current' => 100,
                'stock_minimum' => 1,
                'status' => 'active',
            ]);
        }

        $sale = Sale::create([
            'invoice_number' => 'INV-CF30-'.now()->format('YmdHis'),
            'client_id' => null,
            'seller_admin_id' => $this->adminUser->user_id,
            'subtotal' => 1000,
            'iva' => 0,
            'discount' => 0,
            'total' => 1000,
            'payment_method' => 'cash',
            'payment_reference' => null,
            'status' => 'completed',
            'notes' => null,
            'sale_date' => now(),
            'buyer_name' => null,
            'buyer_email' => null,
            'order_source' => 'walk_in',
        ]);

        foreach ($products as $i => $product) {
            $revenue = (float) (($i + 1) * 100);
            SaleItem::create([
                'sale_id' => $sale->sale_id,
                'product_id' => $product->product_id,
                'quantity' => $i + 1,
                'unit_price' => $revenue / ($i + 1),
                'unit_discount' => 0,
                'total' => $revenue,
            ]);
        }
    }

    public function test_guest_cannot_access_reports_hub(): void
    {
        $this->get(route('admin.reports.index'))
            ->assertRedirect(route('admin.login'));
    }

    public function test_admin_sees_reports_hub(): void
    {
        $response = $this->actingAs($this->adminUser, 'admin')
            ->get(route('admin.reports.index'));

        $response->assertOk();
        $response->assertSee('Productos más vendidos', false);
    }

    /** CP30-01: orden por ingresos descendente y columnas en JSON. */
    public function test_cp30_01_json_ordered_by_revenue_desc(): void
    {
        $this->seedFifteenProductsWithSales();

        $response = $this->actingAs($this->adminUser, 'admin')
            ->getJson(route('admin.reports.product-sales.table', [
                'period' => '30d',
                'sort' => 'revenue',
                'dir' => 'desc',
                'page' => 1,
            ]));

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $rows = $response->json('rows');
        $this->assertIsArray($rows);
        $this->assertCount(10, $rows, 'La tabla está paginada a 10 por página.');
        $first = $rows[0];
        $this->assertArrayHasKey('name', $first);
        $this->assertArrayHasKey('sku', $first);
        $this->assertArrayHasKey('units_sold', $first);
        $this->assertArrayHasKey('revenue', $first);

        $revenues = array_map(fn ($r) => (float) $r['revenue'], $rows);
        $sorted = $revenues;
        rsort($sorted, SORT_NUMERIC);
        $this->assertEquals($sorted, $revenues, 'Las filas deben ir ordenadas por ingresos descendente.');
    }

    /** CP30-02: Top 10 como máximo 10 ítems cuando hay más productos con ventas. */
    public function test_cp30_02_top10_has_at_most_ten_rows(): void
    {
        $this->seedFifteenProductsWithSales();

        $response = $this->actingAs($this->adminUser, 'admin')
            ->getJson(route('admin.reports.product-sales.table', [
                'period' => '30d',
                'sort' => 'units',
                'dir' => 'asc',
                'top10' => 'revenue',
            ]));

        $response->assertOk();
        $top10 = $response->json('top10');
        $this->assertIsArray($top10);
        $this->assertCount(10, $top10);
    }

    public function test_top10_can_be_sorted_by_units(): void
    {
        $this->seedFifteenProductsWithSales();

        $response = $this->actingAs($this->adminUser, 'admin')
            ->getJson(route('admin.reports.product-sales.table', [
                'period' => '30d',
                'top10' => 'units',
            ]));

        $response->assertOk();
        $top10 = $response->json('top10');
        $this->assertIsArray($top10);
        $this->assertCount(10, $top10);

        $units = array_map(fn ($r) => (int) $r['units_sold'], $top10);
        $sorted = $units;
        rsort($sorted, SORT_NUMERIC);
        $this->assertSame($sorted, $units, 'Top 10 por unidades debe venir en orden descendente.');
    }

    /** CP30-03: filtro por texto reduce resultados (nombre o SKU vía servidor). */
    public function test_cp30_03_query_parameter_filters_table_rows(): void
    {
        $this->seedFifteenProductsWithSales();

        $without = $this->actingAs($this->adminUser, 'admin')
            ->getJson(route('admin.reports.product-sales.table', [
                'period' => '30d',
                'sort' => 'revenue',
                'dir' => 'desc',
                'q' => 'RepTest-',
            ]));
        $without->assertOk();
        $this->assertSame(15, (int) $without->json('pagination.total'));

        $filtered = $this->actingAs($this->adminUser, 'admin')
            ->getJson(route('admin.reports.product-sales.table', [
                'period' => '30d',
                'sort' => 'revenue',
                'dir' => 'desc',
                'q' => 'RepTest-UNIQUE-XYZ-marker',
            ]));
        $filtered->assertOk();
        $rows = $filtered->json('rows');
        $this->assertCount(1, $rows);
        $this->assertStringContainsString('RepTest-UNIQUE-XYZ-marker', $rows[0]['name']);
    }

    public function test_cp30_03_filter_by_partial_sku(): void
    {
        $this->seedFifteenProductsWithSales();

        $product = Product::where('name', 'like', '%RepTest-Prod-14%')->first();
        $this->assertNotNull($product);
        $sku = Product::skuFromId((int) $product->product_id);
        $partial = substr($sku, 0, 5);

        $filtered = $this->actingAs($this->adminUser, 'admin')
            ->getJson(route('admin.reports.product-sales.table', [
                'period' => '30d',
                'q' => $partial,
            ]));
        $filtered->assertOk();
        $names = array_column($filtered->json('rows'), 'name');
        $this->assertContains($product->name, $names);
        $this->assertLessThan(15, count($filtered->json('rows')));
    }
}
