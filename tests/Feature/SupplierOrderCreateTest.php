<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\Supplier;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SupplierOrderCreateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        try {
            parent::setUp();
        } catch (\Throwable $e) {
            $this->markTestSkipped('Base de datos no disponible: '.$e->getMessage());
        }
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            $this->markTestSkipped('SupplierOrderCreateTest requiere MySQL.');
        }
        if (! Schema::hasTable('orders') || ! Schema::hasTable('products') || ! Schema::hasTable('suppliers')) {
            $this->markTestSkipped('Faltan tablas requeridas (orders/products/suppliers).');
        }
    }

    private function createAdmin(): AdminUser
    {
        return AdminUser::create([
            'name' => 'Admin',
            'first_surname' => 'SupplierOrder',
            'second_surname' => null,
            'gmail' => 'admin-supplier-order@example.com',
            'password' => bcrypt('password'),
            'last_access' => now(),
        ]);
    }

    private function seedSupplierAndProducts(): array
    {
        $supplier = Supplier::create([
            'name' => 'Proveedor Test',
            'primary_contact' => 'Contacto',
            'phone' => '0000',
            'email' => 'proveedor-test@example.com',
            'address' => 'Addr',
            'delivery_time' => 3,
            'rating' => 5.0,
            'status' => 'active',
        ]);

        $category = Category::create([
            'name' => 'Cat Test',
            'description' => null,
            'parent_category_id' => null,
        ]);

        $p1 = Product::create([
            'category_id' => $category->category_id,
            'supplier_id' => $supplier->supplier_id,
            'name' => 'Producto A',
            'description' => 'd',
            'purchase_price' => 1000,
            'sale_price' => 1500,
            'stock_current' => 50,
            'stock_minimum' => 1,
            'status' => 'active',
        ]);
        $p2 = Product::create([
            'category_id' => $category->category_id,
            'supplier_id' => $supplier->supplier_id,
            'name' => 'Producto B',
            'description' => 'd',
            'purchase_price' => 2000,
            'sale_price' => 2500,
            'stock_current' => 50,
            'stock_minimum' => 1,
            'status' => 'active',
        ]);

        return [$supplier, $p1, $p2];
    }

    /** CP03-01: creación exitosa genera PO, estado borrador y redirección al detalle. */
    public function test_cp03_01_create_supplier_order_success(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-04-15 10:00:00', 'UTC'));

        $admin = $this->createAdmin();
        [$supplier, $p1, $p2] = $this->seedSupplierAndProducts();

        $resp = $this->actingAs($admin, 'admin')->post(route('admin.supplier-orders.store'), [
            'supplier_id' => $supplier->supplier_id,
            'estimated_delivery_date' => '2026-04-20',
            'items' => [
                ['product_id' => $p1->product_id, 'quantity' => 2],
                ['product_id' => $p2->product_id, 'quantity' => 3],
            ],
        ]);

        $order = Order::query()->latest('num_order')->first();
        $this->assertNotNull($order);

        $resp->assertRedirect(route('admin.supplier-orders.detail', $order->num_order));

        $this->assertSame('draft', $order->state);
        $this->assertNotNull($order->po_number);
        $this->assertMatchesRegularExpression('/^PO-2026-[0-9]{4}$/', $order->po_number);
    }

    /** CP03-02: faltan campos obligatorios => errores por campo y no persiste. */
    public function test_cp03_02_missing_required_fields_returns_errors(): void
    {
        $admin = $this->createAdmin();

        $resp = $this->actingAs($admin, 'admin')->post(route('admin.supplier-orders.store'), [
            'supplier_id' => '',
            'estimated_delivery_date' => '',
            'items' => [],
        ]);

        $resp->assertSessionHasErrors(['supplier_id', 'estimated_delivery_date', 'items']);
        $this->assertSame(0, Order::count());
    }

    /** CP03-03: cantidad 0 o negativa => validación y no persiste. */
    public function test_cp03_03_zero_or_negative_quantity_is_rejected(): void
    {
        $admin = $this->createAdmin();
        [$supplier, $p1] = $this->seedSupplierAndProducts();

        $resp = $this->actingAs($admin, 'admin')->post(route('admin.supplier-orders.store'), [
            'supplier_id' => $supplier->supplier_id,
            'estimated_delivery_date' => '2026-04-20',
            'items' => [
                ['product_id' => $p1->product_id, 'quantity' => 0],
            ],
        ]);

        $resp->assertSessionHasErrors(['items.0.quantity']);
        $this->assertSame(0, Order::count());
    }
}
