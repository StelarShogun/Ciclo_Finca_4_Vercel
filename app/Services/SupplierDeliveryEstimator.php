<?php

namespace App\Services;

use App\Models\AppSetting;
use App\Models\Order;
use Illuminate\Support\Carbon;

/**
 * Calculates a supplier order's estimated delivery date based on the historical
 * average between "confirmed" and "delivered" timeline events of the same supplier.
 *
 * Falls back to AppSetting::getSupplierOrderDefaultDeliveryDays() when the supplier
 * has no usable historical pairs (e.g. a brand-new supplier, or pedidos previos
 * sin recepción registrada).
 */
class SupplierDeliveryEstimator
{
    /**
     * Estimate the delivery date for the given order, anchored to confirmedAt.
     *
     * The order is excluded from its own history (excludeOrderId = num_order) so
     * recalculations on confirmation never feed back into the average.
     */
    public function estimateFor(Order $order, Carbon $confirmedAt): Carbon
    {
        $averageDays = $this->averageDeliveryDays(
            supplierId: (int) $order->supplier_id,
            excludeOrderId: (int) $order->num_order
        );

        $days = $averageDays ?? AppSetting::getSupplierOrderDefaultDeliveryDays();

        return $confirmedAt->copy()->addDays($days)->startOfDay();
    }

    /**
     * Average delivery days across the supplier's previous orders that have
     * both a "confirmed" and a "delivered" timeline entry.
     *
     * Returns null when no valid historical pair exists.
     *
     * Implementation notes:
     * - Pairs are matched per order: first "confirmed" with last "delivered".
     * - Orders where delivered_at <= confirmed_at are discarded (likely backfills).
     * - Sub-day deltas are rounded up to 1 day (min floor) so same-day deliveries
     *   don't poison the average toward zero.
     */
    public function averageDeliveryDays(int $supplierId, int $excludeOrderId = 0): ?int
    {
        if ($supplierId < 1) {
            return null;
        }

        $orders = Order::query()
            ->where('supplier_id', $supplierId)
            ->when($excludeOrderId > 0, fn ($q) => $q->where('num_order', '<>', $excludeOrderId))
            ->whereHas('stateTimeline', fn ($q) => $q->where('state', 'confirmed'))
            ->whereHas('stateTimeline', fn ($q) => $q->where('state', 'delivered'))
            ->with(['stateTimeline' => fn ($q) => $q->whereIn('state', ['confirmed', 'delivered'])])
            ->get(['num_order', 'supplier_id']);

        $deliveryDays = $orders
            ->map(function (Order $historicalOrder) {
                $confirmedAt = $historicalOrder->stateTimeline
                    ->firstWhere('state', 'confirmed')
                    ?->changed_at;

                $deliveredAt = $historicalOrder->stateTimeline
                    ->where('state', 'delivered')
                    ->last()
                    ?->changed_at;

                if (! $confirmedAt || ! $deliveredAt || $deliveredAt->lessThanOrEqualTo($confirmedAt)) {
                    return null;
                }

                // Use hours -> ceil to days so same-day pairs count as 1, not 0.
                $hours = max(1, $confirmedAt->diffInHours($deliveredAt));

                return max(1, (int) ceil($hours / 24));
            })
            ->filter(fn (?int $days) => $days !== null)
            ->values();

        if ($deliveryDays->isEmpty()) {
            return null;
        }

        return max(1, (int) round($deliveryDays->avg()));
    }
}
