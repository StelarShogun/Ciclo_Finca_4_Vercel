<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    /**
     * Etiquetas legibles para mostrar tipos de acción en español.
     *
     * @var array<string, string>
     */
    private const ACTION_TYPE_LABELS = [
        'admin_login' => 'Inicio de sesión',
        'admin_login_failed' => 'Intento fallido de inicio de sesión',
        'admin_logout' => 'Cierre de sesión',
        'module_access' => 'Acceso a módulo',
        'sale_create' => 'Venta creada',
        'sale_update_status' => 'Estado de venta actualizado',
        'sale_complete' => 'Venta completada',
        'sale_cancel' => 'Venta cancelada',
        'sale_refund' => 'Reembolso de venta',
        'client_ban' => 'Cliente bloqueado',
        'client_unban' => 'Cliente desbloqueado',
        'client_order_settings_update' => 'Configuración de pedidos actualizada',
        'supplier_order_create' => 'Pedido a proveedor creado',
        'supplier_order_state_update' => 'Estado de pedido a proveedor actualizado',
    ];

    /**
     * Etiquetas legibles para mostrar módulos en español.
     *
     * @var array<string, string>
     */
    private const MODULE_LABELS = [
        'auth' => 'Autenticación',
        'dashboard' => 'Panel principal',
        'reports' => 'Reportes',
        'sales' => 'Ventas',
        'orders' => 'Pedidos de clientes',
        'supplier_orders' => 'Pedidos a proveedores',
        'clients' => 'Clientes',
        'products' => 'Inventario y productos',
    ];

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
            'actionTypeLabels' => self::ACTION_TYPE_LABELS,
            'moduleLabels' => self::MODULE_LABELS,
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

    public static function actionTypeLabel(string $value): string
    {
        return self::ACTION_TYPE_LABELS[$value] ?? self::humanizeToken($value);
    }

    public static function moduleLabel(string $value): string
    {
        return self::MODULE_LABELS[$value] ?? self::humanizeToken($value);
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

    private static function humanizeToken(string $value): string
    {
        $normalized = trim(str_replace(['_', '-'], ' ', $value));
        if ($normalized === '') {
            return 'Sin etiqueta';
        }

        return mb_convert_case($normalized, MB_CASE_TITLE, 'UTF-8');
    }
}
