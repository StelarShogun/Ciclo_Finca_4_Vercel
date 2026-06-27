<?php

namespace App\Http\Controllers\Client;

use App\Actions\Client\Cart\AddProductToCart;
use App\Actions\Client\Cart\BuildCartPagePayload;
use App\Actions\Client\Cart\ClearCart;
use App\Actions\Client\Cart\RemoveCartItem;
use App\Actions\Client\Cart\UpdateCartItem;
use App\Http\Controllers\Controller;
use App\Http\Requests\Client\Cart\AddToCartRequest;
use App\Http\Requests\Client\Cart\CheckoutCartRequest;
use App\Http\Requests\Client\Cart\UpdateCartItemRequest;
use App\Services\Client\Cart\CheckoutService;
use Illuminate\Http\Request;
use Inertia\Inertia;

final class CartController extends Controller
{
    public function addToCart(AddToCartRequest $request, AddProductToCart $action)
    {
        return $action->handle($request->validated())->toJsonResponse();
    }

    public function updateCart(UpdateCartItemRequest $request, UpdateCartItem $action)
    {
        return $action->handle($request->validated())->toJsonResponse();
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

    public function checkout(CheckoutCartRequest $request, CheckoutService $checkout)
    {
        return $checkout->checkout($request->validated())->toJsonResponse();
    }
}
