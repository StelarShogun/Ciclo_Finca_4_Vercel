<?php

namespace App\Services\Shared\Sales;

use App\Models\AppSetting;
use App\Models\Sale;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

final class OrderExpirationPolicy
{
    public function orderExpirationDays(): int
    {
        return Cache::remember(AppSetting::cacheKeyOrderExpirationDays(), 3600, function (): int {
            $fromDb = AppSetting::getStoredOrderExpirationDays();
            if ($fromDb !== null && $fromDb > 0) {
                return $fromDb;
            }

            return max(1, (int) config('sales.order_expiration_days', 30));
        });
    }

    public function readyToPickupExpirationHours(): int
    {
        return Cache::remember(AppSetting::cacheKeyReadyToPickupExpirationHours(), 3600, function (): int {
            $fromHours = AppSetting::getStoredReadyToPickupExpirationHours();
            if ($fromHours !== null && $fromHours > 0) {
                return max(1, $fromHours);
            }

            $fromDays = AppSetting::getStoredReadyToPickupExpirationDays();
            if ($fromDays !== null && $fromDays > 0) {
                return max(1, $fromDays * 24);
            }

            return max(1, (int) config('sales.ready_to_pickup_expiration_hours', 72));
        });
    }

    public function expiresAt(Sale $sale): Carbon
    {
        return $sale->sale_date->copy()->addDays($this->orderExpirationDays());
    }

    public function pickupExpiresAt(Sale $sale): ?Carbon
    {
        if ($sale->ready_at === null) {
            return null;
        }

        return $sale->ready_at->copy()->addHours($this->readyToPickupExpirationHours());
    }

    public function isPickupExpired(Sale $sale): bool
    {
        $expires = $this->pickupExpiresAt($sale);

        return $expires !== null && now()->greaterThanOrEqualTo($expires);
    }

    public function pickupTimeRemainingLabel(Sale $sale): string
    {
        $expires = $this->pickupExpiresAt($sale);
        if ($expires === null) {
            return '';
        }

        $now = now();
        if ($now->greaterThanOrEqualTo($expires)) {
            return 'Vencido';
        }

        $secondsLeft = max(0, $expires->getTimestamp() - $now->getTimestamp());
        $hoursLeft = (int) floor($secondsLeft / 3600);

        if ($hoursLeft >= 24) {
            $daysLeft = (int) floor($hoursLeft / 24);

            return $daysLeft === 1 ? '1 día restante' : "{$daysLeft} días restantes";
        }

        if ($hoursLeft >= 1) {
            return $hoursLeft === 1 ? '1 hora restante' : "{$hoursLeft} horas restantes";
        }

        $minutesLeft = max(1, (int) ceil($secondsLeft / 60));

        return "{$minutesLeft} min restantes";
    }

    public function readyToPickupCutoff(?int $minutesOverride = null): Carbon
    {
        if ($minutesOverride !== null) {
            return Carbon::now()->subMinutes($minutesOverride);
        }

        return Carbon::now()->subHours($this->readyToPickupExpirationHours());
    }

    public function orderExpirationCutoff(): Carbon
    {
        return now()->subDays($this->orderExpirationDays());
    }
}
