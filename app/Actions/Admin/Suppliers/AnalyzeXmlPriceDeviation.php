<?php

namespace App\Actions\Admin\Suppliers;

use App\Services\Admin\Suppliers\XmlPriceDeviationAnalyzer;
use App\Services\Admin\Suppliers\XmlPriceDeviationStorage;
use Illuminate\Http\UploadedFile;

final readonly class AnalyzeXmlPriceDeviation
{
    public function __construct(
        private XmlPriceDeviationAnalyzer $analyzer,
        private XmlPriceDeviationStorage $storage,
    ) {}

    public function handle(int $adminId, UploadedFile $file, float $thresholdPct): string
    {
        $analysis = $this->analyzer->analyze($file, $thresholdPct);

        if (empty($analysis['items'])) {
            throw new \RuntimeException('No se encontraron productos en el archivo XML.');
        }

        return $this->storage->put($adminId, $analysis);
    }
}
