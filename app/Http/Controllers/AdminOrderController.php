<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use App\Models\Sale;
use Illuminate\Http\Request;

class AdminOrderController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->get('status');
        $search = $request->get('search');

        $baseWebOrdersQuery = $this->baseWebOrdersQuery();

        $query = (clone $baseWebOrdersQuery)
            ->with(['client', 'saleItems.product']);

        if ($status) {
            $query->where('status', $status);
        }

        if ($search) {
            $search = trim($search);
            $query->where(function ($q) use ($search) {
                $q->where('sale_id', 'like', "%{$search}%")
                    ->orWhere('invoice_number', 'like', "%{$search}%")
                    ->orWhereHas('client', function ($subQ) use ($search) {
                        $subQ->where('name', 'like', "%{$search}%")
                            ->orWhere('first_surname', 'like', "%{$search}%")
                            ->orWhere('gmail', 'like', "%{$search}%");
                    });
            });
        }

        $orders = $query->orderBy('sale_date', 'desc')->paginate(10)->withQueryString();

        $basePurchasesQuery = (clone $baseWebOrdersQuery)
            ->whereIn('status', ['pending', 'completed']);

        $latestPurchaseSaleId = (clone $basePurchasesQuery)->max('sale_id') ?? 0;
        $pendingWebOrdersCount = (clone $baseWebOrdersQuery)
            ->where('status', 'pending')
            ->count();

        $stored = AppSetting::getStoredReadyToPickupExpirationDays();
        $readyToPickupExpirationDays = ($stored !== null && $stored > 0)
            ? $stored
            : max(1, (int) config('sales.ready_to_pickup_expiration_days', 3));
        $usesEnvDefaultForExpiry = $stored === null;

        return view('admin.orders.index', compact(
            'orders',
            'latestPurchaseSaleId',
            'pendingWebOrdersCount',
            'readyToPickupExpirationDays',
            'usesEnvDefaultForExpiry'
        ));
    }

    private function baseWebOrdersQuery()
    {
        return Sale::query()
            ->where(function ($q) {
                $q->where('order_source', 'web_cart')
                    ->orWhereNull('order_source');
            })
            ->whereIn('status', ['pending', 'ready_to_pickup', 'completed', 'cancelled', 'refunded'])
            ->notExpired();
    }
}
