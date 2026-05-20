<?php

namespace App\Support;

use App\Models\FavoriteProduct;

final class ClientFavoriteFormatter
{
    public static function fromFavorite(?FavoriteProduct $favorite): ?array
    {
        if ($favorite === null || $favorite->product === null) {
            return null;
        }

        $product = $favorite->product;

        return [
            'product_id' => (int) $product->product_id,
            'name' => (string) $product->name,
            'category' => (string) ($product->category->name ?? 'Sin categoría'),
            'price' => (float) $product->sale_price,
            'price_formatted' => '₡'.number_format((float) $product->sale_price, 0, ',', '.'),
            'stock_label' => (string) $product->clientCatalogStockLabel(),
            'url' => (string) $product->clientProductUrl(),
            'image_url' => (string) ($product->getFirstMediaUrl('main_image')
                ?: asset('assets/images/products/'.($product->image ?? 'default.png'))),
        ];
    }

    /**
     * @param  iterable<FavoriteProduct>  $favorites
     * @return list<array<string, mixed>>
     */
    public static function collect(iterable $favorites): array
    {
        $rows = [];

        foreach ($favorites as $favorite) {
            $row = self::fromFavorite($favorite);
            if ($row !== null) {
                $rows[] = $row;
            }
        }

        return $rows;
    }
}
