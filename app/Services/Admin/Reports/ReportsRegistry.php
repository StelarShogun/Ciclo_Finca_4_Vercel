<?php

namespace App\Services\Admin\Reports;

use App\Enums\Reports\ReportExportFormat;

final class ReportsRegistry
{
    /** @return array<int, string> */
    public function slugs(): array
    {
        return [
            'proveedores',
            'marcas',
            'pedidos-proveedores',
            'usuarios',
            'pedidos-clientes',
        ];
    }

    public function has(string $slug): bool
    {
        return in_array($slug, $this->slugs(), true);
    }

    /** @return array<int, string> */
    public function allowedFormats(string $slug): array
    {
        return $this->has($slug)
            ? [ReportExportFormat::Pdf->value, ReportExportFormat::Excel->value, ReportExportFormat::Csv->value]
            : [];
    }
}
