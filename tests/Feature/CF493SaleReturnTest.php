<?php

namespace Tests\Feature;

use App\Enums\MovementType;
use App\Models\AdminUser;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Supplier;
use App\Services\InventoryMovementService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class CF493SaleReturnTest extends TestCase
{
    use DatabaseTransactions;

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function createAdmin(): AdminUser
    {
        return AdminUser::firstOrCreate(
            ['gmail' => 'admin@cicloperez.com'],
            [
                'name'           => 'Administrador',
                'first_surname'  => 'Sistema',
                'second_surname' => null,
                'password'       => bcrypt('Admin2024!@#'),
                'last_access'    => null,
            ]
        );
    }

    private function createProduct(int $stock = 10): Product
    {
        static $counter = 0;
        $counter++;

        $supplier = Supplier::create([
            'name'  => "Supplier {$counter}",
            'email' => "supplier{$counter}@test.com",
        ]);

        return Product::create([
            'name'           => "Product {$counter}",
            'supplier_id'    => $supplier->supplier_id,
            'stock_current'  => $stock,
            'sale_price'     => 100.00,
            'purchase_price' => 60.00,
            'status'         => 'active',
        ]);
    }

    /**
     * Creates a sale with one item in the given status.
     * Stock is NOT decremented here — tests that need prior stock decrement
     * should set the product stock accordingly.
     */
    private function createSaleWithItem(
        Product $product,
        int $quantity,
        string $status = 'completed'
    ): Sale {
        $sale = Sale::create([
            'invoice_number'  => 'TEST-' . strtoupper(uniqid()),
            'seller_admin_id' => null,
            'sale_date'       => now(),
            'payment_method'  => 'cash',
            'status'          => $status,
            'subtotal'        => $quantity * 100.00,
            'iva'             => 0,
            'discount'        => 0,
            'total'           => $quantity * 100.00,
            'order_source'    => 'walk_in',
        ]);

        SaleItem::create([
            'sale_id'    => $sale->sale_id,
            'product_id' => $product->product_id,
            'quantity'   => $quantity,
            'unit_price' => 100.00,
            'total'      => $quantity * 100.00,
        ]);

        return $sale->load('saleItems.product');
    }

    private function returnUrl(Sale $sale): string
    {
        return route('sales.return', $sale->sale_id);
    }

    // -------------------------------------------------------------------------
    // Tests
    // -------------------------------------------------------------------------

    /**
     * A completed sale can be returned with a valid reason.
     * The sale status must change to 'returned'.
     */
    public function test_completed_sale_can_be_returned(): void
    {
        $admin   = $this->createAdmin();
        $product = $this->createProduct(stock: 10);
        $sale    = $this->createSaleWithItem($product, quantity: 2);

        $this->actingAs($admin, 'admin')
            ->postJson($this->returnUrl($sale), ['reason' => 'El cliente devolvió el producto.'])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('sales', [
            'sale_id' => $sale->sale_id,
            'status'  => 'returned',
        ]);
    }

    /**
     * Returning a sale creates exactly one DEVOLUCION movement per sale item.
     */
    public function test_return_creates_one_devolucion_movement_per_item(): void
    {
        $admin   = $this->createAdmin();
        $product = $this->createProduct(stock: 10);
        $sale    = $this->createSaleWithItem($product, quantity: 3);

        $this->actingAs($admin, 'admin')
            ->postJson($this->returnUrl($sale), ['reason' => 'Producto defectuoso.'])
            ->assertOk();

        $movements = InventoryMovement::where('reference_id', $sale->sale_id)->get();

        $this->assertCount(1, $movements, 'Expected exactly one movement for one sale item.');
        $this->assertEquals(MovementType::DEVOLUCION, $movements->first()->type);
        $this->assertEquals('return', $movements->first()->origin);
    }

    /**
     * The stock of each returned product is restored after the return.
     */
    public function test_return_restores_stock_for_each_product(): void
    {
        $admin   = $this->createAdmin();
        $product = $this->createProduct(stock: 5); // stock already reduced by prior sale
        $sale    = $this->createSaleWithItem($product, quantity: 3);

        $this->actingAs($admin, 'admin')
            ->postJson($this->returnUrl($sale), ['reason' => 'El cliente no lo quiso.'])
            ->assertOk();

        $product->refresh();
        $this->assertEquals(8, $product->stock_current); // 5 + 3 returned
    }

    /**
     * The return reason is stored in inventory_movements.reason, not in sales.
     * No return_reason column should be written on the sale.
     */
    public function test_return_reason_is_stored_in_inventory_movements_not_in_sale(): void
    {
        $admin   = $this->createAdmin();
        $product = $this->createProduct(stock: 10);
        $sale    = $this->createSaleWithItem($product, quantity: 2);
        $reason  = 'Talla incorrecta, cliente pidió cambio.';

        $this->actingAs($admin, 'admin')
            ->postJson($this->returnUrl($sale), ['reason' => $reason])
            ->assertOk();

        // Reason lives in inventory_movements.reason
        $this->assertDatabaseHas('inventory_movements', [
            'reference_id' => $sale->sale_id,
            'origin'       => 'return',
            'reason'       => $reason,
        ]);
    }

    /**
     * The movement record contains stock_before and stock_after correctly set.
     */
    public function test_return_movement_has_correct_stock_before_and_after(): void
    {
        $admin   = $this->createAdmin();
        $product = $this->createProduct(stock: 4);
        $sale    = $this->createSaleWithItem($product, quantity: 4);

        $this->actingAs($admin, 'admin')
            ->postJson($this->returnUrl($sale), ['reason' => 'Devolución completa del pedido.'])
            ->assertOk();

        $movement = InventoryMovement::where('reference_id', $sale->sale_id)->first();

        $this->assertNotNull($movement);
        $this->assertEquals(4,  $movement->stock_before);
        $this->assertEquals(8,  $movement->stock_after);  // 4 + 4 returned
        $this->assertEquals(4,  $movement->quantity);
        $this->assertEquals($product->product_id, $movement->product_id);
    }

    /**
     * A sale that is not completed cannot be returned.
     * Pending sales must be rejected with a 400 response.
     */
    public function test_pending_sale_cannot_be_returned(): void
    {
        $admin   = $this->createAdmin();
        $product = $this->createProduct(stock: 10);
        $sale    = $this->createSaleWithItem($product, quantity: 2, status: 'pending');

        $this->actingAs($admin, 'admin')
            ->postJson($this->returnUrl($sale), ['reason' => 'Intento de devolución en pedido pendiente.'])
            ->assertStatus(400)
            ->assertJson(['success' => false]);

        $this->assertDatabaseMissing('inventory_movements', [
            'reference_id' => $sale->sale_id,
            'origin'       => 'return',
        ]);
    }

    /**
     * A sale already returned cannot be returned again.
     */
    public function test_already_returned_sale_cannot_be_returned_again(): void
    {
        $admin   = $this->createAdmin();
        $product = $this->createProduct(stock: 10);
        $sale    = $this->createSaleWithItem($product, quantity: 2);

        $this->actingAs($admin, 'admin')
            ->postJson($this->returnUrl($sale), ['reason' => 'Primera devolución válida.'])
            ->assertOk();

        $this->actingAs($admin, 'admin')
            ->postJson($this->returnUrl($sale), ['reason' => 'Intento de doble devolución.'])
            ->assertStatus(400)
            ->assertJson(['success' => false]);

        // Only one movement should exist
        $this->assertCount(
            1,
            InventoryMovement::where('reference_id', $sale->sale_id)->get()
        );
    }

    /**
     * A return without a reason is rejected with a 422 validation error.
     */
    public function test_return_without_reason_is_rejected(): void
    {
        $admin   = $this->createAdmin();
        $product = $this->createProduct(stock: 10);
        $sale    = $this->createSaleWithItem($product, quantity: 1);

        $this->actingAs($admin, 'admin')
            ->postJson($this->returnUrl($sale), [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['reason']);

        $this->assertDatabaseMissing('sales', [
            'sale_id' => $sale->sale_id,
            'status'  => 'returned',
        ]);
    }

    /**
     * A return reason shorter than 3 characters is rejected.
     */
    public function test_return_with_reason_too_short_is_rejected(): void
    {
        $admin   = $this->createAdmin();
        $product = $this->createProduct(stock: 10);
        $sale    = $this->createSaleWithItem($product, quantity: 1);

        $this->actingAs($admin, 'admin')
            ->postJson($this->returnUrl($sale), ['reason' => 'ab'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['reason']);
    }

    /**
     * A sale with multiple items creates one DEVOLUCION movement per item
     * and restores stock for each product individually.
     */
    public function test_return_with_multiple_items_creates_one_movement_per_item(): void
    {
        $admin    = $this->createAdmin();
        $productA = $this->createProduct(stock: 5);
        $productB = $this->createProduct(stock: 8);

        // Build a sale with two items manually
        $sale = Sale::create([
            'invoice_number'  => 'TEST-' . strtoupper(uniqid()),
            'seller_admin_id' => null,
            'sale_date'       => now(),
            'payment_method'  => 'cash',
            'status'          => 'completed',
            'subtotal'        => 500.00,
            'iva'             => 0,
            'discount'        => 0,
            'total'           => 500.00,
            'order_source'    => 'walk_in',
        ]);

        SaleItem::create([
            'sale_id'    => $sale->sale_id,
            'product_id' => $productA->product_id,
            'quantity'   => 2,
            'unit_price' => 100.00,
            'total'      => 200.00,
        ]);

        SaleItem::create([
            'sale_id'    => $sale->sale_id,
            'product_id' => $productB->product_id,
            'quantity'   => 3,
            'unit_price' => 100.00,
            'total'      => 300.00,
        ]);

        $this->actingAs($admin, 'admin')
            ->postJson($this->returnUrl($sale), ['reason' => 'Devolución de pedido completo.'])
            ->assertOk();

        $movements = InventoryMovement::where('reference_id', $sale->sale_id)->get();
        $this->assertCount(2, $movements);

        $productA->refresh();
        $productB->refresh();
        $this->assertEquals(7,  $productA->stock_current); // 5 + 2
        $this->assertEquals(11, $productB->stock_current); // 8 + 3
    }
}