<?php

namespace App\Actions\Admin\Products;

use App\Http\Requests\Admin\Products\ManualStockAdjustmentRequest;

final class UpdateManualStock
{
    public function __construct(
        private AddProductManualStock $addProductManualStock,
        private RemoveProductManualStock $removeProductManualStock,
    ) {}

    /**
     * @return array{success: true, message: string, stock_current: int}
     */
    public function add(ManualStockAdjustmentRequest $request, int $productId): array
    {
        return $this->addProductManualStock->handle($request, $productId);
    }

    /**
     * @return array{success: true, message: string, stock_current: int}
     */
    public function remove(ManualStockAdjustmentRequest $request, int $productId): array
    {
        return $this->removeProductManualStock->handle($request, $productId);
    }
}
