<?php

namespace Tests\Feature;

use App\Enums\MovementType;
use App\Models\AdminUser;
use App\Models\AuditLog;
use App\Models\InventoryMovement;
use App\Models\Order;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Supplier;
use App\Support\AdminDateRange;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon as SupportCarbon;
use Illuminate\Support\Facades\Schema;
use Tests\Support\InteractsWithMysqlTestDatabase;
use Tests\TestCase;

class CF4157AdminTodayDateFilterTest extends TestCase
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

        $this->skipUnlessMysqlTestDatabase(['sales', 'admins', 'orders', 'products', 'inventory_movements', 'audit_logs']);

        config(['app.timezone' => 'America/Costa_Rica']);
        Carbon::setTestNow(Carbon::parse('2026-05-22 23:58:00', 'America/Costa_Rica'));
        SupportCarbon::setTestNow(Carbon::parse('2026-05-22 23:58:00', 'America/Costa_Rica'));

        $this->admin = AdminUser::firstOrCreate(
            ['gmail' => 'admin-cf4157@example.com'],
            [
                'name' => 'Admin',
                'first_surname' => 'CF4157',
                'second_surname' => null,
                'password' => bcrypt('password'),
                'last_access' => now(),
            ]
        );
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        SupportCarbon::setTestNow();
        parent::tearDown();
    }

    public function test_sales_today_filter_includes_today_and_excludes_yesterday(): void
    {
        $todaySale = $this->createSale('INV-CF157-TODAY', AdminDateRange::now()->utc());
        $yesterdaySale = $this->createSale(
            'INV-CF157-YDAY',
            AdminDateRange::now()->copy()->subDay()->setTime(10, 0)->utc(),
        );

        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('sales.index', ['date_range' => 'today']));

        $response->assertOk();
        $response->assertSee($todaySale->invoice_number, false);
        $response->assertDontSee($yesterdaySale->invoice_number, false);
    }

    public function test_encargos_today_filter_includes_today_and_excludes_yesterday(): void
    {
        $todayOrder = $this->createWebOrder('INV-CF157-ENC-TODAY', AdminDateRange::now()->utc());
        $yesterdayOrder = $this->createWebOrder(
            'INV-CF157-ENC-YDAY',
            AdminDateRange::now()->copy()->subDay()->setTime(12, 0)->utc(),
        );

        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.orders.index', ['date_range' => 'today']));

        $response->assertOk();
        $response->assertSee($todayOrder->invoice_number, false);
        $response->assertDontSee($yesterdayOrder->invoice_number, false);
    }

    public function test_supplier_orders_today_filter_includes_today_and_excludes_yesterday(): void
    {
        $supplier = Supplier::create([
            'name' => 'Proveedor CF157',
            'primary_contact' => 'Contacto',
            'phone' => '0000',
            'email' => 'cf157-supplier@example.com',
            'address' => 'Addr',
            'delivery_time' => 3,
            'rating' => 5.0,
            'status' => 'active',
        ]);

        $todayOrder = Order::create([
            'supplier_id' => $supplier->supplier_id,
            'po_number' => 'PO-CF157-TODAY',
            'estimated_delivery_date' => now()->addDays(3)->toDateString(),
            'date' => AdminDateRange::now()->utc(),
            'state' => 'confirmed',
            'total' => 100,
        ]);

        $yesterdayOrder = Order::create([
            'supplier_id' => $supplier->supplier_id,
            'po_number' => 'PO-CF157-YDAY',
            'estimated_delivery_date' => now()->addDays(3)->toDateString(),
            'date' => AdminDateRange::now()->copy()->subDay()->setTime(9, 30)->utc(),
            'state' => 'confirmed',
            'total' => 200,
        ]);

        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.supplier-orders.index', ['date_range' => 'today']));

        $response->assertOk();
        $response->assertSee($todayOrder->po_number, false);
        $response->assertDontSee($yesterdayOrder->po_number, false);
    }

    public function test_inventory_movements_today_json_excludes_yesterday(): void
    {
        $supplier = Supplier::create([
            'name' => 'Proveedor mov CF157',
            'primary_contact' => 'Contacto',
            'phone' => '0000',
            'email' => 'cf157-mov@example.com',
            'address' => 'Addr',
            'delivery_time' => 3,
            'rating' => 5.0,
            'status' => 'active',
        ]);

        $product = Product::create([
            'name' => 'Producto CF157',
            'supplier_id' => $supplier->supplier_id,
            'stock_current' => 10,
            'stock_minimum' => 1,
            'sale_price' => 100,
            'purchase_price' => 60,
            'status' => 'active',
        ]);
        $todayMovement = $this->createInventoryMovement(
            $product->product_id,
            AdminDateRange::now()->utc(),
            1,
        );

        $yesterdayMovement = $this->createInventoryMovement(
            $product->product_id,
            AdminDateRange::now()->copy()->subDay()->setTime(8, 0)->utc(),
            2,
        );

        $response = $this->actingAs($this->admin, 'admin')
            ->getJson(route('admin.inventory.movements.json', [
                'productId' => $product->product_id,
                'date_range' => 'today',
            ]));

        $response->assertOk();
        $ids = collect($response->json('data'))->pluck('id');
        $this->assertTrue($ids->contains($todayMovement->id));
        $this->assertFalse($ids->contains($yesterdayMovement->id));
    }

    public function test_audit_log_today_filter_includes_today_and_excludes_yesterday(): void
    {
        AuditLog::query()->create([
            'admin_user_id' => $this->admin->user_id,
            'admin_email_snapshot' => $this->admin->gmail,
            'action_type' => 'module_access',
            'module' => 'reports',
            'description' => 'CF157 hoy',
            'meta' => null,
            'created_at' => AdminDateRange::now()->utc(),
        ]);

        AuditLog::query()->create([
            'admin_user_id' => $this->admin->user_id,
            'admin_email_snapshot' => $this->admin->gmail,
            'action_type' => 'module_access',
            'module' => 'reports',
            'description' => 'CF157 ayer',
            'meta' => null,
            'created_at' => AdminDateRange::now()->copy()->subDay()->setTime(15, 0)->utc(),
        ]);

        $today = AdminDateRange::todayDateString();

        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.reports.audit-log', [
                'from' => $today,
                'to' => $today,
            ]));

        $response->assertOk();
        $response->assertSee('CF157 hoy');
        $response->assertDontSee('CF157 ayer');
    }

    private function createSale(string $invoice, Carbon $saleDate): Sale
    {
        return Sale::create([
            'invoice_number' => $invoice,
            'customer_id' => null,
            'client_id' => null,
            'seller_id' => null,
            'seller_admin_id' => $this->admin->user_id,
            'subtotal' => 100,
            'iva' => 0,
            'discount' => 0,
            'total' => 100,
            'payment_method' => 'cash',
            'payment_reference' => null,
            'status' => 'completed',
            'notes' => null,
            'sale_date' => $saleDate,
            'buyer_name' => 'Cliente',
            'buyer_email' => null,
            'order_source' => 'walk_in',
        ]);
    }

    private function createInventoryMovement(int $productId, Carbon $createdAt, int $quantity): InventoryMovement
    {
        $movement = InventoryMovement::create([
            'product_id' => $productId,
            'user_id' => $this->admin->user_id,
            'type' => MovementType::ENTRADA->value,
            'origin' => 'manual_adjustment',
            'quantity' => $quantity,
            'stock_before' => 0,
            'stock_after' => $quantity,
        ]);

        $movement->forceFill([
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ])->save();

        return $movement->fresh();
    }

    private function createWebOrder(string $invoice, Carbon $saleDate): Sale
    {
        return Sale::create([
            'invoice_number' => $invoice,
            'customer_id' => null,
            'client_id' => null,
            'seller_id' => null,
            'seller_admin_id' => $this->admin->user_id,
            'subtotal' => 50,
            'iva' => 0,
            'discount' => 0,
            'total' => 50,
            'payment_method' => 'cash',
            'payment_reference' => null,
            'status' => 'pending',
            'notes' => null,
            'sale_date' => $saleDate,
            'buyer_name' => 'Web',
            'buyer_email' => null,
            'order_source' => 'web_cart',
        ]);
    }
}
