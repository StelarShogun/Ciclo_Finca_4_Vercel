<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Supplier;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class CF455CancelExpiredReadyOrdersTest extends TestCase
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
                'name' => 'Administrador',
                'first_surname' => 'Sistema',
                'second_surname' => null,
                'password' => bcrypt('Admin2024!@#'),
                'last_access' => null,
            ]
        );
    }

    private function createProduct(int $stock = 10): Product
    {
        static $counter = 0;
        $counter++;

        $supplier = Supplier::create([
            'name' => "Supplier {$counter}",
            'email' => "supplier{$counter}@test.com",
        ]);

        return Product::create([
            'name' => "Product {$counter}",
            'supplier_id' => $supplier->supplier_id,
            'stock_current' => $stock,
            'sale_price' => 1000.00,
            'purchase_price' => 600.00,
            'status' => 'active',
        ]);
    }

    private function createReadyToPickupSale(Product $product, int $quantity, ?string $readyAt = null): Sale
    {
        static $invoiceCounter = 0;
        $invoiceCounter++;

        $sale = Sale::create([
            'invoice_number' => 'TEST-'.str_pad((string) $invoiceCounter, 4, '0', STR_PAD_LEFT),
            'client_id' => null,
            'sale_date' => now(),
            'payment_method' => 'cash',
            'status' => 'ready_to_pickup',
            'order_source' => 'web_cart',
            'subtotal' => $product->sale_price * $quantity,
            'iva' => 0,
            'discount' => 0,
            'total' => $product->sale_price * $quantity,
            'ready_at' => $readyAt ?? now()->subMinutes(5),
        ]);

        SaleItem::create([
            'sale_id' => $sale->sale_id,
            'product_id' => $product->product_id,
            'quantity' => $quantity,
            'unit_price' => $product->sale_price,
            'total' => $product->sale_price * $quantity,
        ]);

        return $sale;
    }

    private function runCommand(): int
    {
        return Artisan::call('orders:cancel-expired-ready', ['--minutes' => 2]);
    }

    // -------------------------------------------------------------------------
    // Tests
    // -------------------------------------------------------------------------

    /** An expired ready_to_pickup order is cancelled when the command runs. */
    public function test_expired_ready_order_is_cancelled(): void
    {
        $product = $this->createProduct(stock: 10);
        $sale = $this->createReadyToPickupSale($product, quantity: 2, readyAt: now()->subMinutes(5));

        $this->runCommand();

        $this->assertDatabaseHas('sales', [
            'sale_id' => $sale->sale_id,
            'status' => 'cancelled',
        ]);
    }

    /** A non-expired ready_to_pickup order is left untouched by the command. */
    public function test_non_expired_ready_order_is_not_cancelled(): void
    {
        $product = $this->createProduct(stock: 10);

        // ready_at is recent — not yet past the 2-minute threshold
        $sale = $this->createReadyToPickupSale($product, quantity: 2, readyAt: now()->subSeconds(30));

        $this->runCommand();

        $this->assertDatabaseHas('sales', [
            'sale_id' => $sale->sale_id,
            'status' => 'ready_to_pickup',
        ]);
    }

    /** Cancelling an expired order restores the product stock via a refund movement. */
    public function test_cancelling_expired_order_restores_stock(): void
    {
        $product = $this->createProduct(stock: 10);
        $sale = $this->createReadyToPickupSale($product, quantity: 3, readyAt: now()->subMinutes(5));
        $stockBefore = (int) $product->stock_current;

        $this->runCommand();

        $product->refresh();
        $this->assertEquals($stockBefore + 3, $product->stock_current);
    }

    /** A refund inventory movement is recorded for each item in the cancelled order. */
    public function test_cancelling_expired_order_records_refund_movement(): void
    {
        $product = $this->createProduct(stock: 10);
        $sale = $this->createReadyToPickupSale($product, quantity: 3, readyAt: now()->subMinutes(5));

        $this->runCommand();

        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => $product->product_id,
            'reference_id' => $sale->sale_id,
            'type' => 'cancelado',
            'origin' => 'cancellation',
            'quantity' => 3,
        ]);
    }

    /** Orders without ready_at set are skipped — they predate the feature. */
    public function test_order_without_ready_at_is_skipped(): void
    {
        $product = $this->createProduct(stock: 10);
        $sale = $this->createReadyToPickupSale($product, quantity: 2, readyAt: null);

        // Force ready_at to null to simulate a pre-migration record
        Sale::where('sale_id', $sale->sale_id)->update(['ready_at' => null]);

        $this->runCommand();

        $this->assertDatabaseHas('sales', [
            'sale_id' => $sale->sale_id,
            'status' => 'ready_to_pickup',
        ]);

        $this->assertDatabaseMissing('inventory_movements', [
            'reference_id' => $sale->sale_id,
        ]);
    }

    /** A cancelled note is appended to the order's notes field. */
    public function test_cancellation_note_is_appended_to_order_notes(): void
    {
        $product = $this->createProduct(stock: 10);
        $sale = $this->createReadyToPickupSale($product, quantity: 2, readyAt: now()->subMinutes(5));

        $this->runCommand();

        $sale->refresh();
        $this->assertStringContainsString(
            'Cancelado automáticamente por vencimiento del plazo de recogida.',
            (string) $sale->notes
        );
    }

    /** Only expired orders are cancelled — non-expired ones in the same run are untouched. */
    public function test_only_expired_orders_are_cancelled_in_mixed_batch(): void
    {
        $product = $this->createProduct(stock: 20);

        $expiredSale = $this->createReadyToPickupSale($product, quantity: 2, readyAt: now()->subMinutes(5));
        $nonExpiredSale = $this->createReadyToPickupSale($product, quantity: 2, readyAt: now()->subSeconds(30));

        $this->runCommand();

        $this->assertDatabaseHas('sales', [
            'sale_id' => $expiredSale->sale_id,
            'status' => 'cancelled',
        ]);

        $this->assertDatabaseHas('sales', [
            'sale_id' => $nonExpiredSale->sale_id,
            'status' => 'ready_to_pickup',
        ]);
    }

    /** Completed orders are never touched by the command, regardless of ready_at. */
    public function test_completed_orders_are_never_cancelled(): void
    {
        $product = $this->createProduct(stock: 10);

        $sale = Sale::create([
            'invoice_number' => 'TEST-COMPLETED-001',
            'client_id' => null,
            'sale_date' => now(),
            'payment_method' => 'cash',
            'status' => 'completed',
            'order_source' => 'web_cart',
            'subtotal' => 1000.00,
            'iva' => 0,
            'discount' => 0,
            'total' => 1000.00,
            'ready_at' => now()->subMinutes(10),
        ]);

        $this->runCommand();

        $this->assertDatabaseHas('sales', [
            'sale_id' => $sale->sale_id,
            'status' => 'completed',
        ]);
    }

    /** The command returns SUCCESS exit code when all cancellations succeed. */
    public function test_command_returns_success_exit_code(): void
    {
        $product = $this->createProduct(stock: 10);
        $this->createReadyToPickupSale($product, quantity: 2, readyAt: now()->subMinutes(5));

        $exitCode = $this->runCommand();

        $this->assertEquals(0, $exitCode);
    }

    /** The command returns SUCCESS when there are no expired orders to process. */
    public function test_command_returns_success_when_nothing_to_cancel(): void
    {
        $exitCode = $this->runCommand();

        $this->assertEquals(0, $exitCode);
    }

    /** Multiple items in one order all get their stock restored individually. */
    public function test_all_items_in_expired_order_get_stock_restored(): void
    {
        $productA = $this->createProduct(stock: 10);
        $productB = $this->createProduct(stock: 20);

        static $invoiceCounter = 500;
        $invoiceCounter++;

        $sale = Sale::create([
            'invoice_number' => 'TEST-MULTI-'.$invoiceCounter,
            'client_id' => null,
            'sale_date' => now(),
            'payment_method' => 'cash',
            'status' => 'ready_to_pickup',
            'order_source' => 'web_cart',
            'subtotal' => 5000.00,
            'iva' => 0,
            'discount' => 0,
            'total' => 5000.00,
            'ready_at' => now()->subMinutes(5),
        ]);

        SaleItem::create([
            'sale_id' => $sale->sale_id,
            'product_id' => $productA->product_id,
            'quantity' => 3,
            'unit_price' => $productA->sale_price,
            'total' => $productA->sale_price * 3,
        ]);

        SaleItem::create([
            'sale_id' => $sale->sale_id,
            'product_id' => $productB->product_id,
            'quantity' => 5,
            'unit_price' => $productB->sale_price,
            'total' => $productB->sale_price * 5,
        ]);

        $this->runCommand();

        $productA->refresh();
        $productB->refresh();

        $this->assertEquals(13, $productA->stock_current);
        $this->assertEquals(25, $productB->stock_current);

        $movementsForThisSale = InventoryMovement::where('reference_id', $sale->sale_id)->count();
        $this->assertEquals(2, $movementsForThisSale);
    }
}
