<?php

namespace App\Services\Admin;

use Illuminate\Http\Request;

/**
 * Query string al ir a Reportes → hub (/reports) desde pantallas con filtros,
 * para conservarlos al abrir «Exportar datos» (/reports/exportaciones).
 */
final class AdminReportsHubQuery
{
    public static function sidebarReportsIndexSuffix(Request $request): string
    {
        return match (true) {
            $request->routeIs('inventory') => AdminInventoryExportQuery::queryStringFromRequest($request),
            $request->routeIs('admin.supplier-orders.index') => AdminSupplierOrdersExportQuery::queryStringFromRequest($request),
            $request->routeIs('admin.orders.index') => AdminClientOrdersExportQuery::queryStringFromRequest($request),
            $request->routeIs('suppliers.index') => AdminSuppliersCatalogExportQuery::queryStringFromRequest($request),
            $request->routeIs('brands.index') => AdminBrandsCatalogExportQuery::queryStringFromRequest($request),
            default => '',
        };
    }
}
