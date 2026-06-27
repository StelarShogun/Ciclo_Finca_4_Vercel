<?php

namespace App\Services\Client\Favorites;

use App\Models\FavoriteProduct;
use App\Services\Shared\Media\ProductImageUrls;

final class ClientFavoriteFormatter
{
    public static function fromFavorite(?FavoriteProduct $favorite): ?array
    {
        if ($favorite === null || $favorite->product === null) {
            return null;
        }

        $product = $favorite->product;
        $image = ProductImageUrls::clientPresentation($product);

        return [
            'product_id' => (int) $product->product_id,
            'name' => (string) $product->name,
            'category' => (string) ($product->category->name ?? 'Sin categoría'),
            'price' => (float) $product->sale_price,
            'price_formatted' => '₡'.number_format((float) $product->sale_price, 0, ',', '.'),
            'stock_label' => (string) $product->clientCatalogStockLabel(),
            'url' => (string) $product->clientProductUrl(),
            'image_url' => $image['image_url'],
            'uses_placeholder_image' => $image['uses_placeholder_image'],
            'placeholder_icon_class' => $image['placeholder_icon_class'],
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
