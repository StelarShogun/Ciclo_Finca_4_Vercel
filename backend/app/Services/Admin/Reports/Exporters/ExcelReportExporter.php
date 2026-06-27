<?php

namespace App\Services\Admin\Reports\Exporters;

use App\DTOs\Admin\Reports\RegistryReportData;
use App\Services\Admin\RegistryExcelExport;
use App\Services\Admin\ReportExcelFilename;
use App\Services\Admin\Reports\Contracts\ReportExporter;
use Symfony\Component\HttpFoundation\Response;

final readonly class ExcelReportExporter implements ReportExporter
{
    public function __construct(private RegistryExcelExport $excelExport) {}

    public function download(RegistryReportData $report): Response
    {
        return $this->excelExport->download(
            $report->title,
            $report->subtitle,
            $report->headers,
            $report->rows,
            $report->filterLines,
            ReportExcelFilename::make($report->filenameSlug),
        );
    }
}
