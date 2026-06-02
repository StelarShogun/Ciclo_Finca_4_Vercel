<?php

namespace App\Support\ClientInertia;

use App\Models\Product;
use App\Support\ClientPickupPolicy;
use App\Support\ProductImageUrls;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class CartPagePayloadBuilder
{
    /**
     * @param  list<array<string, mixed>>  $cartItems
     * @return array<string, mixed>
     */
    public function build(LengthAwarePaginator $cartItemsPaginator, float $total): array
    {
        return [
            'items' => collect($cartItemsPaginator->items())->values()->all(),
            'pagination' => [
                'currentPage' => (int) $cartItemsPaginator->currentPage(),
                'lastPage' => (int) $cartItemsPaginator->lastPage(),
                'perPage' => (int) $cartItemsPaginator->perPage(),
                'total' => (int) $cartItemsPaginator->total(),
                'links' => collect($cartItemsPaginator->linkCollection())->map(fn (array $link): array => [
                    'url' => $link['url'],
                    'label' => (string) $link['label'],
                    'active' => (bool) $link['active'],
                    'page' => $link['page'] ?? null,
                ])->values()->all(),
            ],
            'total' => $total,
            'totalFormatted' => '₡'.number_format($total, 0, ',', '.'),
            'pickupPolicyLine' => ClientPickupPolicy::summaryLine(),
            'pickupPolicyNotice' => ClientPickupPolicy::fullNotice(),
            'stockAdjustedMessage' => session('cart_stock_adjusted'),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function itemFromProduct(Product $product, int $qty, float $linePrice): ?array
    {
        if (! $product->isPurchasableByClient() || $qty < 1) {
            return null;
        }

        $image = ProductImageUrls::clientPresentation($product);
        $subtotal = $linePrice * $qty;

        return [
            'product_id' => (int) $product->product_id,
            'name' => (string) $product->name,
            'price' => $linePrice,
            'priceFormatted' => '₡'.number_format($linePrice, 0, ',', '.'),
            'image_url' => $image['image_url'],
            'uses_placeholder_image' => $image['uses_placeholder_image'],
            'placeholder_icon_class' => $image['placeholder_icon_class'],
            'quantity' => $qty,
            'stock_available' => (int) $product->stock_current,
            'subtotal' => $subtotal,
            'subtotalFormatted' => '₡'.number_format($subtotal, 0, ',', '.'),
            'product_url' => $product->clientProductUrl(),
        ];
    }
}
