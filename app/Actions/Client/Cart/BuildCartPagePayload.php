<?php

namespace App\Actions\Client\Cart;

use App\Models\Product;
use App\Services\Client\Cart\CartManager;
use App\Services\Client\Cart\CartProductLookup;
use App\Services\Client\Catalog\CatalogSpotlightBuilder;
use App\Services\Client\Inertia\CartPagePayloadBuilder;
use App\Services\Media\ProductImageUrls;
use App\Support\AdminPerPage;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

final class BuildCartPagePayload
{
    public function __construct(
        private CartManager $cart,
        private CartProductLookup $products,
        private CartPagePayloadBuilder $payloadBuilder,
        private CatalogSpotlightBuilder $spotlightBuilder,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function handle(Request $request): array
    {
        $this->cart->syncWithStock();

        $cart = $this->cart->lines();
        $productsById = $this->products->indexedByProductId($cart);
        $cartItems = [];
        $total = 0.0;

        foreach ($cart as $item) {
            $product = $productsById->get((int) ($item['product_id'] ?? 0));

            if ($product && $product->isPurchasableByClient()) {
                $qty = min((int) $item['quantity'], $product->stock_current);
                $row = CartPagePayloadBuilder::itemFromProduct($product, $qty, (float) $item['price']);
                if ($row !== null) {
                    $total += (float) $row['subtotal'];
                    $cartItems[] = $row;
                }
            }
        }

        $perPage = AdminPerPage::resolve($request->input('per_page', 10));
        $currentPage = LengthAwarePaginator::resolveCurrentPage();

        $cartItemsPaginator = new LengthAwarePaginator(
            collect($cartItems)->forPage($currentPage, $perPage)->values()->all(),
            count($cartItems),
            $perPage,
            $currentPage,
            ['path' => route('clients.cart')]
        );
        $cartItemsPaginator->withQueryString();

        $payload = $this->payloadBuilder->build($cartItemsPaginator, $total);
        $payload['featuredProducts'] = count($cartItems) === 0 ? $this->featuredProducts() : [];

        return $payload;
    }

    /**
     * Productos destacados para mostrar cuando el carrito está vacío.
     *
     * @return array<int, array<string, mixed>>
     */
    private function featuredProducts(): array
    {
        return $this->spotlightBuilder->rows()
            ->take(4)
            ->map(function (array $row): array {
                /** @var Product $product */
                $product = $row['product'];
                $picture = ProductImageUrls::cardPicture($product);

                return [
                    'id' => (int) $product->product_id,
                    'name' => (string) $product->name,
                    'priceFormatted' => '₡'.number_format((float) $product->sale_price, 0, ',', '.'),
                    'url' => $product->clientProductUrl(),
                    'image' => [
                        'fallback' => $picture['fallback'],
                        'desktopWebp' => $picture['desktopWebp'],
                        'mobileWebp' => $picture['mobileWebp'],
                        'usesPlaceholder' => ProductImageUrls::usesPlaceholder($product),
                        'placeholderIconClass' => ProductImageUrls::placeholderIconClass($product),
                    ],
                ];
            })
            ->values()
            ->all();
    }
}
