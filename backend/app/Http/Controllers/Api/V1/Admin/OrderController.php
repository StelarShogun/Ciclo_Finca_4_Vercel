<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Orders\AdminOrderIndexRequest;
use App\Models\Sale;
use App\Services\Client\Inertia\ListPaginationPayload;
use App\Support\AdminDateRange;
use App\Support\AdminPerPage;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

/**
 * Encargos (pedidos del carrito web) para el SPA Next. Subconjunto de Sales con
 * order_source web_cart; el ciclo de vida (listo/confirmar/rechazar) reusa los
 * endpoints de /sales. Replica el OrderController Inertia.
 */
final class OrderController extends Controller
{
    private const LABELS = [
        'pending' => 'Pendiente',
        'ready_to_pickup' => 'Listo para recoger',
        'completed' => 'Confirmado',
        'cancelled' => 'Rechazado',
        'refunded' => 'Reembolsado',
    ];

    public function index(AdminOrderIndexRequest $request): JsonResponse
    {
        Gate::forUser(Auth::guard('admin')->user())->authorize('viewAny', Sale::class);

        $filters = $request->validated();
        $status = $filters['status'] ?? '';
        $search = trim((string) ($filters['search'] ?? ''));

        $query = $this->baseWebOrdersQuery()->with(['client', 'saleItems.product']);

        if ($status) {
            $query->where('status', $status);
        }

        $range = AdminDateRange::resolvePresetFromRequest($filters['date_range'] ?? null, $filters['date_from'] ?? null, $filters['date_to'] ?? null);
        if ($range !== null) {
            AdminDateRange::applyDateTimeBetween($query, 'sale_date', $range, $filters['date_from'] ?? null, $filters['date_to'] ?? null, storedAsUtc: true);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('sale_id', 'like', "%{$search}%")
                    ->orWhere('invoice_number', 'like', "%{$search}%")
                    ->orWhereHas('client', function ($sub) use ($search) {
                        $sub->where('name', 'like', "%{$search}%")
                            ->orWhere('first_surname', 'like', "%{$search}%")
                            ->orWhere('gmail', 'like', "%{$search}%");
                    });
            });
        }

        $orders = $query->orderByDesc('sale_date')->paginate(AdminPerPage::resolve($filters['per_page'] ?? 10))->withQueryString();

        $rows = collect($orders->items())->map(function (Sale $sale): array {
            $customer = $sale->client_id && $sale->client
                ? trim($sale->client->name.' '.$sale->client->first_surname.' '.($sale->client->second_surname ?: ''))
                : ($sale->buyer_name ?: 'Sin datos de cliente');

            return [
                'sale_id' => (int) $sale->sale_id,
                'reference' => $sale->invoice_number ?? '#'.$sale->sale_id,
                'customer' => $customer,
                'customer_email' => $sale->client?->gmail ?? $sale->buyer_email,
                'order_placed_label' => $sale->adminOrderPlacedAtLabel(),
                'status' => $sale->status,
                'status_label' => self::LABELS[$sale->status] ?? ucfirst($sale->status),
                'total' => (float) $sale->total,
            ];
        })->values()->all();

        return response()->json(['data' => [
            'orders' => $rows,
            'pagination' => ListPaginationPayload::from($orders),
            'pendingWebOrdersCount' => (int) (clone $this->baseWebOrdersQuery())->where('status', 'pending')->count(),
            'filters' => [
                'status' => (string) $status,
                'date_range' => (string) ($filters['date_range'] ?? ''),
                'search' => $search,
            ],
        ]]);
    }

    private function baseWebOrdersQuery()
    {
        return Sale::query()
            ->where(fn ($q) => $q->where('order_source', 'web_cart')->orWhereNull('order_source'))
            ->whereIn('status', ['pending', 'ready_to_pickup', 'completed', 'cancelled', 'refunded'])
            ->notExpired();
    }
}
