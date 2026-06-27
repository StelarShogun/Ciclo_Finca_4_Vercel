<?php

namespace App\Services\Client\Catalog;

use App\Models\Category;
use App\Models\Product;
use App\Services\Shared\Media\ProductImageUrls;

final class ProductSuggestionService
{
    public function suggestions(string $search): array
    {
        $search = trim($search);
        $skuId = $this->parseSkuLikeToProductId($search);

        if (mb_strlen($search) < 2 && $skuId === null) {
            return [];
        }

        $search = mb_substr($search, 0, 80);
        $suggestions = [];
        $productWith = [
            'category',
            'media' => static fn ($query) => $query->where('collection_name', 'main_image'),
        ];

        if ($skuId !== null) {
            $product = Product::query()
                ->with($productWith)
                ->activeInClientStore()
                ->where('product_id', $skuId)
                ->first();

            if ($product instanceof Product) {
                $suggestions[] = $this->productSuggestionRow($product, 'sku', 1000);
            }
        }

        $term = mb_strtolower($search);
        $like = '%'.$term.'%';

        Product::query()
            ->select('products.*')
            ->leftJoin('categories', 'categories.category_id', '=', 'products.category_id')
            ->with($productWith)
            ->activeInClientStore()
            ->where(function ($query) use ($like) {
                $query->whereRaw('LOWER(products.name) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(COALESCE(products.description, \'\')) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(COALESCE(categories.name, \'\')) LIKE ?', [$like]);
            })
            ->distinct()
            ->limit(28)
            ->get()
            ->each(function (Product $product) use (&$suggestions, $term): void {
                $row = $this->scoredProductSuggestion($product, $term);
                if ($row !== null) {
                    $suggestions[] = $row;
                }
            });

        Category::query()
            ->whereRaw('LOWER(name) LIKE ?', [$like])
            ->limit(12)
            ->get()
            ->each(function (Category $category) use (&$suggestions, $term): void {
                $name = (string) $category->name;
                $suggestions[] = [
                    'type' => 'category',
                    'id' => (int) $category->category_id,
                    'name' => $name,
                    'sku' => null,
                    'category' => null,
                    'image_url' => null,
                    'match_type' => 'category',
                    'url' => route('clients.catalog', ['category_id' => (int) $category->category_id]),
                    '_score' => $this->scoreTextMatch($term, mb_strtolower($name), 140, 90),
                ];
            });

        $deduped = [];
        foreach ($suggestions as $suggestion) {
            $key = ($suggestion['type'] ?? 'unknown').':'.(string) ($suggestion['id'] ?? '');
            if (! isset($deduped[$key]) || ($suggestion['_score'] ?? 0) > ($deduped[$key]['_score'] ?? 0)) {
                $deduped[$key] = $suggestion;
            }
        }

        $suggestions = array_values($deduped);
        usort($suggestions, function (array $a, array $b): int {
            $scoreA = (int) ($a['_score'] ?? 0);
            $scoreB = (int) ($b['_score'] ?? 0);

            return $scoreA === $scoreB
                ? mb_strtolower((string) ($a['name'] ?? '')) <=> mb_strtolower((string) ($b['name'] ?? ''))
                : $scoreB <=> $scoreA;
        });

        return array_map(function (array $suggestion): array {
            unset($suggestion['_score']);

            return $suggestion;
        }, array_slice($suggestions, 0, 10));
    }

    private function parseSkuLikeToProductId(string $input): ?int
    {
        if (preg_match('/^\\s*bk\\s*[-_]?\\s*0*(\\d+)\\s*$/i', $input, $matches) === 1) {
            $id = (int) $matches[1];

            return $id > 0 ? $id : null;
        }

        if (preg_match('/^\\s*0*(\\d+)\\s*$/', $input, $matches) === 1) {
            $id = (int) $matches[1];

            return $id > 0 ? $id : null;
        }

        return null;
    }

    private function scoredProductSuggestion(Product $product, string $termLower): ?array
    {
        $name = (string) $product->name;
        $categoryName = $product->category !== null ? (string) $product->category->name : '';
        $sku = Product::skuFromId((int) $product->product_id);
        $nameScore = $this->scoreTextMatch($termLower, mb_strtolower($name), 420, 300);
        $skuScore = $this->scoreTextMatch($termLower, mb_strtolower($sku), 360, 240);
        $categoryScore = $categoryName !== '' ? $this->scoreTextMatch($termLower, mb_strtolower($categoryName), 180, 120) : 0;
        $best = max($nameScore, $skuScore, $categoryScore);

        if ($best <= 0) {
            return null;
        }

        $matchType = $best === $nameScore ? 'name' : ($best === $skuScore ? 'sku' : 'category');

        return $this->productSuggestionRow($product, $matchType, $best + match ($matchType) {
            'name' => 3,
            'sku' => 2,
            default => 1,
        });
    }

    private function productSuggestionRow(Product $product, string $matchType, int $score): array
    {
        $image = ProductImageUrls::clientPresentation($product);

        return [
            'type' => 'product',
            'id' => (int) $product->product_id,
            'name' => (string) $product->name,
            'sku' => Product::skuFromId((int) $product->product_id),
            'category' => $product->category !== null ? (string) $product->category->name : '',
            'image_url' => $image['image_url'],
            'uses_placeholder_image' => $image['uses_placeholder_image'],
            'placeholder_icon_class' => $image['placeholder_icon_class'],
            'match_type' => $matchType,
            'url' => route('clients.product', [
                'id' => (int) $product->product_id,
                'slug' => $product->clientPublicSlug(),
            ]),
            '_score' => $score,
        ];
    }

    private function scoreTextMatch(string $needleLower, string $haystackLower, int $prefixScore, int $containsScore): int
    {
        if ($needleLower === '' || $haystackLower === '') {
            return 0;
        }

        return str_starts_with($haystackLower, $needleLower)
            ? $prefixScore
            : (str_contains($haystackLower, $needleLower) ? $containsScore : 0);
    }
}
