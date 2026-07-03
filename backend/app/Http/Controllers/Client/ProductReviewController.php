<?php

namespace App\Http\Controllers\Client;

use App\Actions\Client\Reviews\SaveProductReview;
use App\Http\Controllers\Controller;
use App\Http\Requests\Client\Reviews\StoreProductReviewBatchRequest;
use App\Http\Requests\Client\Reviews\StoreProductReviewRequest;
use App\Models\Product;
use App\Models\ProductReview;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class ProductReviewController extends Controller
{
    public function storeOrUpdate(StoreProductReviewRequest $request, Product $product, SaveProductReview $action): RedirectResponse|JsonResponse
    {
        $client = Auth::guard('clients')->user();
        abort_if(! $client, 403);
        Gate::forUser($client)->authorize('create', ProductReview::class);

        try {
            $action->handle($client, (int) $product->product_id, (int) $request->validated('stars'));
        } catch (AuthorizationException) {
            $message = 'Solo puedes reseñar productos que hayas comprado y retirado.';
            if ($request->expectsJson()) {
                return response()->json(['message' => $message], 403);
            }

            return back()->withErrors(['review' => $message])->withInput();
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Reseña guardada correctamente.']);
        }

        return back()->with('status', 'Tu reseña se guardó correctamente.');
    }

    public function storeBatch(StoreProductReviewBatchRequest $request, SaveProductReview $action): JsonResponse
    {
        $client = Auth::guard('clients')->user();
        abort_if(! $client, 403);
        Gate::forUser($client)->authorize('create', ProductReview::class);

        $validated = $request->validated();

        $reviews = collect($validated['reviews'])
            ->unique('product_id')
            ->values();

        try {
            foreach ($reviews as $row) {
                $action->handle($client, (int) $row['product_id'], (int) $row['stars']);
            }
        } catch (AuthorizationException) {
            return response()->json([
                'message' => 'Solo puedes reseñar productos que hayas comprado y retirado.',
            ], 403);
        }

        return response()->json([
            'message' => 'Reseñas guardadas correctamente.',
        ]);
    }
}
