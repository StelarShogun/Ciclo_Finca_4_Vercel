<?php

namespace App\Http\Controllers\Api\V1\Client;

use App\Actions\Client\Catalog\GetProductSuggestions;
use App\Http\Controllers\Controller;
use App\Models\FavoriteProduct;
use App\Services\Api\PublicIdMapper;
use App\Services\Client\Catalog\CatalogFilterResolver;
use App\Services\Client\Catalog\CatalogPayloadBuilder;
use App\Services\Client\Catalog\CatalogProductSearchTelemetry;
use App\Services\Client\Catalog\CatalogQueryBuilder;
use App\Services\Client\Storefront\ClientStorefrontCache;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Catálogo público para el SPA Next. Reusa los mismos Services que el web
 * (BuildCatalogPage) y devuelve el payload como JSON en vez de Inertia.
 */
final class CatalogController extends Controller
{
    public function __construct(
        private CatalogFilterResolver $filterResolver,
        private CatalogQueryBuilder $queryBuilder,
        private CatalogPayloadBuilder $payloadBuilder,
        private PublicIdMapper $publicIds,
    ) {}

    public function index(Request $request): JsonResponse
    {
        // El SPA filtra por IDs públicos; los services trabajan con internos.
        // Cualquier valor que no sea un ID público válido (incluido un numérico
        // interno) se vuelve -1: filtro imposible, sin enumeración posible.
        foreach (['category_id' => 'category', 'brand_id' => 'brand'] as $param => $entity) {
            $value = $request->query($param);
            if (is_string($value) && $value !== '') {
                $request->merge([$param => $this->publicIds->internalId($entity, $value) ?? -1]);
            }
        }

        $filters = $this->filterResolver->resolve($request);
        if ($filters->priceValidationRedirect !== null) {
            return response()->json(['message' => 'Rango de precios inválido.'], 422);
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

        $props = $this->payloadBuilder->build($request, $filters, $products, $favoriteProductIds);

        return response()->json(['data' => $this->publicIds->map('catalog', $props)]);
    }

    public function heartbeat(): JsonResponse
    {
        return response()->json(['version' => ClientStorefrontCache::catalogVersion()]);
    }

    /** Sugerencias para el buscador inteligente del header. */
    public function suggestions(Request $request, GetProductSuggestions $action): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));
        if (mb_strlen($q) < 2) {
            return response()->json(['suggestions' => []]);
        }

        // Las sugerencias mezclan productos y categorías: mapear cada una según su tipo.
        $suggestions = array_map(
            fn (array $item): array => $this->publicIds->map(
                ($item['type'] ?? '') === 'category' ? 'suggestionCategory' : 'suggestionProduct',
                $item,
            ),
            $action->handle($q),
        );

        return response()->json(['suggestions' => $suggestions]);
    }
}
