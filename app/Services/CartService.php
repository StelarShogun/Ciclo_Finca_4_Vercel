<?php

namespace App\Services;

use App\Services\Client\Cart\CartDatabaseStore;
use App\Services\Client\Cart\CartManager;
use App\Services\Client\Cart\CartSessionStore;

/**
 * @deprecated Prefer {@see CartManager}, {@see CartDatabaseStore}, and {@see CartSessionStore}.
 */
class CartService
{
    /**
     * @param  array<int, array<string, mixed>>  $cart
     */
    public static function saveToDb(int $clientId, array $cart): void
    {
        app(CartDatabaseStore::class)->save($clientId, $cart);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function loadFromDb(int $clientId): array
    {
        return app(CartDatabaseStore::class)->load($clientId);
    }

    public static function mergeOnLogin(int $clientId): void
    {
        app(CartManager::class)->mergeOnLogin($clientId);
    }
}
