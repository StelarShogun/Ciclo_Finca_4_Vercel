<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\AppSetting;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStateTimeline;
use App\Models\Product;
use App\Models\Supplier;
use App\Services\SupplierDeliveryEstimator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CF4143SupplierOrderEstimatedDeliveryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        try {
            parent::setUp();
        } catch (\Throwable $e) {
            $this->markTestSkipped('Base de datos no disponible para tests: '.$e->getMessage());
        }

        foreach (['suppliers', 'products', 'orders', 'order_items', 'timeline_order_state', 'app_settings'] as $table) {
            if (! Schema::hasTable($table)) {
                $this->markTestSkipped("Falta la tabla requerida ({$table}).");
            }
        }
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function createAdmin(): AdminUser
    {
        return AdminUser::firstOrCreate(
            ['gmail' => 'admin@cicloperez.com'],
            [
                'name' => 'Administrador',
                'first_surname' => 'Sistema',
                'second_surname' => null,
                'password' => bcrypt('Admin2024!@#'),
                'last_access' => null,
            ]
        );
    }

    private function createSupplier(string $name = 'Estimator Supplier'): Supplier
    {
        return Supplier::create([
            'name' => $name,
            'email' => strtolower(str_replace(' ', '.', $name)).'@test.com',
        ]);
    }

    private function createProductFor(Supplier $supplier): Product
    {
        static $counter = 0;
        $counter++;

        return Product::create([
            'name' => "Estimator Product {$counter}",
            'supplier_id' => $supplier->supplier_id,
            'stock_current' => 100,
            'sale_price' => 100.00,
            'purchase_price' => 60.00,
            'status' => 'active',
        ]);
    }

    /**
     * Create a fully-historical order: state = delivered, with confirmed+delivered
     * timeline entries spaced exactly $daysBetween days apart.
     */
    private function createHistoricalDeliveredOrder(Supplier $supplier, int $daysBetween): Order
    {
        static $poCounter = 0;
        $poCounter++;

        $confirmedAt = Carbon::now()->subDays($daysBetween + 30)->startOfDay();
        $deliveredAt = $confirmedAt->copy()->addDays($daysBetween);

        $product = $this->createProductFor($supplier);

        $order = Order::create([
            'supplier_id' => $supplier->supplier_id,
            'po_number' => 'PO-HIST-'.str_pad((string) $poCounter, 4, '0', STR_PAD_LEFT),
            'estimated_delivery_date' => null,
            'date' => $confirmedAt,
            'state' => 'delivered',
            'total' => 60.00,
            'received_at' => $deliveredAt,
            'delivered_at' => $deliveredAt,
        ]);

        OrderItem::create([
            'order_num_order' => $order->num_order,
            'product_id' => $product->product_id,
            'name' => $product->name,
            'quantity' => 1,
            'unit_price' => 60.00,
            'total' => 60.00,
            'received_quantity' => 1,
        ]);

        OrderStateTimeline::create([
            'num_order' => $order->num_order,
            'user_id' => $this->createAdmin()->user_id,
            'state' => 'confirmed',
            'changed_at' => $confirmedAt,
        ]);

        OrderStateTimeline::create([
            'num_order' => $order->num_order,
            'user_id' => $this->createAdmin()->user_id,
            'state' => 'delivered',
            'changed_at' => $deliveredAt,
        ]);

        return $order->fresh();
    }

    /**
     * Create a draft order ready to be transitioned to "confirmed" via the controller.
     */
    private function createDraftOrderFor(Supplier $supplier): Order
    {
        static $poCounter = 0;
        $poCounter++;

        $product = $this->createProductFor($supplier);

        $order = Order::create([
            'supplier_id' => $supplier->supplier_id,
            'po_number' => 'PO-DRAFT-'.str_pad((string) $poCounter, 4, '0', STR_PAD_LEFT),
            'estimated_delivery_date' => null,
            'date' => now(),
            'state' => 'draft',
            'total' => 60.00,
        ]);

        OrderItem::create([
            'order_num_order' => $order->num_order,
            'product_id' => $product->product_id,
            'name' => $product->name,
            'quantity' => 1,
            'unit_price' => 60.00,
            'total' => 60.00,
        ]);

        OrderStateTimeline::create([
            'num_order' => $order->num_order,
            'user_id' => $this->createAdmin()->user_id,
            'state' => 'draft',
            'changed_at' => now(),
        ]);

        return $order->fresh()->load('orderItems');
    }

    // -------------------------------------------------------------------------
    // Tests — Caso 1: histórico con 3 pedidos (5, 7, 9 días) → promedio = 7
    // -------------------------------------------------------------------------

    /** Average of 5, 7 and 9 days of historical deliveries must yield 7. */
    public function test_supplier_with_three_historical_orders_averages_seven_days(): void
    {
        $supplier = $this->createSupplier('Avg Supplier');
        $this->createHistoricalDeliveredOrder($supplier, daysBetween: 5);
        $this->createHistoricalDeliveredOrder($supplier, daysBetween: 7);
        $this->createHistoricalDeliveredOrder($supplier, daysBetween: 9);

        $estimator = app(SupplierDeliveryEstimator::class);
        $average = $estimator->averageDeliveryDays($supplier->supplier_id);

        $this->assertSame(7, $average);
    }

    /**
     * Same scenario as above but checked through the full confirmation flow:
     * the order, when confirmed, persists confirmed_at + 7 days as the estimate.
     */
    public function test_confirming_order_uses_historical_average_for_estimated_delivery(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-01 10:00:00'));

        $supplier = $this->createSupplier('Confirm Avg Supplier');
        $this->createHistoricalDeliveredOrder($supplier, daysBetween: 5);
        $this->createHistoricalDeliveredOrder($supplier, daysBetween: 7);
        $this->createHistoricalDeliveredOrder($supplier, daysBetween: 9);

        $order = $this->createDraftOrderFor($supplier);
        $admin = $this->createAdmin();

        $this->actingAs($admin, 'admin')
            ->patchJson(route('admin.supplier-orders.update-state', $order->num_order), [
                'state' => 'confirmed',
            ])->assertOk()
            ->assertJson(['success' => true]);

        $order->refresh();

        $this->assertSame('confirmed', $order->state);
        $this->assertNotNull($order->estimated_delivery_date, 'Estimated delivery date must be set after confirmation.');
        $this->assertSame(
            Carbon::parse('2026-06-08')->toDateString(),
            $order->estimated_delivery_date->toDateString(),
            'Estimated delivery should be confirmed_at + 7 days.'
        );

        Carbon::setTestNow();
    }

    // -------------------------------------------------------------------------
    // Tests — Caso 2: proveedor sin historial → confirmación + default AppSetting
    // -------------------------------------------------------------------------

    /** A supplier with zero historical orders falls back to the AppSetting default. */
    public function test_supplier_without_history_uses_app_setting_default(): void
    {
        AppSetting::setSupplierOrderDefaultDeliveryDays(10);

        $supplier = $this->createSupplier('No History Supplier');

        $estimator = app(SupplierDeliveryEstimator::class);
        $average = $estimator->averageDeliveryDays($supplier->supplier_id);

        $this->assertNull($average, 'No historical orders -> averageDeliveryDays must return null.');

        $estimate = $estimator->estimateFor(
            order: $this->createDraftOrderFor($supplier),
            confirmedAt: Carbon::parse('2026-06-01 10:00:00'),
        );

        $this->assertSame(
            Carbon::parse('2026-06-11')->toDateString(),
            $estimate->toDateString(),
            'Without history the estimator must use the AppSetting default (10 days here).'
        );
    }

    /** End-to-end version: confirm via HTTP, assert detail shows confirmed_at + default. */
    public function test_confirming_order_without_history_uses_app_setting_default(): void
    {
        AppSetting::setSupplierOrderDefaultDeliveryDays(10);
        Carbon::setTestNow(Carbon::parse('2026-06-01 10:00:00'));

        $supplier = $this->createSupplier('E2E No History Supplier');
        $order = $this->createDraftOrderFor($supplier);
        $admin = $this->createAdmin();

        $this->actingAs($admin, 'admin')
            ->patchJson(route('admin.supplier-orders.update-state', $order->num_order), [
                'state' => 'confirmed',
            ])->assertOk();

        $order->refresh();

        $this->assertSame(
            Carbon::parse('2026-06-11')->toDateString(),
            $order->estimated_delivery_date->toDateString()
        );

        Carbon::setTestNow();
    }

    // -------------------------------------------------------------------------
    // Tests — Caso 3: el formulario de crear NO tiene campo de fecha estimada
    // -------------------------------------------------------------------------

    /** The create form HTML must not expose an estimated_delivery_date input. */
    public function test_create_form_does_not_render_estimated_delivery_field(): void
    {
        $admin = $this->createAdmin();
        // Crear al menos un proveedor para que la vista renderice sin errores.
        $this->createSupplier('Form Supplier');

        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.supplier-orders.create'))
            ->assertOk();

        $html = $response->getContent();

        $this->assertStringNotContainsString(
            'name="estimated_delivery_date"',
            $html,
            'El formulario de crear pedido no debe exponer el campo estimated_delivery_date.'
        );
        $this->assertStringNotContainsString(
            'id="estimated_delivery_date"',
            $html,
            'El formulario de crear pedido no debe contener el input estimated_delivery_date.'
        );
    }

    /**
     * The store endpoint must persist null as estimated_delivery_date even if the
     * client tries to inject one — the field is no longer accepted from the form.
     */
    public function test_store_ignores_estimated_delivery_date_from_request(): void
    {
        $admin = $this->createAdmin();
        $supplier = $this->createSupplier('Store Supplier');
        $product = $this->createProductFor($supplier);

        $this->actingAs($admin, 'admin')
            ->post(route('admin.supplier-orders.store'), [
                'supplier_id' => $supplier->supplier_id,
                'items' => [
                    ['product_id' => $product->product_id, 'quantity' => 2],
                ],
                // Intent: attempt to inject a value; controller must ignore it.
                'estimated_delivery_date' => '2030-01-01',
            ])->assertRedirect();

        $order = Order::where('supplier_id', $supplier->supplier_id)->latest('num_order')->first();

        $this->assertNotNull($order, 'Order should have been created.');
        $this->assertNull(
            $order->estimated_delivery_date,
            'Newly created drafts must keep estimated_delivery_date null — it is computed on confirmation, not on creation.'
        );
    }

    // -------------------------------------------------------------------------
    // Tests — Caso 4: al confirmar, el detalle muestra la fecha calculada
    // -------------------------------------------------------------------------

    /** The detail page renders the estimated_delivery_date after confirmation. */
    public function test_detail_page_displays_estimated_delivery_date_after_confirmation(): void
    {
        AppSetting::setSupplierOrderDefaultDeliveryDays(7);
        Carbon::setTestNow(Carbon::parse('2026-06-01 09:00:00'));

        $admin = $this->createAdmin();
        $supplier = $this->createSupplier('Detail Supplier');
        $order = $this->createDraftOrderFor($supplier);

        $this->actingAs($admin, 'admin')
            ->patchJson(route('admin.supplier-orders.update-state', $order->num_order), [
                'state' => 'confirmed',
            ])->assertOk();

        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.supplier-orders.detail', $order->num_order))
            ->assertOk();

        $html = $response->getContent();

        // The detail blade renders the date as d/m/Y. Confirmed 2026-06-01 + 7 -> 08/06/2026.
        $this->assertStringContainsString('08/06/2026', $html);
        $this->assertStringContainsString('Calculada automáticamente', $html);

        Carbon::setTestNow();
    }

    // -------------------------------------------------------------------------
    // Tests — extra: contrato del estimador con valores límite
    // -------------------------------------------------------------------------

    /** History limited to orders without a delivered timeline entry must fall back to default. */
    public function test_supplier_with_only_confirmed_but_no_delivered_falls_back_to_default(): void
    {
        AppSetting::setSupplierOrderDefaultDeliveryDays(7);

        $supplier = $this->createSupplier('Confirmed Only Supplier');
        $product = $this->createProductFor($supplier);

        // Order in "confirmed" state — no delivered timeline entry yet.
        $order = Order::create([
            'supplier_id' => $supplier->supplier_id,
            'po_number' => 'PO-CONF-ONLY-0001',
            'estimated_delivery_date' => null,
            'date' => now()->subDays(5),
            'state' => 'confirmed',
            'total' => 60.00,
        ]);

        OrderItem::create([
            'order_num_order' => $order->num_order,
            'product_id' => $product->product_id,
            'name' => $product->name,
            'quantity' => 1,
            'unit_price' => 60.00,
            'total' => 60.00,
        ]);

        OrderStateTimeline::create([
            'num_order' => $order->num_order,
            'user_id' => $this->createAdmin()->user_id,
            'state' => 'confirmed',
            'changed_at' => now()->subDays(5),
        ]);

        $average = app(SupplierDeliveryEstimator::class)
            ->averageDeliveryDays($supplier->supplier_id);

        $this->assertNull($average, 'Without delivered entries no average should be computed.');
    }

    /** The estimated order itself must not be considered in its own average. */
    public function test_average_excludes_the_order_being_estimated(): void
    {
        $supplier = $this->createSupplier('Self-Exclude Supplier');

        // One previous order with 5 days delivery.
        $this->createHistoricalDeliveredOrder($supplier, daysBetween: 5);

        // A current order that already has confirmed+delivered (rare but possible
        // when recalculating). It must be excluded so the previous average stays 5.
        $self = $this->createHistoricalDeliveredOrder($supplier, daysBetween: 20);

        $estimator = app(SupplierDeliveryEstimator::class);

        $averageExcludingSelf = $estimator->averageDeliveryDays(
            supplierId: $supplier->supplier_id,
            excludeOrderId: $self->num_order
        );

        $this->assertSame(5, $averageExcludingSelf);
    }
}
