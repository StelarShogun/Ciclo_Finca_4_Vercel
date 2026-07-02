<?php

namespace App\Http\Controllers\Api\V1\Client;

use App\Actions\Client\Cart\AddProductToCart;
use App\Actions\Client\Cart\BuildCartPagePayload;
use App\Actions\Client\Cart\ClearCart;
use App\Actions\Client\Cart\RemoveCartItem;
use App\Actions\Client\Cart\UpdateCartItem;
use App\Http\Controllers\Controller;
use App\Http\Requests\Client\Cart\AddToCartRequest;
use App\Http\Requests\Client\Cart\CheckoutCartRequest;
use App\Http\Requests\Client\Cart\UpdateCartItemRequest;
use App\Services\Api\PublicIdMapper;
use App\Services\Client\Cart\CheckoutService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Carrito para el SPA Next. Reusa las Actions (CartManager elige sesión para
 * invitados o DB para clientes logueados). Las mutaciones ya devuelven JSON.
 */
final class CartController extends Controller
{
    public function index(Request $request, BuildCartPagePayload $action, PublicIdMapper $publicIds): JsonResponse
    {
        return response()->json(['data' => $publicIds->map('cart', $action->handle($request))]);
    }

    public function add(AddToCartRequest $request, AddProductToCart $action): JsonResponse
    {
        return $action->handle($request->validated())->toJsonResponse();
    }

    public function update(UpdateCartItemRequest $request, UpdateCartItem $action): JsonResponse
    {
        return $action->handle($request->validated())->toJsonResponse();
    }

    public function remove(string $id, RemoveCartItem $action, PublicIdMapper $publicIds): JsonResponse
    {
        // Solo ID público; un numérico interno o desconocido => 404.
        $internal = $publicIds->internalId('product', $id);
        if ($internal === null) {
            return response()->json(['message' => 'Producto no encontrado.'], 404);
        }

        return $action->handle($internal)->toJsonResponse();
    }

    public function clear(ClearCart $action): JsonResponse
    {
        return $action->handle()->toJsonResponse();
    }

    /** Checkout (requiere cliente logueado). Stock con locks/transacción en la Action. */
    public function checkout(CheckoutCartRequest $request, CheckoutService $checkout): JsonResponse
    {
        return $checkout->checkout($request->validated())->toJsonResponse();
    }
}
