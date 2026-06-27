<?php

namespace App\Enums\Sales;

use App\Enums\Concerns\HasOptions;

enum SaleStatus: string
{
    use HasOptions;

    case Pending = 'pending';
    case ReadyToPickup = 'ready_to_pickup';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case Returned = 'returned';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pendiente',
            self::ReadyToPickup => 'Listo para retirar',
            self::Completed => 'Completado',
            self::Cancelled => 'Cancelado',
            self::Returned => 'Devuelto',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'amber',
            self::ReadyToPickup => 'blue',
            self::Completed => 'green',
            self::Cancelled => 'red',
            self::Returned => 'slate',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Pending => 'clock',
            self::ReadyToPickup => 'package-check',
            self::Completed => 'check-circle',
            self::Cancelled => 'x-circle',
            self::Returned => 'rotate-ccw',
        };
    }
}
