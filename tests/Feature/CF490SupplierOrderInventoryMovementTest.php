<?php

namespace Tests\Feature;

use App\Enums\MovementType;
use App\Models\AdminUser;
use App\Models\InventoryMovement;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Supplier;
use App\Services\InventoryMovementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CF490SupplierOrderInventoryMovementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        try {
            parent::setUp();
        } catch (\Throwable $e) {
            $this->markTestSkipped('Base de datos no disponible para tests: '.$e->getMessage());
        }

        foreach (['suppliers', 'products', 'orders', 'order_items', 'inventory_movements'] as $table) {
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

    private function supplierData(): array
    {
        return [
            'name' => 'Test Supplier',
            'email' => 'supplier@test.com',
        ];
    }

    private function productData(Supplier $supplier, int $stock): array
    {
        static $counter = 0;
        $counter++;

        return [
            'name' => "Test Product {$counter}",
            'supplier_id' => $supplier->supplier_id,
            'stock_current' => $stock,
            'sale_price' => 100.00,
            'purchase_price' => 60.00,
            'status' => 'active',
        ];
    }

    private function createConfirmedOrderWithProduct(int $stockBefore, int $quantity): Order
    {
        $supplier = Supplier::create($this->supplierData());
        $product = Product::create($this->productData($supplier, stock: $stockBefore));

        return $this->createOrderForProduct($product, quantity: $quantity, state: 'confirmed');
    }

    private function createOrderForProduct(Product $product, int $quantity, string $state): Order
    {
        static $poCounter = 0;
        $poCounter++;

        $order = Order::create([
            'supplier_id' => $product->supplier_id,
            'po_number' => 'PO-TEST-'.str_pad((string) $poCounter, 4, '0', STR_PAD_LEFT),
            'estimated_delivery_date' => now()->addDays(7)->toDateString(),
            'date' => now(),
            'state' => $state,
            'total' => $quantity * 60.00,
        ]);

        OrderItem::create([
            'order_num_order' => $order->num_order,
            'product_id' => $product->product_id,
            'name' => $product->name,
            'quantity' => $quantity,
            'unit_price' => 60.00,
            'total' => $quantity * 60.00,
        ]);

        return $order->load('orderItems');
    }

    private function buildReceivePayload(Order $order, int $receivedQty): array
    {
        return [
            'items' => $order->orderItems->map(fn (OrderItem $item) => [
                'order_item_id' => $item->id,
                'received_quantity' => $receivedQty,
            ])->toArray(),
        ];
    }

    // -------------------------------------------------------------------------
    // Tests
    // -------------------------------------------------------------------------

    /** Receiving a confirmed order creates exactly one movement per product line. */
    public function test_receiving_a_confirmed_order_creates_one_movement_per_product(): void
    {
        $admin = $this->createAdmin();
        $order = $this->createConfirmedOrderWithProduct(stockBefore: 10, quantity: 5);
        $firstItem = $order->orderItems->first();
        if (! $firstItem) {
            $this->markTestSkipped('El pedido de prueba no tiene líneas.');
        }

        $product = $firstItem->product;
        if (! $product) {
            $this->markTestSkipped('La línea de pedido no tiene producto asociado.');
        }

        $this->actingAs($admin, 'admin')
            ->postJson(
                route('admin.supplier-orders.receive', $order->num_order),
                $this->buildReceivePayload($order, receivedQty: 5)
            )->assertOk()
            ->assertJson(['success' => true]);

        // Exactly one movement scoped to this order and product.
        $this->assertSame(
            1,
            InventoryMovement::where('reference_id', $order->num_order)
                ->where('product_id', $product->product_id)
                ->count()
        );
    }

    /** Each product line in a multi-item order gets its own movement record. */
    public function test_receiving_an_order_with_multiple_products_creates_one_movement_per_line(): void
    {
        $admin = $this->createAdmin();
        $supplier = Supplier::create($this->supplierData());
        $productA = Product::create($this->productData($supplier, stock: 10));
        $productB = Product::create($this->productData($supplier, stock: 20));

        $order = Order::create([
            'supplier_id' => $supplier->supplier_id,
            'po_number' => 'PO-TEST-MULTI',
            'estimated_delivery_date' => now()->addDays(7)->toDateString(),
            'date' => now(),
            'state' => 'confirmed',
            'total' => 500.00,
        ]);

        $itemA = OrderItem::create([
            'order_num_order' => $order->num_order,
            'product_id' => $productA->product_id,
            'name' => $productA->name,
            'quantity' => 3,
            'unit_price' => 50.00,
            'total' => 150.00,
        ]);

        $itemB = OrderItem::create([
            'order_num_order' => $order->num_order,
            'product_id' => $productB->product_id,
            'name' => $productB->name,
            'quantity' => 7,
            'unit_price' => 50.00,
            'total' => 350.00,
        ]);

        $this->actingAs($admin, 'admin')
            ->postJson(route('admin.supplier-orders.receive', $order->num_order), [
                'items' => [
                    ['order_item_id' => $itemA->id, 'received_quantity' => 3],
                    ['order_item_id' => $itemB->id, 'received_quantity' => 7],
                ],
            ])->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => $productA->product_id,
            'reference_id' => $order->num_order,
        ]);

        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => $productB->product_id,
            'reference_id' => $order->num_order,
        ]);
    }

    /** Movement record contains the correct product, quantities, origin, type, and user. */
    public function test_generated_movement_contains_all_required_fields(): void
    {
        $admin = $this->createAdmin();
        $order = $this->createConfirmedOrderWithProduct(stockBefore: 10, quantity: 5);

        $this->actingAs($admin, 'admin')
            ->postJson(
                route('admin.supplier-orders.receive', $order->num_order),
                $this->buildReceivePayload($order, receivedQty: 5)
            )->assertOk();

        $movement = InventoryMovement::where('reference_id', $order->num_order)->first();

        $this->assertNotNull($movement, 'Expected an inventory movement to be created.');
        $this->assertEquals($order->orderItems->first()->product_id, $movement->product_id);
        $this->assertEquals(5, $movement->quantity);
        $this->assertEquals(10, $movement->stock_before);
        $this->assertEquals(15, $movement->stock_after);
        $this->assertEquals($order->num_order, $movement->reference_id);
        $this->assertEquals($admin->user_id, $movement->user_id);
        $this->assertEquals('provider', $movement->origin);
        $this->assertEquals(MovementType::ENTRADA, $movement->type);
    }

    /**
     * The movement reason matches the standardized supplier reception text.
     *
     * Columna: inventory_movements.reason  (antes llamada notes — corregido)
     * Constante: InventoryMovementService::ORIGIN_REASONS  (antes ORIGIN_NOTES — corregido)
     */
    public function test_movement_reason_is_set_to_standardized_supplier_reception_text(): void
    {
        $admin = $this->createAdmin();
        $order = $this->createConfirmedOrderWithProduct(stockBefore: 10, quantity: 5);

        $this->actingAs($admin, 'admin')
            ->postJson(
                route('admin.supplier-orders.receive', $order->num_order),
                $this->buildReceivePayload($order, receivedQty: 5)
            )->assertOk();

        $this->assertDatabaseHas('inventory_movements', [
            'reference_id' => $order->num_order,
            'origin' => 'provider',
            'reason' => InventoryMovementService::ORIGIN_REASONS['provider'],
        ]);
    }

    /**
     * The ORIGIN_REASONS constant holds the expected Spanish reception label.
     *
     * Constante renombrada de ORIGIN_NOTES a ORIGIN_REASONS.
     */
    public function test_origin_reasons_constant_returns_expected_supplier_text(): void
    {
        $this->assertEquals(
            'Recepción de pedido de proveedor',
            InventoryMovementService::ORIGIN_REASONS['provider']
        );
    }

    /** Filtering movement history by product ID returns only that product's movements. */
    public function test_movement_history_can_be_filtered_by_product(): void
    {
        $admin = $this->createAdmin();
        $supplier = Supplier::create($this->supplierData());
        $productA = Product::create($this->productData($supplier, stock: 10));
        $productB = Product::create($this->productData($supplier, stock: 20));

        $orderA = $this->createOrderForProduct($productA, quantity: 3, state: 'confirmed');
        $this->actingAs($admin, 'admin')
            ->postJson(route('admin.supplier-orders.receive', $orderA->num_order), [
                'items' => [
                    ['order_item_id' => $orderA->orderItems->first()->id, 'received_quantity' => 3],
                ],
            ])->assertOk();

        $orderB = $this->createOrderForProduct($productB, quantity: 5, state: 'confirmed');
        $this->actingAs($admin, 'admin')
            ->postJson(route('admin.supplier-orders.receive', $orderB->num_order), [
                'items' => [
                    ['order_item_id' => $orderB->orderItems->first()->id, 'received_quantity' => 5],
                ],
            ])->assertOk();

        $response = $this->actingAs($admin, 'admin')
            ->getJson(route('admin.inventory.movements.json', [
                'productId' => $productA->product_id,
            ]))->assertOk();

        $ids = collect($response->json('data'))->pluck('id');

        $movementA = InventoryMovement::where('product_id', $productA->product_id)->first();
        $movementB = InventoryMovement::where('product_id', $productB->product_id)->first();

        $this->assertTrue($ids->contains($movementA->id));
        $this->assertFalse($ids->contains($movementB->id));
    }

    /** Date-range filter returns movements within the range and excludes those outside it. */
    public function test_movement_history_can_be_filtered_by_date_range(): void
    {
        $admin = $this->createAdmin();
        $order = $this->createConfirmedOrderWithProduct(stockBefore: 10, quantity: 5);
        $firstItem = $order->orderItems->first();
        if (! $firstItem) {
            $this->markTestSkipped('El pedido de prueba no tiene líneas.');
        }

        $product = $firstItem->product;
        if (! $product) {
            $this->markTestSkipped('La línea de pedido no tiene producto asociado.');
        }

        $this->actingAs($admin, 'admin')
            ->postJson(
                route('admin.supplier-orders.receive', $order->num_order),
                $this->buildReceivePayload($order, receivedQty: 5)
            )->assertOk();

        $withinRange = $this->actingAs($admin, 'admin')
            ->getJson(route('admin.inventory.movements.json', [
                'productId' => $product->product_id,
                'date_from' => now()->toDateString(),
                'date_to' => now()->toDateString(),
            ]))->assertOk();

        $this->assertNotEmpty($withinRange->json('data'));

        $outsideRange = $this->actingAs($admin, 'admin')
            ->getJson(route('admin.inventory.movements.json', [
                'productId' => $product->product_id,
                'date_from' => now()->subDays(10)->toDateString(),
                'date_to' => now()->subDays(5)->toDateString(),
            ]))->assertOk();

        $this->assertEmpty($outsideRange->json('data'));
    }

    /** Attempting to receive an already-delivered order returns 422 and no new movements. */
    public function test_already_received_order_cannot_be_received_again(): void
    {
        $admin = $this->createAdmin();
        $order = $this->createConfirmedOrderWithProduct(stockBefore: 10, quantity: 5);
        $payload = $this->buildReceivePayload($order, receivedQty: 5);

        $this->actingAs($admin, 'admin')
            ->postJson(
                route('admin.supplier-orders.receive', $order->num_order),
                $payload
            )->assertOk()
            ->assertJson(['success' => true]);

        // One movement created after the first receive.
        $this->assertSame(
            1,
            InventoryMovement::where('reference_id', $order->num_order)->count()
        );

        $order->refresh()->load('orderItems');

        $this->actingAs($admin, 'admin')
            ->postJson(
                route('admin.supplier-orders.receive', $order->num_order),
                $payload
            )->assertStatus(422)
            ->assertJson(['success' => false]);

        // No additional movement after the rejected second receive.
        $this->assertSame(
            1,
            InventoryMovement::where('reference_id', $order->num_order)->count()
        );
    }

    /** Closing a partially received order does not generate additional inventory movements. */
    public function test_closing_partial_order_does_not_add_extra_movements(): void
    {
        $admin = $this->createAdmin();
        $order = $this->createConfirmedOrderWithProduct(stockBefore: 10, quantity: 3);
        $item = $order->orderItems->first();

        $this->actingAs($admin, 'admin')
            ->postJson(route('admin.supplier-orders.receive', $order->num_order), [
                'items' => [['order_item_id' => $item->id, 'received_quantity' => 2]],
            ])->assertOk();

        // One movement after partial receive.
        $this->assertSame(
            1,
            InventoryMovement::where('reference_id', $order->num_order)->count()
        );

        $order->refresh();
        $this->assertEquals('partial_received', $order->state);

        $this->actingAs($admin, 'admin')
            ->postJson(route('admin.supplier-orders.close-partial', $order->num_order), [
                'reason' => 'Proveedor no entregó el resto.',
            ])->assertOk();

        // Still one movement — closing must not create additional movements.
        $this->assertSame(
            1,
            InventoryMovement::where('reference_id', $order->num_order)->count()
        );
    }
}
