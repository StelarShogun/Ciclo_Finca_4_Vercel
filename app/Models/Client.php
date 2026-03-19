<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Client extends Authenticatable
{
    use Notifiable;

    protected $table      = 'client_table';
    protected $primaryKey = 'user_id';

    protected $fillable = [
        'name',
        'first_surname',
        'second_surname',
        'gmail',
        'password',
        'provider',
        'google_id',
        'remember_token',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    // Normalizes provider: null values are treated as 'local' to prevent incorrect UI rendering.
    public function getProviderAttribute($value): string
    {
        return $value ?? 'local';
    }
}