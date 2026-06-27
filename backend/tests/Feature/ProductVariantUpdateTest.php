<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductVariantUpdateTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdmin(): void
    {
        $admin = AdminUser::create([
            'name' => 'Admin',
            'first_surname' => 'Test',
            'second_surname' => 'User',
            'gmail' => 'admin-variant-update@example.com',
            'password' => bcrypt('password'),
        ]);

        $this->actingAs($admin, 'admin');
    }

    /** CP72-01 / CP72-05 — Precio y stock de la variante; producto base intacto. */
    public function test_updates_variant_price_and_stock_without_changing_base_product(): void
    {
        $this->actingAsAdmin();

        $base = Product::create([
            'name' => 'Base Product',
            'sale_price' => 100,
            'purchase_price' => 40,
            'stock_current' => 50,
            'stock_minimum' => 2,
            'status' => 'active',
            'is_featured' => false,
        ]);
        $variant = Product::create([
            'name' => 'Variant Red',
            'sale_price' => 120,
            'purchase_price' => 50,
            'stock_current' => 10,
            'stock_minimum' => 1,
            'status' => 'active',
            'is_featured' => false,
        ]);
        ProductVariant::create([
            'base_product_id' => $base->product_id,
            'variant_product_id' => $variant->product_id,
        ]);

        $res = $this->putJson("/products/{$base->product_id}/variants/{$variant->product_id}", [
            'sale_price' => 199.99,
            'stock_current' => 33,
        ]);

        $res->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Variante actualizada correctamente.',
            ]);

        $variant->refresh();
        $this->assertSame('199.99', (string) $variant->sale_price);
        $this->assertSame(33, (int) $variant->stock_current);

        $base->refresh();
        $this->assertSame('Base Product', $base->name);
        $this->assertSame('100.00', (string) $base->sale_price);
        $this->assertSame(50, (int) $base->stock_current);
    }

    /** CP72-02 — No modificar SKU si hay ventas asociadas. */
    public function test_blocks_sku_change_when_variant_has_sales(): void
    {
        $this->actingAsAdmin();

        $base = Product::create([
            'name' => 'Base',
            'sale_price' => 100,
            'purchase_price' => 40,
            'stock_current' => 5,
            'stock_minimum' => 1,
            'status' => 'active',
            'is_featured' => false,
        ]);
        $variant = Product::create([
            'name' => 'Variant With Sales',
            'sale_price' => 110,
            'purchase_price' => 50,
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
            'invoice_number' => 'CF72-SKU-LOCK',
            'subtotal' => 110,
            'iva' => 0,
            'discount' => 0,
            'total' => 110,
            'payment_method' => 'cash',
            'status' => 'completed',
        ]);
        SaleItem::create([
            'sale_id' => $sale->sale_id,
            'product_id' => $variant->product_id,
            'quantity' => 1,
            'unit_price' => 110,
            'unit_discount' => 0,
            'total' => 110,
        ]);

        $res = $this->putJson("/products/{$base->product_id}/variants/{$variant->product_id}", [
            'sale_price' => 115,
            'stock_current' => 4,
            'sku' => 'NEW-SKU-72',
        ]);

        $res->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'No se puede modificar el SKU porque esta variante ya tiene ventas asociadas.',
            ]);

        $variant->refresh();
        $this->assertNull($variant->sku);
    }

    public function test_with_sales_can_update_price_and_stock_without_sending_sku(): void
    {
        $this->actingAsAdmin();

        $base = Product::create([
            'name' => 'Base',
            'sale_price' => 100,
            'purchase_price' => 40,
            'stock_current' => 5,
            'stock_minimum' => 1,
            'status' => 'active',
            'is_featured' => false,
        ]);
        $variant = Product::create([
            'name' => 'Variant',
            'sale_price' => 110,
            'purchase_price' => 50,
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
            'invoice_number' => 'CF72-NO-SKU-KEY',
            'subtotal' => 110,
            'iva' => 0,
            'discount' => 0,
            'total' => 110,
            'payment_method' => 'cash',
            'status' => 'completed',
        ]);
        SaleItem::create([
            'sale_id' => $sale->sale_id,
            'product_id' => $variant->product_id,
            'quantity' => 1,
            'unit_price' => 110,
            'unit_discount' => 0,
            'total' => 110,
        ]);

        $res = $this->putJson("/products/{$base->product_id}/variants/{$variant->product_id}", [
            'sale_price' => 125,
            'stock_current' => 2,
        ]);

        $res->assertOk()->assertJson(['success' => true]);
        $variant->refresh();
        $this->assertSame('125.00', (string) $variant->sale_price);
        $this->assertSame(2, (int) $variant->stock_current);
    }

    /** CP72-03 — SKU editable sin ventas; único en catálogo. */
    public function test_allows_sku_update_when_no_sales(): void
    {
        $this->actingAsAdmin();

        $base = Product::create([
            'name' => 'Base',
            'sale_price' => 100,
            'purchase_price' => 40,
            'stock_current' => 5,
            'stock_minimum' => 1,
            'status' => 'active',
            'is_featured' => false,
        ]);
        $variant = Product::create([
            'name' => 'Variant',
            'sale_price' => 110,
            'purchase_price' => 50,
            'stock_current' => 5,
            'stock_minimum' => 1,
            'status' => 'active',
            'is_featured' => false,
        ]);
        ProductVariant::create([
            'base_product_id' => $base->product_id,
            'variant_product_id' => $variant->product_id,
        ]);

        $res = $this->putJson("/products/{$base->product_id}/variants/{$variant->product_id}", [
            'sale_price' => 110,
            'stock_current' => 5,
            'sku' => 'CF72-CUSTOM-001',
        ]);

        $res->assertOk()->assertJson(['success' => true]);

        $variant->refresh();
        $this->assertSame('CF72-CUSTOM-001', $variant->sku);
        $this->assertSame('CF72-CUSTOM-001', $variant->displaySku());
    }

    public function test_rejects_duplicate_sku_used_by_another_product(): void
    {
        $this->actingAsAdmin();

        Product::create([
            'name' => 'Otro',
            'sku' => 'TAKEN-SKU',
            'sale_price' => 50,
            'purchase_price' => 20,
            'stock_current' => 1,
            'stock_minimum' => 1,
            'status' => 'active',
            'is_featured' => false,
        ]);

        $base = Product::create([
            'name' => 'Base',
            'sale_price' => 100,
            'purchase_price' => 40,
            'stock_current' => 5,
            'stock_minimum' => 1,
            'status' => 'active',
            'is_featured' => false,
        ]);
        $variant = Product::create([
            'name' => 'Variant',
            'sale_price' => 110,
            'purchase_price' => 50,
            'stock_current' => 5,
            'stock_minimum' => 1,
            'status' => 'active',
            'is_featured' => false,
        ]);
        ProductVariant::create([
            'base_product_id' => $base->product_id,
            'variant_product_id' => $variant->product_id,
        ]);

        $res = $this->putJson("/products/{$base->product_id}/variants/{$variant->product_id}", [
            'sale_price' => 110,
            'stock_current' => 5,
            'sku' => 'TAKEN-SKU',
        ]);

        $res->assertStatus(422)->assertJson(['success' => false]);
        $this->assertArrayHasKey('errors', $res->json());
    }

    /** CP72-04 — Validación precio / stock. */
    public function test_validation_rejects_negative_price_and_stock(): void
    {
        $this->actingAsAdmin();

        $base = Product::create([
            'name' => 'Base',
            'sale_price' => 100,
            'purchase_price' => 40,
            'stock_current' => 5,
            'stock_minimum' => 1,
            'status' => 'active',
            'is_featured' => false,
        ]);
        $variant = Product::create([
            'name' => 'Variant',
            'sale_price' => 110,
            'purchase_price' => 50,
            'stock_current' => 5,
            'stock_minimum' => 1,
            'status' => 'active',
            'is_featured' => false,
        ]);
        ProductVariant::create([
            'base_product_id' => $base->product_id,
            'variant_product_id' => $variant->product_id,
        ]);

        $res = $this->putJson("/products/{$base->product_id}/variants/{$variant->product_id}", [
            'sale_price' => -10,
            'stock_current' => -1,
        ]);

        $res->assertStatus(422)->assertJson(['success' => false]);
    }

    public function test_returns_404_when_variant_not_linked_to_base(): void
    {
        $this->actingAsAdmin();

        $baseA = Product::create([
            'name' => 'Base A',
            'sale_price' => 100,
            'purchase_price' => 40,
            'stock_current' => 5,
            'stock_minimum' => 1,
            'status' => 'active',
            'is_featured' => false,
        ]);
        $baseB = Product::create([
            'name' => 'Base B',
            'sale_price' => 100,
            'purchase_price' => 40,
            'stock_current' => 5,
            'stock_minimum' => 1,
            'status' => 'active',
            'is_featured' => false,
        ]);
        $variant = Product::create([
            'name' => 'Variant',
            'sale_price' => 110,
            'purchase_price' => 50,
            'stock_current' => 5,
            'stock_minimum' => 1,
            'status' => 'active',
            'is_featured' => false,
        ]);
        ProductVariant::create([
            'base_product_id' => $baseA->product_id,
            'variant_product_id' => $variant->product_id,
        ]);

        $res = $this->putJson("/products/{$baseB->product_id}/variants/{$variant->product_id}", [
            'sale_price' => 110,
            'stock_current' => 5,
        ]);

        $res->assertStatus(404)->assertJson(['success' => false]);
    }
}
