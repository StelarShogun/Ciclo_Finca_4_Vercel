<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Proveedor extends Model
{
    // Nombre de la tabla
    protected $table = 'proveedores';

    // Clave primaria
    protected $primaryKey = 'proveedor_id';

    // Campos que se pueden asignar masivamente
    protected $fillable = [
        'nombre',
        'contacto_principal',
        'telefono',
    'correo_electronico',
        'direccion',
        'tiempo_entrega',
        'evaluacion',
    ];

    // Autoincremental
    public $incrementing = true;

    // Tipo de clave primaria
    protected $keyType = 'int';

    // Timestamps personalizados
    const CREATED_AT = 'fecha_creacion';
    const UPDATED_AT = 'fecha_modificacion';

    // Casts para tipos de datos
    protected $casts = [
        'tiempo_entrega' => 'integer',
        'evaluacion' => 'float',
        'fecha_creacion' => 'datetime',
        'fecha_actualizacion' => 'datetime',
    ];
    
    // Relación con productos
    public function productos()
    {
        return $this->hasMany(Producto::class, 'proveedor_id', 'proveedor_id');
    }
}
