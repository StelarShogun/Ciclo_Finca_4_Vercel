<?php

namespace App\Enums\Products;

use App\Enums\Concerns\HasOptions;

enum ProductVisibility: string
{
    use HasOptions;

    case Public = 'public';
    case Hidden = 'hidden';

    public function label(): string
    {
        return match ($this) {
            self::Public => 'Público',
            self::Hidden => 'Oculto',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Public => 'green',
            self::Hidden => 'slate',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Public => 'eye',
            self::Hidden => 'eye-off',
        };
    }
}
