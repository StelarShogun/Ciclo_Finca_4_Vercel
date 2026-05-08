<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * @property int $user_id
 * @property string $name
 * @property string|null $first_surname
 * @property string|null $second_surname
 * @property string $gmail
 */
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
