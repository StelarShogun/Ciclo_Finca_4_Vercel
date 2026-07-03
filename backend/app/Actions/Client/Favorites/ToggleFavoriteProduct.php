<?php

namespace App\Actions\Client\Favorites;

use App\Models\FavoriteProduct;
use App\Models\Product;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;

final class ToggleFavoriteProduct
{
    public function handle(int $clientId, int $productId): JsonResponse
    {
        if (! Product::query()->activeInClientStore()->whereKey($productId)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'El producto no está disponible para favoritos.',
            ], 422);
        }

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

            FavoriteProduct::query()->create([
                'user_id' => $clientId,
                'product_id' => $productId,
            ]);

            return response()->json([
                'success' => true,
                'is_favorite' => true,
                'message' => 'Agregado a favoritos.',
            ]);
        } catch (QueryException $e) {
            if ((string) $e->getCode() === '23000') {
                return response()->json([
                    'success' => true,
                    'is_favorite' => true,
                    'message' => 'Agregado a favoritos.',
                ]);
            }

            report($e);
        } catch (\Throwable $e) {
            report($e);
        }

        return response()->json([
            'success' => false,
            'message' => 'No se pudo guardar el favorito en este momento.',
        ], 500);
    }
}
