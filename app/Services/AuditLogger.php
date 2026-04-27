<?php

namespace App\Services;

use App\Models\AdminUser;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;

class AuditLogger
{
    /**
     * Registra una acción administrativa en la bitácora de auditoría.
     */
    public function logAdminAction(
        string $actionType,
        string $module,
        string $description,
        array $meta = [],
        ?AdminUser $admin = null
    ): AuditLog {
        $admin = $admin ?? Auth::guard('admin')->user();
        $safeMeta = $meta === [] ? null : $meta;

        return AuditLog::query()->create([
            'admin_user_id' => $admin?->user_id,
            'admin_email_snapshot' => $admin?->gmail,
            'action_type' => mb_substr(trim($actionType), 0, 64),
            'module' => mb_substr(trim($module), 0, 64),
            'description' => mb_substr(trim($description), 0, 255),
            'meta' => $safeMeta,
            'created_at' => now(),
        ]);
    }
}
