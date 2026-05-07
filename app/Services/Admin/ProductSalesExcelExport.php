<?php

namespace App\Services\Admin;

use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Generates the "Productos más vendidos" Excel export.
 * Structure mirrors the PDF: filter summary block → Top 10 → full table.
 */
class ProductSalesExcelExport
{
    // ── Brand colours (same palette as the PDF CSS) ──────────────────────────
    private const COLOR_HEADER_BG = 'FF2D6A4F'; // dark green

    private const COLOR_HEADER_FG = 'FFFFFFFF';

    private const COLOR_TOP10_BG = 'FFE8F5E9'; // soft green

    private const COLOR_SECTION_BG = 'FF1B4332'; // darker green for section titles

    private const COLOR_SECTION_FG = 'FFFFFFFF';

    private const COLOR_ALT_ROW = 'FFF5F5F5';

    private const COLOR_BORDER = 'FFCCCCCC';

    private const COLOR_FILTER_BG = 'FFFFF9C4'; // light yellow

    private const COLOR_FILTER_FG = 'FF555555';

    /**
     * Build a streamed XLSX download response.
     *
     * @param  iterable<array<string, mixed>>  $top10
     * @param  iterable<array<string, mixed>>  $tableRows
     * @param  array<int, string>  $filterLines
     */
    public function download(
        iterable $top10,
        iterable $tableRows,
        string $top10Metric,
        array $filterLines,
        string $filename,
    ): StreamedResponse {
        $spreadsheet = $this->build(
            collect($top10),
            collect($tableRows),
            $top10Metric,
            $filterLines
        );

        return response()->streamDownload(function () use ($spreadsheet): void {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $top10
     * @param  Collection<int, array<string, mixed>>  $tableRows
     * @param  array<int, string>  $filterLines
     */
    private function build(
        Collection $top10,
        Collection $tableRows,
        string $top10Metric,
        array $filterLines,
    ): Spreadsheet {
        $spreadsheet = new Spreadsheet;
        $spreadsheet->getProperties()
            ->setCreator('Ciclo Finca 4')
            ->setTitle('Productos más vendidos')
            ->setSubject('Reporte de ventas por producto')
            ->setDescription('Exportado desde el módulo Reportes — Ciclo Finca 4');

        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Productos más vendidos');

        $row = 1;
        $row = $this->writeFilters($sheet, $filterLines, $row);
        $row = $this->writeTop10($sheet, $top10, $top10Metric, $row);
        $row = $this->writeFullTable($sheet, $tableRows, $row);

        // Auto-fit columns A–E
        foreach (range('A', 'E') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        return $spreadsheet;
    }

    /** ── Section 1: applied filters ─────────────────────────────────────── */
    private function writeFilters(Worksheet $sheet, array $filterLines, int $startRow): int
    {
        $row = $startRow;

        $this->writeSectionTitle($sheet, $row, 'Filtros aplicados');
        $row++;

        if (empty($filterLines)) {
            $sheet->setCellValue("A{$row}", 'Sin filtros adicionales');
            $sheet->getStyle("A{$row}")->getFont()->setItalic(true)->setColor(new Color(self::COLOR_FILTER_FG));
            $row++;
        } else {
            foreach ($filterLines as $line) {
                $sheet->setCellValue("A{$row}", $line);
                $sheet->mergeCells("A{$row}:E{$row}");
                $style = $sheet->getStyle("A{$row}");
                $style->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(self::COLOR_FILTER_BG);
                $style->getFont()->setColor(new Color(self::COLOR_FILTER_FG));
                $style->getAlignment()->setWrapText(true);
                $row++;
            }
        }

        $row++; // blank separator

        return $row;
    }

    /** ── Section 2: Top 10 table ─────────────────────────────────────────── */
    private function writeTop10(Worksheet $sheet, Collection $top10, string $top10Metric, int $startRow): int
    {
        $metricLabel = $top10Metric === 'units' ? 'unidades vendidas' : 'ingresos (₡)';
        $row = $startRow;

        $this->writeSectionTitle($sheet, $row, "Top 10 — {$metricLabel}");
        $row++;

        // Headers
        $headers = ['#', 'Producto', 'SKU', 'Unidades vendidas', 'Ingresos (₡)'];
        $this->writeTableHeaders($sheet, $row, $headers);
        $row++;

        // Data rows
        foreach ($top10 as $i => $item) {
            $isAlt = ($i % 2 === 1);
            $sheet->setCellValue("A{$row}", $i + 1);
            $sheet->setCellValue("B{$row}", $item['name']);
            $sheet->setCellValue("C{$row}", $item['sku']);
            $sheet->setCellValue("D{$row}", (int) $item['units_sold']);
            $sheet->setCellValue("E{$row}", (float) $item['revenue']);

            $rangeBg = $isAlt ? self::COLOR_ALT_ROW : self::COLOR_TOP10_BG;
            $this->applyDataRowStyle($sheet, $row, $rangeBg);
            $sheet->getStyle("D{$row}")->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER);
            $sheet->getStyle("E{$row}")->getNumberFormat()->setFormatCode('#,##0');
            $row++;
        }

        if ($top10->isEmpty()) {
            $sheet->setCellValue("A{$row}", 'Sin datos en este periodo.');
            $sheet->mergeCells("A{$row}:E{$row}");
            $sheet->getStyle("A{$row}")->getFont()->setItalic(true);
            $row++;
        }

        $row++;

        return $row;
    }

    /** ── Section 3: Full product table ──────────────────────────────────── */
    private function writeFullTable(Worksheet $sheet, Collection $tableRows, int $startRow): int
    {
        $row = $startRow;

        $this->writeSectionTitle($sheet, $row, 'Todos los productos con ventas');
        $row++;

        $headers = ['Producto', 'SKU', 'Unidades vendidas', 'Ingresos (₡)', 'Generado'];
        // Reuse columns A–D, E holds generation timestamp for first data row only
        $this->writeTableHeaders($sheet, $row, array_slice($headers, 0, 4));
        $row++;

        foreach ($tableRows as $i => $item) {
            $isAlt = ($i % 2 === 1);
            $sheet->setCellValue("A{$row}", $item['name']);
            $sheet->setCellValue("B{$row}", $item['sku']);
            $sheet->setCellValue("C{$row}", (int) $item['units_sold']);
            $sheet->setCellValue("D{$row}", (float) $item['revenue']);

            $this->applyDataRowStyle($sheet, $row, $isAlt ? self::COLOR_ALT_ROW : 'FFFFFFFF');
            $sheet->getStyle("C{$row}")->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER);
            $sheet->getStyle("D{$row}")->getNumberFormat()->setFormatCode('#,##0');
            $row++;
        }

        if ($tableRows->isEmpty()) {
            $sheet->setCellValue("A{$row}", 'Sin ventas completadas en este periodo para los criterios seleccionados.');
            $sheet->mergeCells("A{$row}:D{$row}");
            $sheet->getStyle("A{$row}")->getFont()->setItalic(true);
            $row++;
        }

        // Generation timestamp in footer
        $row++;
        $sheet->setCellValue("A{$row}", 'Generado el:');
        $sheet->setCellValue("B{$row}", now()->toDateTimeString());
        $sheet->getStyle("A{$row}")->getFont()->setBold(true);

        return $row + 1;
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function writeSectionTitle(Worksheet $sheet, int $row, string $title): void
    {
        $sheet->setCellValue("A{$row}", $title);
        $sheet->mergeCells("A{$row}:E{$row}");
        $style = $sheet->getStyle("A{$row}");
        $style->getFont()
            ->setBold(true)
            ->setSize(12)
            ->setColor(new Color(self::COLOR_SECTION_FG));
        $style->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB(self::COLOR_SECTION_BG);
        $style->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $sheet->getRowDimension($row)->setRowHeight(22);
    }

    /** @param array<int, string> $headers */
    private function writeTableHeaders(Worksheet $sheet, int $row, array $headers): void
    {
        $cols = range('A', 'E');
        foreach ($headers as $i => $label) {
            $cell = $cols[$i].$row;
            $sheet->setCellValue($cell, $label);
            $style = $sheet->getStyle($cell);
            $style->getFont()->setBold(true)->setColor(new Color(self::COLOR_HEADER_FG));
            $style->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(self::COLOR_HEADER_BG);
            $style->getAlignment()->setHorizontal(
                in_array($i, [2, 3], true) ? Alignment::HORIZONTAL_RIGHT : Alignment::HORIZONTAL_LEFT
            );
            $style->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN)
                ->getColor()->setARGB(self::COLOR_BORDER);
        }
        $sheet->getRowDimension($row)->setRowHeight(18);
    }

    private function applyDataRowStyle(Worksheet $sheet, int $row, string $bgARGB): void
    {
        $range = "A{$row}:E{$row}";
        $style = $sheet->getStyle($range);
        $style->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB($bgARGB);
        $style->getBorders()->getBottom()->setBorderStyle(Border::BORDER_HAIR)
            ->getColor()->setARGB(self::COLOR_BORDER);
        // Right-align numeric columns (C, D, E)
        foreach (['C', 'D', 'E'] as $col) {
            $sheet->getStyle("{$col}{$row}")
                ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        }
    }
}
