<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CF113ProductCategoryParentValidationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        try {
            parent::setUp();
        } catch (\Throwable $e) {
            $this->markTestSkipped('Base de datos no disponible: '.$e->getMessage());
        }

    }

    private function makeAdmin(): AdminUser
    {
        return AdminUser::create([
            'name' => 'Admin',
            'first_surname' => 'CF113',
            'second_surname' => null,
            'gmail' => 'cf113-admin@example.com',
            'password' => bcrypt('password'),
            'last_access' => now(),
        ]);
    }

    private function makeSupplier(): Supplier
    {
        return Supplier::create([
            'name' => 'Supplier CF113',
            'primary_contact' => 'Contacto',
            'phone' => '0000',
            'email' => 'supplier-cf113@example.com',
            'address' => 'Address',
            'delivery_time' => 3,
            'rating' => 4.5,
            'status' => 'active',
        ]);
    }

    public function test_update_rejects_subcategory_when_declared_parent_does_not_match(): void
    {
        $admin = $this->makeAdmin();
        $parentA = Category::create([
            'name' => 'CF113-A',
            'description' => null,
            'parent_category_id' => null,
        ]);
        $parentB = Category::create([
            'name' => 'CF113-B',
            'description' => null,
            'parent_category_id' => null,
        ]);
        $subA = Category::create([
            'name' => 'CF113-SubA',
            'description' => null,
            'parent_category_id' => $parentA->category_id,
        ]);

        $supplier = $this->makeSupplier();
        $brand = Brand::create(['name' => 'Marca CF113']);

        $product = Product::create([
            'category_id' => $subA->category_id,
            'supplier_id' => $supplier->supplier_id,
            'name' => 'Producto CF113',
            'description' => 'Desc',
            'sale_price' => 1500,
            'purchase_price' => 1200,
            'stock_current' => 10,
            'stock_minimum' => 2,
            'status' => 'active',
            'is_featured' => false,
        ]);
        $product->brands()->attach($brand->id);

        $payload = [
            'category_id' => $subA->category_id,
            'parent_category_id' => $parentB->category_id,
            'supplier_id' => $supplier->supplier_id,
            'brand_id' => $brand->id,
            'name' => 'Producto CF113',
            'description' => 'Desc',
            'sale_price' => 1600,
            'purchase_price' => 1200,
            'stock_current' => 10,
            'stock_minimum' => 2,
            'status' => 'active',
            'is_featured' => false,
        ];

        $response = $this->actingAs($admin, 'admin')
            ->putJson(route('products.update', $product->product_id), $payload);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['category_id']);
    }

    public function test_update_accepts_subcategory_when_declared_parent_matches_canonical_root(): void
    {
        $admin = $this->makeAdmin();
        $parentA = Category::create([
            'name' => 'CF113-P',
            'description' => null,
            'parent_category_id' => null,
        ]);
        $subA = Category::create([
            'name' => 'CF113-Type',
            'description' => null,
            'parent_category_id' => $parentA->category_id,
        ]);

        $supplier = $this->makeSupplier();
        $brand = Brand::create(['name' => 'Marca CF113b']);

        $product = Product::create([
            'category_id' => $subA->category_id,
            'supplier_id' => $supplier->supplier_id,
            'name' => 'Producto OK',
            'description' => 'Desc',
            'sale_price' => 1500,
            'purchase_price' => 1200,
            'stock_current' => 10,
            'stock_minimum' => 2,
            'status' => 'active',
            'is_featured' => false,
        ]);
        $product->brands()->attach($brand->id);

        $canonicalParent = Category::canonicalRootIdByPhysicalRootId()[(int) $parentA->category_id]
            ?? (int) $parentA->category_id;

        $payload = [
            'category_id' => $subA->category_id,
            'parent_category_id' => $canonicalParent,
            'supplier_id' => $supplier->supplier_id,
            'brand_id' => $brand->id,
            'name' => 'Producto OK',
            'description' => 'Desc',
            'sale_price' => 1600,
            'purchase_price' => 1200,
            'stock_current' => 10,
            'stock_minimum' => 2,
            'status' => 'active',
            'is_featured' => false,
        ];

        $response = $this->actingAs($admin, 'admin')
            ->putJson(route('products.update', $product->product_id), $payload);

        $response->assertOk();
        $response->assertJsonPath('success', true);
    }

    public function test_store_accepts_root_category_with_matching_declared_parent(): void
    {
        $admin = $this->makeAdmin();
        $parent = Category::create([
            'name' => 'CF113-Root',
            'description' => null,
            'parent_category_id' => null,
        ]);

        $supplier = $this->makeSupplier();
        $brand = Brand::create(['name' => 'Marca CF113c']);

        $canonicalParent = Category::canonicalRootIdByPhysicalRootId()[(int) $parent->category_id]
            ?? (int) $parent->category_id;

        $payload = [
            'category_id' => $parent->category_id,
            'parent_category_id' => $canonicalParent,
            'supplier_id' => $supplier->supplier_id,
            'brand_id' => $brand->id,
            'name' => 'Nuevo CF113',
            'description' => 'Nuevo',
            'sale_price' => 2000,
            'purchase_price' => 1000,
            'stock_current' => 5,
            'stock_minimum' => 1,
            'status' => 'active',
            'is_featured' => false,
        ];

        $response = $this->actingAs($admin, 'admin')
            ->postJson(route('products.store'), $payload);

        $response->assertOk();
        $response->assertJsonPath('success', true);
    }
}
