<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use App\Services\Client\Storefront\ClientStorefrontCache;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\Support\InteractsWithMysqlTestDatabase;
use Tests\TestCase;

class CF4166ClientCatalogAutoRefreshTest extends TestCase
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

        $this->skipUnlessMysqlTestDatabase(['products', 'brands', 'products_brand', 'categories', 'suppliers', 'admins']);

        Cache::flush();

        $this->admin = AdminUser::create([
            'name' => 'Admin',
            'first_surname' => 'CF4166',
            'second_surname' => null,
            'gmail' => 'admin-cf4166@example.com',
            'password' => bcrypt('password'),
            'last_access' => now(),
        ]);
    }

    public function test_catalog_response_includes_no_cache_headers(): void
    {
        $response = $this->get(route('clients.catalog'));

        $response->assertOk();
        $cacheControl = (string) $response->headers->get('Cache-Control');
        $this->assertStringContainsString('no-cache', $cacheControl);
        $this->assertStringContainsString('max-age=0', $cacheControl);
    }

    public function test_catalog_heartbeat_returns_version_json_without_http_cache(): void
    {
        ClientStorefrontCache::bumpCatalogVersion();

        $response = $this->getJson(route('api.catalog.heartbeat'));

        $response->assertOk();
        $response->assertJsonStructure(['version']);
        $cacheControl = (string) $response->headers->get('Cache-Control');
        $this->assertStringContainsString('no-cache', $cacheControl);
        $this->assertStringContainsString('max-age=0', $cacheControl);
    }

    public function test_brand_store_clears_catalog_brands_cache(): void
    {
        Cache::put(ClientStorefrontCache::KEY_CATALOG_BRANDS, collect([
            (object) ['id' => 999, 'name' => 'Stale Brand'],
        ]), 600);

        $response = $this->actingAs($this->admin, 'admin')
            ->postJson(route('brands.store'), ['name' => 'Marca CF4166 Nueva']);

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $this->assertFalse(Cache::has(ClientStorefrontCache::KEY_CATALOG_BRANDS));
    }

    public function test_brand_store_makes_new_brand_visible_in_catalog_without_products(): void
    {
        Cache::put(ClientStorefrontCache::KEY_CATALOG_BRANDS, collect([
            (object) ['id' => 999, 'name' => 'Stale Brand'],
        ]), 600);

        $response = $this->actingAs($this->admin, 'admin')
            ->postJson(route('brands.store'), ['name' => 'Marca CF4166 Sin Producto']);

        $response->assertOk();
        $response->assertJsonPath('success', true);

        $catalog = $this->get(route('clients.catalog'));
        $catalog->assertOk();
        $catalog->assertSee('Marca CF4166 Sin Producto', false);
    }

    public function test_new_brand_with_active_product_appears_in_catalog_filter(): void
    {
        $brand = Brand::create(['name' => 'Marca Visible CF4166']);
        $category = Category::create([
            'name' => 'Cat CF4166',
            'description' => null,
            'parent_category_id' => null,
        ]);
        $supplier = Supplier::create([
            'name' => 'Proveedor CF4166',
            'primary_contact' => 'Contacto',
            'phone' => '0000',
            'email' => 'cf4166-supplier@example.com',
            'address' => 'Addr',
            'delivery_time' => 3,
            'rating' => 5.0,
            'status' => 'active',
        ]);

        Product::create([
            'category_id' => $category->category_id,
            'supplier_id' => $supplier->supplier_id,
            'name' => 'Producto CF4166',
            'description' => 'Desc',
            'sale_price' => 1500,
            'purchase_price' => 800,
            'stock_current' => 5,
            'stock_minimum' => 1,
            'status' => 'active',
            'is_featured' => false,
        ])->brands()->attach($brand->id);

        Cache::put(ClientStorefrontCache::KEY_CATALOG_BRANDS, collect(), 600);

        ClientStorefrontCache::forgetAfterBrandMutation();

        $response = $this->get(route('clients.catalog'));
        $response->assertOk();
        $response->assertSee('Marca Visible CF4166', false);
    }

    public function test_product_store_clears_client_catalog_caches(): void
    {
        Cache::put(ClientStorefrontCache::KEY_CATALOG_SPOTLIGHT, collect(['stale']), 600);
        Cache::put(ClientStorefrontCache::KEY_CATALOG_BRANDS, collect(['stale']), 600);

        $parent = Category::create([
            'name' => 'Root CF4166',
            'description' => null,
            'parent_category_id' => null,
        ]);
        $supplier = Supplier::create([
            'name' => 'Proveedor Producto CF4166',
            'primary_contact' => 'Contacto',
            'phone' => '0000',
            'email' => 'cf4166-product-supplier@example.com',
            'address' => 'Addr',
            'delivery_time' => 3,
            'rating' => 5.0,
            'status' => 'active',
        ]);
        $brand = Brand::create(['name' => 'Marca Producto CF4166']);

        $canonicalParent = Category::canonicalRootIdByPhysicalRootId()[(int) $parent->category_id]
            ?? (int) $parent->category_id;

        $response = $this->actingAs($this->admin, 'admin')
            ->postJson(route('products.store'), [
                'category_id' => $parent->category_id,
                'parent_category_id' => $canonicalParent,
                'supplier_id' => $supplier->supplier_id,
                'brand_id' => $brand->id,
                'name' => 'Producto Nuevo CF4166',
                'description' => 'Nuevo',
                'sale_price' => 2000,
                'purchase_price' => 1000,
                'stock_current' => 5,
                'stock_minimum' => 1,
                'status' => 'active',
                'is_featured' => false,
            ]);

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $this->assertFalse(Cache::has(ClientStorefrontCache::KEY_CATALOG_SPOTLIGHT));
        $this->assertFalse(Cache::has(ClientStorefrontCache::KEY_CATALOG_BRANDS));
    }

    public function test_new_product_appears_in_catalog_list_after_store(): void
    {
        $parent = Category::create([
            'name' => 'Root CF4166-B',
            'description' => null,
            'parent_category_id' => null,
        ]);
        $supplier = Supplier::create([
            'name' => 'Proveedor CF4166-B',
            'primary_contact' => 'Contacto',
            'phone' => '0000',
            'email' => 'cf4166-b-supplier@example.com',
            'address' => 'Addr',
            'delivery_time' => 3,
            'rating' => 5.0,
            'status' => 'active',
        ]);
        $brand = Brand::create(['name' => 'Marca CF4166-B']);

        $canonicalParent = Category::canonicalRootIdByPhysicalRootId()[(int) $parent->category_id]
            ?? (int) $parent->category_id;

        $this->actingAs($this->admin, 'admin')
            ->postJson(route('products.store'), [
                'category_id' => $parent->category_id,
                'parent_category_id' => $canonicalParent,
                'supplier_id' => $supplier->supplier_id,
                'brand_id' => $brand->id,
                'name' => 'Bici CF4166 Visible',
                'description' => 'Nuevo',
                'sale_price' => 2500,
                'purchase_price' => 1200,
                'stock_current' => 3,
                'stock_minimum' => 1,
                'status' => 'active',
                'is_featured' => false,
            ])
            ->assertOk();

        $response = $this->get(route('clients.catalog'));
        $response->assertOk();
        $response->assertSee('Bici CF4166 Visible', false);
    }
}
