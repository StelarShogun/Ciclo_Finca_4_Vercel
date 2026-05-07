<?php

namespace App\Services\Admin;

use Carbon\Carbon;

/**
 * Generates a descriptive, date-stamped filename for Excel report downloads.
 * Mirrors ReportPdfFilename but produces .xlsx files.
 *
 * Example: ReportExcelFilename::make('productos-vendidos')
 *          → "reporte-productos-vendidos-2025-06-01.xlsx"
 */
class ReportExcelFilename
{
    public static function make(string $slug): string
    {
        $date = Carbon::now()->format('Y-m-d');

        return "reporte-{$slug}-{$date}.xlsx";
    }
}
