<?php

namespace App\Services\Admin\Reports;

use App\Enums\Reports\ReportExportFormat;
use App\Exceptions\ReportExportException;
use App\Services\Admin\Reports\Exporters\CsvReportExporter;
use App\Services\Admin\Reports\Exporters\ExcelReportExporter;
use App\Services\Admin\Reports\Exporters\PdfReportExporter;
use App\Services\Admin\Reports\Providers\CompositeReportProvider;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final readonly class ReportsExportRouter
{
    public function __construct(
        private ReportsRegistry $registry,
        private CompositeReportProvider $provider,
        private PdfReportExporter $pdfExporter,
        private ExcelReportExporter $excelExporter,
        private CsvReportExporter $csvExporter,
    ) {}

    public function download(Request $request, string $slug): Response
    {
        if (! $this->registry->has($slug)) {
            throw ReportExportException::unknownReport();
        }

        $format = ReportExportFormat::tryFrom(strtolower((string) $request->query('format', ReportExportFormat::Pdf->value)));

        if (! $format || ! in_array($format->value, $this->registry->allowedFormats($slug), true)) {
            throw ReportExportException::invalidFormat();
        }

        $effectiveRequest = $request->query('scope') === 'all' ? new Request : $request;
        $report = $this->provider->forSlug($slug, $effectiveRequest);

        return match ($format) {
            ReportExportFormat::Pdf => $this->pdfExporter->download($report),
            ReportExportFormat::Excel => $this->excelExporter->download($report),
            ReportExportFormat::Csv => $this->csvExporter->download($report),
        };
    }
}
