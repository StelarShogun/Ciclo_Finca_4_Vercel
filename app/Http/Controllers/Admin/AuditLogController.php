<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Support\AdminDateRange;
use App\Support\AdminPerPage;
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
        'product_create' => 'Producto creado',
        'product_update' => 'Producto actualizado',
        'product_delete' => 'Producto desactivado',
        'product_force_delete' => 'Producto eliminado permanentemente',
        'product_toggle_featured' => 'Producto destacado actualizado',
        'products_import' => 'Importación de productos',
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

    /**
     * Descripciones legadas para mostrar textos de auditoría en español.
     *
     * @var array<string, string>
     */
    private const DESCRIPTION_LABELS = [
        'Admin user logged in.' => 'Administrador inició sesión.',
        'Admin login failed.' => 'Intento fallido de inicio de sesión de administrador.',
        'Admin user logged out.' => 'Administrador cerró sesión.',
        'Acceso a módulo sensible desde el panel.' => 'Acceso a módulo sensible desde el panel.',
        'Product marked as featured.' => 'Producto marcado como destacado.',
        'Product removed from featured.' => 'Producto removido de destacados.',
        'Product created.' => 'Producto creado.',
        'Product updated.' => 'Producto actualizado.',
        'Product deactivated.' => 'Producto desactivado.',
        'Product permanently deleted.' => 'Producto eliminado permanentemente.',
        'Products import processed (XML).' => 'Importación de productos procesada (XML).',
        'Products import processed (CSV).' => 'Importación de productos procesada (CSV).',
        'Products import processed (JSON).' => 'Importación de productos procesada (JSON).',
    ];

    public function index(Request $request)
    {
        $user = $this->normalizeText($request->query('user'));
        $actionType = $this->normalizeText($request->query('action_type'));
        $module = $this->normalizeText($request->query('module'));
        $from = $this->normalizeDate($request->query('from'));
        $to = $this->normalizeDate($request->query('to'));
        $dir = $this->normalizeDir($request->query('dir'));

        $perPage = AdminPerPage::resolve($request->input('per_page', 10));
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
            ->when($from !== null, fn ($query) => $query->where(
                'created_at',
                '>=',
                AdminDateRange::parseDateStart($from->toDateString())->utc(),
            ))
            ->when($to !== null, fn ($query) => $query->where(
                'created_at',
                '<=',
                AdminDateRange::parseDateEnd($to->toDateString())->utc(),
            ))
            ->orderBy('created_at', $dir)
            ->paginate($perPage)
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

    public static function descriptionLabel(?string $value): string
    {
        $description = trim((string) $value);
        if ($description === '') {
            return 'Sin descripción';
        }

        return self::DESCRIPTION_LABELS[$description] ?? $description;
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
