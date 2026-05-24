<?php

namespace App\Support;

use App\Models\Product;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class ProductImageUrls
{
    private const PLACEHOLDER_IMAGE = 'default.png';

    public static function usesPlaceholder(Product $product): bool
    {
        if ($product->getFirstMedia('main_image') !== null) {
            return false;
        }

        $image = $product->image ?? self::PLACEHOLDER_IMAGE;

        return $image === '' || $image === self::PLACEHOLDER_IMAGE;
    }

    public static function placeholderIconClass(Product $product): string
    {
        return ClientCategoryIcons::iconClassForProduct($product);
    }

    public static function placeholderWebpDesktop(): string
    {
        return asset('assets/images/products/default-480.webp');
    }

    public static function placeholderWebpMobile(): string
    {
        return asset('assets/images/products/default-96.webp');
    }

    public static function fallbackUrl(Product $product): string
    {
        $mediaUrl = $product->getFirstMediaUrl('main_image');

        if ($mediaUrl !== '') {
            return $mediaUrl;
        }

        if (self::usesPlaceholder($product)) {
            return asset('assets/images/products/'.self::PLACEHOLDER_IMAGE);
        }

        return asset('assets/images/products/'.($product->image ?? self::PLACEHOLDER_IMAGE));
    }

    /**
     * Image fields for client UI and JSON APIs (cart, favorites, search suggestions).
     * When the product has no real image, image_url is null and placeholder metadata is set.
     *
     * @return array{image_url: ?string, uses_placeholder_image: bool, placeholder_icon_class: string}
     */
    public static function clientPresentation(Product $product): array
    {
        $iconClass = self::placeholderIconClass($product);

        if (self::usesPlaceholder($product)) {
            return [
                'image_url' => null,
                'uses_placeholder_image' => true,
                'placeholder_icon_class' => $iconClass,
            ];
        }

        $mediaUrl = $product->getFirstMediaUrl('main_image');
        if ($mediaUrl !== '') {
            return [
                'image_url' => $mediaUrl,
                'uses_placeholder_image' => false,
                'placeholder_icon_class' => $iconClass,
            ];
        }

        $legacy = $product->image ?? '';
        if ($legacy !== '' && $legacy !== self::PLACEHOLDER_IMAGE) {
            return [
                'image_url' => asset('assets/images/products/'.$legacy),
                'uses_placeholder_image' => false,
                'placeholder_icon_class' => $iconClass,
            ];
        }

        return [
            'image_url' => null,
            'uses_placeholder_image' => true,
            'placeholder_icon_class' => $iconClass,
        ];
    }

    public static function webpCardUrl(?Media $media): ?string
    {
        if ($media === null || ! $media->hasGeneratedConversion('webp_480')) {
            return null;
        }

        return $media->getUrl('webp_480') ?: null;
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
        if ($media === null) {
            return null;
        }

        if ($media->hasGeneratedConversion('webp_96')) {
            return $media->getUrl('webp_96') ?: null;
        }

        if ($media->hasGeneratedConversion('webp_768')) {
            return $media->getUrl('webp_768') ?: null;
        }

        return null;
    }

    public static function webpDetailUrl(?Media $media): ?string
    {
        if ($media === null) {
            return null;
        }

        if ($media->hasGeneratedConversion('webp_1200')) {
            return $media->getUrl('webp_1200') ?: null;
        }

        return self::webpDesktopUrl($media);
    }

    /**
     * @return array{fallback: string, desktopWebp: ?string, mobileWebp: ?string}
     */
    public static function cardPicture(Product $product): array
    {
        if (self::usesPlaceholder($product)) {
            return [
                'fallback' => self::fallbackUrl($product),
                'desktopWebp' => self::placeholderWebpDesktop(),
                'mobileWebp' => self::placeholderWebpMobile(),
            ];
        }

        $media = $product->getFirstMedia('main_image');
        $cardWebp = self::webpCardUrl($media);

        return [
            'fallback' => self::fallbackUrl($product),
            'desktopWebp' => $cardWebp ?? self::webpDesktopUrl($media),
            'mobileWebp' => self::webpMobileUrl($media) ?? $cardWebp,
        ];
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
