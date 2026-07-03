<?php

namespace App\Enums\Products;

use App\Enums\Concerns\HasOptions;

enum ProductStatus: string
{
    use HasOptions;

    case Active = 'active';
    case Inactive = 'inactive';
    case Activo = 'activo';
    case Inactivo = 'inactivo';

    public function label(): string
    {
        return match ($this) {
            self::Active, self::Activo => 'Activo',
            self::Inactive, self::Inactivo => 'Inactivo',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Active, self::Activo => 'green',
            self::Inactive, self::Inactivo => 'slate',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Active, self::Activo => 'check-circle',
            self::Inactive, self::Inactivo => 'pause-circle',
        };
    }
}
