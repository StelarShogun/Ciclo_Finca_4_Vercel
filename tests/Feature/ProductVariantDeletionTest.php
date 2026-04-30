<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductVariantDeletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_attach_active_variant_to_base_product(): void
    {
        $base = Product::create([
            'name' => 'Base',
            'sale_price' => 100,
            'purchase_price' => 50,
            'stock_current' => 10,
            'stock_minimum' => 1,
            'status' => 'active',
            'is_featured' => false,
        ]);
        $variant = Product::create([
            'name' => 'Variante nueva',
            'sale_price' => 110,
            'purchase_price' => 55,
            'stock_current' => 3,
            'stock_minimum' => 1,
            'status' => 'active',
            'is_featured' => false,
        ]);

        $this->actingAsAdmin();

        $res = $this->postJson("/products/{$base->product_id}/variants", [
            'variant_product_id' => $variant->product_id,
        ]);

        $res->assertOk();
        $res->assertJson(['success' => true]);

        $this->assertDatabaseHas('product_variants', [
            'base_product_id' => $base->product_id,
            'variant_product_id' => $variant->product_id,
        ]);
    }

    public function test_admin_can_fetch_product_show_json_after_variants_change(): void
    {
        $base = Product::create([
            'name' => 'Base JSON',
            'sale_price' => 100,
            'purchase_price' => 50,
            'stock_current' => 10,
            'stock_minimum' => 1,
            'status' => 'active',
            'is_featured' => false,
        ]);

        $this->actingAsAdmin();

        $res = $this->getJson("/products/{$base->product_id}");
        $res->assertOk();
        $res->assertJson([
            'success' => true,
        ]);
        $this->assertArrayHasKey('variants', (array) $res->json('data'));
    }

    public function test_admin_can_delete_variant_when_no_active_orders(): void
    {
        $base = Product::create([
            'name' => 'Producto base',
            'sale_price' => 100,
            'purchase_price' => 50,
            'stock_current' => 10,
            'stock_minimum' => 1,
            'status' => 'active',
            'is_featured' => false,
        ]);
        $variant = Product::create([
            'name' => 'Variante 1',
            'sale_price' => 120,
            'purchase_price' => 60,
            'stock_current' => 5,
            'stock_minimum' => 1,
            'status' => 'active',
            'is_featured' => false,
        ]);
        ProductVariant::create([
            'base_product_id' => $base->product_id,
            'variant_product_id' => $variant->product_id,
        ]);

        $this->actingAsAdmin();

        $res = $this->deleteJson("/products/{$base->product_id}/variants/{$variant->product_id}");
        $res->assertOk();
        $res->assertJson(['success' => true]);

        $this->assertDatabaseMissing('product_variants', [
            'base_product_id' => $base->product_id,
            'variant_product_id' => $variant->product_id,
        ]);

        $this->assertDatabaseHas('products', [
            'product_id' => $base->product_id,
            'status' => 'active',
        ]);
        $this->assertDatabaseHas('products', [
            'product_id' => $variant->product_id,
            'status' => 'inactive',
        ]);
    }

    public function test_admin_cannot_delete_variant_with_active_sales_or_orders(): void
    {
        $supplier = Supplier::create([
            'name' => 'Proveedor 1',
            'status' => 'active',
            'delivery_time' => 0,
            'rating' => 0,
        ]);

        $base = Product::create([
            'name' => 'Producto base',
            'sale_price' => 100,
            'purchase_price' => 50,
            'stock_current' => 10,
            'stock_minimum' => 1,
            'status' => 'active',
            'is_featured' => false,
        ]);
        $variant = Product::create([
            'name' => 'Variante bloqueada',
            'sale_price' => 120,
            'purchase_price' => 60,
            'stock_current' => 5,
            'stock_minimum' => 1,
            'status' => 'active',
            'is_featured' => false,
        ]);
        ProductVariant::create([
            'base_product_id' => $base->product_id,
            'variant_product_id' => $variant->product_id,
        ]);

        $sale = Sale::create([
            'invoice_number' => 'CF4-9999',
            'subtotal' => 120,
            'iva' => 0,
            'discount' => 0,
            'total' => 120,
            'payment_method' => 'cash',
            'status' => 'pending',
        ]);
        SaleItem::create([
            'sale_id' => $sale->sale_id,
            'product_id' => $variant->product_id,
            'quantity' => 1,
            'unit_price' => 120,
            'unit_discount' => 0,
            'total' => 120,
        ]);

        $order = Order::create([
            'supplier_id' => $supplier->supplier_id,
            'state' => 'pending',
            'total' => 0,
        ]);
        OrderItem::create([
            'order_num_order' => $order->num_order,
            'product_id' => $variant->product_id,
            'name' => 'Variante bloqueada',
            'quantity' => 1,
            'unit_price' => 120,
            'total' => 120,
        ]);

        $this->actingAsAdmin();

        $res = $this->deleteJson("/products/{$base->product_id}/variants/{$variant->product_id}");
        $res->assertStatus(409);
        $res->assertJson(['success' => false]);

        $this->assertDatabaseHas('product_variants', [
            'base_product_id' => $base->product_id,
            'variant_product_id' => $variant->product_id,
        ]);
        $this->assertDatabaseHas('products', [
            'product_id' => $variant->product_id,
            'status' => 'active',
        ]);
    }

    private function actingAsAdmin(): void
    {
        // Admin guard is used in the app; for tests, bypass via session guard using a minimal user row.
        $admin = \App\Models\AdminUser::create([
            'name' => 'Admin',
            'first_surname' => 'Test',
            'second_surname' => 'User',
            'gmail' => 'admin@example.com',
            'password' => bcrypt('password'),
        ]);

        $this->actingAs($admin, 'admin');
    }
}

