<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable; 
use Illuminate\Notifications\Notifiable;

class Usuario extends Authenticatable
{
    use Notifiable;

    // Nombre de la tabla
    protected $table = 'usuarios';

    // Clave primaria
    protected $primaryKey = 'usuario_id';

    // Campos que se pueden asignar masivamente
    protected $fillable = [
        'nombre',
        'apellido',
        'email',
        'password',
        'rol',
        'provider',
        'provider_id',
        'avatar',
        'ultimo_acceso',
    ];

    // Autoincremental
    public $incrementing = true;

    // Tipo de clave primaria
    protected $keyType = 'int';

    // Si quieres usar timestamps personalizados:
    const CREATED_AT = 'fecha_creacion';   
    const UPDATED_AT = 'fecha_actualizacion';
    
    // Habilitar timestamps
    public $timestamps = true;

    // Ocultar password en respuestas JSON
    protected $hidden = [
        'password',
    ];

    // Encriptar contraseña automáticamente (solo si no es null)
    public function setPasswordAttribute($value)
    {
        if ($value !== null && $value !== '') {
            $this->attributes['password'] = bcrypt($value);
        } else {
            $this->attributes['password'] = null;
        }
    }

    // Casts para fechas
    protected $casts = [
        'ultimo_acceso' => 'datetime',
        'fecha_creacion' => 'datetime',
        'fecha_actualizacion' => 'datetime',
    ];

    /**
     * Verificar si el usuario es administrador
     */
    public function isAdmin()
    {
        return $this->rol === 'admin';
    }

    /**
     * Verificar si el usuario puede acceder al sistema
     * (Solo administradores tienen acceso)
     */
    public function canAccessSystem()
    {
        return $this->isAdmin();
    }
}
