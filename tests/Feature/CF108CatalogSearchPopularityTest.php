<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * CF4-108 — admin ver productos más buscados en catálogo + telemetría de impresiones.
 */
class CF108CatalogSearchPopularityTest extends TestCase
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
            $this->markTestSkipped('CF108CatalogSearchPopularityTest requiere MySQL.');
        }

        foreach (['products', 'catalog_product_search_events', 'categories', 'suppliers'] as $table) {
            if (! Schema::hasTable($table)) {
                $this->markTestSkipped('Tabla requerida no existe: '.$table);
            }
        }

        Carbon::setTestNow(Carbon::parse('2026-06-15 12:00:00', 'UTC'));

        $this->adminUser = AdminUser::create([
            'name' => 'Admin',
            'first_surname' => 'CF108',
            'second_surname' => null,
            'gmail' => 'admin-cf108@example.com',
            'password' => bcrypt('password'),
            'last_access' => now(),
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function seedCatalogProducts(): array
    {
        $category = Category::create([
            'name' => 'Cat CF108',
            'description' => null,
            'parent_category_id' => null,
        ]);

        $supplier = Supplier::create([
            'name' => 'Sup CF108',
            'primary_contact' => 'Contact',
            'phone' => '0000',
            'email' => 'sup-cf108@example.com',
            'address' => 'Addr',
            'delivery_time' => 1,
            'rating' => 5.0,
            'status' => 'active',
        ]);

        $matching = Product::create([
            'category_id' => $category->category_id,
            'supplier_id' => $supplier->supplier_id,
            'name' => 'CF108 Alpha Marker bicicleta demo',
            'description' => 'Demo',
            'image' => 'default.png',
            'sale_price' => 1000,
            'purchase_price' => 500,
            'stock_current' => 10,
            'stock_minimum' => 1,
            'status' => 'active',
        ]);

        $other = Product::create([
            'category_id' => $category->category_id,
            'supplier_id' => $supplier->supplier_id,
            'name' => 'Otro producto sin marcador',
            'description' => 'Sin hit',
            'image' => 'default.png',
            'sale_price' => 2000,
            'purchase_price' => 800,
            'stock_current' => 5,
            'stock_minimum' => 1,
            'status' => 'active',
        ]);

        return [$matching, $other];
    }

    public function test_guest_catalog_search_records_events_for_matching_products_only(): void
    {
        [$matching] = $this->seedCatalogProducts();

        $this->get(route('clients.catalog', ['search' => 'CF108 Alpha']))
            ->assertOk();

        $countMatching = DB::table('catalog_product_search_events')
            ->where('product_id', $matching->product_id)
            ->count();

        $this->assertGreaterThanOrEqual(1, $countMatching);
    }

    public function test_short_search_query_does_not_record_events(): void
    {
        $this->seedCatalogProducts();

        $before = DB::table('catalog_product_search_events')->count();

        $this->get(route('clients.catalog', ['search' => 'a']))
            ->assertOk();

        $after = DB::table('catalog_product_search_events')->count();

        $this->assertSame($before, $after);
    }

    public function test_admin_report_lists_products_ordered_by_hits(): void
    {
        [$matching, $other] = $this->seedCatalogProducts();

        $this->get(route('clients.catalog', ['search' => 'CF108 Alpha']))->assertOk();
        $this->get(route('clients.catalog', ['search' => 'CF108 Alpha']))->assertOk();

        $this->actingAs($this->adminUser, 'admin')
            ->get(route('admin.reports.catalog-search-products', ['period' => '30d']))
            ->assertOk()
            ->assertSee('CF108 Alpha Marker bicicleta demo', false)
            ->assertSee(\App\Models\Product::skuFromId((int) $matching->product_id), false);

        $this->assertSame(
            0,
            DB::table('catalog_product_search_events')->where('product_id', $other->product_id)->count()
        );
    }

    public function test_guest_cannot_open_admin_catalog_search_report(): void
    {
        $this->get(route('admin.reports.catalog-search-products'))
            ->assertRedirect(route('admin.login'));
    }
}
