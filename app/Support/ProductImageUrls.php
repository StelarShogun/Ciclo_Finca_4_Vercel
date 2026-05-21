<?php

namespace App\Support;

use App\Models\Product;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class ProductImageUrls
{
    public static function fallbackUrl(Product $product): string
    {
        $mediaUrl = $product->getFirstMediaUrl('main_image');

        if ($mediaUrl !== '') {
            return $mediaUrl;
        }

        return asset('assets/images/products/'.($product->image ?? 'default.png'));
    }

    public static function webpDesktopUrl(?Media $media): ?string
    {
        if ($media === null || ! $media->hasGeneratedConversion('webp_1920')) {
            return null;
        }

        return $media->getUrl('webp_1920') ?: null;
    }

    public static function webpMobileUrl(?Media $media): ?string
    {
        if ($media === null || ! $media->hasGeneratedConversion('webp_768')) {
            return null;
        }

        return $media->getUrl('webp_768') ?: null;
    }

    public static function mainImageWebpDesktop(Product $product): ?string
    {
        return self::webpDesktopUrl($product->getFirstMedia('main_image'));
    }

    public static function mainImageWebpMobile(Product $product): ?string
    {
        return self::webpMobileUrl($product->getFirstMedia('main_image'));
    }

    /**
     * @return array{fallback: string, desktopWebp: ?string, mobileWebp: ?string}
     */
    public static function carouselSlide(?Media $media, string $legacyFallback): array
    {
        if ($media === null) {
            return [
                'fallback' => $legacyFallback,
                'desktopWebp' => null,
                'mobileWebp' => null,
            ];
        }

        return [
            'fallback' => $media->getUrl(),
            'desktopWebp' => self::webpDesktopUrl($media),
            'mobileWebp' => self::webpMobileUrl($media),
        ];
    }
}
