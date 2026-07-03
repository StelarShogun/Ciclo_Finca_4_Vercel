<?php

namespace Tests\Unit\Services;

use App\Models\InventoryMovement;
use App\Models\Product;
use App\Services\Admin\Inventory\InventoryMovementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class InventoryMovementServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_manual_entry_updates_stock_and_records_movement(): void
    {
        $product = $this->createProduct(stock: 4);

        $movement = app(InventoryMovementService::class)->recordManualEntry(
            product: $product,
            quantity: 3,
            reason: 'Conteo fisico',
        );

        $this->assertSame(7, (int) $product->stock_current);
        $this->assertSame(7, (int) $product->fresh()->stock_current);
        $this->assertSame(4, (int) $movement->stock_before);
        $this->assertSame(7, (int) $movement->stock_after);
        $this->assertSame('manual_adjustment', $movement->origin);
        $this->assertSame('Conteo fisico', $movement->reason);
    }

    public function test_manual_exit_rejects_negative_stock_without_writing_movement(): void
    {
        $product = $this->createProduct(stock: 2);

        $this->expectException(ValidationException::class);

        try {
            app(InventoryMovementService::class)->recordManualExit(
                product: $product,
                quantity: 3,
                reason: 'Merma',
            );
        } finally {
            $this->assertSame(2, (int) $product->fresh()->stock_current);
            $this->assertSame(0, InventoryMovement::query()->count());
        }
    }

    private function createProduct(int $stock): Product
    {
        return Product::query()->create([
            'name' => 'Producto inventario unitario',
            'description' => null,
            'image' => 'default.png',
            'sale_price' => 100,
            'purchase_price' => 50,
            'stock_current' => $stock,
            'stock_minimum' => 1,
            'status' => 'active',
        ]);
    }
}
