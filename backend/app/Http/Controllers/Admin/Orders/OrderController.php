<?php

namespace App\Http\Controllers\Admin\Orders;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Orders\AdminOrderIndexRequest;
use App\Models\AppSetting;
use App\Models\Sale;
use App\Services\Client\Inertia\ListPaginationPayload;
use App\Support\AdminDateRange;
use App\Support\AdminPerPage;
use Inertia\Inertia;

class OrderController extends Controller
{
    public function index(AdminOrderIndexRequest $request)
    {
        $filters = $request->validated();
        $status = $filters['status'] ?? '';
        $search = $filters['search'] ?? '';

        $baseWebOrdersQuery = $this->baseWebOrdersQuery();

        $query = (clone $baseWebOrdersQuery)
            ->with(['client', 'saleItems.product']);

        if ($status) {
            $query->where('status', $status);
        }

        $dateRange = AdminDateRange::resolvePresetFromRequest(
            $filters['date_range'] ?? null,
            $filters['date_from'] ?? null,
            $filters['date_to'] ?? null,
        );

        if ($dateRange !== null) {
            if ($dateRange === AdminDateRange::PRESET_CUSTOM) {
                if (($filters['date_from'] ?? '') !== '' || ($filters['date_to'] ?? '') !== '') {
                    AdminDateRange::applyDateTimeBetween(
                        $query,
                        'sale_date',
                        AdminDateRange::PRESET_CUSTOM,
                        $filters['date_from'] ?? null,
                        $filters['date_to'] ?? null,
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

        $perPage = AdminPerPage::resolve($filters['per_page'] ?? 10);
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

        $orderLabels = [
            'pending' => 'Pendiente',
            'ready_to_pickup' => 'Listo para recoger',
            'completed' => 'Confirmado',
            'cancelled' => 'Rechazado',
            'refunded' => 'Reembolsado',
        ];

        $rows = collect($orders->items())->map(function (Sale $sale) use ($orderLabels): array {
            if ($sale->client_id && $sale->client) {
                $customer = trim($sale->client->name.' '.$sale->client->first_surname.' '.($sale->client->second_surname ?: ''));
                $customerEmail = $sale->client->gmail;
            } elseif ($sale->buyer_name) {
                $customer = $sale->buyer_name;
                $customerEmail = $sale->buyer_email;
            } else {
                $customer = 'Sin datos de cliente';
                $customerEmail = null;
            }

            return [
                'sale_id' => (int) $sale->sale_id,
                'invoice_number' => $sale->invoice_number,
                'reference' => $sale->invoice_number ?? '#'.$sale->sale_id,
                'customer' => $customer,
                'customer_email' => $customerEmail,
                'order_placed_label' => $sale->adminOrderPlacedAtLabel(),
                'ready_label' => $sale->adminReadyAtLabel(),
                'confirmed_label' => $sale->adminConfirmedAtLabel(),
                'status' => $sale->status,
                'status_label' => $orderLabels[$sale->status] ?? ucfirst($sale->status),
                'total' => (float) $sale->total,
            ];
        })->values()->all();

        return Inertia::render('Admin/Orders/Index', [
            'orders' => $rows,
            'pagination' => ListPaginationPayload::from($orders),
            'pendingWebOrdersCount' => (int) $pendingWebOrdersCount,
            'latestPurchaseSaleId' => (int) $latestPurchaseSaleId,
            'readyToPickupExpirationHours' => (int) $readyToPickupExpirationHours,
            'usesEnvDefaultForExpiry' => (bool) $usesEnvDefaultForExpiry,
            'filters' => [
                'status' => (string) ($status ?? ''),
                'date_range' => (string) ($filters['date_range'] ?? ''),
                'date_from' => (string) ($filters['date_from'] ?? ''),
                'date_to' => (string) ($filters['date_to'] ?? ''),
                'search' => (string) ($search ?? ''),
            ],
        ]);
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
