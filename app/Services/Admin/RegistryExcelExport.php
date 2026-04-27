<?php

namespace App\Services\Admin;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Genera exportaciones Excel para los listados administrativos del módulo Reportes.
 * Estructura: bloque de filtros → tabla de datos.
 * Paleta y estilo idénticos a ProductSalesExcelExport.
 */
class RegistryExcelExport
{
    // ── Paleta (misma que ProductSalesExcelExport) ────────────────────────────
    private const COLOR_HEADER_BG  = 'FF2D6A4F';
    private const COLOR_HEADER_FG  = 'FFFFFFFF';
    private const COLOR_SECTION_BG = 'FF1B4332';
    private const COLOR_SECTION_FG = 'FFFFFFFF';
    private const COLOR_ALT_ROW    = 'FFF5F5F5';
    private const COLOR_BORDER     = 'FFCCCCCC';
    private const COLOR_FILTER_BG  = 'FFFFF9C4';
    private const COLOR_FILTER_FG  = 'FF555555';

    /**
     * Construye y descarga el archivo XLSX.
     *
     * @param  array<int, string>              $headers      Encabezados de columna
     * @param  array<int, array<int, string>>  $rows         Filas de datos ya formateadas
     * @param  array<int, string>              $filterLines  Líneas del bloque de filtros
     */
    public function download(
        string $title,
        string $subtitle,
        array $headers,
        array $rows,
        array $filterLines,
        string $filename,
    ): StreamedResponse {
        $spreadsheet = $this->build($title, $subtitle, $headers, $rows, $filterLines);

        return response()->streamDownload(function () use ($spreadsheet): void {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control'       => 'max-age=0',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    private function build(
        string $title,
        string $subtitle,
        array $headers,
        array $rows,
        array $filterLines,
    ): Spreadsheet {
        $spreadsheet = new Spreadsheet;
        $spreadsheet->getProperties()
            ->setCreator('Ciclo Finca 4')
            ->setTitle($title)
            ->setSubject($subtitle)
            ->setDescription('Exportado desde el módulo Reportes — Ciclo Finca 4');

        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle(mb_substr($title, 0, 31)); // Excel: máx 31 chars

        $colCount = max(1, count($headers));
        $lastCol  = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colCount);

        $row = 1;
        $row = $this->writeFilters($sheet, $filterLines, $row, $lastCol);
        $this->writeTable($sheet, $headers, $rows, $row, $lastCol);

        // Auto-fit todas las columnas
        foreach (range('A', $lastCol) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        return $spreadsheet;
    }

    // ── Sección filtros ───────────────────────────────────────────────────────

    private function writeFilters(Worksheet $sheet, array $filterLines, int $startRow, string $lastCol): int
    {
        $row = $startRow;

        $this->writeSectionTitle($sheet, $row, 'Filtros aplicados', $lastCol);
        $row++;

        if (empty($filterLines)) {
            $sheet->setCellValue("A{$row}", 'Sin filtros adicionales');
            $sheet->getStyle("A{$row}")->getFont()->setItalic(true)->setColor(new Color(self::COLOR_FILTER_FG));
            $row++;
        } else {
            foreach ($filterLines as $line) {
                $sheet->setCellValue("A{$row}", $line);
                $sheet->mergeCells("A{$row}:{$lastCol}{$row}");
                $style = $sheet->getStyle("A{$row}");
                $style->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(self::COLOR_FILTER_BG);
                $style->getFont()->setColor(new Color(self::COLOR_FILTER_FG));
                $style->getAlignment()->setWrapText(true);
                $row++;
            }
        }

        $row++; // separador en blanco
        return $row;
    }

    // ── Tabla de datos ────────────────────────────────────────────────────────

    private function writeTable(Worksheet $sheet, array $headers, array $rows, int $startRow, string $lastCol): int
    {
        $row = $startRow;

        $this->writeSectionTitle($sheet, $row, 'Datos', $lastCol);
        $row++;

        $this->writeTableHeaders($sheet, $row, $headers, $lastCol);
        $row++;

        if (empty($rows)) {
            $sheet->setCellValue("A{$row}", 'Sin registros para los criterios seleccionados.');
            $sheet->mergeCells("A{$row}:{$lastCol}{$row}");
            $sheet->getStyle("A{$row}")->getFont()->setItalic(true);
            $row++;
        } else {
            foreach ($rows as $i => $dataRow) {
                $isAlt = ($i % 2 === 1);
                $bgARGB = $isAlt ? self::COLOR_ALT_ROW : 'FFFFFFFF';

                $cols = range('A', $lastCol);
                foreach ($dataRow as $ci => $value) {
                    if (isset($cols[$ci])) {
                        $sheet->setCellValue($cols[$ci].$row, $value);
                    }
                }

                $rangeStyle = $sheet->getStyle("A{$row}:{$lastCol}{$row}");
                $rangeStyle->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB($bgARGB);
                $rangeStyle->getBorders()->getBottom()
                    ->setBorderStyle(Border::BORDER_HAIR)
                    ->getColor()->setARGB(self::COLOR_BORDER);

                $row++;
            }
        }

        // Footer: fecha de generación
        $row++;
        $sheet->setCellValue("A{$row}", 'Generado el:');
        $sheet->setCellValue("B{$row}", now()->toDateTimeString());
        $sheet->getStyle("A{$row}")->getFont()->setBold(true);

        return $row + 1;
    }

    // ── Helpers de estilo ─────────────────────────────────────────────────────

    private function writeSectionTitle(Worksheet $sheet, int $row, string $title, string $lastCol): void
    {
        $sheet->setCellValue("A{$row}", $title);
        $sheet->mergeCells("A{$row}:{$lastCol}{$row}");
        $style = $sheet->getStyle("A{$row}");
        $style->getFont()->setBold(true)->setSize(12)->setColor(new Color(self::COLOR_SECTION_FG));
        $style->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(self::COLOR_SECTION_BG);
        $style->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $sheet->getRowDimension($row)->setRowHeight(22);
    }

    private function writeTableHeaders(Worksheet $sheet, int $row, array $headers, string $lastCol): void
    {
        $cols = range('A', $lastCol);
        foreach ($headers as $i => $label) {
            if (! isset($cols[$i])) {
                break;
            }
            $cell  = $cols[$i].$row;
            $sheet->setCellValue($cell, $label);
            $style = $sheet->getStyle($cell);
            $style->getFont()->setBold(true)->setColor(new Color(self::COLOR_HEADER_FG));
            $style->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(self::COLOR_HEADER_BG);
            $style->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $style->getBorders()->getBottom()
                ->setBorderStyle(Border::BORDER_THIN)
                ->getColor()->setARGB(self::COLOR_BORDER);
        }
        $sheet->getRowDimension($row)->setRowHeight(18);
    }
}