<?php

namespace App\Services\Admin\Audit;

final class AuditLogPresenter
{
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
        'product_activate' => 'Producto reactivado',
        'product_force_delete' => 'Producto eliminado permanentemente',
        'product_toggle_featured' => 'Producto destacado actualizado',
        'products_import' => 'Importación de productos',
    ];

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

    private static function humanizeToken(string $value): string
    {
        $normalized = trim(str_replace(['_', '-'], ' ', $value));

        return $normalized === ''
            ? 'Sin etiqueta'
            : mb_convert_case($normalized, MB_CASE_TITLE, 'UTF-8');
    }
}
