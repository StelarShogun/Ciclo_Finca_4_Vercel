<?php

namespace App\Enums\Audit;

use App\Enums\Concerns\HasOptions;

enum AuditModule: string
{
    use HasOptions;

    case Products = 'products';
    case Sales = 'sales';
    case Suppliers = 'suppliers';
    case SupplierOrders = 'supplier_orders';
    case Inventory = 'inventory';
    case Reports = 'reports';
    case Auth = 'auth';
    case Clients = 'clients';

    public function label(): string
    {
        return match ($this) {
            self::Products => 'Productos',
            self::Sales => 'Ventas',
            self::Suppliers => 'Proveedores',
            self::SupplierOrders => 'Pedidos proveedor',
            self::Inventory => 'Inventario',
            self::Reports => 'Reportes',
            self::Auth => 'Autenticación',
            self::Clients => 'Clientes',
        };
    }

    public function color(): string
    {
        return 'slate';
    }

    public function icon(): string
    {
        return match ($this) {
            self::Products => 'package',
            self::Sales => 'receipt',
            self::Suppliers => 'truck',
            self::SupplierOrders => 'clipboard-list',
            self::Inventory => 'warehouse',
            self::Reports => 'bar-chart',
            self::Auth => 'lock',
            self::Clients => 'users',
        };
    }
}
