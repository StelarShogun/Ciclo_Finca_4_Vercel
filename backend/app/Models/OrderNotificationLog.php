<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderNotificationLog extends Model
{
    protected $fillable = [
        'sale_id',
        'client_id',
        'channel',
        'status',
        'reason',
        'cancelled_at',
        'sent_at',
        'error_message',
    ];

    protected $casts = [
        'cancelled_at' => 'datetime',
        'sent_at' => 'datetime',
    ];

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class, 'sale_id', 'sale_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id', 'user_id');
    }
}
