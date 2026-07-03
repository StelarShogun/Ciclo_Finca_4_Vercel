<?php

namespace App\Http\Controllers\Client;

use App\Actions\Client\Favorites\ListFavoriteProducts;
use App\Actions\Client\Favorites\ToggleFavoriteProduct;
use App\Http\Controllers\Controller;
use App\Http\Requests\Client\Favorites\ToggleFavoriteRequest;
use App\Models\FavoriteProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class FavoriteController extends Controller
{
    public function index(Request $request, ListFavoriteProducts $favorites)
    {
        $client = Auth::guard('clients')->user();
        Gate::forUser($client)->authorize('viewAny', FavoriteProduct::class);

        $payload = $favorites->handle((int) $client->user_id, $request);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'favorites' => $payload['favorites'],
                'pagination' => $payload['json_pagination'],
            ]);
        }

        unset($payload['json_pagination']);

        return Inertia::render('Client/Favorites/Index', $payload);
    }

    public function toggle(ToggleFavoriteRequest $request, ToggleFavoriteProduct $toggleFavorite)
    {
        $client = Auth::guard('clients')->user();
        Gate::forUser($client)->authorize('toggle', FavoriteProduct::class);

        return $toggleFavorite->handle((int) $client->user_id, (int) $request->validated('product_id'));
    }
}
