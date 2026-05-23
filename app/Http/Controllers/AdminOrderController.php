<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use App\Models\Sale;
use App\Support\AdminDateRange;
use App\Support\AdminPerPage;
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

        $dateRange = AdminDateRange::resolvePresetFromRequest(
            $request->input('date_range'),
            $request->input('date_from'),
            $request->input('date_to'),
        );

        if ($dateRange !== null) {
            if ($dateRange === AdminDateRange::PRESET_CUSTOM) {
                if ($request->filled('date_from') || $request->filled('date_to')) {
                    AdminDateRange::applyDateTimeBetween(
                        $query,
                        'sale_date',
                        AdminDateRange::PRESET_CUSTOM,
                        $request->input('date_from'),
                        $request->input('date_to'),
                        storedAsUtc: true,
                    );
                }
            } else {
                AdminDateRange::applyDateTimeBetween($query, 'sale_date', $dateRange, storedAsUtc: true);
            }
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

        $perPage = AdminPerPage::resolve($request->input('per_page', 10));
        $orders = $query->orderBy('sale_date', 'desc')->paginate($perPage)->withQueryString();

        $basePurchasesQuery = (clone $baseWebOrdersQuery)
            ->whereIn('status', ['pending', 'completed']);

        $latestPurchaseSaleId = (clone $basePurchasesQuery)->max('sale_id') ?? 0;

        $pendingWebOrdersCount = (clone $baseWebOrdersQuery)
            ->where('status', 'pending')
            ->count();

        $storedHours = AppSetting::getStoredReadyToPickupExpirationHours();
        $storedDaysLegacy = AppSetting::getStoredReadyToPickupExpirationDays();

        if ($storedHours !== null && $storedHours > 0) {
            $readyToPickupExpirationHours = $storedHours;
        } elseif ($storedDaysLegacy !== null && $storedDaysLegacy > 0) {
            $readyToPickupExpirationHours = $storedDaysLegacy * 24;
        } else {
            $readyToPickupExpirationHours = max(1, (int) config('sales.ready_to_pickup_expiration_hours', 72));
        }

        $usesEnvDefaultForExpiry = $storedHours === null && $storedDaysLegacy === null;

        $weeklyReportDay = AppSetting::getWeeklyReportDay();
        $weeklyReportHour = AppSetting::getWeeklyReportHour();
        $weeklyReportRecipients = AppSetting::getWeeklyReportRecipients();

        return view('admin.orders.index', compact(
            'orders',
            'latestPurchaseSaleId',
            'pendingWebOrdersCount',
            'readyToPickupExpirationHours',
            'usesEnvDefaultForExpiry',
            'weeklyReportDay',
            'weeklyReportHour',
            'weeklyReportRecipients'
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
