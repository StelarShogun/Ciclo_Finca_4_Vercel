<?php

namespace App\Enums\Reports;

use App\Enums\Concerns\HasOptions;

enum ReportExportFormat: string
{
    use HasOptions;

    case Pdf = 'pdf';
    case Excel = 'excel';
    case Csv = 'csv';

    public function label(): string
    {
        return match ($this) {
            self::Pdf => 'PDF',
            self::Excel => 'Excel',
            self::Csv => 'CSV',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pdf => 'red',
            self::Excel => 'green',
            self::Csv => 'slate',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Pdf => 'file-text',
            self::Excel => 'file-spreadsheet',
            self::Csv => 'table',
        };
    }
}
