<?php

namespace App\Services\Admin\Suppliers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

final class XmlPriceDeviationStorage
{
    private const PREFIX = 'xml_price_deviation:';

    public function put(int $adminId, array $analysis): string
    {
        $analysisId = (string) Str::uuid();

        Cache::put($this->key($adminId, $analysisId), $analysis, now()->addMinutes(30));

        return $analysisId;
    }

    public function get(int $adminId, ?string $analysisId): ?array
    {
        if ($analysisId === null || $analysisId === '') {
            return null;
        }

        $analysis = Cache::get($this->key($adminId, $analysisId));

        return is_array($analysis) ? $analysis : null;
    }

    public function forget(int $adminId, ?string $analysisId): void
    {
        if ($analysisId === null || $analysisId === '') {
            return;
        }

        Cache::forget($this->key($adminId, $analysisId));
    }

    private function key(int $adminId, string $analysisId): string
    {
        return self::PREFIX.$adminId.':'.$analysisId;
    }
}
