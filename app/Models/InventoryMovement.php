<?php

namespace App\Models;

use App\Enums\MovementType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo para la tabla inventory_movements.
 *
 * Cada instancia representa un movimiento atómico de stock sobre un producto.
 * Este modelo es de solo escritura mediante InventoryMovementService;
 * nunca se actualiza ni elimina (es un log inmutable).
 *
 * @property int             $id
 * @property int             $product_id
 * @property int|null        $user_id
 * @property string          $type         (MovementType::value)
 * @property string          $origin
 * @property int             $quantity
 * @property int             $stock_before
 * @property int             $stock_after
 * @property int|null        $reference_id
 * @property \Carbon\Carbon  $created_at
 * @property \Carbon\Carbon  $updated_at
 *
 * @property-read Product              $product
 * @property-read AdminUser|null       $adminUser
 */
class InventoryMovement extends Model
{
    protected $table = 'inventory_movements';

    /**
     * Los movimientos son registros de auditoría: nunca se modifican manualmente.
     * Solo se crean a través de InventoryMovementService.
     */
    protected $fillable = [
        'product_id',
        'user_id',
        'type',
        'origin',
        'quantity',
        'stock_before',
        'stock_after',
        'reference_id',
    ];

    protected $casts = [
        'type'         => MovementType::class,
        'quantity'     => 'integer',
        'stock_before' => 'integer',
        'stock_after'  => 'integer',
        'reference_id' => 'integer',
        'created_at'   => 'datetime',
        'updated_at'   => 'datetime',
    ];

    // ── Relaciones ──────────────────────────────────────────────────────────

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id', 'product_id');
    }

    /**
     * Administrador que originó el movimiento.
     *
     * Usa AdminUser (tabla `admins`, PK `user_id`).
     *
     * - FK local:   inventory_movements.user_id
     * - PK remota:  admins.user_id
     *
     * Nullable: los movimientos automáticos (sale_web, jobs) no tienen admin asociado.
     */
    public function adminUser(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class, 'user_id', 'user_id');
    }

    // ── Helpers de presentación ─────────────────────────────────────────────

    /**
     * Etiqueta legible del tipo de movimiento (delegada al Enum).
     */
    public function typeLabel(): string
    {
        return $this->type instanceof MovementType
            ? $this->type->label()
            : ucfirst((string) $this->type);
    }

    /**
     * Clase Bootstrap del badge del tipo.
     */
    public function typeBadgeClass(): string
    {
        return $this->type instanceof MovementType
            ? $this->type->badgeClass()
            : 'secondary';
    }

    /**
     * Etiqueta legible del campo origin para la vista.
     *
     * Valores posibles (definidos en InventoryMovementService::VALID_ORIGINS):
     *   sale_admin        → Venta (admin)
     *   sale_web          → Venta web
     *   return            → Devolución / Cancelación
     *   provider          → Entrada de proveedor
     *   manual_adjustment → Ajuste manual
     *   damage            → Daño / Merma
     */
    public function originLabel(): string
    {
        return match ($this->origin) {
            'sale_admin'        => 'Venta (admin)',
            'sale_web'          => 'Venta web',
            'return'            => 'Devolución / Cancelación',
            'provider'          => 'Entrada de proveedor',
            'manual_adjustment' => 'Ajuste manual',
            'damage'            => 'Daño / Merma',
            default             => ucwords(str_replace('_', ' ', $this->origin)),
        };
    }

    /**
     * Nombre del administrador que originó el movimiento,
     * o null si fue un movimiento automático (sin admin asociado).
     *
     * Combina nombre y apellidos del AdminUser cuando están disponibles.
     */
    public function adminName(): ?string
    {
        $admin = $this->adminUser;

        if (! $admin) {
            return null;
        }

        return trim(implode(' ', array_filter([
            $admin->name,
            $admin->first_surname,
            $admin->second_surname,
        ])));
    }
}