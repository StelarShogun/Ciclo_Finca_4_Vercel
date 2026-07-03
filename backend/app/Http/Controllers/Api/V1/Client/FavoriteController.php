<?php

namespace App\Http\Controllers\Api\V1\Client;

use App\Actions\Client\Favorites\ListFavoriteProducts;
use App\Actions\Client\Favorites\ToggleFavoriteProduct;
use App\Http\Controllers\Controller;
use App\Http\Requests\Client\Favorites\ToggleFavoriteRequest;
use App\Models\FavoriteProduct;
use App\Services\Api\PublicIdMapper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

/**
 * Favoritos del cliente para el SPA Next. Reusa las Actions; el toggle es
 * idempotente (único por user+product) y la lista valida pertenencia.
 */
final class FavoriteController extends Controller
{
    public function index(Request $request, ListFavoriteProducts $favorites, PublicIdMapper $publicIds): JsonResponse
    {
        $client = Auth::guard('clients')->user();
        Gate::forUser($client)->authorize('viewAny', FavoriteProduct::class);

        $payload = $favorites->handle((int) $client->user_id, $request);

        return response()->json(['data' => $publicIds->map('favorites', [
            'favorites' => $payload['favorites'],
            'pagination' => $payload['json_pagination'],
        ])]);
    }

    public function toggle(ToggleFavoriteRequest $request, ToggleFavoriteProduct $toggle): JsonResponse
    {
        $client = Auth::guard('clients')->user();
        Gate::forUser($client)->authorize('toggle', FavoriteProduct::class);

        return $toggle->handle((int) $client->user_id, (int) $request->validated('product_id'));
    }
}
