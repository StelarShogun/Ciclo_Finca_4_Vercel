<?php

namespace App\Http\Controllers\Client\Catalog;

use App\Actions\Client\Catalog\GetTrendingSearches;
use App\Http\Controllers\Controller;
use App\Http\Requests\Client\Catalog\SearchTrendingRequest;
use App\Services\Shared\Security\SensitiveDataMasker;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

final class SearchTrendingController extends Controller
{
    public function __invoke(SearchTrendingRequest $request, GetTrendingSearches $trending): JsonResponse
    {
        $validated = $request->validated();
        $period = (string) $validated['period'];
        $limit = (int) $validated['limit'];

        try {
            return response()->json($trending->handle($period, $limit));
        } catch (\Throwable $exception) {
            Log::error('Catalog search trending failed.', SensitiveDataMasker::exceptionContext($exception, [
                'period' => $period,
                'limit' => $limit,
            ]));

            return response()->json(array_merge($trending->periodMeta($period), [
                'error' => 'temporary_unavailable',
                'limit' => $limit,
                'is_fallback' => false,
                'products' => [],
                'terms' => [],
            ]), 500);
        }
    }
}
