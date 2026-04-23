<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderStateTimeline extends Model
{
    public $timestamps = false;

    protected $table = 'timeline_order_state';

    protected $fillable = [
        'num_order',
        'user_id',
        'state',
        'changed_at',
    ];

    protected $casts = [
        'changed_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'num_order', 'num_order');
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class, 'user_id', 'user_id');
    }
}
