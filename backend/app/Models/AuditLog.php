<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    public $timestamps = false;

    protected $table = 'audit_logs';

    protected $fillable = [
        'admin_user_id',
        'admin_email_snapshot',
        'action_type',
        'module',
        'description',
        'meta',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'admin_user_id' => 'integer',
            'meta' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function adminUser(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class, 'admin_user_id', 'user_id');
    }
}
