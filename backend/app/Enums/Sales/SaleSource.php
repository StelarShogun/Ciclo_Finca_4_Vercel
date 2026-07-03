<?php

namespace App\Enums\Sales;

use App\Enums\Concerns\HasOptions;

enum SaleSource: string
{
    use HasOptions;

    case Admin = 'admin';
    case WebCart = 'web_cart';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administración',
            self::WebCart => 'Tienda web',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Admin => 'slate',
            self::WebCart => 'green',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Admin => 'shield',
            self::WebCart => 'shopping-cart',
        };
    }
}
