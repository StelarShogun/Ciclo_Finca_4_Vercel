<?php

namespace App\Http\Resources\Admin;

use App\Models\AuditLog;
use App\Services\Admin\Audit\AuditLogPresenter;
use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class AuditLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var AuditLog $log */
        $log = $this->resource;
        $userLabel = $log->adminUser
            ? trim($log->adminUser->name.' '.($log->adminUser->first_surname ?? '')).' ('.$log->adminUser->gmail.')'
            : ($log->admin_email_snapshot ?? 'Sistema');
        $createdAt = $log->created_at instanceof DateTimeInterface
            ? $log->created_at
            : ($log->created_at ? Carbon::parse($log->created_at) : null);

        return [
            'id' => (int) $log->id,
            'created_at' => $createdAt?->format('d/m/Y H:i:s') ?? '—',
            'user' => $userLabel,
            'action_type' => $log->action_type,
            'action_label' => AuditLogPresenter::actionTypeLabel((string) $log->action_type),
            'module' => $log->module,
            'module_label' => AuditLogPresenter::moduleLabel((string) $log->module),
            'description' => AuditLogPresenter::descriptionLabel($log->description),
        ];
    }
}
