<?php

namespace App\Http\Resources\Client;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Product */
final class ProductDetailResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'product_id' => (int) $this->product_id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'sku' => $this->sku,
            'price' => (float) $this->sale_price,
            'stock' => (int) $this->stock_current,
            'status' => $this->status,
            'category' => $this->whenLoaded('category', fn () => $this->category?->name),
            'subcategory' => $this->whenLoaded('subcategory', fn () => $this->subcategory?->name),
            'brand' => $this->whenLoaded('brand', fn () => $this->brand?->name),
            'supplier' => $this->whenLoaded('supplier', fn () => $this->supplier?->name),
            'image' => $this->image,
            'images' => is_array($this->images) ? array_values($this->images) : [],
        ];
    }
}
