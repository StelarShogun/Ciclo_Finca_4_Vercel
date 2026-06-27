<?php

namespace App\Enums\Audit;

use App\Enums\Concerns\HasOptions;

enum AuditAction: string
{
    use HasOptions;

    case Create = 'create';
    case Update = 'update';
    case Delete = 'delete';
    case Export = 'export';
    case Import = 'import';
    case Cancel = 'cancel';
    case Complete = 'complete';
    case Return = 'return';
    case Login = 'login';
    case Logout = 'logout';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    public function color(): string
    {
        return match ($this) {
            self::Delete, self::Cancel => 'red',
            self::Create, self::Complete => 'green',
            self::Export, self::Import => 'blue',
            default => 'slate',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Create => 'plus',
            self::Update => 'pencil',
            self::Delete => 'trash',
            self::Export => 'download',
            self::Import => 'upload',
            self::Cancel => 'x-circle',
            self::Complete => 'check-circle',
            self::Return => 'rotate-ccw',
            self::Login => 'log-in',
            self::Logout => 'log-out',
        };
    }
}
