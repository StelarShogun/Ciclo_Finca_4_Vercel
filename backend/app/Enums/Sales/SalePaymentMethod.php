<?php

namespace App\Enums\Sales;

use App\Enums\Concerns\HasOptions;

enum SalePaymentMethod: string
{
    use HasOptions;

    case Cash = 'cash';
    case Sinpe = 'sinpe';
    case Transfer = 'transfer';
    case Card = 'card';

    public function label(): string
    {
        return match ($this) {
            self::Cash => 'Efectivo',
            self::Sinpe => 'SINPE',
            self::Transfer => 'Transferencia',
            self::Card => 'Tarjeta',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Cash => 'green',
            self::Sinpe => 'blue',
            self::Transfer => 'indigo',
            self::Card => 'purple',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Cash => 'banknote',
            self::Sinpe => 'smartphone',
            self::Transfer => 'landmark',
            self::Card => 'credit-card',
        };
    }
}
