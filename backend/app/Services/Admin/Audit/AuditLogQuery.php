<?php

namespace App\Services\Admin\Audit;

use App\Http\Resources\Admin\AuditLogResource;
use App\Models\AuditLog;
use App\Services\Client\Inertia\ListPaginationPayload;
use App\Support\AdminDateRange;
use App\Support\AdminPerPage;
use Illuminate\Database\Eloquent\Builder;

final class AuditLogQuery
{
    /**
     * @param  array<string,mixed>  $filters
     * @return array<string,mixed>
     */
    public function indexPayload(array $filters): array
    {
        $logs = AuditLog::query()
            ->with('adminUser')
            ->when(($filters['user'] ?? '') !== '', function (Builder $query) use ($filters): void {
                $user = (string) $filters['user'];
                $query->where(function (Builder $sub) use ($user): void {
                    $sub->where('admin_email_snapshot', 'like', '%'.$user.'%')
                        ->orWhereHas('adminUser', function (Builder $adminQuery) use ($user): void {
                            $adminQuery->where('gmail', 'like', '%'.$user.'%')
                                ->orWhere('name', 'like', '%'.$user.'%')
                                ->orWhere('first_surname', 'like', '%'.$user.'%')
                                ->orWhere('second_surname', 'like', '%'.$user.'%');
                        });
                });
            })
            ->when(($filters['action_type'] ?? '') !== '', fn (Builder $query) => $query->where('action_type', $filters['action_type']))
            ->when(($filters['module'] ?? '') !== '', fn (Builder $query) => $query->where('module', $filters['module']))
            ->when(($filters['from'] ?? '') !== '', fn (Builder $query) => $query->where(
                'created_at',
                '>=',
                AdminDateRange::parseDateStart((string) $filters['from'])->utc(),
            ))
            ->when(($filters['to'] ?? '') !== '', fn (Builder $query) => $query->where(
                'created_at',
                '<=',
                AdminDateRange::parseDateEnd((string) $filters['to'])->utc(),
            ))
            ->orderBy('created_at', (string) ($filters['dir'] ?? 'desc'))
            ->paginate(AdminPerPage::resolve($filters['per_page'] ?? 10))
            ->withQueryString();

        return [
            'logs' => AuditLogResource::collection($logs->getCollection())->resolve(),
            'pagination' => ListPaginationPayload::from($logs),
            'actionTypeOptions' => $this->optionRows('action_type', 'actionTypeLabel'),
            'moduleOptions' => $this->optionRows('module', 'moduleLabel'),
            'filters' => [
                'user' => (string) ($filters['user'] ?? ''),
                'action_type' => (string) ($filters['action_type'] ?? ''),
                'module' => (string) ($filters['module'] ?? ''),
                'from' => (string) ($filters['from'] ?? ''),
                'to' => (string) ($filters['to'] ?? ''),
                'dir' => (string) ($filters['dir'] ?? 'desc'),
            ],
        ];
    }

    /**
     * @return array<int,array{value: string, label: string}>
     */
    private function optionRows(string $column, string $labelMethod): array
    {
        return AuditLog::query()
            ->select($column)
            ->distinct()
            ->orderBy($column)
            ->pluck($column)
            ->map(fn ($value): array => [
                'value' => (string) $value,
                'label' => AuditLogPresenter::{$labelMethod}((string) $value),
            ])
            ->values()
            ->all();
    }
}
