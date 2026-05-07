<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class ClientCatalogProductSuggestionsController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $raw = (string) $request->query('search', '');
        $search = trim($raw);

        if (mb_strlen($search) < 2) {
            return response()->json(['suggestions' => []]);
        }

        if (mb_strlen($search) > 80) {
            $search = mb_substr($search, 0, 80);
        }

        try {
            $hasMediaTable = Schema::hasTable('media');
            $suggestions = [];

            // SKU can be derived from product_id, so when the user types BK-001 or 001,
            // attempt to resolve the exact product_id first.
            $skuId = $this->parseSkuLikeToProductId($search);
            if ($skuId !== null) {
                $p = Product::query()
                    ->with(['category'])
                    ->activeInClientStore()
                    ->where('product_id', $skuId)
                    ->first();

                if ($p instanceof Product) {
                    $suggestions[] = $this->productSuggestionRow($p, 'sku', 1000, $hasMediaTable);
                }
            }

            $term = mb_strtolower($search);
            $like = '%'.$term.'%';

            $productCandidates = Product::query()
                ->with(['category'])
                ->activeInClientStore()
                ->where(function ($q) use ($like) {
                    $q->whereRaw('LOWER(name) LIKE ?', [$like])
                        ->orWhereRaw('LOWER(COALESCE(description, \'\')) LIKE ?', [$like])
                        ->orWhereHas('category', function ($cq) use ($like) {
                            $cq->whereRaw('LOWER(name) LIKE ?', [$like]);
                        });
                })
                ->limit(60)
                ->get();

            foreach ($productCandidates as $p) {
                $row = $this->scoredProductSuggestion($p, $term, $hasMediaTable);
                if ($row !== null) {
                    $suggestions[] = $row;
                }
            }

            $categoryCandidates = Category::query()
                ->whereRaw('LOWER(name) LIKE ?', [$like])
                ->limit(20)
                ->get();

            foreach ($categoryCandidates as $c) {
                $name = (string) $c->name;
                $score = $this->scoreTextMatch($term, mb_strtolower($name), 140, 90);

                $suggestions[] = [
                    'type' => 'category',
                    'id' => (int) $c->category_id,
                    'name' => $name,
                    'sku' => null,
                    'category' => null,
                    'image_url' => null,
                    'match_type' => 'category',
                    'url' => route('clients.catalog', ['category_id' => (int) $c->category_id]),
                    '_score' => $score,
                ];
            }

            $deduped = [];
            foreach ($suggestions as $s) {
                $key = ($s['type'] ?? 'unknown').':'.(string) ($s['id'] ?? '');
                if (! isset($deduped[$key]) || ($s['_score'] ?? 0) > ($deduped[$key]['_score'] ?? 0)) {
                    $deduped[$key] = $s;
                }
            }

            $suggestions = array_values($deduped);
            usort($suggestions, function (array $a, array $b): int {
                $sa = (int) ($a['_score'] ?? 0);
                $sb = (int) ($b['_score'] ?? 0);
                if ($sa !== $sb) {
                    return $sb <=> $sa;
                }

                $na = mb_strtolower((string) ($a['name'] ?? ''));
                $nb = mb_strtolower((string) ($b['name'] ?? ''));

                return $na <=> $nb;
            });

            $suggestions = array_slice($suggestions, 0, 10);

            // Remove private internal score before returning.
            foreach ($suggestions as &$s) {
                unset($s['_score']);
            }
            unset($s);

            return response()->json(['suggestions' => $suggestions]);
        } catch (\Throwable $e) {
            Log::error('Catalog suggestions failed', [
                'search' => $search,
                'exception' => get_class($e),
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'suggestions' => [],
                'error' => 'temporary_unavailable',
            ]);
        }
    }

    private function parseSkuLikeToProductId(string $input): ?int
    {
        $s = trim($input);
        if ($s === '') {
            return null;
        }

        if (preg_match('/^\\s*bk\\s*[-_]?\\s*0*(\\d+)\\s*$/i', $s, $m) === 1) {
            $id = (int) $m[1];

            return $id > 0 ? $id : null;
        }

        // Also accept pure numeric input like "001" to mean product_id 1.
        if (preg_match('/^\\s*0*(\\d+)\\s*$/', $s, $m) === 1) {
            $id = (int) $m[1];

            return $id > 0 ? $id : null;
        }

        return null;
    }

    private function scoredProductSuggestion(Product $p, string $termLower, bool $hasMediaTable): ?array
    {
        $name = (string) $p->name;
        $nameLower = mb_strtolower($name);
        $catName = $p->category !== null ? (string) $p->category->name : '';
        $catLower = mb_strtolower($catName);
        $sku = Product::skuFromId((int) $p->product_id);
        $skuLower = mb_strtolower($sku);

        $nameScore = $this->scoreTextMatch($termLower, $nameLower, 420, 300);
        $skuScore = $this->scoreTextMatch($termLower, $skuLower, 360, 240);
        $catScore = $catName !== '' ? $this->scoreTextMatch($termLower, $catLower, 180, 120) : 0;

        $best = max($nameScore, $skuScore, $catScore);
        if ($best <= 0) {
            return null;
        }

        $matchType = $best === $nameScore ? 'name' : ($best === $skuScore ? 'sku' : 'category');

        // Ensure "name > sku > category" tie-breaking by nudging their scores.
        $best += match ($matchType) {
            'name' => 3,
            'sku' => 2,
            default => 1,
        };

        return $this->productSuggestionRow($p, $matchType, $best, $hasMediaTable);
    }

    private function productSuggestionRow(Product $p, string $matchType, int $score, bool $hasMediaTable): array
    {
        $sku = Product::skuFromId((int) $p->product_id);
        $imageUrl = '';
        if ($hasMediaTable) {
            try {
                $imageUrl = (string) $p->getFirstMediaUrl('main_image');
            } catch (\Throwable $e) {
                $imageUrl = '';
            }
        }
        if ($imageUrl === '') {
            $imageUrl = (string) asset('assets/images/products/'.($p->image ?? 'default.png'));
        }

        return [
            'type' => 'product',
            'id' => (int) $p->product_id,
            'name' => (string) $p->name,
            'sku' => $sku,
            'category' => $p->category !== null ? (string) $p->category->name : '',
            'image_url' => $imageUrl,
            'match_type' => $matchType,
            'url' => route('clients.product', [
                'id' => (int) $p->product_id,
                'slug' => $p->clientPublicSlug(),
            ]),
            '_score' => $score,
        ];
    }

    private function scoreTextMatch(string $needleLower, string $haystackLower, int $prefixScore, int $containsScore): int
    {
        if ($needleLower === '' || $haystackLower === '') {
            return 0;
        }

        if (str_starts_with($haystackLower, $needleLower)) {
            return $prefixScore;
        }

        return str_contains($haystackLower, $needleLower) ? $containsScore : 0;
    }
}
