<?php

namespace App\Enums\Users;

use App\Enums\Concerns\HasOptions;

enum UserRole: string
{
    use HasOptions;

    case Admin = 'admin';
    case Client = 'client';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrador',
            self::Client => 'Cliente',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Admin => 'slate',
            self::Client => 'green',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Admin => 'shield',
            self::Client => 'user',
        };
    }
}
