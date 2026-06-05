<?php

namespace App\Actions\Client\Cart;

use App\Services\Client\Cart\CartManager;
use App\Services\Client\Cart\CartProductLookup;
use App\Services\Client\Inertia\CartPagePayloadBuilder;
use App\Support\AdminPerPage;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

final class BuildCartPagePayload
{
    public function __construct(
        private CartManager $cart,
        private CartProductLookup $products,
        private CartPagePayloadBuilder $payloadBuilder,
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

        return $this->payloadBuilder->build($cartItemsPaginator, $total);
    }
}
