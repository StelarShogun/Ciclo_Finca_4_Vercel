<?php

namespace App\Services\Admin;

use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Support\Str;

final class ReportPdfFilename
{
    public static function make(string $slug, ?DateTimeInterface $at = null): string
    {
        $date = $at ? Carbon::instance($at) : now();

        return 'reporte-'.Str::slug($slug, '-').'-'.$date->format('Y-m-d').'.pdf';
    }
}
