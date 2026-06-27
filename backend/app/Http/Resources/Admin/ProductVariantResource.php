<?php

namespace App\Http\Resources\Admin;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Product */
final class ProductVariantResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'product_id' => (int) $this->product_id,
            'name' => (string) $this->name,
            'status' => (string) $this->status,
            'stock_current' => (int) $this->stock_current,
            'sale_price' => (string) $this->sale_price,
            'sku' => $this->displaySku(),
            'sku_custom' => $this->sku,
            'sku_locked' => (bool) ($this->sku_locked ?? false),
        ];
    }
}
