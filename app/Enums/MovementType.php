<?php

namespace App\Enums;

/**
 * Tipos de movimiento de inventario (dirección del movimiento).
 *
 * Usado por InventoryMovementService para determinar cómo calcular
 * el stock resultante y para mostrar etiquetas legibles en la vista.
 */
enum MovementType: string
{
    /** Incrementa el stock: compras de proveedor, ajustes positivos. */
    case ENTRADA = 'entrada';

    /** Decrementa el stock: ventas, ajustes negativos, daños. */
    case SALIDA = 'salida';

    /**
     * Fija el stock a un valor absoluto.
     * La quantity en este caso representa el nuevo stock total, no una diferencia.
     */
    case AJUSTE = 'ajuste';

    /**
     * Incrementa el stock por devolución de un cliente.
     * Semánticamente distinto de ENTRADA aunque ambos suman stock,
     * para que los reportes puedan diferenciarlos.
     */
    case DEVOLUCION = 'devolucion';

    // ── Helpers de presentación ─────────────────────────────────────────────

    /** Etiqueta legible en español para vistas y exports. */
    public function label(): string
    {
        return match ($this) {
            self::ENTRADA    => 'Entrada',
            self::SALIDA     => 'Salida',
            self::AJUSTE     => 'Ajuste',
            self::DEVOLUCION => 'Devolución',
        };
    }

    /** Clase CSS Bootstrap para el badge de tipo en la vista. */
    public function badgeClass(): string
    {
        return match ($this) {
            self::ENTRADA    => 'success',
            self::SALIDA     => 'danger',
            self::AJUSTE     => 'warning',
            self::DEVOLUCION => 'info',
        };
    }

    /** Icono Font Awesome sugerido (fa-solid). */
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