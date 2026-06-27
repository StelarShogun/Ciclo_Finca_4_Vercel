<?php

namespace App\Services\Admin\Suppliers;

use Illuminate\Http\UploadedFile;

final readonly class XmlPriceDeviationAnalyzer
{
    public function __construct(private XmlPriceDeviationService $service) {}

    /**
     * @return array<string, mixed>
     */
    public function analyze(UploadedFile $file, float $thresholdPct): array
    {
        return $this->service->analyse($file, $thresholdPct);
    }
}
