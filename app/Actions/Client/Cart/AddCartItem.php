<?php

namespace App\Actions\Client\Cart;

use App\Data\Client\Cart\CartMutationResult;
use App\Models\Product;
use App\Services\Client\Cart\CartManager;
use Illuminate\Http\Request;

final class AddCartItem
{
    public function __construct(
        private CartManager $cart,
    ) {}

    public function handle(Request $request): CartMutationResult
    {
        $request->validate([
            'product_id' => 'required|exists:products,product_id',
            'quantity' => 'required|integer|min:1',
        ]);

        $product = Product::findOrFail($request->product_id);

        if (! $product->isPurchasableByClient()) {
            return new CartMutationResult(false, 400, [
                'success' => false,
                'message' => Product::MSG_CLIENT_AGOTADO,
            ]);
        }

        if ($product->stock_current < $request->quantity) {
            return new CartMutationResult(false, 400, [
                'success' => false,
                'message' => $product->stock_current < 1
                    ? Product::MSG_CLIENT_AGOTADO
                    : Product::MSG_CLIENT_STOCK_INSUFICIENTE,
            ]);
        }

        $cart = $this->cart->lines();
        $existingIndex = null;

        foreach ($cart as $index => $item) {
            if ($item['product_id'] == $request->product_id) {
                $existingIndex = $index;
                break;
            }
        }

        if ($existingIndex !== null) {
            $newQuantity = ($cart[$existingIndex]['quantity'] ?? 0) + $request->quantity;

            if ($newQuantity > $product->stock_current) {
                return new CartMutationResult(false, 400, [
                    'success' => false,
                    'message' => $product->stock_current < 1
                        ? Product::MSG_CLIENT_AGOTADO
                        : Product::MSG_CLIENT_STOCK_INSUFICIENTE,
                ]);
            }

            $cart[$existingIndex]['quantity'] = $newQuantity;
        } else {
            $mediaUrl = $product->getFirstMediaUrl('main_image');
            $cart[] = [
                'product_id' => $product->product_id,
                'name' => $product->name,
                'price' => $product->sale_price,
                'quantity' => $request->quantity,
                'image' => $mediaUrl,
            ];
        }

        $this->cart->persist($cart);

        return new CartMutationResult(true, 200, [
            'success' => true,
            'message' => 'Producto agregado al carrito',
            'cart_count' => $this->cart->totalItemCount(),
            'cart_total' => $this->cart->subtotal(),
        ]);
    }
}
