<?php

namespace App\Services;

use App\Enums\MovementType;
use App\Models\InventoryMovement;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

// Single entry point for inventory stock movement logging.
class InventoryMovementService
{
    // Allowed values for the origin field.
    public const VALID_ORIGINS = [
        'sale_admin',
        'sale_web',
        'return',
        'provider',
        'manual_adjustment',
        'damage',
        'refund',
    ];

    // Default human-readable reasons mapped by origin.
    // Used when no explicit reason is provided by the caller.
    public const ORIGIN_REASONS = [
        'sale_admin'        => 'Venta por administrador',
        'sale_web'          => 'Venta por tienda web',
        'return'            => 'Devolución de cliente',
        'provider'          => 'Recepción de pedido de proveedor',
        'manual_adjustment' => 'Ajuste manual de inventario',
        'damage'            => 'Producto dañado o pérdida',
        'refund'            => 'Reembolso / cancelación',
    ];

    // Records an inventory movement and updates product stock atomically.
    public function record(
        Product $product,
        MovementType $type,
        string $origin,
        int $quantity,
        ?int $referenceId = null,
        ?int $userId = null,
        ?string $reason = null,
    ): InventoryMovement {
        // Reject invalid movement quantities.
        if ($quantity < 1) {
            throw new \RuntimeException('La cantidad del movimiento debe ser al menos 1.');
        }

        // Reject unsupported movement origins.
        if (! in_array($origin, self::VALID_ORIGINS, true)) {
            throw new \RuntimeException(
                "Origin '{$origin}' no es válido. Valores permitidos: " . implode(', ', self::VALID_ORIGINS)
            );
        }

        // Resolve the admin user when the flow is authenticated.
        $resolvedUserId = $userId ?? Auth::guard('admin')->id();

        // Use the standardized reason for the origin if none provided.
        $resolvedReason = $reason ?? self::ORIGIN_REASONS[$origin] ?? null;

        return DB::transaction(function () use ($product, $type, $origin, $quantity, $referenceId, $resolvedUserId, $resolvedReason) {

            // Lock the product row to prevent concurrent stock updates.
            /** @var Product $freshProduct */
            $freshProduct = Product::lockForUpdate()->findOrFail($product->product_id);
            $stockBefore = (int) $freshProduct->stock_current;

            // Calculate the resulting stock based on movement type.
            $stockAfter = match ($type) {
                MovementType::ENTRADA,
                MovementType::DEVOLUCION => $stockBefore + $quantity,

                MovementType::SALIDA => $stockBefore - $quantity,

                // For adjustments, quantity represents the final stock value.
                MovementType::AJUSTE => $quantity,
            };

            // Prevent negative stock values.
            if ($stockAfter < 0) {
                throw ValidationException::withMessages([
                    'quantity' => [
                        "La cantidad ({$quantity}) supera el stock disponible ({$stockBefore}).",
                    ],
                ]);
            }

            // Persist the updated stock value.
            $freshProduct->stock_current = $stockAfter;
            $freshProduct->save();

            // Store the movement in the audit log.
            $movement = InventoryMovement::create([
                'product_id'   => $freshProduct->product_id,
                'user_id'      => $resolvedUserId,
                'type'         => $type->value,
                'origin'       => $origin,
                'quantity'     => $quantity,
                'stock_before' => $stockBefore,
                'stock_after'  => $stockAfter,
                'reference_id' => $referenceId,
                'reason'       => $resolvedReason,
            ]);

            // Sync the provided product instance with the updated stock.
            $product->stock_current = $stockAfter;

            return $movement;
        });
    }

    // Records an admin sale as an inventory exit.
    public function recordSale(
        Product $product,
        int $quantity,
        int $saleId,
    ): InventoryMovement {
        return $this->record(
            product: $product,
            type: MovementType::SALIDA,
            origin: 'sale_admin',
            quantity: $quantity,
            referenceId: $saleId,
        );
    }

    // Records a web checkout sale without an associated admin user.
    public function recordWebCartSale(
        Product $product,
        int $quantity,
        int $saleId,
    ): InventoryMovement {
        return $this->record(
            product: $product,
            type: MovementType::SALIDA,
            origin: 'sale_web',
            quantity: $quantity,
            referenceId: $saleId,
            userId: null,
        );
    }

    // Records returned stock from a refund or cancellation.
    public function recordRefund(
        Product $product,
        int $quantity,
        int $saleId,
    ): InventoryMovement {
        return $this->record(
            product: $product,
            type: MovementType::DEVOLUCION,
            origin: 'return',
            quantity: $quantity,
            referenceId: $saleId,
        );
    }

    // Records a sale return initiated by an admin, carrying the explicit
    // reason entered in the return form (CA-02, CA-04).
    public function recordSaleReturn(
        Product $product,
        int $quantity,
        int $saleId,
        string $reason,
    ): InventoryMovement {
        return $this->record(
            product: $product,
            type: MovementType::DEVOLUCION,
            origin: 'return',
            quantity: $quantity,
            referenceId: $saleId,
            reason: $reason,
        );
    }

    // Records stock received from a supplier order.
    // Reason is automatically set to "Recepción de pedido de proveedor".
    public function recordSupplierEntry(
        Product $product,
        int $quantity,
        int $orderId,
    ): InventoryMovement {
        return $this->record(
            product: $product,
            type: MovementType::ENTRADA,
            origin: 'provider',
            quantity: $quantity,
            referenceId: $orderId,
            reason: self::ORIGIN_REASONS['provider'],
        );
    }

    // Records a manual stock increase.
    public function recordManualEntry(
        Product $product,
        int $quantity,
        string $reason,
    ): InventoryMovement {
        return $this->record(
            product: $product,
            type: MovementType::ENTRADA,
            origin: $reason,
            quantity: $quantity,
        );
    }

    // Records a manual stock decrease.
    public function recordManualExit(
        Product $product,
        int $quantity,
        string $reason,
    ): InventoryMovement {
        return $this->record(
            product: $product,
            type: MovementType::SALIDA,
            origin: $reason,
            quantity: $quantity,
        );
    }
}