<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    // Table name
    protected $table = 'suppliers';

    // Primary key
    protected $primaryKey = 'supplier_id';

    // Mass assignable fields
    protected $fillable = [
        'name',
        'primary_contact',
        'phone',
        'email',
        'address',
        'delivery_time',
        'rating',
        'status',
    ];

    // Auto-increment
    public $incrementing = true;

    // Primary key type
    protected $keyType = 'int';

    // Custom timestamp column names
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    // Data type casts
    protected $casts = [
        'delivery_time' => 'integer',
        'rating'        => 'float',
        'created_at'    => 'datetime',
        'updated_at'    => 'datetime',
    ];

    // Relación con productos
    public function productos()
    {
        return $this->hasMany(Producto::class, 'proveedor_id', 'proveedor_id');
    }
}
