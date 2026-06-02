<?php

namespace App\Services\Client\Product;

use App\Models\Category;
use App\Models\Product;

final class ProductDetailPageSupport
{
    public const NOVELTY_DAYS = 30;

    public function whatsappConsultUrl(Product $product): ?string
    {
        $configured = config('cf4_legal.whatsapp_url');
        if (is_string($configured) && trim($configured) !== '') {
            return trim($configured);
        }

        $phone = config('cf4_legal.contact_phone');
        if (! is_string($phone) || trim($phone) === '') {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $phone);
        if ($digits === '') {
            return null;
        }

        $message = 'Hola, me gustaría consultar por: '.$product->name;

        return 'https://wa.me/'.$digits.'?text='.rawurlencode($message);
    }

    /**
     * @return array{parentCategory: ?Category, subcategory: ?Category, catalogParentUrl: ?string, catalogSubcategoryUrl: ?string}
     */
    public function taxonomy(Product $product): array
    {
        $category = $product->category;
        if ($category === null) {
            return [
                'parentCategory' => null,
                'subcategory' => null,
                'catalogParentUrl' => null,
                'catalogSubcategoryUrl' => null,
            ];
        }

        if ($category->parent_category_id !== null) {
            $parentCategory = $category->parent;
            $subcategory = $category;

            return [
                'parentCategory' => $parentCategory,
                'subcategory' => $subcategory,
                'catalogParentUrl' => $parentCategory
                    ? route('clients.catalog', ['category_id' => $parentCategory->category_id])
                    : null,
                'catalogSubcategoryUrl' => route('clients.catalog', ['category_id' => $subcategory->category_id]),
            ];
        }

        return [
            'parentCategory' => $category,
            'subcategory' => null,
            'catalogParentUrl' => route('clients.catalog', ['category_id' => $category->category_id]),
            'catalogSubcategoryUrl' => null,
        ];
    }

    public function isNoveltyProduct(Product $product): bool
    {
        return $product->created_at !== null
            && $product->created_at->greaterThanOrEqualTo(now()->subDays(self::NOVELTY_DAYS));
    }
}
