<?php

namespace App\Actions\Admin\Products;

use App\Http\Requests\Admin\Products\ManualStockAdjustmentRequest;
use App\Models\Product;
use App\Services\InventoryMovementService;

final class RemoveProductManualStock
{
    public function __construct(
        private InventoryMovementService $inventoryService,
    ) {}

    /**
     * @return array{success: true, message: string, stock_current: int}
     */
    public function handle(ManualStockAdjustmentRequest $request, int $productId): array
    {
        $validated = $request->validated();
        $product = Product::findOrFail($productId);

        $this->inventoryService->recordManualExit(
            product: $product,
            quantity: (int) $validated['quantity'],
            reason: $validated['reason'],
        );

        return [
            'success' => true,
            'message' => "Se eliminaron {$validated['quantity']} unidades correctamente.",
            'stock_current' => $product->stock_current,
        ];
    }
}
