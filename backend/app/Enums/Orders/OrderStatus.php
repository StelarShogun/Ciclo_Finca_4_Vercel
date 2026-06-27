<?php

namespace App\Enums\Orders;

use App\Enums\Concerns\HasOptions;

enum OrderStatus: string
{
    use HasOptions;

    case Pending = 'pending';
    case ReadyToPickup = 'ready_to_pickup';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case Expired = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pendiente',
            self::ReadyToPickup => 'Listo para retirar',
            self::Completed => 'Completado',
            self::Cancelled => 'Cancelado',
            self::Expired => 'Expirado',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'amber',
            self::ReadyToPickup => 'blue',
            self::Completed => 'green',
            self::Cancelled, self::Expired => 'red',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Pending => 'clock',
            self::ReadyToPickup => 'package-check',
            self::Completed => 'check-circle',
            self::Cancelled => 'x-circle',
            self::Expired => 'timer-off',
        };
    }
}
