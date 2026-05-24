<?php

namespace Tests\Feature;

use App\Http\Middleware\LogSensitiveAdminModuleAccess;
use App\Models\AdminUser;
use App\Models\Category;
use App\Models\Product;
use App\Support\ProductCatalog\ProductCatalogExporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class ProductCatalogImportExportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        try {
            parent::setUp();

            if (Schema::getConnection()->getDriverName() !== 'mysql') {
                $this->markTestSkipped('Requires MySQL.');
            }

            foreach (['products', 'categories', 'admins'] as $table) {
                if (! Schema::hasTable($table)) {
                    $this->markTestSkipped('Missing table: '.$table);
                }
            }
        } catch (\Throwable $e) {
            $this->markTestSkipped($e->getMessage());
        }

        $this->withoutMiddleware(LogSensitiveAdminModuleAccess::class);
    }

    private function createAdmin(): AdminUser
    {
        return AdminUser::create([
            'name' => 'Admin',
            'first_surname' => 'Catalog',
            'second_surname' => null,
            'gmail' => 'admin-catalog-'.uniqid().'@example.com',
            'password' => bcrypt('password'),
            'last_access' => now(),
        ]);
    }

    public function test_admin_can_export_catalog_bundle_zip(): void
    {
        $admin = $this->createAdmin();
        $category = Category::create([
            'name' => 'CF4 Export Cat',
            'description' => null,
            'parent_category_id' => null,
        ]);
        Product::create([
            'category_id' => $category->category_id,
            'supplier_id' => null,
            'name' => 'CF4 Export Product',
            'description' => 'demo',
            'image' => 'default.png',
            'sale_price' => 1500,
            'purchase_price' => 500,
            'stock_current' => 3,
            'stock_minimum' => 1,
            'status' => 'active',
            'is_featured' => false,
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->get(route('products.export', ['format' => 'bundle', 'scope' => 'all']));

        $response->assertOk();
        $response->assertHeader('content-disposition');
        $this->assertStringContainsString('zip', strtolower((string) $response->headers->get('content-type')));
    }

    public function test_admin_can_import_csv_and_update_product(): void
    {
        $admin = $this->createAdmin();
        $category = Category::create([
            'name' => 'CF4 Import Cat',
            'description' => null,
            'parent_category_id' => null,
        ]);
        $product = Product::create([
            'category_id' => $category->category_id,
            'supplier_id' => null,
            'name' => 'CF4 Import Product',
            'description' => null,
            'image' => 'default.png',
            'sale_price' => 1000,
            'purchase_price' => 100,
            'stock_current' => 2,
            'stock_minimum' => 1,
            'status' => 'active',
            'is_featured' => false,
        ]);

        $csv = implode("\n", [
            'nombre,precio_venta,stock_actual,categoria',
            'CF4 Import Product,2500,9,CF4 Import Cat',
        ]);

        $file = UploadedFile::fake()->createWithContent('proveedor.csv', $csv);

        $response = $this->actingAs($admin, 'admin')
            ->from(route('inventory'))
            ->post(route('products.import'), ['import_file' => $file]);

        $response->assertRedirect(route('inventory'));
        $response->assertSessionHas('status');

        $product->refresh();
        $this->assertSame(2500.0, (float) $product->sale_price);
        $this->assertSame(9, (int) $product->stock_current);
    }

    public function test_exporter_manifest_includes_product_name(): void
    {
        $category = Category::create([
            'name' => 'CF4 Manifest Cat',
            'description' => null,
            'parent_category_id' => null,
        ]);
        Product::create([
            'category_id' => $category->category_id,
            'supplier_id' => null,
            'name' => 'CF4 Manifest Product',
            'description' => null,
            'image' => 'default.png',
            'sale_price' => 800,
            'purchase_price' => 80,
            'stock_current' => 1,
            'stock_minimum' => 1,
            'status' => 'active',
            'is_featured' => false,
        ]);

        $manifest = app(ProductCatalogExporter::class)->buildManifest(
            Product::query()->where('name', 'CF4 Manifest Product'),
            false,
        );

        $this->assertSame(1, $manifest['product_count']);
        $this->assertSame('CF4 Manifest Product', $manifest['products'][0]['name'] ?? null);
    }

    public function test_round_trip_zip_preserves_product_name(): void
    {
        $category = Category::create([
            'name' => 'CF4 Zip Cat',
            'description' => null,
            'parent_category_id' => null,
        ]);
        Product::create([
            'category_id' => $category->category_id,
            'supplier_id' => null,
            'name' => 'CF4 Zip Product Original',
            'description' => 'zip test',
            'image' => 'default.png',
            'sale_price' => 1200,
            'purchase_price' => 120,
            'stock_current' => 4,
            'stock_minimum' => 1,
            'status' => 'active',
            'is_featured' => false,
        ]);

        $exporter = app(ProductCatalogExporter::class);
        $query = Product::query()->where('name', 'CF4 Zip Product Original');
        $manifest = $exporter->buildManifest($query, false);
        $products = (clone $query)->with([
            'category.parent',
            'supplier:supplier_id,name',
            'brands:id,name',
            'classificationValues.dimension',
            'variants:product_id,name,sku',
        ])->get();

        $zipPath = storage_path('app/temp/test-catalog-'.Str::uuid().'.zip');
        if (! is_dir(dirname($zipPath))) {
            mkdir(dirname($zipPath), 0755, true);
        }
        $exporter->writeBundleZip($zipPath, $products, $manifest);

        $this->assertFileExists($zipPath);

        $upload = new UploadedFile($zipPath, 'catalogo.zip', 'application/zip', null, true);
        $stats = app(\App\Support\ProductCatalog\ProductCatalogImporter::class)->import($upload);

        @unlink($zipPath);

        $this->assertGreaterThanOrEqual(1, $stats['created'] + $stats['updated']);
        $this->assertDatabaseHas('products', ['name' => 'CF4 Zip Product Original']);
    }
}
