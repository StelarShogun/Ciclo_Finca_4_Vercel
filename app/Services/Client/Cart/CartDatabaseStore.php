<?php

namespace App\Services\Client\Cart;

use App\Models\CartItem;

final class CartDatabaseStore
{
    /**
     * Persist the session cart to the cart_items table.
     * Removes DB items no longer in cart and upserts the rest.
     *
     * @param  array<int, array<string, mixed>>  $cart
     */
    public function save(int $clientId, array $cart): void
    {
        $productIds = array_column($cart, 'product_id');

        CartItem::where('client_id', $clientId)
            ->when(
                ! empty($productIds),
                fn ($q) => $q->whereNotIn('product_id', $productIds),
                fn ($q) => $q
            )
            ->delete();

        foreach ($cart as $item) {
            CartItem::updateOrCreate(
                ['client_id' => $clientId, 'product_id' => $item['product_id']],
                ['quantity' => (int) $item['quantity']]
            );
        }
    }

    /**
     * Load cart items from DB and return them in session-compatible format.
     * Prices and names are read from the current product state.
     *
     * @return array<int, array<string, mixed>>
     */
    public function load(int $clientId): array
    {
        return CartItem::with('product')
            ->where('client_id', $clientId)
            ->get()
            ->filter(fn ($i) => $i->product !== null)
            ->map(fn ($i) => [
                'product_id' => (int) $i->product_id,
                'name' => (string) $i->product->name,
                'price' => (float) $i->product->sale_price,
                'quantity' => (int) $i->quantity,
                'image' => (string) ($i->product->getFirstMediaUrl('main_image') ?? ''),
            ])
            ->values()
            ->all();
    }
}
