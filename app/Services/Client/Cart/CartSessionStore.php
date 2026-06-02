<?php

namespace App\Services\Client\Cart;

use Illuminate\Support\Facades\Session;

final class CartSessionStore
{
    public const SESSION_KEY = 'cart';

    /**
     * @return array<int, array<string, mixed>>
     */
    public function get(): array
    {
        return Session::get(self::SESSION_KEY, []);
    }

    /**
     * @param  array<int, array<string, mixed>>  $cart
     */
    public function put(array $cart): void
    {
        Session::put(self::SESSION_KEY, $cart);
    }

    public function forget(): void
    {
        Session::forget(self::SESSION_KEY);
    }
}
