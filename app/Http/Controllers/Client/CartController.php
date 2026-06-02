<?php

namespace App\Http\Controllers\Client;

use App\Actions\Client\Cart\AddCartItem;
use App\Actions\Client\Cart\BuildCartPagePayload;
use App\Actions\Client\Cart\CheckoutCart;
use App\Actions\Client\Cart\ClearCart;
use App\Actions\Client\Cart\RemoveCartItem;
use App\Actions\Client\Cart\UpdateCartItem;
use App\Http\Controllers\Controller;
use App\Services\InventoryMovementService;
use Illuminate\Http\Request;
use Inertia\Inertia;

final class CartController extends Controller
{
    public function addToCart(Request $request, AddCartItem $action)
    {
        return $action->handle($request)->toJsonResponse();
    }

    public function updateCart(Request $request, UpdateCartItem $action)
    {
        return $action->handle($request)->toJsonResponse();
    }

    public function cart(Request $request, BuildCartPagePayload $buildCartPagePayload)
    {
        return Inertia::render('Client/Cart/Index', $buildCartPagePayload->handle($request));
    }

    public function removeFromCart(int $id, RemoveCartItem $action)
    {
        return $action->handle($id)->toJsonResponse();
    }

    public function clearCart(ClearCart $action)
    {
        return $action->handle()->toJsonResponse();
    }

    public function checkout(Request $request, CheckoutCart $action, InventoryMovementService $inventoryService)
    {
        return $action->handle($request, $inventoryService)->toJsonResponse();
    }
}
