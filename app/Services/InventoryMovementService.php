<?php

namespace App\Services;

use App\Enums\MovementType;
use App\Models\InventoryMovement;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * InventoryMovementService — puerta única de registro de movimientos de stock.
 *
 * ╔══════════════════════════════════════════════════════════════════╗
 * ║  REGLA DE ORO                                                    ║
 * ║  Todo cambio a products.stock_current debe pasar por este        ║
 * ║  service. Nunca usar increment/decrement/update directamente     ║
 * ║  en los controllers para modificar el stock.                     ║
 * ╚══════════════════════════════════════════════════════════════════╝
 *
 * ┌─────────────────────────────────────────────────────────────────────────────┐
 * │  MAPA COMPLETO DE MOVIMIENTOS DEL SISTEMA                                   │
 * ├──────────────────────┬──────────────┬────────────────────┬──────────────────┤
 * │  Evento              │  type        │  origin            │  Origen código   │
 * ├──────────────────────┼──────────────┼────────────────────┼──────────────────┤
 * │  Venta admin         │  SALIDA      │  sale_admin        │  SalesController::store()             │
 * │  Pedido web (cart)   │  SALIDA      │  sale_web          │  ClientPageController::checkout()     │
 * │  Cancelación pedido  │  DEVOLUCION  │  return            │  SalesController::cancel()            │
 * │  Reembolso venta     │  DEVOLUCION  │  return            │  SalesController::refund()            │
 * │  Recepción proveedor │  ENTRADA     │  provider          │  SupplierOrderController::updateState()│
 * │  Ajuste manual +     │  ENTRADA     │  manual_adjustment │  ProductController::addManualStock()  │
 * │  Ajuste manual +     │  ENTRADA     │  damage            │  ProductController::addManualStock()  │
 * │  Ajuste manual +     │  ENTRADA     │  return            │  ProductController::addManualStock()  │
 * │  Ajuste manual −     │  SALIDA      │  manual_adjustment │  ProductController::removeManualStock()│
 * │  Ajuste manual −     │  SALIDA      │  damage            │  ProductController::removeManualStock()│
 * │  Ajuste manual −     │  SALIDA      │  return            │  ProductController::removeManualStock()│
 * └──────────────────────┴──────────────┴────────────────────┴──────────────────┘
 *
 * Valores válidos para origin:
 *   - 'sale_admin'        Venta registrada por un administrador (mostrador).
 *   - 'sale_web'          Pedido colocado desde la tienda web por un cliente.
 *   - 'return'            Devolución o cancelación de venta (stock que vuelve).
 *   - 'provider'          Recepción de mercancía de una orden de proveedor.
 *   - 'manual_adjustment' Ajuste manual de inventario sin causa específica.
 *   - 'damage'            Merma o daño físico de producto.
 *
 * El método record() es atómico: actualiza el stock del producto y
 * registra el movimiento en la misma DB::transaction. Si cualquiera
 * de las dos operaciones falla, ninguna persiste.
 */
class InventoryMovementService
{
    /** Valores permitidos para el campo origin. */
    public const VALID_ORIGINS = [
        'sale_admin',
        'sale_web',
        'return',
        'provider',
        'manual_adjustment',
        'damage',
    ];

    // ── Método principal ────────────────────────────────────────────────────

    /**
     * Registra un movimiento de inventario y actualiza el stock del producto.
     *
     * @param  Product      $product      Instancia del producto a modificar.
     * @param  MovementType $type         Tipo de movimiento (dirección).
     * @param  string       $origin       Origen del movimiento. Debe ser uno de VALID_ORIGINS.
     * @param  int          $quantity     Cantidad afectada. Siempre positiva.
     *                                    Para AJUSTE es el nuevo valor absoluto del stock.
     * @param  int|null     $referenceId  ID del documento origen (sale_id, num_order…).
     * @param  int|null     $userId       ID del AdminUser. Si es null se lee del guard 'admin'.
     *                                    Los movimientos automáticos (sale_web, jobs) pasan null
     *                                    explícito; user_id quedará null en el log.
     *
     * @throws ValidationException  Si la salida o ajuste dejaría el stock negativo.
     * @throws \RuntimeException    Si quantity < 1 o origin no es válido.
     */
    public function record(
        Product      $product,
        MovementType $type,
        string       $origin,
        int          $quantity,
        ?int         $referenceId = null,
        ?int         $userId      = null,
    ): InventoryMovement {
        if ($quantity < 1) {
            throw new \RuntimeException('La cantidad del movimiento debe ser al menos 1.');
        }

        if (! in_array($origin, self::VALID_ORIGINS, true)) {
            throw new \RuntimeException(
                "Origin '{$origin}' no es válido. Valores permitidos: " . implode(', ', self::VALID_ORIGINS)
            );
        }

        /**
         * El único guard de administradores en este proyecto es 'admin',
         * que autentica instancias de AdminUser (tabla admins, PK user_id).
         * Si no hay admin autenticado (flujos automáticos / cliente web),
         * el campo user_id quedará null en el registro, lo cual es correcto.
         */
        $resolvedUserId = $userId ?? Auth::guard('admin')->id();

        return DB::transaction(function () use ($product, $type, $origin, $quantity, $referenceId, $resolvedUserId) {

            // 1. Bloquear la fila del producto para evitar condiciones de carrera
            /** @var Product $freshProduct */
            $freshProduct = Product::lockForUpdate()->findOrFail($product->product_id);
            $stockBefore  = (int) $freshProduct->stock_current;

            // 2. Calcular el stock resultante según el tipo de movimiento
            $stockAfter = match ($type) {
                MovementType::ENTRADA,
                MovementType::DEVOLUCION => $stockBefore + $quantity,

                MovementType::SALIDA     => $stockBefore - $quantity,

                // AJUSTE: quantity es el nuevo valor absoluto del stock
                MovementType::AJUSTE     => $quantity,
            };

            // 3. Validar que el stock no quede negativo
            if ($stockAfter < 0) {
                throw ValidationException::withMessages([
                    'quantity' => [
                        "La cantidad ({$quantity}) supera el stock disponible ({$stockBefore}).",
                    ],
                ]);
            }

            // 4. Persistir el nuevo stock en el producto
            $freshProduct->stock_current = $stockAfter;
            $freshProduct->save();

            // 5. Registrar el movimiento en la tabla de auditoría
            $movement = InventoryMovement::create([
                'product_id'   => $freshProduct->product_id,
                'user_id'      => $resolvedUserId,
                'type'         => $type->value,
                'origin'       => $origin,
                'quantity'     => $quantity,
                'stock_before' => $stockBefore,
                'stock_after'  => $stockAfter,
                'reference_id' => $referenceId,
            ]);

            // 6. Sincronizar la instancia recibida para evitar datos stale
            $product->stock_current = $stockAfter;

            return $movement;
        });
    }

    // ── Métodos de conveniencia (semántica de negocio) ─────────────────────

    /**
     * Registra una SALIDA por venta realizada por un administrador (mostrador).
     * Llamado por SalesController::store().
     *
     * origin = 'sale_admin'
     */
    public function recordSale(
        Product $product,
        int     $quantity,
        int     $saleId,
    ): InventoryMovement {
        return $this->record(
            product:     $product,
            type:        MovementType::SALIDA,
            origin:      'sale_admin',
            quantity:    $quantity,
            referenceId: $saleId,
        );
    }

    /**
     * Registra una SALIDA por pedido colocado desde la tienda web (carrito cliente).
     * Llamado por ClientPageController::checkout().
     *
     * origin = 'sale_web'
     * userId = null — no hay admin autenticado en el flujo cliente.
     */
    public function recordWebCartSale(
        Product $product,
        int     $quantity,
        int     $saleId,
    ): InventoryMovement {
        return $this->record(
            product:     $product,
            type:        MovementType::SALIDA,
            origin:      'sale_web',
            quantity:    $quantity,
            referenceId: $saleId,
            userId:      null,
        );
    }

    /**
     * Registra una DEVOLUCIÓN (stock que vuelve al almacén por cancelación o reembolso).
     * Llamado por SalesController::refund() y SalesController::cancel().
     *
     * origin = 'return'
     */
    public function recordRefund(
        Product $product,
        int     $quantity,
        int     $saleId,
    ): InventoryMovement {
        return $this->record(
            product:     $product,
            type:        MovementType::DEVOLUCION,
            origin:      'return',
            quantity:    $quantity,
            referenceId: $saleId,
        );
    }

    /**
     * Registra una ENTRADA por recepción de orden de proveedor.
     * Llamado por SupplierOrderController::updateState() al pasar a 'delivered'.
     *
     * origin = 'provider'
     */
    public function recordSupplierEntry(
        Product $product,
        int     $quantity,
        int     $orderId,
    ): InventoryMovement {
        return $this->record(
            product:     $product,
            type:        MovementType::ENTRADA,
            origin:      'provider',
            quantity:    $quantity,
            referenceId: $orderId,
        );
    }

    /**
     * Registra una ENTRADA manual de stock.
     * Llamado por ProductController::addManualStock().
     *
     * origin = 'manual_adjustment' | 'damage' | 'return'
     */
    public function recordManualEntry(
        Product $product,
        int     $quantity,
        string  $reason,
    ): InventoryMovement {
        return $this->record(
            product:  $product,
            type:     MovementType::ENTRADA,
            origin:   $reason,
            quantity: $quantity,
        );
    }

    /**
     * Registra una SALIDA manual de stock.
     * Llamado por ProductController::removeManualStock().
     *
     * origin = 'manual_adjustment' | 'damage' | 'return'
     */
    public function recordManualExit(
        Product $product,
        int     $quantity,
        string  $reason,
    ): InventoryMovement {
        return $this->record(
            product:  $product,
            type:     MovementType::SALIDA,
            origin:   $reason,
            quantity: $quantity,
        );
    }
}