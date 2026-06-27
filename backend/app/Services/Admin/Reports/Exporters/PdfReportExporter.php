<?php

namespace App\Services\Admin\Reports\Exporters;

use App\DTOs\Admin\Reports\RegistryReportData;
use App\Services\Admin\AdminPdfExportService;
use App\Services\Admin\Reports\Contracts\ReportExporter;
use Symfony\Component\HttpFoundation\Response;

final readonly class PdfReportExporter implements ReportExporter
{
    public function __construct(private AdminPdfExportService $pdfExportService) {}

    public function download(RegistryReportData $report): Response
    {
        return $this->pdfExportService->download('admin.exports.registry-table-pdf', [
            'pdfTitle' => $report->title,
            'pdfSubtitle' => $report->subtitle,
            'logoPath' => $this->resolvedLogoPath(),
            'filterLines' => $report->filterLines,
            'generatedFor' => 'Administración',
            'headers' => $report->headers,
            'rows' => $report->rows,
        ], $report->filenameSlug);
    }

    private function resolvedLogoPath(): ?string
    {
        $path = public_path('assets/images/brand/logo-ciclo-finca-icon.png');

        return is_file($path) ? $path : null;
    }
}
