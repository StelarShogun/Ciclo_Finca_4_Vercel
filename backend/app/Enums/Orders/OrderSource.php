<?php

namespace App\Enums\Orders;

use App\Enums\Concerns\HasOptions;

enum OrderSource: string
{
    use HasOptions;

    case WebCart = 'web_cart';
    case Admin = 'admin';

    public function label(): string
    {
        return match ($this) {
            self::WebCart => 'Tienda web',
            self::Admin => 'Administración',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::WebCart => 'green',
            self::Admin => 'slate',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::WebCart => 'shopping-cart',
            self::Admin => 'shield',
        };
    }
}
