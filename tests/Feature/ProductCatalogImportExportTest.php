<?php

namespace Tests\Feature;

use App\Http\Middleware\LogSensitiveAdminModuleAccess;
use App\Models\AdminUser;
use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use App\Services\Admin\ProductCatalog\ProductCatalogExporter;
use App\Services\Admin\ProductCatalog\ProductCatalogImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Tests\TestCase;

class ProductCatalogImportExportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
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

    private function createActiveSupplier(): Supplier
    {
        return Supplier::create([
            'name' => 'CF4 Catalog Supplier',
            'primary_contact' => 'Contact',
            'phone' => '0000',
            'email' => 'catalog-supplier-'.uniqid().'@example.com',
            'address' => 'Addr',
            'delivery_time' => 1,
            'rating' => 5.0,
            'status' => 'active',
        ]);
    }

    public function test_admin_can_export_catalog_bundle_zip(): void
    {
        $admin = $this->createAdmin();
        $this->createActiveSupplier();
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
        $this->createActiveSupplier();
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

        // La importación se encola en un job en segundo plano (202 + importId para seguir el progreso).
        $response->assertStatus(202);
        $response->assertJsonStructure(['importId', 'progress' => ['status', 'filename']]);

        // Con QUEUE_CONNECTION=sync el job corre en la misma request, así que los cambios ya están aplicados.
        $product->refresh();
        $this->assertSame(2500.0, (float) $product->sale_price);
        $this->assertSame(9, (int) $product->stock_current);
    }

    public function test_admin_import_queues_job_and_processes_for_ajax_request(): void
    {
        $admin = $this->createAdmin();
        $this->createActiveSupplier();
        $category = Category::create([
            'name' => 'CF4 Ajax Import Cat',
            'description' => null,
            'parent_category_id' => null,
        ]);
        Product::create([
            'category_id' => $category->category_id,
            'supplier_id' => null,
            'name' => 'CF4 Ajax Import Product',
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
            'CF4 Ajax Import Product,1800,5,CF4 Ajax Import Cat',
        ]);

        $file = UploadedFile::fake()->createWithContent('proveedor.csv', $csv);

        $response = $this->actingAs($admin, 'admin')
            ->withHeaders([
                'Accept' => 'application/json',
                'X-Requested-With' => 'XMLHttpRequest',
            ])
            ->post(route('products.import'), ['import_file' => $file]);

        // Respuesta 202: la importación se encola y devuelve el importId para hacer polling del progreso.
        $response->assertStatus(202);
        $response->assertJsonStructure(['importId', 'progress' => ['status', 'filename']]);
        $response->assertJsonPath('progress.status', 'queued');

        $importId = $response->json('importId');
        $this->assertIsString($importId);

        // Con QUEUE_CONNECTION=sync el job se ejecuta en línea: el producto debe quedar actualizado…
        $this->assertDatabaseHas('products', [
            'name' => 'CF4 Ajax Import Product',
            'sale_price' => 1800,
            'stock_current' => 5,
        ]);

        // …y el progreso debe haber alcanzado el estado final "done".
        $progress = \App\Services\Admin\ProductCatalog\CatalogImportProgress::get($importId);
        $this->assertNotNull($progress);
        $this->assertSame('done', $progress['status']);
        $this->assertSame(1, $progress['updated']);
    }

    public function test_import_reports_rows_total_and_duration_metrics(): void
    {
        $this->createActiveSupplier();
        $category = Category::create([
            'name' => 'CF4 Metrics Cat',
            'description' => null,
            'parent_category_id' => null,
        ]);

        $csv = implode("\n", [
            'nombre,precio_venta,stock_actual,categoria',
            'CF4 Metrics Product,1200,2,CF4 Metrics Cat',
        ]);

        $stats = app(ProductCatalogImporter::class)->import(
            UploadedFile::fake()->createWithContent('metrics.csv', $csv),
        );

        $this->assertSame(1, $stats['rows_total']);
        $this->assertGreaterThanOrEqual(0, $stats['duration_ms']);
        $this->assertSame(0, $stats['media_count']);
        $this->assertSame(1, $stats['created']);
    }

    public function test_import_updates_existing_product_by_sku(): void
    {
        $this->createActiveSupplier();
        $category = Category::create([
            'name' => 'CF4 Sku Cat',
            'description' => null,
            'parent_category_id' => null,
        ]);
        $product = Product::create([
            'category_id' => $category->category_id,
            'supplier_id' => null,
            'name' => 'CF4 Sku Product',
            'sku' => 'CF4-SKU-001',
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
            'nombre,sku,precio_venta,stock_actual,categoria',
            'CF4 Sku Product Renamed,CF4-SKU-001,2200,8,CF4 Sku Cat',
        ]);

        $stats = app(ProductCatalogImporter::class)->import(
            UploadedFile::fake()->createWithContent('sku-update.csv', $csv),
        );

        $this->assertSame(1, $stats['updated']);
        $product->refresh();
        $this->assertSame(2200.0, (float) $product->sale_price);
        $this->assertSame(8, (int) $product->stock_current);
    }

    public function test_import_updates_existing_product_by_name_and_category(): void
    {
        $this->createActiveSupplier();
        $category = Category::create([
            'name' => 'CF4 Name Cat',
            'description' => null,
            'parent_category_id' => null,
        ]);
        $product = Product::create([
            'category_id' => $category->category_id,
            'supplier_id' => null,
            'name' => 'CF4 Name Match Product',
            'sku' => null,
            'description' => null,
            'image' => 'default.png',
            'sale_price' => 900,
            'purchase_price' => 100,
            'stock_current' => 1,
            'stock_minimum' => 1,
            'status' => 'active',
            'is_featured' => false,
        ]);

        $csv = implode("\n", [
            'nombre,precio_venta,stock_actual,categoria',
            'CF4 Name Match Product,1750,4,CF4 Name Cat',
        ]);

        $stats = app(ProductCatalogImporter::class)->import(
            UploadedFile::fake()->createWithContent('name-update.csv', $csv),
        );

        $this->assertSame(1, $stats['updated']);
        $product->refresh();
        $this->assertSame(1750.0, (float) $product->sale_price);
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
        $this->createActiveSupplier();
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
        $stats = app(ProductCatalogImporter::class)->import($upload);

        @unlink($zipPath);

        $this->assertGreaterThanOrEqual(1, $stats['created'] + $stats['updated']);
        $this->assertDatabaseHas('products', ['name' => 'CF4 Zip Product Original']);
    }

    public function test_bundle_import_creates_missing_categories_on_target(): void
    {
        $this->createActiveSupplier();

        $bundleDir = storage_path('app/temp/test-catalog-cats-'.Str::uuid());
        mkdir($bundleDir, 0755, true);
        file_put_contents($bundleDir.'/catalog.json', json_encode([
            'version' => 1,
            'products' => [[
                'export_key' => 'cf4-auto-cat',
                'name' => 'CF4 Auto Category Product',
                'category_path' => ['CF4 Import Parent', 'CF4 Import Sub'],
                'sale_price' => 1500,
                'purchase_price' => 500,
                'stock_current' => 4,
                'stock_minimum' => 1,
                'status' => 'active',
            ]],
        ], JSON_UNESCAPED_UNICODE));

        $placeholder = UploadedFile::fake()->create('bundle.json', 1, 'application/json');
        $stats = app(ProductCatalogImporter::class)->import($placeholder, $bundleDir);

        @unlink($bundleDir.'/catalog.json');
        @rmdir($bundleDir);

        $this->assertSame(1, $stats['created']);
        $this->assertSame([], $stats['errors']);
        $this->assertDatabaseHas('categories', [
            'name' => 'CF4 Import Parent',
            'parent_category_id' => null,
        ]);
        $this->assertDatabaseHas('categories', [
            'name' => 'CF4 Import Sub',
        ]);
        $this->assertDatabaseHas('products', ['name' => 'CF4 Auto Category Product']);
    }
}
