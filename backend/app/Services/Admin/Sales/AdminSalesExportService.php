<?php

namespace App\Services\Admin\Sales;

use App\Enums\Reports\ReportExportFormat;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Services\Admin\AdminPdfExportLimits;
use App\Services\Admin\AdminPdfExportService;
use App\Services\Admin\RegistryExcelExport;
use App\Services\Admin\ReportExcelFilename;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class AdminSalesExportService
{
    public function download(Request $request, AdminSalesQuery $salesQuery): Response|StreamedResponse
    {
        $format = strtolower((string) $request->get('format', 'pdf'));
        $base = Sale::query();

        if ($request->query('scope') === 'all') {
            $base->notExpired();
        } else {
            $salesQuery->applyListFilters($base, $request);
        }

        return match ($format) {
            ReportExportFormat::Pdf->value => $this->pdf($request, $base, $salesQuery),
            ReportExportFormat::Excel->value => $this->excel($request, $base, $salesQuery),
            ReportExportFormat::Csv->value => $this->csv($base),
            default => response()->json([
                'success' => false,
                'message' => 'Formato no soportado. Use pdf, excel o csv.',
            ], 400),
        };
    }

    private function excel(Request $request, Builder $base, AdminSalesQuery $salesQuery): StreamedResponse
    {
        $maxRows = AdminPdfExportLimits::SALES_MAX_ROWS;
        $totalMatching = (clone $base)->count();
        $filterLines = $salesQuery->filterLines($request);

        if ($totalMatching > $maxRows) {
            $filterLines[] = 'Nota: el Excel incluye como máximo '.$maxRows.' filas ('.$totalMatching.' ventas coinciden con los filtros).';
        }

        $rows = (clone $base)
            ->with(['client', 'saleItems.product'])
            ->orderBy('sale_date', 'desc')
            ->limit($maxRows)
            ->get();

        $headers = ['ID Venta', 'Factura', 'Cliente', 'Email', 'Fecha', 'Estado', 'Método pago', 'Subtotal (₡)', 'IVA (₡)', 'Descuento (₡)', 'Total (₡)', 'Ítems', 'Notas'];

        $dataRows = $rows->map(function ($sale): array {
            if (! $sale instanceof Sale) {
                return [];
            }

            $customer = $sale->client
                ? trim($sale->client->name.' '.($sale->client->first_surname ?? '').' '.($sale->client->second_surname ?? ''))
                : ($sale->buyer_name ?: 'Walk-in / Sin datos');
            $email = $sale->client ? $sale->client->gmail : ($sale->buyer_email ?: '');
            $items = $sale->saleItems->map(fn (SaleItem $item): string => ($item->product !== null ? $item->product->name : '?').' (×'.$item->quantity.')')->implode(', ');
            $saleDate = $sale->sale_date;

            return [
                (string) $sale->sale_id,
                (string) ($sale->invoice_number ?? ''),
                $customer,
                $email,
                $saleDate !== null ? $saleDate->format('d/m/Y H:i') : '',
                ucfirst((string) $sale->status),
                ucfirst((string) $sale->payment_method),
                number_format((float) $sale->subtotal, 2, '.', ''),
                number_format((float) $sale->iva, 2, '.', ''),
                number_format((float) $sale->discount, 2, '.', ''),
                number_format((float) $sale->total, 2, '.', ''),
                $items,
                (string) ($sale->notes ?? ''),
            ];
        })->values()->all();

        return app(RegistryExcelExport::class)->download(
            'Ventas',
            'Listado de ventas — Ciclo Finca 4',
            $headers,
            $dataRows,
            $filterLines,
            ReportExcelFilename::make('ventas'),
        );
    }

    private function pdf(Request $request, Builder $base, AdminSalesQuery $salesQuery): Response
    {
        $maxRows = AdminPdfExportLimits::SALES_MAX_ROWS;
        $totalMatching = (clone $base)->count();
        $filterLines = $salesQuery->filterLines($request);

        if ($totalMatching > $maxRows) {
            $filterLines[] = 'Nota: el PDF incluye como máximo '.$maxRows.' filas ('.$totalMatching.' ventas coinciden con los filtros).';
        }

        $aggregate = (clone $base)
            ->selectRaw('COUNT(*) as agg_count')
            ->selectRaw('COALESCE(SUM(total), 0) as agg_sum_total')
            ->selectRaw('COALESCE(SUM(subtotal), 0) as agg_sum_subtotal')
            ->selectRaw('COALESCE(SUM(iva), 0) as agg_sum_iva')
            ->selectRaw('COALESCE(SUM(discount), 0) as agg_sum_discount')
            ->first();

        $agg = $aggregate !== null ? $aggregate->getAttributes() : [];
        $rows = (clone $base)->with(['client'])->orderBy('sale_date', 'desc')->limit($maxRows)->get();
        $logoPath = public_path('assets/images/brand/logo-ciclo-finca-icon.png');

        return app(AdminPdfExportService::class)->download('admin.sales.sales-pdf', [
            'sales' => $rows,
            'totals' => [
                'count' => (int) ($agg['agg_count'] ?? 0),
                'sum_total' => (float) ($agg['agg_sum_total'] ?? 0.0),
                'sum_subtotal' => (float) ($agg['agg_sum_subtotal'] ?? 0.0),
                'sum_iva' => (float) ($agg['agg_sum_iva'] ?? 0.0),
                'sum_discount' => (float) ($agg['agg_sum_discount'] ?? 0.0),
            ],
            'pdfTitle' => 'Reporte de ventas',
            'pdfSubtitle' => 'Listado filtrado — Ciclo Finca 4',
            'logoPath' => is_file($logoPath) ? $logoPath : null,
            'filterLines' => $filterLines,
            'generatedFor' => 'Administración',
        ], 'ventas');
    }

    private function csv(Builder $base): Response
    {
        $filename = 'sales_'.now()->format('Y-m-d_H-i-s').'.csv';
        $chunkSize = AdminPdfExportLimits::SALES_CSV_CHUNK;

        return response()->stream(function () use ($base, $chunkSize): void {
            $file = fopen('php://output', 'w');

            if ($file === false) {
                return;
            }

            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($file, [
                'Sale ID', 'Customer', 'Email', 'Date', 'Status',
                'Payment', 'Subtotal', 'IVA', 'Discount', 'Total', 'Items', 'Notes',
            ], ';');

            (clone $base)
                ->with(['client', 'sellerAdmin', 'saleItems.product'])
                ->orderBy('sale_id')
                ->chunkById($chunkSize, function ($sales) use ($file): void {
                    foreach ($sales as $sale) {
                        $items = $sale->saleItems->map(fn (SaleItem $item): string => ($item->product !== null ? $item->product->name : '?').' (x'.$item->quantity.')')->implode(', ');
                        $customerDisplayName = $sale->client
                            ? trim($sale->client->name.' '.$sale->client->first_surname.' '.($sale->client->second_surname ?: ''))
                            : ($sale->buyer_name ?: 'Walk-in / Sin datos');
                        $customerEmail = $sale->client ? $sale->client->gmail : ($sale->buyer_email ?: 'N/A');
                        $saleDate = $sale->sale_date;

                        fputcsv($file, [
                            $sale->sale_id,
                            $customerDisplayName,
                            $customerEmail,
                            $saleDate !== null ? $saleDate->format('d/m/Y H:i') : '',
                            ucfirst((string) $sale->status),
                            ucfirst((string) $sale->payment_method),
                            '₡'.number_format((float) $sale->subtotal, 2, ',', '.'),
                            '₡'.number_format((float) $sale->iva, 2, ',', '.'),
                            '₡'.number_format((float) $sale->discount, 2, ',', '.'),
                            '₡'.number_format((float) $sale->total, 2, ',', '.'),
                            $items,
                            $sale->notes ?? '',
                        ], ';');
                    }
                }, 'sale_id');

            fclose($file);
        }, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }
}
