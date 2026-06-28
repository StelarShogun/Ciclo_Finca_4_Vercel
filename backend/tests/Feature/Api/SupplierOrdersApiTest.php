<?php

namespace Tests\Feature\Api;

use App\Models\AdminUser;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * API v1 admin supplier-orders: auth, listado, búsqueda de productos, alta
 * (estado draft), detalle, transición de estado y recepción (entrada de stock).
 * La recepción suma stock vía SupplierOrderWorkflowService (transacciones).
 */
class SupplierOrdersApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['sanctum.stateful' => ['localhost', 'localhost:3000', '127.0.0.1']]);
        $this->withHeader('Origin', 'http://localhost:3000');
    }

    private function admin(): AdminUser
    {
        return AdminUser::firstOrCreate(
            ['gmail' => 'so-admin@example.com'],
            ['name' => 'SO', 'first_surname' => 'Admin', 'second_surname' => null, 'password' => bcrypt('password123'), 'last_access' => now()],
        );
    }

    private function supplier(): Supplier
    {
        return Supplier::firstOrCreate(
            ['email' => 'so-supplier@example.com'],
            ['name' => 'Proveedor SO', 'primary_contact' => 'Contacto', 'phone' => '0000', 'address' => 'Addr', 'delivery_time' => 3, 'rating' => 5.0, 'status' => 'active'],
        );
    }

    private function product(Supplier $supplier): Product
    {
        Category::firstOrCreate(['name' => 'Cat SO']);

        return Product::factory()->create([
            'supplier_id' => $supplier->supplier_id,
            'stock_current' => 4,
            'purchase_price' => 500,
            'sale_price' => 1000,
        ]);
    }

    public function test_requires_authentication(): void
    {
        $this->getJson('/api/v1/admin/supplier-orders')->assertStatus(401);
    }

    public function test_index_returns_payload(): void
    {
        $this->actingAs($this->admin(), 'admin');
        $supplier = $this->supplier();
        Order::create([
            'supplier_id' => $supplier->supplier_id,
            'po_number' => 'PO-API-1',
            'date' => now(),
            'state' => 'confirmed',
            'total' => 1000,
        ]);

        $this->getJson('/api/v1/admin/supplier-orders')
            ->assertOk()
            ->assertJsonStructure(['data' => ['orders', 'pagination', 'openSupplierOrdersCount', 'suppliers', 'filters']]);
    }

    public function test_search_products_filters_by_supplier(): void
    {
        $this->actingAs($this->admin(), 'admin');
        $supplier = $this->supplier();
        $product = $this->product($supplier);

        $this->getJson("/api/v1/admin/supplier-orders/search-products?supplier_id={$supplier->supplier_id}")
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('products.0.product_id', (int) $product->product_id);
    }

    public function test_store_creates_draft_order(): void
    {
        $this->actingAs($this->admin(), 'admin');
        $supplier = $this->supplier();
        $product = $this->product($supplier);

        $this->postJson('/api/v1/admin/supplier-orders', [
            'supplier_id' => $supplier->supplier_id,
            'items' => [['product_id' => $product->product_id, 'quantity' => 3]],
        ])->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.state', 'draft');

        $this->assertDatabaseHas('orders', ['supplier_id' => $supplier->supplier_id, 'state' => 'draft']);
    }

    public function test_show_returns_detail(): void
    {
        $this->actingAs($this->admin(), 'admin');
        $supplier = $this->supplier();
        $order = Order::create([
            'supplier_id' => $supplier->supplier_id,
            'po_number' => 'PO-API-2',
            'date' => now(),
            'state' => 'draft',
            'total' => 500,
        ]);

        $this->getJson("/api/v1/admin/supplier-orders/{$order->num_order}")
            ->assertOk()
            ->assertJsonPath('data.num_order', (int) $order->num_order)
            ->assertJsonPath('data.state', 'draft');
    }

    public function test_receive_adds_stock(): void
    {
        $this->actingAs($this->admin(), 'admin');
        $supplier = $this->supplier();
        $product = $this->product($supplier);
        $before = $product->fresh()->stock_current;

        $order = Order::create([
            'supplier_id' => $supplier->supplier_id,
            'po_number' => 'PO-API-3',
            'date' => now(),
            'state' => 'confirmed',
            'total' => 1500,
        ]);
        $item = OrderItem::create([
            'order_num_order' => $order->num_order,
            'product_id' => $product->product_id,
            'name' => $product->name,
            'quantity' => 3,
            'unit_price' => 500,
            'total' => 1500,
        ]);

        $this->postJson("/api/v1/admin/supplier-orders/{$order->num_order}/receive", [
            'items' => [['order_item_id' => $item->id, 'received_quantity' => 3]],
        ])->assertOk()->assertJsonPath('success', true);

        $this->assertSame($before + 3, $product->fresh()->stock_current);
    }
}
