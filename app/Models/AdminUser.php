<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class AdminUser extends Authenticatable
{
    use Notifiable;

    protected $table = 'admins';

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
