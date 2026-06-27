<?php

namespace App\Services\Client\Cart;

use App\Actions\Client\Cart\CheckoutCart;
use App\DTOs\Client\Cart\CartMutationResult;
use App\Services\Admin\Inventory\InventoryMovementService;

final class CheckoutService
{
    public function __construct(
        private CheckoutCart $checkoutCart,
        private InventoryMovementService $inventoryMovementService,
    ) {}

    /**
     * @param  array{payment_method:string}  $validatedCheckout
     */
    public function checkout(array $validatedCheckout): CartMutationResult
    {
        return $this->checkoutCart->handle($validatedCheckout, $this->inventoryMovementService);
    }
}
