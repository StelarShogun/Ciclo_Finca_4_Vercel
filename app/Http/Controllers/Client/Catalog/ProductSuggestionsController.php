<?php

namespace App\Http\Controllers\Client\Catalog;

use App\Actions\Client\Catalog\GetProductSuggestions;
use App\Http\Controllers\Controller;
use App\Http\Requests\Client\Catalog\ProductSuggestionsRequest;
use App\Services\Shared\Security\SensitiveDataMasker;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

final class ProductSuggestionsController extends Controller
{
    public function __invoke(ProductSuggestionsRequest $request, GetProductSuggestions $suggestions): JsonResponse
    {
        $search = (string) $request->validated('search', '');

        try {
            return response()->json([
                'suggestions' => $suggestions->handle($search),
            ]);
        } catch (\Throwable $exception) {
            Log::error('Catalog suggestions failed.', SensitiveDataMasker::exceptionContext($exception, [
                'search_length' => mb_strlen($search),
            ]));

            return response()->json([
                'suggestions' => [],
                'error' => 'temporary_unavailable',
            ]);
        }
    }
}
