<?php

namespace App\Http\Resources\Admin;

use App\Models\Brand;
use App\Models\Product;
use App\Models\SaleItem;
use App\Services\Shared\Media\ProductImageUrls;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $product = $this->resource;
        $data = $product->toArray();
        $firstBrand = $product->brands->first();
        $variantIds = $product->variants->pluck('product_id')->map(fn ($id) => (int) $id)->unique()->values()->all();
        $lockedVariantIds = $variantIds === []
            ? []
            : SaleItem::query()
                ->whereIn('product_id', $variantIds)
                ->distinct()
                ->pluck('product_id')
                ->map(fn ($id) => (int) $id)
                ->all();
        $lockedSet = array_fill_keys($lockedVariantIds, true);

        $data['brand_id'] = $firstBrand instanceof Brand ? $firstBrand->id : null;
        $data['classification_value_ids'] = $product->classificationValues->pluck('id')->values()->all();
        $data['media_main'] = $this->safeMediaUrl(fn () => $product->getFirstMediaUrl('main_image'));
        $data['media_gallery'] = $product->getMedia('gallery')
            ->map(fn ($media) => $this->safeMediaUrl(fn () => $media->getUrl()))
            ->filter()
            ->values()
            ->toArray();
        $data['uses_placeholder_image'] = ProductImageUrls::usesPlaceholder($product);
        $data['placeholder_icon_class'] = ProductImageUrls::placeholderIconClass($product);
        $data['variants'] = $product->variants
            ->map(fn (Product $variant): array => [
                'product_id' => (int) $variant->product_id,
                'name' => (string) $variant->name,
                'status' => (string) $variant->status,
                'stock_current' => (int) $variant->stock_current,
                'sale_price' => (string) $variant->sale_price,
                'sku' => $variant->displaySku(),
                'sku_custom' => $variant->sku,
                'sku_locked' => isset($lockedSet[(int) $variant->product_id]),
            ])
            ->values()
            ->all();

        return $data;
    }

    private function safeMediaUrl(callable $resolver): string
    {
        try {
            return (string) $resolver();
        } catch (\Throwable) {
            return '';
        }
    }
}
