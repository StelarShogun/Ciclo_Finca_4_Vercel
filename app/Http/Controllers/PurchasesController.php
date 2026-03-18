<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use Illuminate\Http\Request;

class PurchasesController extends Controller
{
    public function index(Request $request)
    {
        $baseQuery = Sale::query()
            ->where('status', 'completed')
            ->where(function ($q) {
                $q->whereIn('order_source', ['web_cart', 'walk_in'])
                  ->orWhereNull('order_source');
            })
            ->notExpired();

        $sales = (clone $baseQuery)
            ->with(['client', 'customer'])
            ->withCount('saleItems')
            ->orderBy('sale_date', 'desc')
            ->paginate(15)
            ->withQueryString();

        $latestSaleId = (clone $baseQuery)->max('sale_id') ?? 0;

        return view('purchases.index', compact('sales', 'latestSaleId'));
    }

    public function heartbeat(Request $request)
    {
        $since = (int) $request->query('since', 0);

        $baseQuery = Sale::query()
            ->whereIn('status', ['pending', 'completed'])
            ->where(function ($q) {
                $q->whereIn('order_source', ['web_cart', 'walk_in'])
                  ->orWhereNull('order_source');
            })
            ->notExpired();

        $hasNew = (clone $baseQuery)
            ->where('sale_id', '>', $since)
            ->exists();

        $latestSaleId = (clone $baseQuery)->max('sale_id') ?? 0;

        return response()->json([
            'hasNew' => $hasNew,
            'latestSaleId' => $latestSaleId,
        ]);
    }
}

