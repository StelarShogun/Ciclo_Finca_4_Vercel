<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class AdminUser extends Authenticatable
{
    use Notifiable;

    // El proyecto actualmente usa `admin_table` para administradores.
    // El guard `admin` espera este modelo; por eso apuntamos a la tabla real.
    protected $table = 'admin_table';
    protected $primaryKey = 'user_id';

    protected $fillable = [
        'name',
        'first_surname',
        'second_surname',
        'gmail',
        'password',
        'last_access',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];
}
