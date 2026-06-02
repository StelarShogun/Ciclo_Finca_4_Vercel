<?php

namespace App\Services\Client\Catalog;

use App\Models\Category;
use App\Services\Client\Storefront\ClientCategoryIcons;
use App\Services\Client\Storefront\ClientStorefrontCache;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

final class CatalogCategoryNavigationBuilder
{
    /**
     * @return Collection<int, Category>
     */
    public function rootCategories(): Collection
    {
        $ttl = ClientStorefrontCache::ttlSeconds((int) config('cf4_performance.client_root_categories_ttl', 60));

        return Cache::remember(ClientStorefrontCache::KEY_ROOT_CATEGORIES, $ttl, function () {
            return Category::whereNull('parent_category_id')
                ->with(['childCategories' => function ($q) {
                    $q->orderBy('name');
                }])
                ->orderBy('name')
                ->get();
        });
    }

    /**
     * @param  Collection<int, Category>  $rootCategories
     * @return array<int, array{id: int, name: string, icon: string, url_parent: string, children: array<int, array{id: int, name: string, url: string}>}>
     */
    public function navigation(Collection $rootCategories, array $catalogParams): array
    {
        return $rootCategories->map(function (Category $c) use ($catalogParams) {
            return [
                'id' => (int) $c->category_id,
                'name' => $c->name,
                'icon' => ClientCategoryIcons::iconClassForName($c->name),
                'url_parent' => route('clients.catalog', array_merge($catalogParams, ['category_id' => $c->category_id])),
                'children' => $c->childCategories->map(function (Category $ch) use ($catalogParams) {
                    return [
                        'id' => (int) $ch->category_id,
                        'name' => $ch->name,
                        'url' => route('clients.catalog', array_merge($catalogParams, ['category_id' => $ch->category_id])),
                    ];
                })->values()->all(),
            ];
        })->values()->all();
    }

    /**
     * @return array{id: int, name: string, url: string}
     */
    public function categorySummary(Category $category): array
    {
        return [
            'id' => (int) $category->category_id,
            'name' => (string) $category->name,
            'url' => route('clients.catalog', ['category_id' => $category->category_id]),
        ];
    }
}
