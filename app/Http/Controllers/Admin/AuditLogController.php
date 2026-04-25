<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $user = $this->normalizeText($request->query('user'));
        $actionType = $this->normalizeText($request->query('action_type'));
        $module = $this->normalizeText($request->query('module'));
        $from = $this->normalizeDate($request->query('from'));
        $to = $this->normalizeDate($request->query('to'));
        $dir = $this->normalizeDir($request->query('dir'));

        $logs = AuditLog::query()
            ->with('adminUser')
            ->when($user !== '', function ($query) use ($user) {
                $query->where(function ($sub) use ($user) {
                    $sub->where('admin_email_snapshot', 'like', '%'.$user.'%')
                        ->orWhereHas('adminUser', function ($adminQuery) use ($user) {
                            $adminQuery->where('gmail', 'like', '%'.$user.'%')
                                ->orWhere('name', 'like', '%'.$user.'%')
                                ->orWhere('first_surname', 'like', '%'.$user.'%')
                                ->orWhere('second_surname', 'like', '%'.$user.'%');
                        });
                });
            })
            ->when($actionType !== '', fn ($query) => $query->where('action_type', $actionType))
            ->when($module !== '', fn ($query) => $query->where('module', $module))
            ->when($from !== null, fn ($query) => $query->where('created_at', '>=', $from->copy()->startOfDay()))
            ->when($to !== null, fn ($query) => $query->where('created_at', '<=', $to->copy()->endOfDay()))
            ->orderBy('created_at', $dir)
            ->paginate(20)
            ->withQueryString();

        $actionTypes = AuditLog::query()
            ->select('action_type')
            ->distinct()
            ->orderBy('action_type')
            ->pluck('action_type');

        $modules = AuditLog::query()
            ->select('module')
            ->distinct()
            ->orderBy('module')
            ->pluck('module');

        return view('admin.reports.audit-log', [
            'logs' => $logs,
            'actionTypes' => $actionTypes,
            'modules' => $modules,
            'filters' => [
                'user' => $user,
                'action_type' => $actionType,
                'module' => $module,
                'from' => $from?->toDateString() ?? '',
                'to' => $to?->toDateString() ?? '',
                'dir' => $dir,
            ],
        ]);
    }

    private function normalizeText(mixed $value): string
    {
        if (! is_string($value)) {
            return '';
        }

        return mb_substr(trim($value), 0, 100);
    }

    private function normalizeDate(mixed $value): ?Carbon
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }

    private function normalizeDir(mixed $value): string
    {
        return is_string($value) && strtolower($value) === 'asc' ? 'asc' : 'desc';
    }
}
