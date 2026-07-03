<?php

namespace App\Services\Admin\Dashboard;

use App\Enums\Reports\ReportExportFormat;
use App\Services\Admin\AdminPdfExportService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

final class DashboardExportService
{
    public function __construct(
        private readonly DashboardDataService $data,
        private readonly AdminPdfExportService $pdf,
    ) {}

    public function download(string $format, string $period): Response|JsonResponse
    {
        $payload = $this->data->summary();

        if ($format === ReportExportFormat::Pdf->value) {
            $startDate = $this->data->startDate($period)->startOfDay();
            $logoPath = public_path('assets/images/brand/logo-ciclo-finca-icon.png');

            return $this->pdf->download('admin.exports.dashboard-pdf', array_merge($payload, [
                'salesChartSeries' => $this->data->chartData($period)['sales'],
                'chartPeriodLabel' => $this->data->chartPeriodLabel($period),
                'pdfTitle' => 'Reporte del dashboard',
                'pdfSubtitle' => 'Resumen operativo — Ciclo Finca 4',
                'logoPath' => is_file($logoPath) ? $logoPath : null,
                'filterLines' => ['Gráfico de ventas: '.$this->data->chartPeriodLabel($period)],
                'generatedFor' => 'Administración',
                'periodStart' => $startDate,
            ]), 'dashboard');
        }

        if ($format === ReportExportFormat::Excel->value) {
            return response()->json($payload);
        }

        return response()->json([
            'success' => false,
            'message' => 'Formato no soportado',
        ], 400);
    }
}
