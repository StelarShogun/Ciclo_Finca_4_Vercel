<?php

namespace App\Support;

use App\Models\Brand;
use App\Models\Product;
use Illuminate\Support\Facades\Cache;

/**
 * CF4-166 — client storefront read caches (categories, brand filter, spotlight).
 */
final class ClientStorefrontCache
{
    public const KEY_ROOT_CATEGORIES = 'cf4:client:root_categories';

    public const KEY_CATALOG_BRANDS = 'cf4:client:catalog_brands';

    public const KEY_CATALOG_SPOTLIGHT = 'cf4:client:catalog_spotlight';

    public const KEY_CATALOG_VERSION = 'cf4:client:catalog_version';

    public static function ttlSeconds(int $configured): int
    {
        return min(60, max(30, $configured));
    }

    public static function forgetBrands(): void
    {
        Cache::forget(self::KEY_CATALOG_BRANDS);
        self::bumpCatalogVersion();
    }

    public static function forgetSpotlight(): void
    {
        Cache::forget(self::KEY_CATALOG_SPOTLIGHT);
        self::bumpCatalogVersion();
    }

    public static function forgetCategories(): void
    {
        Cache::forget(self::KEY_ROOT_CATEGORIES);
        self::bumpCatalogVersion();
    }

    public static function forgetAfterProductMutation(): void
    {
        self::forgetSpotlight();
        self::forgetBrands();
    }

    public static function forgetAfterBrandMutation(): void
    {
        self::forgetBrands();
    }

    public static function bumpCatalogVersion(): void
    {
        Cache::forever(self::KEY_CATALOG_VERSION, (string) microtime(true));
    }

    public static function catalogVersion(): string
    {
        $version = Cache::get(self::KEY_CATALOG_VERSION);
        if (is_string($version) && $version !== '') {
            return $version;
        }

        return self::computeCatalogFingerprint();
    }

    private static function computeCatalogFingerprint(): string
    {
        $productStamp = (string) (Product::query()->max('updated_at') ?? '0');
        $brandMaxId = (string) (Brand::query()->max('id') ?? '0');
        $brandCount = (string) Brand::query()->count();

        return hash('xxh128', $productStamp.'|'.$brandMaxId.'|'.$brandCount);
    }
}
