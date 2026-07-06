<?php

namespace App\Services\Admin\Sales;

use App\Models\Sale;

final class OrderStatusPolicy
{
    /** @return array{allowed: bool, message?: string, already_done?: bool} */
    public function complete(Sale $sale): array
    {
        return match ((string) $sale->status) {
            'ready_to_pickup' => ['allowed' => true],
            'completed' => ['allowed' => false, 'message' => 'Este pedido ya está confirmado. No puede confirmarse de nuevo.'],
            'cancelled' => ['allowed' => false, 'message' => 'Este pedido fue rechazado o cancelado. No puede confirmarse.'],
            'returned' => ['allowed' => false, 'message' => 'No se puede confirmar un pedido devuelto.'],
            'pending' => ['allowed' => false, 'message' => 'El pedido debe estar en estado "Listo para recoger" antes de confirmarse.'],
            default => ['allowed' => false, 'message' => 'Solo los pedidos listos para recoger pueden confirmarse.'],
        };
    }

    /** @return array{allowed: bool, message?: string, already_done?: bool} */
    public function markReadyToPickup(Sale $sale): array
    {
        return match ((string) $sale->status) {
            'pending' => ['allowed' => true],
            'ready_to_pickup' => ['allowed' => true, 'already_done' => true, 'message' => 'El pedido ya estaba marcado como listo para recoger.'],
            default => ['allowed' => false, 'message' => 'Solo los pedidos pendientes pueden marcarse como listos para recoger.'],
        };
    }

    /** @return array{allowed: bool, message?: string} */
    public function cancel(Sale $sale): array
    {
        return match ((string) $sale->status) {
            'pending', 'ready_to_pickup' => ['allowed' => true],
            'cancelled' => ['allowed' => false, 'message' => 'Este pedido ya está cancelado o rechazado.'],
            'completed' => ['allowed' => false, 'message' => 'No se puede rechazar un pedido ya confirmado. Use devolución si aplica.'],
            'returned' => ['allowed' => false, 'message' => 'Este pedido ya fue devuelto.'],
            default => ['allowed' => false, 'message' => 'Solo los pedidos pendientes o listos para recoger pueden rechazarse.'],
        };
    }

    /** @return array{allowed: bool, message?: string} */
    public function destroy(Sale $sale): array
    {
        return (string) $sale->status === 'pending'
            ? ['allowed' => true]
            : ['allowed' => false, 'message' => 'Solo los pedidos pendientes pueden cancelarse desde esta acción.'];
    }

    /** @return array{allowed: bool, message?: string} */
    public function returnSale(Sale $sale): array
    {
        return (string) $sale->status === 'completed'
            ? ['allowed' => true]
            : ['allowed' => false, 'message' => 'Solo las ventas confirmadas pueden registrar una devolución.'];
    }
}
