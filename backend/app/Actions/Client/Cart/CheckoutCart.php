<?php

namespace App\Actions\Client\Cart;

use App\DTOs\Client\Cart\CartMutationResult;
use App\Models\Client;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Services\Admin\Inventory\InventoryMovementService;
use App\Services\Client\Cart\CartManager;
use App\Services\Shared\Security\SensitiveDataMasker;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

final class CheckoutCart
{
    public function __construct(
        private CartManager $cart,
    ) {}

    /**
     * @param  array{payment_method:string}  $validatedCheckout
     */
    public function handle(array $validatedCheckout, InventoryMovementService $inventoryService): CartMutationResult
    {
        $cart = collect($this->cart->lines())
            ->sortBy(fn (array $item): int => (int) ($item['product_id'] ?? 0))
            ->values()
            ->all();

        if ($cart === []) {
            return new CartMutationResult(false, 400, [
                'success' => false,
                'message' => 'El carrito está vacío',
            ]);
        }

        DB::beginTransaction();
        try {
            $subtotal = 0;
            $validatedItems = [];

            foreach ($cart as $item) {
                $product = Product::query()
                    ->lockForUpdate()
                    ->find($item['product_id']);

                if (! $product || ! $product->isPurchasableByClient()) {
                    DB::rollBack();

                    return new CartMutationResult(false, 400, [
                        'success' => false,
                        'message' => Product::MSG_CLIENT_AGOTADO,
                    ]);
                }

                $quantity = (int) ($item['quantity'] ?? 0);
                if ($quantity < 1) {
                    Log::warning('checkout_invalid_quantity', [
                        'product_id' => $item['product_id'] ?? null,
                        'raw_quantity' => $item['quantity'] ?? null,
                    ]);
                    DB::rollBack();

                    return new CartMutationResult(false, 400, [
                        'success' => false,
                        'message' => 'Cantidad inválida en el carrito. Actualiza la página e inténtalo de nuevo.',
                    ]);
                }

                if ($product->stock_current < $quantity) {
                    DB::rollBack();

                    return new CartMutationResult(false, 400, [
                        'success' => false,
                        'message' => $product->stock_current < 1
                            ? Product::MSG_CLIENT_AGOTADO
                            : Product::MSG_CLIENT_STOCK_INSUFICIENTE,
                    ]);
                }

                $itemTotal = $item['price'] * $quantity;
                $subtotal += $itemTotal;

                $validatedItems[] = [
                    'product' => $product,
                    'quantity' => $quantity,
                    'price' => $item['price'],
                    'total' => $itemTotal,
                ];
            }

            Log::info('checkout_persisting_items', [
                'items' => collect($validatedItems)->map(fn ($i) => [
                    'product_id' => $i['product']->product_id,
                    'qty' => $i['quantity'],
                ])->values()->all(),
            ]);

            /** @var Client|null $client */
            $client = Auth::guard('clients')->user();

            $sale = Sale::create([
                'invoice_number' => (new Sale)->generateInvoiceNumber(),
                'client_id' => $client?->user_id,
                'sale_date' => now(),
                'payment_method' => $validatedCheckout['payment_method'],
                'status' => 'pending',
                'order_source' => 'web_cart',
                'subtotal' => $subtotal,
                'iva' => 0,
                'discount' => 0,
                'total' => $subtotal,
                'notes' => 'Pedido realizado desde la tienda en línea',
            ]);

            foreach ($validatedItems as $item) {
                SaleItem::create([
                    'sale_id' => $sale->sale_id,
                    'product_id' => $item['product']->product_id,
                    'quantity' => (int) $item['quantity'],
                    'unit_price' => $item['price'],
                    'unit_discount' => 0,
                    'total' => $item['total'],
                ]);

                $inventoryService->recordWebCartSale(
                    product: $item['product'],
                    quantity: (int) $item['quantity'],
                    saleId: $sale->sale_id,
                );
            }

            $this->cart->clear();
            DB::commit();

            return new CartMutationResult(true, 200, [
                'success' => true,
                'message' => 'Pedido creado exitosamente',
                'sale_id' => $sale->sale_id,
                'invoice_number' => $sale->invoice_number,
                'payment_method' => $sale->payment_method,
                'cart_count' => 0,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('checkout_failed', SensitiveDataMasker::exceptionContext($e, [
                'client_id' => Auth::guard('clients')->id(),
            ]));

            if ($e instanceof ValidationException) {
                return new CartMutationResult(false, 422, [
                    'success' => false,
                    'message' => collect($e->errors())->flatten()->first()
                        ?: 'No fue posible completar el pedido con el stock disponible.',
                ]);
            }

            return new CartMutationResult(false, 500, [
                'success' => false,
                'message' => 'No fue posible procesar el pedido. Inténtalo nuevamente.',
            ]);
        }
    }
}
