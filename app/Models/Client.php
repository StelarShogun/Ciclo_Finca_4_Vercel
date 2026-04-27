<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * @property int $user_id
 * @property string $name
 * @property string|null $first_surname
 * @property string|null $second_surname
 * @property string $gmail
 */
class Client extends Authenticatable
{
    use Notifiable;

    protected $table = 'client_table';

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
        'active',
        'provider',
        'provider_id',
        'google_id',
        'remember_token',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'verification_code_expires_at' => 'datetime',
        'email_verified' => 'boolean',
        'active' => 'boolean',
    ];

    public function cartItems(): HasMany
    {
        return $this->hasMany(CartItem::class, 'client_id', 'user_id');
    }

    /** Ventas asociadas al usuario cliente (CF4-33 y reportes). */
    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class, 'client_id', 'user_id');
    }

    // Normalizes provider: null values are treated as 'local' to prevent incorrect UI rendering.
    public function getProviderAttribute($value): string
    {
        return $value ?? 'local';
    }
}
