<?php

namespace App\Services\Admin\Reports\Providers;

use App\Services\Admin\Reports\Contracts\ReportDataProvider;

final class SupplierOrdersReportProvider implements ReportDataProvider
{
    use DelegatesRegistryReport;

    public function slug(): string
    {
        return 'pedidos-proveedores';
    }
}
