<?php

namespace App\Services\Admin;

use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\Response;

final class AdminPdfExportService
{
    /**
     * @param  array<string, mixed>  $viewData
     */
    public function download(string $view, array $viewData, string $filenameSlug): Response
    {
        $pdf = Pdf::loadView($view, $viewData);

        return $pdf->download(ReportPdfFilename::make($filenameSlug));
    }
}

