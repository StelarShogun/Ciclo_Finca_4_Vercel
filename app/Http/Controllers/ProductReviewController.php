<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductReview;
use App\Models\SaleItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProductReviewController extends Controller
{
    public function storeOrUpdate(Request $request, Product $product): RedirectResponse|JsonResponse
    {
        $client = Auth::guard('clients')->user();
        abort_if(! $client, 403);

        $validated = $request->validate([
            'stars' => 'required|integer|between:1,5',
        ]);

        $hasCompletedPurchase = SaleItem::query()
            ->where('product_id', $product->product_id)
            ->whereHas('sale', function ($q) use ($client) {
                $q->where('client_id', $client->user_id)
                    ->where('status', 'completed');
            })
            ->exists();

        if (! $hasCompletedPurchase) {
            $message = 'Solo puedes reseñar productos que hayas comprado y retirado.';
            if ($request->expectsJson()) {
                return response()->json(['message' => $message], 403);
            }

            return back()->withErrors(['review' => $message])->withInput();
        }

        ProductReview::query()->updateOrCreate(
            [
                'product_id' => $product->product_id,
                'client_id' => $client->user_id,
            ],
            [
                'stars' => (int) $validated['stars'],
            ]
        );

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Reseña guardada correctamente.']);
        }

        return back()->with('status', 'Tu reseña se guardó correctamente.');
    }

    public function storeBatch(Request $request): JsonResponse
    {
        $client = Auth::guard('clients')->user();
        abort_if(! $client, 403);

        $validated = $request->validate([
            'reviews' => 'required|array|min:1',
            'reviews.*.product_id' => 'required|integer|exists:products,product_id',
            'reviews.*.stars' => 'required|integer|between:1,5',
        ]);

        $reviews = collect($validated['reviews'])
            ->unique('product_id')
            ->values();

        foreach ($reviews as $row) {
            $productId = (int) $row['product_id'];
            $hasCompletedPurchase = SaleItem::query()
                ->where('product_id', $productId)
                ->whereHas('sale', function ($q) use ($client) {
                    $q->where('client_id', $client->user_id)
                        ->where('status', 'completed');
                })
                ->exists();

            if (! $hasCompletedPurchase) {
                return response()->json([
                    'message' => 'Solo puedes reseñar productos que hayas comprado y retirado.',
                ], 403);
            }

            ProductReview::query()->updateOrCreate(
                [
                    'product_id' => $productId,
                    'client_id' => $client->user_id,
                ],
                [
                    'stars' => (int) $row['stars'],
                ]
            );
        }

        return response()->json([
            'message' => 'Reseñas guardadas correctamente.',
        ]);
    }
}
