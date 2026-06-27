<?php

namespace App\Services\Admin\Reports\Exporters;

use App\DTOs\Admin\Reports\RegistryReportData;
use App\Services\Admin\Reports\Contracts\ReportExporter;
use Symfony\Component\HttpFoundation\Response;

final class CsvReportExporter implements ReportExporter
{
    public function download(RegistryReportData $report): Response
    {
        $filename = 'reporte-'.$report->filenameSlug.'-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($report): void {
            $handle = fopen('php://output', 'w');

            if ($handle === false) {
                return;
            }

            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($handle, [$report->title]);
            fputcsv($handle, [$report->subtitle]);

            foreach ($report->filterLines as $line) {
                fputcsv($handle, [$line]);
            }

            fputcsv($handle, []);
            fputcsv($handle, $report->headers);

            foreach ($report->rows as $row) {
                fputcsv($handle, $row);
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
