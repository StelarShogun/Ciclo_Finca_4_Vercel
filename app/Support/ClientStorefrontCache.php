<?php

namespace App\Support;

/**
 * @deprecated Use {@see \App\Services\Client\Storefront\ClientStorefrontCache}.
 */
final class ClientStorefrontCache
{
    public const KEY_ROOT_CATEGORIES = \App\Services\Client\Storefront\ClientStorefrontCache::KEY_ROOT_CATEGORIES;

    public const KEY_CATALOG_BRANDS = \App\Services\Client\Storefront\ClientStorefrontCache::KEY_CATALOG_BRANDS;

    public const KEY_CATALOG_SPOTLIGHT = \App\Services\Client\Storefront\ClientStorefrontCache::KEY_CATALOG_SPOTLIGHT;

    public const KEY_CATALOG_VERSION = \App\Services\Client\Storefront\ClientStorefrontCache::KEY_CATALOG_VERSION;

    public static function ttlSeconds(int $configured): int
    {
        return \App\Services\Client\Storefront\ClientStorefrontCache::ttlSeconds($configured);
    }

    public static function forgetBrands(): void
    {
        \App\Services\Client\Storefront\ClientStorefrontCache::forgetBrands();
    }

    public static function forgetSpotlight(): void
    {
        \App\Services\Client\Storefront\ClientStorefrontCache::forgetSpotlight();
    }

    public static function forgetCategories(): void
    {
        \App\Services\Client\Storefront\ClientStorefrontCache::forgetCategories();
    }

    public static function forgetAfterProductMutation(): void
    {
        \App\Services\Client\Storefront\ClientStorefrontCache::forgetAfterProductMutation();
    }

    public static function forgetAfterBrandMutation(): void
    {
        \App\Services\Client\Storefront\ClientStorefrontCache::forgetAfterBrandMutation();
    }

    public static function bumpCatalogVersion(): void
    {
        \App\Services\Client\Storefront\ClientStorefrontCache::bumpCatalogVersion();
    }

    public static function catalogVersion(): string
    {
        return \App\Services\Client\Storefront\ClientStorefrontCache::catalogVersion();
    }
}
