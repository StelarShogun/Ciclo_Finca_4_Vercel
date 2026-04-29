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
use Tests\TestCase;

class SupplierOrderInventoryMovementTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // CA-06 – Acceso restringido a administradores autorizados
    // -------------------------------------------------------------------------

    public function test_guest_cannot_receive_supplier_order(): void
    {
        $order = $this->createConfirmedOrderWithProduct(stockBefore: 10, quantity: 5);

        $payload = $this->buildReceivePayload($order, receivedQty: 5);

        $this->postJson(
            route('admin.supplier-orders.receive', $order->num_order),
            $payload
        )->assertUnauthorized();

        $this->assertDatabaseMissing('inventory_movements', [
            'reference_id' => $order->num_order,
        ]);
    }

    // -------------------------------------------------------------------------
    // CA-01 – Generación automática del movimiento al recibir el pedido
    // -------------------------------------------------------------------------

    public function test_receiving_a_confirmed_order_creates_one_movement_per_product(): void
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

        $this->assertDatabaseCount('inventory_movements', 1);
    }

    public function test_receiving_an_order_with_multiple_products_creates_one_movement_per_line(): void
    {
        $admin    = $this->createAdmin();
        $supplier = Supplier::create($this->supplierData());

        $productA = Product::create($this->productData($supplier, stock: 10));
        $productB = Product::create($this->productData($supplier, stock: 20));

        $order = Order::create([
            'supplier_id'             => $supplier->supplier_id,
            'po_number'               => 'PO-TEST-0001',
            'estimated_delivery_date' => now()->addDays(7)->toDateString(),
            'date'                    => now(),
            'state'                   => 'confirmed',
            'total'                   => 500.00,
        ]);

        $itemA = OrderItem::create([
            'order_num_order' => $order->num_order,
            'product_id'      => $productA->product_id,
            'name'            => $productA->name,
            'quantity'        => 3,
            'unit_price'      => 50.00,
            'total'           => 150.00,
        ]);

        $itemB = OrderItem::create([
            'order_num_order' => $order->num_order,
            'product_id'      => $productB->product_id,
            'name'            => $productB->name,
            'quantity'        => 7,
            'unit_price'      => 50.00,
            'total'           => 350.00,
        ]);

        $this->actingAs($admin, 'admin')
            ->postJson(route('admin.supplier-orders.receive', $order->num_order), [
                'items' => [
                    ['order_item_id' => $itemA->id, 'received_quantity' => 3],
                    ['order_item_id' => $itemB->id, 'received_quantity' => 7],
                ],
            ])->assertOk()
              ->assertJson(['success' => true]);

        $this->assertDatabaseCount('inventory_movements', 2);

        $this->assertDatabaseHas('inventory_movements', [
            'product_id'   => $productA->product_id,
            'reference_id' => $order->num_order,
        ]);

        $this->assertDatabaseHas('inventory_movements', [
            'product_id'   => $productB->product_id,
            'reference_id' => $order->num_order,
        ]);
    }

    // -------------------------------------------------------------------------
    // CA-02 – Datos registrados en cada movimiento
    // -------------------------------------------------------------------------

    public function test_generated_movement_contains_all_required_fields(): void
    {
        $admin = $this->createAdmin();
        $order = $this->createConfirmedOrderWithProduct(stockBefore: 10, quantity: 5);

        $payload = $this->buildReceivePayload($order, receivedQty: 5);

        $this->actingAs($admin, 'admin')
            ->postJson(
                route('admin.supplier-orders.receive', $order->num_order),
                $payload
            )->assertOk();

        $movement = InventoryMovement::first();

        $this->assertNotNull($movement, 'Se esperaba un movimiento de inventario.');
        $this->assertEquals($order->orderItems->first()->product_id, $movement->product_id);
        $this->assertEquals(5,                  $movement->quantity);
        $this->assertEquals(10,                 $movement->stock_before);
        $this->assertEquals(15,                 $movement->stock_after);
        $this->assertEquals($order->num_order,  $movement->reference_id);
        $this->assertEquals($admin->user_id,    $movement->user_id);
        $this->assertEquals('provider',         $movement->origin);
        $this->assertEquals(MovementType::ENTRADA, $movement->type);
    }

    // -------------------------------------------------------------------------
    // CA-03 – Motivo estandarizado del movimiento
    // -------------------------------------------------------------------------

    public function test_movement_note_is_set_to_standardized_supplier_reception_text(): void
    {
        $admin = $this->createAdmin();
        $order = $this->createConfirmedOrderWithProduct(stockBefore: 10, quantity: 5);

        $payload = $this->buildReceivePayload($order, receivedQty: 5);

        $this->actingAs($admin, 'admin')
            ->postJson(
                route('admin.supplier-orders.receive', $order->num_order),
                $payload
            )->assertOk();

        $this->assertDatabaseHas('inventory_movements', [
            'origin' => 'provider',
            'notes'  => InventoryMovementService::ORIGIN_NOTES['provider'],
        ]);
    }

    public function test_origin_notes_constant_returns_expected_supplier_text(): void
    {
        $this->assertEquals(
            'Recepción de pedido de proveedor',
            InventoryMovementService::ORIGIN_NOTES['provider']
        );
    }

    // -------------------------------------------------------------------------
    // CA-04 – Listado de movimientos filtrable
    // -------------------------------------------------------------------------

    public function test_movement_history_can_be_filtered_by_product(): void
    {
        $admin = $this->createAdmin();

        $supplier = Supplier::create($this->supplierData());
        $productA = Product::create($this->productData($supplier, stock: 10));
        $productB = Product::create($this->productData($supplier, stock: 20));

        // Genera un movimiento para el producto A.
        $orderA = $this->createOrderForProduct($productA, quantity: 3, state: 'confirmed');
        $this->actingAs($admin, 'admin')
            ->postJson(route('admin.supplier-orders.receive', $orderA->num_order), [
                'items' => [
                    [
                        'order_item_id'     => $orderA->orderItems->first()->id,
                        'received_quantity'  => 3,
                    ],
                ],
            ])->assertOk();

        // Genera un movimiento para el producto B.
        $orderB = $this->createOrderForProduct($productB, quantity: 5, state: 'confirmed');
        $this->actingAs($admin, 'admin')
            ->postJson(route('admin.supplier-orders.receive', $orderB->num_order), [
                'items' => [
                    [
                        'order_item_id'     => $orderB->orderItems->first()->id,
                        'received_quantity'  => 5,
                    ],
                ],
            ])->assertOk();

        // Filtra el historial por el producto A.
        $response = $this->actingAs($admin, 'admin')
            ->getJson(route('admin.inventory-movements.json', [
                'productId' => $productA->product_id,
            ]))->assertOk();

        $ids = collect($response->json('data'))->pluck('id');

        $movementA = InventoryMovement::where('product_id', $productA->product_id)->first();
        $movementB = InventoryMovement::where('product_id', $productB->product_id)->first();

        $this->assertTrue($ids->contains($movementA->id));
        $this->assertFalse($ids->contains($movementB->id));
    }

    public function test_movement_history_can_be_filtered_by_date_range(): void
    {
        $admin   = $this->createAdmin();
        $order   = $this->createConfirmedOrderWithProduct(stockBefore: 10, quantity: 5);
        $product = $order->orderItems->first()->product;

        $this->actingAs($admin, 'admin')
            ->postJson(
                route('admin.supplier-orders.receive', $order->num_order),
                $this->buildReceivePayload($order, receivedQty: 5)
            )->assertOk();

        // Filtro que incluye hoy: debe devolver el movimiento.
        $response = $this->actingAs($admin, 'admin')
            ->getJson(route('admin.inventory-movements.json', [
                'productId' => $product->product_id,
                'date_from' => now()->toDateString(),
                'date_to'   => now()->toDateString(),
            ]))->assertOk();

        $this->assertNotEmpty($response->json('data'));

        // Filtro con rango pasado: no debe devolver el movimiento.
        $responsePast = $this->actingAs($admin, 'admin')
            ->getJson(route('admin.inventory-movements.json', [
                'productId' => $product->product_id,
                'date_from' => now()->subDays(10)->toDateString(),
                'date_to'   => now()->subDays(5)->toDateString(),
            ]))->assertOk();

        $this->assertEmpty($responsePast->json('data'));
    }

    // -------------------------------------------------------------------------
    // CA-05 – Prevención de movimientos duplicados
    // -------------------------------------------------------------------------

    public function test_already_received_order_cannot_be_received_again(): void
    {
        $admin = $this->createAdmin();
        $order = $this->createConfirmedOrderWithProduct(stockBefore: 10, quantity: 5);

        $payload = $this->buildReceivePayload($order, receivedQty: 5);

        // Primera recepción: debe tener éxito.
        $this->actingAs($admin, 'admin')
            ->postJson(
                route('admin.supplier-orders.receive', $order->num_order),
                $payload
            )->assertOk()
             ->assertJson(['success' => true]);

        $this->assertDatabaseCount('inventory_movements', 1);

        // Segunda recepción sobre el mismo pedido ya en estado 'delivered':
        // el controller rechaza la acción y no genera más movimientos.
        $order->refresh();
        $order->load('orderItems');

        $this->actingAs($admin, 'admin')
            ->postJson(
                route('admin.supplier-orders.receive', $order->num_order),
                $payload
            )->assertStatus(422)
             ->assertJson(['success' => false]);

        $this->assertDatabaseCount('inventory_movements', 1);
    }

    public function test_direct_delivered_transition_does_not_duplicate_movements_when_receive_order_already_ran(): void
    {
        $admin = $this->createAdmin();
        $order = $this->createConfirmedOrderWithProduct(stockBefore: 10, quantity: 3);

        // Primera recepción parcial (recibe 2 de 3).
        $item = $order->orderItems->first();

        $this->actingAs($admin, 'admin')
            ->postJson(route('admin.supplier-orders.receive', $order->num_order), [
                'items' => [['order_item_id' => $item->id, 'received_quantity' => 2]],
            ])->assertOk();

        $this->assertDatabaseCount('inventory_movements', 1);

        $order->refresh();
        $this->assertEquals('partial_received', $order->state);

        // Cierra el pedido con faltantes: no debe añadir más movimientos de inventario.
        $this->actingAs($admin, 'admin')
            ->postJson(route('admin.supplier-orders.update-state', $order->num_order), [
                'state'  => 'close_partial',
                'reason' => 'Proveedor no entregó el resto.',
            ])->assertOk();

        $this->assertDatabaseCount('inventory_movements', 1);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function createAdmin(): AdminUser
    {
        return AdminUser::create([
            'name'           => 'Admin',
            'first_surname'  => 'Test',
            'second_surname' => null,
            'gmail'          => 'admin-inv-test@example.com',
            'password'       => bcrypt('password'),
            'last_access'    => now(),
        ]);
    }

    private function supplierData(): array
    {
        return [
            'name'  => 'Proveedor Test',
            'email' => 'supplier@test.com',
        ];
    }

    private function productData(Supplier $supplier, int $stock): array
    {
        static $counter = 0;
        $counter++;

        return [
            'name'          => "Producto Test {$counter}",
            'supplier_id'   => $supplier->supplier_id,
            'stock_current' => $stock,
            'sale_price'    => 100.00,
            'purchase_price'=> 60.00,
            'status'        => 'active',
        ];
    }

    private function createConfirmedOrderWithProduct(int $stockBefore, int $quantity): Order
    {
        $supplier = Supplier::create($this->supplierData());
        $product  = Product::create($this->productData($supplier, stock: $stockBefore));

        return $this->createOrderForProduct($product, quantity: $quantity, state: 'confirmed');
    }

    private function createOrderForProduct(Product $product, int $quantity, string $state): Order
    {
        static $poCounter = 0;
        $poCounter++;

        $order = Order::create([
            'supplier_id'             => $product->supplier_id,
            'po_number'               => 'PO-TEST-' . str_pad((string) $poCounter, 4, '0', STR_PAD_LEFT),
            'estimated_delivery_date' => now()->addDays(7)->toDateString(),
            'date'                    => now(),
            'state'                   => $state,
            'total'                   => $quantity * 60.00,
        ]);

        OrderItem::create([
            'order_num_order' => $order->num_order,
            'product_id'      => $product->product_id,
            'name'            => $product->name,
            'quantity'        => $quantity,
            'unit_price'      => 60.00,
            'total'           => $quantity * 60.00,
        ]);

        return $order->load('orderItems');
    }

    private function buildReceivePayload(Order $order, int $receivedQty): array
    {
        return [
            'items' => $order->orderItems->map(fn ($item) => [
                'order_item_id'    => $item->id,
                'received_quantity' => $receivedQty,
            ])->toArray(),
        ];
    }
}
