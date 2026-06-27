<?php

namespace App\Services\Admin\Reports\Providers;

use App\DTOs\Admin\Reports\RegistryReportData;
use Illuminate\Http\Request;

trait DelegatesRegistryReport
{
    public function __construct(private readonly RegistryReportProvider $registryProvider) {}

    abstract public function slug(): string;

    public function forSlug(string $slug, Request $request): RegistryReportData
    {
        abort_unless($slug === $this->slug(), 404);

        return $this->registryProvider->forSlug($slug, $request);
    }
}
