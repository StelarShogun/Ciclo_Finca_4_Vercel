<?php

namespace App\Enums\Suppliers;

use App\Enums\Concerns\HasOptions;

enum SupplierOrderStatus: string
{
    use HasOptions;

    case Draft = 'draft';
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case PartialReceived = 'partial_received';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';
    case ClosePartial = 'close_partial';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Borrador',
            self::Pending => 'Pendiente',
            self::Confirmed => 'Confirmado',
            self::PartialReceived => 'Recepción parcial',
            self::Delivered => 'Entregado',
            self::Cancelled => 'Cancelado',
            self::ClosePartial => 'Cerrar con faltantes',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'slate',
            self::Pending => 'amber',
            self::Confirmed => 'blue',
            self::PartialReceived => 'orange',
            self::Delivered => 'green',
            self::Cancelled => 'red',
            self::ClosePartial => 'orange',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Draft => 'file',
            self::Pending => 'clock',
            self::Confirmed => 'check',
            self::PartialReceived => 'package-open',
            self::Delivered => 'truck',
            self::Cancelled => 'x-circle',
            self::ClosePartial => 'package-x',
        };
    }
}
