<?php

namespace App\Models;

use App\Enums\MovementType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// Eloquent model for the inventory_movements table.
class InventoryMovement extends Model
{
    protected $table = 'inventory_movements';

    // Mass-assignable audit fields used when creating movement records.
    protected $fillable = [
        'product_id',
        'user_id',
        'type',
        'origin',
        'quantity',
        'stock_before',
        'stock_after',
        'reference_id',
        'reason',
    ];

    // Attribute casting for enum, numeric, and timestamp fields.
    protected $casts = [
        'type' => MovementType::class,
        'quantity' => 'integer',
        'stock_before' => 'integer',
        'stock_after' => 'integer',
        'reference_id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Product associated with this inventory movement.
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id', 'product_id');
    }

    // Admin user who triggered the movement, when applicable.
    public function adminUser(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class, 'user_id', 'user_id');
    }

    // Returns a readable label for the movement type.
    public function typeLabel(): string
    {
        return $this->type instanceof MovementType
            ? $this->type->label()
            : ucfirst((string) $this->type);
    }

    // Returns the Bootstrap badge class for the movement type.
    public function typeBadgeClass(): string
    {
        return $this->type instanceof MovementType
            ? $this->type->badgeClass()
            : 'secondary';
    }

    // Returns a readable label for the movement origin.
    public function originLabel(): string
    {
        return match ($this->origin) {
            'sale_admin' => 'Venta (admin)',
            'sale_web'   => 'Venta web',
            'return'     => 'Devolución de venta',
            'cancellation' => 'Cancelación de encargo',
            'provider'   => 'Entrada de proveedor',
            'manual_adjustment' => 'Ajuste manual',
            default      => ucwords(str_replace('_', ' ', $this->origin)),
        };
    }

    // Returns the full admin name or null for automatic movements.
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
