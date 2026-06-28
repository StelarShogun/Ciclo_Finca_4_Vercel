<?php

namespace App\Http\Controllers\Api\V1\Client;

use App\Actions\Client\Product\BuildProductDetailPage;
use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Detalle de producto público para el SPA Next. Reusa BuildProductDetailPage
 * (mismo payload que el web: galería, reseñas, relacionados, taxonomía).
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
}
