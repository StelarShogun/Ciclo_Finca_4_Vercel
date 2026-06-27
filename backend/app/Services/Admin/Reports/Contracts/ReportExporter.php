<?php

namespace App\Services\Admin\Reports\Contracts;

use App\DTOs\Admin\Reports\RegistryReportData;
use Symfony\Component\HttpFoundation\Response;

interface ReportExporter
{
    public function download(RegistryReportData $report): Response;
}
