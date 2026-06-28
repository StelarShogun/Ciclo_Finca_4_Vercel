<?php

namespace App\Http\Controllers\Api\V1\Client;

use App\Actions\Client\Cart\AddProductToCart;
use App\Actions\Client\Cart\BuildCartPagePayload;
use App\Actions\Client\Cart\ClearCart;
use App\Actions\Client\Cart\RemoveCartItem;
use App\Actions\Client\Cart\UpdateCartItem;
use App\Http\Controllers\Controller;
use App\Http\Requests\Client\Cart\AddToCartRequest;
use App\Http\Requests\Client\Cart\UpdateCartItemRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Carrito para el SPA Next. Reusa las Actions (CartManager elige sesión para
 * invitados o DB para clientes logueados). Las mutaciones ya devuelven JSON.
 */
final class CartController extends Controller
{
    public function index(Request $request, BuildCartPagePayload $action): JsonResponse
    {
        return response()->json(['data' => $action->handle($request)]);
    }

    public function add(AddToCartRequest $request, AddProductToCart $action): JsonResponse
    {
        return $action->handle($request->validated())->toJsonResponse();
    }

    public function update(UpdateCartItemRequest $request, UpdateCartItem $action): JsonResponse
    {
        return $action->handle($request->validated())->toJsonResponse();
    }

    public function remove(int $id, RemoveCartItem $action): JsonResponse
    {
        return $action->handle($id)->toJsonResponse();
    }

    public function clear(ClearCart $action): JsonResponse
    {
        return $action->handle()->toJsonResponse();
    }
}
