<?php

namespace Tests\Feature;

use App\Http\Middleware\LogSensitiveAdminModuleAccess;
use App\Models\AdminUser;
use App\Models\AuditLog;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductAuditLoggingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(LogSensitiveAdminModuleAccess::class);
    }

    public function test_product_update_is_written_to_audit_log_with_name_change(): void
    {
        $admin = AdminUser::create([
            'name' => 'Admin',
            'first_surname' => 'QA',
            'second_surname' => null,
            'gmail' => 'admin-product-audit@example.com',
            'password' => bcrypt('password'),
            'last_access' => now(),
        ]);

        $category = Category::create([
            'name' => 'Bicicletas',
            'description' => 'Categoría de prueba',
            'parent_category_id' => null,
        ]);

        $supplier = Supplier::create([
            'name' => 'Proveedor QA',
            'primary_contact' => 'Contacto QA',
            'phone' => '2222-2222',
            'email' => 'proveedor-qa@example.com',
            'address' => 'San José',
            'delivery_time' => 3,
            'rating' => 5,
            'status' => 'active',
        ]);

        $brand = Brand::create(['name' => 'Marca QA']);

        $product = Product::create([
            'category_id' => $category->category_id,
            'supplier_id' => $supplier->supplier_id,
            'name' => 'Producto Original',
            'description' => 'Descripción original',
            'sale_price' => 1500,
            'purchase_price' => 1200,
            'stock_current' => 12,
            'stock_minimum' => 3,
            'status' => 'active',
            'is_featured' => false,
        ]);
        $product->brands()->attach($brand->id);

        $payload = [
            'category_id' => $category->category_id,
            'parent_category_id' => $category->category_id,
            'supplier_id' => $supplier->supplier_id,
            'brand_id' => $brand->id,
            'name' => 'Producto Editado QA',
            'description' => 'Descripción actualizada',
            'sale_price' => 1600,
            'purchase_price' => 1200,
            'stock_current' => 15,
            'stock_minimum' => 3,
            'status' => 'active',
            'is_featured' => false,
        ];

        $response = $this->actingAs($admin, 'admin')
            ->putJson(route('products.update', $product->product_id), $payload);

        $response->assertOk();
        $response->assertJsonPath('success', true);

        $log = AuditLog::query()
            ->where('action_type', 'product_update')
            ->where('module', 'products')
            ->latest('id')
            ->first();

        $this->assertNotNull($log, 'No se creó registro de auditoría product_update.');
        $this->assertSame('Producto actualizado.', $log->description);
        $this->assertSame($product->product_id, (int) ($log->meta['product_id'] ?? 0));
        $this->assertSame('Producto Original', $log->meta['changes']['name']['from'] ?? null);
        $this->assertSame('Producto Editado QA', $log->meta['changes']['name']['to'] ?? null);
    }
}
