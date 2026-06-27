<?php

namespace App\Enums\Products;

use App\Enums\Concerns\HasOptions;

enum ProductBadge: string
{
    use HasOptions;

    case InStock = 'in_stock';
    case LowStock = 'low_stock';
    case OutOfStock = 'out_of_stock';
    case New = 'new';
    case Featured = 'featured';
    case Offer = 'offer';

    public function label(): string
    {
        return match ($this) {
            self::InStock => 'En stock',
            self::LowStock => 'Bajo stock',
            self::OutOfStock => 'Sin stock',
            self::New => 'Nuevo',
            self::Featured => 'Destacado',
            self::Offer => 'Oferta',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::InStock => 'green',
            self::LowStock => 'amber',
            self::OutOfStock => 'red',
            self::New => 'blue',
            self::Featured => 'purple',
            self::Offer => 'orange',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::InStock => 'check-circle',
            self::LowStock => 'alert-triangle',
            self::OutOfStock => 'x-circle',
            self::New => 'sparkles',
            self::Featured => 'star',
            self::Offer => 'tag',
        };
    }
}
