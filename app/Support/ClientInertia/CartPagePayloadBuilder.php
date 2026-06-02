<?php

namespace App\Support\ClientInertia;

use App\Models\Product;
use App\Support\ClientPickupPolicy;
use App\Support\ProductImageUrls;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class CartPagePayloadBuilder
{
    /**
     * @param  LengthAwarePaginator<int, array<string, mixed>>  $cartItemsPaginator
     * @return array<string, mixed>
     */
    public function build(LengthAwarePaginator $cartItemsPaginator, float $total): array
    {
        return [
            'items' => collect($cartItemsPaginator->items())->values()->all(),
            'pagination' => ListPaginationPayload::from($cartItemsPaginator),
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

        $image = ProductImageUrls::cardPicture($product);
        $subtotal = $linePrice * $qty;

        return [
            'productId' => (int) $product->product_id,
            'name' => (string) $product->name,
            'slug' => null,
            'productUrl' => $product->clientProductUrl(),
            'unitPrice' => $linePrice,
            'unitPriceFormatted' => '₡'.number_format($linePrice, 0, ',', '.'),
            'quantity' => $qty,
            'subtotal' => $subtotal,
            'subtotalFormatted' => '₡'.number_format($subtotal, 0, ',', '.'),
            'stockCurrent' => (int) $product->stock_current,
            'canUpdate' => true,
            'image' => [
                'fallback' => $image['fallback'],
                'desktopWebp' => $image['desktopWebp'],
                'mobileWebp' => $image['mobileWebp'],
                'usesPlaceholder' => ProductImageUrls::usesPlaceholder($product),
                'placeholderIconClass' => ProductImageUrls::placeholderIconClass($product),
            ],
        ];
    }
}
