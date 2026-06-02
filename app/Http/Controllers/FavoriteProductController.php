<?php

namespace App\Http\Controllers;

use App\Models\FavoriteProduct;
use App\Support\AdminPerPage;
use App\Support\ClientFavoriteFormatter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;

class FavoriteProductController extends Controller
{
    public function index(Request $request)
    {
        if (! Schema::hasTable('favorite_products')) {
            return response()->json([
                'success' => false,
                'message' => 'La tabla de favoritos no existe. Ejecuta las migraciones.',
            ], 500);
        }

        $clientId = (int) Auth::guard('clients')->id();
        $perPage = AdminPerPage::resolve($request->input('per_page', 10));

        $paginator = FavoriteProduct::query()
            ->with(['product.category'])
            ->where('user_id', $clientId)
            ->latest('id')
            ->paginate($perPage);

        $favorites = ClientFavoriteFormatter::collect($paginator->items());

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'favorites' => $favorites,
                'pagination' => [
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                    'from' => $paginator->firstItem(),
                    'to' => $paginator->lastItem(),
                ],
            ]);
        }

        $links = collect($paginator->toArray()['links'] ?? [])
            ->map(fn ($link) => [
                'url' => $link['url'] ?? null,
                'label' => $link['label'] ?? '',
                'active' => (bool) ($link['active'] ?? false),
            ])
            ->values()
            ->all();

        return Inertia::render('Client/Favorites/Index', [
            'favorites' => $favorites,
            'links' => $links,
        ]);
    }

    public function toggle(Request $request)
    {
        if (! Schema::hasTable('favorite_products')) {
            return response()->json([
                'success' => false,
                'message' => 'La tabla de favoritos no existe. Ejecuta las migraciones.',
            ], 500);
        }

        $request->validate([
            'product_id' => 'required|exists:products,product_id',
        ]);

        $clientId = (int) Auth::guard('clients')->id();
        $productId = (int) $request->input('product_id');

        $favorite = FavoriteProduct::query()
            ->where('user_id', $clientId)
            ->where('product_id', $productId)
            ->first();

        try {
            if ($favorite) {
                $favorite->delete();

                return response()->json([
                    'success' => true,
                    'is_favorite' => false,
                    'message' => 'Quitado de favoritos.',
                ]);
            }

            FavoriteProduct::create([
                'user_id' => $clientId,
                'product_id' => $productId,
            ]);

            return response()->json([
                'success' => true,
                'is_favorite' => true,
                'message' => 'Agregado a favoritos.',
            ]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'No se pudo guardar el favorito en este momento.',
            ], 500);
        }
    }
}
