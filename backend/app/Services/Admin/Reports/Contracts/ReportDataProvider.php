<?php

namespace App\Services\Admin\Reports\Contracts;

use App\DTOs\Admin\Reports\RegistryReportData;
use Illuminate\Http\Request;

interface ReportDataProvider
{
    public function forSlug(string $slug, Request $request): RegistryReportData;
}
