<?php

namespace App\Http\Controllers\Api\V1\Client;

use App\Actions\Client\Product\BuildProductDetailPage;
use App\Actions\Client\Reviews\SaveProductReview;
use App\Http\Controllers\Controller;
use App\Http\Requests\Client\Reviews\StoreProductReviewRequest;
use App\Models\Client;
use App\Models\Product;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Detalle de producto público para el SPA Next. Reusa BuildProductDetailPage
 * (mismo payload que el web: galería, reseñas, relacionados, taxonomía) y
 * SaveProductReview para guardar la calificación (valida compra previa).
 */
final class ProductController extends Controller
{
    public function show(Request $request, int $id, BuildProductDetailPage $builder): JsonResponse
    {
        try {
            return response()->json(['data' => $builder->payload($request, $id)]);
        } catch (ModelNotFoundException) {
            return response()->json(['message' => 'Producto no encontrado.'], 404);
        }
    }

    /** Guarda/actualiza la reseña (estrellas) del cliente; valida compra previa. */
    public function storeReview(StoreProductReviewRequest $request, Product $product, SaveProductReview $action): JsonResponse
    {
        /** @var Client $client */
        $client = Auth::guard('clients')->user();

        try {
            $action->handle($client, (int) $product->product_id, (int) $request->validated('stars'));
        } catch (AuthorizationException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }

        return response()->json(['success' => true, 'message' => 'Reseña guardada correctamente.']);
    }
}
