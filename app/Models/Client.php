<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

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
        'remember_token',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function cartItems(): HasMany
    {
        return $this->hasMany(CartItem::class, 'client_id', 'user_id');
    }
}
