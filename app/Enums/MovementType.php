<?php

namespace App\Enums;

// Defines the supported inventory movement types.
enum MovementType: string
{
    // Increases stock, such as supplier entries or positive adjustments.
    case ENTRADA = 'entrada';

    // Decreases stock, such as sales, negative adjustments, or damage.
    case SALIDA = 'salida';

    // Sets stock to an absolute value instead of applying a delta.
    case AJUSTE = 'ajuste';

    // Increases stock from a customer return.
    case DEVOLUCION = 'devolucion';

    // Returns the display label for the movement type.
    public function label(): string
    {
        return match ($this) {
            self::ENTRADA    => 'Entrada',
            self::SALIDA     => 'Salida',
            self::AJUSTE     => 'Ajuste',
            self::DEVOLUCION => 'Devolución',
        };
    }

    // Returns the Bootstrap badge class for the movement type.
    public function badgeClass(): string
    {
        return match ($this) {
            self::ENTRADA    => 'success',
            self::SALIDA     => 'danger',
            self::AJUSTE     => 'warning',
            self::DEVOLUCION => 'info',
        };
    }

    // Returns the suggested Font Awesome icon for the movement type.
    public function icon(): string
    {
        return match ($this) {
            self::ENTRADA    => 'fa-arrow-down',
            self::SALIDA     => 'fa-arrow-up',
            self::AJUSTE     => 'fa-sliders',
            self::DEVOLUCION => 'fa-rotate-left',
        };
    }
}