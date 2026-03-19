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
        'verification_code',
        'verification_code_expires_at',
        'email_verified',
        'provider',
        'google_id',
        'remember_token',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'verification_code_expires_at' => 'datetime',
        'email_verified'               => 'boolean',
    ];

    public function cartItems(): HasMany
    // Normalizes provider: null values are treated as 'local' to prevent incorrect UI rendering.
    public function getProviderAttribute($value): string
    {
        return $value ?? 'local';
    }
}