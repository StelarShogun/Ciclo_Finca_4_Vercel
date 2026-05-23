<?php

namespace App\Services;

use App\Models\CartItem;
use Illuminate\Support\Facades\Session;

class CartService
{
    private const SESSION_KEY = 'cart';

    /**
     * Persist the session cart to the cart_items table.
     * Removes DB items no longer in cart and upserts the rest.
     */
    public static function saveToDb(int $clientId, array $cart): void
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
     */
    public static function loadFromDb(int $clientId): array
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
            ->toArray();
    }

    /**
     * Called after a successful login.
     * Merges any pre-login session cart with the user's DB cart and persists the result.
     * When both carts contain the same product, quantities are summed.
     */
    public static function mergeOnLogin(int $clientId): void
    {
        $sessionCart = Session::get(self::SESSION_KEY, []);
        $dbCart = self::loadFromDb($clientId);

        if (empty($sessionCart) && empty($dbCart)) {
            return;
        }

        if (empty($sessionCart)) {
            Session::put(self::SESSION_KEY, $dbCart);

            return;
        }

        if (empty($dbCart)) {
            self::saveToDb($clientId, $sessionCart);

            return;
        }

        // Index session cart by product_id for fast conflict detection.
        $sessionByProduct = [];
        foreach ($sessionCart as $item) {
            $sessionByProduct[(int) $item['product_id']] = $item;
        }

        $merged = [];

        foreach ($dbCart as $dbItem) {
            $pid = (int) $dbItem['product_id'];

            if (isset($sessionByProduct[$pid])) {
                $merged[] = array_merge($sessionByProduct[$pid], [
                    'quantity' => $sessionByProduct[$pid]['quantity'] + $dbItem['quantity'],
                ]);
                unset($sessionByProduct[$pid]);
            } else {
                $merged[] = $dbItem;
            }
        }

        foreach ($sessionByProduct as $item) {
            $merged[] = $item;
        }

        Session::put(self::SESSION_KEY, $merged);
        self::saveToDb($clientId, $merged);
    }
}
