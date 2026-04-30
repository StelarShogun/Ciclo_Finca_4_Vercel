<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductReview;
use App\Models\SaleItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProductReviewController extends Controller
{
    public function storeOrUpdate(Request $request, Product $product): RedirectResponse|\Illuminate\Http\JsonResponse
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
}
