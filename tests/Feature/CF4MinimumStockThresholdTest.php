<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * CF4-50 — Stock mínimo por producto (umbral de alerta, ≥ 0, solo admin).
 */
class CF4MinimumStockThresholdTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        try {
            parent::setUp();

            if (Schema::getConnection()->getDriverName() !== 'mysql') {
                $this->markTestSkipped('CF4-50 requiere MySQL para el esquema de catálogo.');
            }

            foreach (['admins', 'products', 'categories', 'suppliers', 'brands', 'products_brand'] as $table) {
                if (! Schema::hasTable($table)) {
                    $this->markTestSkipped('Tabla requerida no existe: '.$table);
                }
            }
        } catch (\Throwable $e) {
            $this->markTestSkipped('Base de datos no disponible: '.$e->getMessage());
        }
    }

    private function makeAdmin(): AdminUser
    {
        return AdminUser::create([
            'name' => 'Test',
            'first_surname' => 'Admin',
            'second_surname' => null,
            'gmail' => 'cf50-admin-'.uniqid().'@example.com',
            'password' => bcrypt('password'),
            'last_access' => now(),
        ]);
    }

    /** @return array{Product, Category, Supplier, Brand} */
    private function seedProductWithRelations(): array
    {
        $root = Category::create([
            'name' => 'CF50 Root '.uniqid(),
            'description' => null,
            'parent_category_id' => null,
        ]);
        $sub = Category::create([
            'name' => 'CF50 Sub '.uniqid(),
            'description' => null,
            'parent_category_id' => $root->category_id,
        ]);
        $supplier = Supplier::create([
            'name' => 'CF50 Supplier '.uniqid(),
            'primary_contact' => null,
            'phone' => null,
            'email' => null,
            'address' => null,
            'delivery_time' => 1,
            'rating' => 3.0,
            'status' => 'active',
        ]);
        $brand = Brand::create(['name' => 'CF50 Brand '.uniqid()]);

        $product = Product::create([
            'category_id' => $sub->category_id,
            'supplier_id' => $supplier->supplier_id,
            'name' => 'Producto CF50 '.uniqid(),
            'description' => null,
            'image' => 'default.png',
            'sale_price' => 100,
            'purchase_price' => 10,
            'stock_current' => 50,
            'stock_minimum' => 2,
            'status' => 'active',
        ]);
        $product->brands()->sync([$brand->id]);

        return [$product, $sub, $supplier, $brand];
    }

    /** CP50-01 */
    public function test_admin_can_persist_minimum_stock_on_product_update(): void
    {
        [$product, $sub, $supplier, $brand] = $this->seedProductWithRelations();
        Auth::guard('admin')->login($this->makeAdmin());

        $response = $this->putJson(route('products.update', $product->product_id), [
            'category_id' => $sub->category_id,
            'supplier_id' => $supplier->supplier_id,
            'brand_id' => $brand->id,
            'name' => $product->name,
            'description' => '',
            'sale_price' => 100,
            'purchase_price' => 10,
            'stock_current' => 50,
            'stock_minimum' => 10,
            'status' => 'active',
            'is_featured' => false,
        ]);

        $response->assertOk();
        $product->refresh();
        $this->assertSame(10, (int) $product->stock_minimum);
    }

    /** CP50-02 */
    public function test_negative_minimum_stock_is_rejected(): void
    {
        [$product, $sub, $supplier, $brand] = $this->seedProductWithRelations();
        Auth::guard('admin')->login($this->makeAdmin());

        $response = $this->putJson(route('products.update', $product->product_id), [
            'category_id' => $sub->category_id,
            'supplier_id' => $supplier->supplier_id,
            'brand_id' => $brand->id,
            'name' => $product->name,
            'description' => '',
            'sale_price' => 100,
            'purchase_price' => 10,
            'stock_current' => 50,
            'stock_minimum' => -1,
            'status' => 'active',
            'is_featured' => false,
        ]);

        $response->assertStatus(422);
        $response->assertJsonStructure(['errors' => ['stock_minimum']]);
    }

    /** CP50-03 */
    public function test_guest_cannot_update_product_minimum_stock(): void
    {
        [$product, $sub, $supplier, $brand] = $this->seedProductWithRelations();

        $response = $this->putJson(route('products.update', $product->product_id), [
            'category_id' => $sub->category_id,
            'supplier_id' => $supplier->supplier_id,
            'brand_id' => $brand->id,
            'name' => $product->name,
            'description' => '',
            'sale_price' => 100,
            'purchase_price' => 10,
            'stock_current' => 50,
            'stock_minimum' => 10,
            'status' => 'active',
            'is_featured' => false,
        ]);

        $response->assertStatus(401);
        $product->refresh();
        $this->assertSame(2, (int) $product->stock_minimum);
    }

    public function test_low_stock_alert_scope_uses_per_product_minimum(): void
    {
        [$pOk] = $this->seedProductWithRelations();
        $pOk->update(['stock_minimum' => 5, 'stock_current' => 20]);

        $pLow = Product::create([
            'category_id' => $pOk->category_id,
            'supplier_id' => $pOk->supplier_id,
            'name' => 'Low CF50 '.uniqid(),
            'description' => null,
            'image' => 'default.png',
            'sale_price' => 50,
            'purchase_price' => 5,
            'stock_current' => 10,
            'stock_minimum' => 10,
            'status' => 'active',
        ]);
        $brandId = $pOk->brands()->first()->id;
        $pLow->brands()->sync([$brandId]);

        $ids = Product::lowStockAlert()->pluck('product_id')->all();
        $this->assertContains($pLow->product_id, $ids);
        $this->assertNotContains($pOk->product_id, $ids);
    }
}
