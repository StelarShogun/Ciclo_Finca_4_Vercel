<?php

namespace App\Actions\Client\Catalog;

use App\Models\FavoriteProduct;
use App\Services\Client\Catalog\CatalogFilterResolver;
use App\Services\Client\Catalog\CatalogPayloadBuilder;
use App\Services\Client\Catalog\CatalogProductSearchTelemetry;
use App\Services\Client\Catalog\CatalogQueryBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

final class BuildCatalogPage
{
    public function __construct(
        private CatalogFilterResolver $filterResolver,
        private CatalogQueryBuilder $queryBuilder,
        private CatalogPayloadBuilder $payloadBuilder,
    ) {}

    public function handle(Request $request): array
    {
        $filters = $this->filterResolver->resolve($request);
        if ($filters->priceValidationRedirect !== null) {
            return ['error' => 'invalid_price_range'];
        }

        $query = $this->queryBuilder->filteredQuery($request, $filters);
        $products = $this->queryBuilder->paginate($query, $request);

        if ($request->filled('search')) {
            CatalogProductSearchTelemetry::recordSearchResultsPage((string) $request->input('search'), $products);
        }

        $favoriteProductIds = collect();
        if (Auth::guard('clients')->check()) {
            $favoriteProductIds = FavoriteProduct::query()
                ->where('user_id', (int) Auth::guard('clients')->id())
                ->pluck('product_id')
                ->map(fn ($id) => (int) $id);
        }

        return $this->payloadBuilder->build($request, $filters, $products, $favoriteProductIds);
    }
}
