<?php

namespace App\Services\Client\Cart;

use App\Models\Product;
use Illuminate\Support\Facades\Auth;

final class CartManager
{
    public function __construct(
        private CartSessionStore $session,
        private CartDatabaseStore $database,
    ) {}

    /**
     * Total units in cart (sum of line quantities), shared by JSON APIs and Inertia.
     */
    public function totalItemCount(): int
    {
        return (int) collect($this->session->get())->sum(
            fn (array $item): int => (int) ($item['quantity'] ?? 0)
        );
    }

    public function subtotal(): float
    {
        return array_reduce(
            $this->session->get(),
            fn (float $carry, array $item): float => $carry + ((float) $item['price']) * ((int) $item['quantity']),
            0.0
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function lines(): array
    {
        return $this->session->get();
    }

    /**
     * @param  array<int, array<string, mixed>>  $cart
     */
    public function persist(array $cart): void
    {
        $this->session->put($cart);

        $client = Auth::guard('clients')->user();
        if ($client) {
            $this->database->save((int) $client->user_id, $cart);
        }
    }

    public function clear(): void
    {
        $this->persist([]);
    }

    /**
     * Called after a successful login.
     * Merges any pre-login session cart with the user's DB cart and persists the result.
     */
    public function mergeOnLogin(int $clientId): void
    {
        $sessionCart = $this->session->get();
        $dbCart = $this->database->load($clientId);

        if ($sessionCart === [] && $dbCart === []) {
            return;
        }

        if ($sessionCart === []) {
            $this->session->put($dbCart);

            return;
        }

        if ($dbCart === []) {
            $this->database->save($clientId, $sessionCart);

            return;
        }

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

        $this->session->put($merged);
        $this->database->save($clientId, $merged);
    }

    /**
     * Clamp quantities to current stock and drop unpurchasable lines. Updates session only when it changes.
     */
    public function syncWithStock(): void
    {
        $before = $this->session->get();
        $synced = [];
        $adjustedNames = [];

        foreach ($before as $item) {
            if (! isset($item['product_id'])) {
                continue;
            }

            $product = Product::find($item['product_id']);

            if (! $product || ! $product->isPurchasableByClient()) {
                continue;
            }

            $requested = (int) ($item['quantity'] ?? 0);
            $qty = min($requested, (int) $product->stock_current);

            if ($qty < 1) {
                continue;
            }

            if ($qty < $requested) {
                $adjustedNames[] = $product->name;
            }

            $synced[] = [
                'product_id' => (int) $product->product_id,
                'name' => (string) ($item['name'] ?? $product->name),
                'price' => (float) ($item['price'] ?? $product->sale_price),
                'quantity' => $qty,
                'image' => (string) ($item['image'] ?? ''),
            ];
        }

        $needsPut = ! $this->cartsAreEquivalent($before, $synced)
            || $this->sessionCartHasNonMinimalKeys($before);

        if ($needsPut) {
            $this->persist($synced);
        }

        if ($adjustedNames !== []) {
            session()->flash(
                'cart_stock_adjusted',
                'Ajustamos el carrito al stock disponible para: '.implode(', ', array_unique($adjustedNames)).'.'
            );
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $a
     * @param  array<int, array<string, mixed>>  $b
     */
    private function cartsAreEquivalent(array $a, array $b): bool
    {
        return json_encode($this->normalizeCartForComparison($a)) === json_encode($this->normalizeCartForComparison($b));
    }

    /**
     * @param  array<int, array<string, mixed>>  $cart
     * @return array<int, array{product_id: int, quantity: int, price: float, name: string, image: string}>
     */
    private function normalizeCartForComparison(array $cart): array
    {
        $rows = [];
        foreach ($cart as $item) {
            if (! isset($item['product_id'])) {
                continue;
            }
            $rows[] = [
                'product_id' => (int) $item['product_id'],
                'quantity' => (int) ($item['quantity'] ?? 0),
                'price' => (float) ($item['price'] ?? 0),
                'name' => (string) ($item['name'] ?? ''),
                'image' => (string) ($item['image'] ?? ''),
            ];
        }
        usort($rows, fn ($x, $y) => $x['product_id'] <=> $y['product_id']);

        return $rows;
    }

    /**
     * @param  array<int, array<string, mixed>>  $cart
     */
    private function sessionCartHasNonMinimalKeys(array $cart): bool
    {
        $allowed = ['product_id', 'name', 'price', 'quantity', 'image'];

        foreach ($cart as $item) {
            foreach (array_keys($item) as $key) {
                if (! in_array($key, $allowed, true)) {
                    return true;
                }
            }
        }

        return false;
    }
}
