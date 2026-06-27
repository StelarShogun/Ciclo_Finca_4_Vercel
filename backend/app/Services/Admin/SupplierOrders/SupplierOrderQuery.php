<?php

namespace App\Services\Admin\SupplierOrders;

use App\Models\AdminUser;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStateTimeline;
use App\Models\Product;
use App\Models\Supplier;
use App\Services\Client\Inertia\ListPaginationPayload;
use App\Support\AdminDateRange;
use App\Support\AdminPerPage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class SupplierOrderQuery
{
    private const FINAL_STATES = ['delivered', 'cancelled'];

    public function searchProductsPayload(Request $request): array
    {
        $q = trim((string) $request->query('q', ''));
        $supplierId = (int) $request->query('supplier_id', 0);
        $forSale = $request->query('context') === 'sale';
        $displaySkuExpression = DB::connection()->getDriverName() === 'sqlite'
            ? "printf('BK-%03d', product_id)"
            : "CONCAT('BK-', LPAD(product_id, 3, '0'))";

        $products = Product::query()
            ->select(['product_id', 'name', 'purchase_price', 'sale_price', 'sku', 'stock_current'])
            ->when($supplierId > 0, fn ($query) => $query->where('supplier_id', $supplierId))
            ->when($q !== '' && mb_strlen($q) >= 2, function ($query) use ($displaySkuExpression, $q) {
                $query->where(function ($inner) use ($displaySkuExpression, $q) {
                    $inner->where('name', 'like', '%'.$q.'%')
                        ->orWhere('sku', 'like', '%'.$q.'%')
                        ->orWhereRaw($displaySkuExpression.' LIKE ?', ['%'.$q.'%']);
                });
            })
            ->orderBy('name')
            ->limit(20)
            ->get()
            ->map(function (Product $product) use ($forSale) {
                $unitPrice = $forSale
                    ? (float) $product->sale_price
                    : (float) ($product->purchase_price ?: $product->sale_price);

                if ($unitPrice <= 0) {
                    $unitPrice = (float) $product->sale_price;
                }

                $row = [
                    'product_id' => (int) $product->product_id,
                    'name' => (string) $product->name,
                    'sku' => $product->displaySku(),
                    'unit_price' => round($unitPrice, 2),
                ];

                if ($forSale) {
                    $row['stock'] = (int) $product->stock_current;
                }

                return $row;
            })
            ->values();

        return ['success' => true, 'products' => $products];
    }

    public function indexPayload(Request $request): array
    {
        $query = Order::with(['supplier', 'orderItems'])
            ->orderByDesc('orders.date');

        $this->applyFilters($query, $request);

        $orders = $query
            ->paginate(AdminPerPage::resolve($request->input('per_page', 10)))
            ->withQueryString();

        $suppliers = Supplier::query()
            ->orderBy('name')
            ->get(['supplier_id', 'name', 'primary_contact', 'email', 'phone']);

        return [
            'orders' => collect($orders->items())->map(fn (Order $order) => $this->indexRow($order))->values()->all(),
            'pagination' => ListPaginationPayload::from($orders),
            'openSupplierOrdersCount' => (int) Order::query()->whereNotIn('state', self::FINAL_STATES)->count(),
            'suppliers' => $suppliers->map(fn (Supplier $supplier): array => [
                'supplier_id' => (int) $supplier->supplier_id,
                'name' => $supplier->name,
                'primary_contact' => $supplier->primary_contact,
                'email' => $supplier->email,
                'phone' => $supplier->phone,
            ])->values()->all(),
            'filters' => [
                'state' => (string) ($request->get('state') ?? ''),
                'date_range' => (string) $request->input('date_range', ''),
                'date_from' => (string) ($request->input('date_from') ?? ''),
                'date_to' => (string) ($request->input('date_to') ?? ''),
                'search' => (string) ($request->get('search') ?? ''),
            ],
        ];
    }

    public function detailPayload(Order $order): array
    {
        $order->loadMissing(['supplier', 'orderItems', 'stateTimeline.admin']);

        $hasReceivedData = $order->orderItems->contains(fn ($it) => $it->received_quantity !== null);
        $initialTotal = (float) ($order->orderItems->reduce(fn ($c, $it) => $c + (float) ($it->total ?? 0), 0.0) ?: (float) ($order->total ?? 0));
        $receivedTotal = $hasReceivedData
            ? (float) $order->orderItems->reduce(fn ($c, $it) => $c + round(((float) ($it->unit_price ?? 0)) * ((int) ($it->received_quantity ?? 0)), 2), 0.0)
            : null;
        $hasShorts = $hasReceivedData && $order->orderItems->contains(fn ($it) => (int) ($it->received_quantity ?? 0) < (int) $it->quantity);
        $shortsTotal = ($hasReceivedData && $receivedTotal !== null) ? max($initialTotal - $receivedTotal, 0.0) : 0.0;
        $confirmEntry = $order->stateTimeline->firstWhere('state', 'confirmed');

        return [
            'order' => [
                'num_order' => (int) $order->num_order,
                'po_number' => $order->po_number ?: ('#'.$order->num_order),
                'supplier_name' => $order->supplier?->name ?? '—',
                'date_label' => $order->date?->format('d/m/Y H:i') ?? '—',
                'estimated_delivery_date' => $order->estimated_delivery_date?->format('d/m/Y'),
                'delivered_at' => $order->delivered_at?->format('d/m/Y H:i'),
                'received_at' => $order->received_at?->format('d/m/Y H:i'),
                'state' => $order->state,
                'state_label' => Order::STATE_LABELS[$order->state] ?? ucfirst((string) $order->state),
                'closed_with_shorts' => (bool) $order->closed_with_shorts,
                'total' => (float) $order->total,
                'has_received_data' => (bool) $hasReceivedData,
                'has_shorts' => (bool) $hasShorts,
                'initial_total' => (float) $initialTotal,
                'received_total' => $receivedTotal !== null ? (float) $receivedTotal : null,
                'shorts_total' => (float) $shortsTotal,
                'items' => $order->orderItems->map(fn (OrderItem $item): array => $this->itemPayload($item))->values()->all(),
                'timeline' => $order->stateTimeline->map(fn (OrderStateTimeline $entry): array => $this->timelinePayload($entry))->values()->all(),
                'confirm_audit' => $confirmEntry ? [
                    'changed_at' => $confirmEntry->changed_at?->format('d/m/Y H:i') ?? '—',
                    'user_name' => $this->adminLabel($confirmEntry->admin),
                ] : null,
            ],
        ];
    }

    public function showPayload(Order $order): array
    {
        $order->loadMissing(['supplier', 'orderItems', 'stateTimeline.admin']);

        return [
            'success' => true,
            'order' => [
                'num_order' => $order->num_order,
                'po_number' => $order->po_number,
                'supplier' => $order->supplier ? [
                    'supplier_id' => $order->supplier->supplier_id,
                    'name' => $order->supplier->name,
                    'primary_contact' => $order->supplier->primary_contact,
                    'email' => $order->supplier->email,
                    'phone' => $order->supplier->phone,
                ] : null,
                'products' => $order->orderItems->map(fn (OrderItem $item) => $this->itemPayload($item))->values(),
                'date' => $order->date?->format('d/m/Y H:i'),
                'estimated_delivery_date' => $order->estimated_delivery_date?->format('d/m/Y'),
                'received_at' => $order->received_at?->format('d/m/Y H:i'),
                'closed_with_shorts' => (bool) $order->closed_with_shorts,
                'state' => $order->state,
                'total' => (float) $order->total,
                'timeline' => $order->stateTimeline->map(fn (OrderStateTimeline $timeline) => $this->timelinePayload($timeline))->values()->all(),
            ],
        ];
    }

    public function supplierPayload(Supplier $supplier): array
    {
        return [
            'success' => true,
            'supplier' => [
                'supplier_id' => $supplier->supplier_id,
                'name' => $supplier->name,
                'primary_contact' => $supplier->primary_contact,
                'phone' => $supplier->phone,
                'email' => $supplier->email,
                'address' => $supplier->address,
                'delivery_time' => $supplier->delivery_time,
                'rating' => $supplier->rating,
                'status' => $supplier->status,
                'products_count' => $supplier->products_count,
            ],
        ];
    }

    private function applyFilters($query, Request $request): void
    {
        $state = $request->get('state');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');
        $dateRange = AdminDateRange::resolvePresetFromRequest($request->input('date_range'), $dateFrom, $dateTo);

        if ($dateRange !== null && $dateRange !== AdminDateRange::PRESET_CUSTOM) {
            [$start, $end] = AdminDateRange::bounds($dateRange);
            $dateFrom = $start->toDateString();
            $dateTo = $end->toDateString();
        }

        if ($dateFrom && $dateTo && $dateTo < $dateFrom) {
            [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
        }

        if ($state) {
            $state === 'open'
                ? $query->whereNotIn('state', self::FINAL_STATES)
                : $query->where('state', $state);
        }

        if ($dateFrom) {
            $query->where('orders.date', '>=', AdminDateRange::parseDateStart($dateFrom)->utc());
        }

        if ($dateTo) {
            $query->where('orders.date', '<=', AdminDateRange::parseDateEnd($dateTo)->utc());
        }

        $search = trim((string) $request->get('search', ''));
        if ($search === '') {
            return;
        }

        $driver = DB::connection()->getDriverName();
        $booleanQuery = $driver === 'mysql' ? $this->fullTextBooleanQuery($search) : '';

        $query
            ->leftJoin('suppliers as s', 's.supplier_id', '=', 'orders.supplier_id')
            ->leftJoin('order_items as oi', 'oi.order_num_order', '=', 'orders.num_order')
            ->select('orders.*')
            ->distinct()
            ->where(function ($q) use ($search, $driver, $booleanQuery) {
                $q->where('orders.num_order', 'like', "%{$search}%")
                    ->orWhere('orders.po_number', 'like', "%{$search}%")
                    ->orWhere('s.name', 'like', "%{$search}%");

                if ($driver === 'mysql' && $booleanQuery !== '') {
                    $q->orWhereRaw('MATCH(s.`name`) AGAINST (? IN BOOLEAN MODE)', [$booleanQuery]);
                    $q->orWhereRaw('MATCH(oi.`name`) AGAINST (? IN BOOLEAN MODE)', [$booleanQuery]);
                } else {
                    $q->orWhere('oi.name', 'like', "%{$search}%");
                }
            });
    }

    private function indexRow(Order $order): array
    {
        $poFull = $order->po_number ?? ('#'.$order->num_order);
        $poShort = is_string($order->po_number) && preg_match('/^PO-(\d{4})-(\d{4})$/', $order->po_number, $m)
            ? 'PO-'.$m[2]
            : $poFull;
        $edd = $order->estimated_delivery_date;
        $hasReceivedData = $order->orderItems->contains(fn ($it) => $it->received_quantity !== null);
        $initialTotal = (float) ($order->total ?? 0);
        $receivedTotal = 0.0;
        $shortsTotal = 0.0;
        $hasShorts = false;

        if ($hasReceivedData) {
            $initialTotal = (float) ($order->orderItems->sum(fn ($it) => (float) ($it->total ?? 0)) ?: $initialTotal);
            $receivedTotal = (float) $order->orderItems->sum(fn ($it) => round(((float) ($it->unit_price ?? 0)) * ((int) ($it->received_quantity ?? 0)), 2));
            $hasShorts = $order->orderItems->contains(fn ($it) => (int) ($it->received_quantity ?? 0) < (int) ($it->quantity ?? 0));
            $shortsTotal = max($initialTotal - $receivedTotal, 0.0);
        }

        return [
            'num_order' => (int) $order->num_order,
            'po_short' => $poShort,
            'po_full' => $poFull,
            'supplier_id' => $order->supplier?->supplier_id ? (int) $order->supplier->supplier_id : null,
            'supplier_name' => $order->supplier?->name,
            'date_label' => $order->date?->format('d/m/Y H:i') ?? '—',
            'edd_label' => $edd?->format('d/m/Y') ?? '—',
            'edd_class' => $this->eddClass($order, $edd),
            'delivered_label' => ($order->delivered_at ?? $order->received_at)?->format('d/m/Y H:i'),
            'state' => $order->state,
            'state_label' => Order::STATE_LABELS[$order->state] ?? ucfirst($order->state),
            'initial_total' => (float) $initialTotal,
            'received_total' => (float) $receivedTotal,
            'shorts_total' => (float) $shortsTotal,
            'has_received_data' => (bool) $hasReceivedData,
            'has_shorts' => (bool) $hasShorts,
        ];
    }

    private function itemPayload(OrderItem $item): array
    {
        return [
            'id' => (int) $item->id,
            'name' => $item->name,
            'quantity' => (int) $item->quantity,
            'received_quantity' => $item->received_quantity !== null ? (int) $item->received_quantity : null,
            'unit_price' => (float) $item->unit_price,
            'total' => (float) $item->total,
        ];
    }

    private function timelinePayload(OrderStateTimeline $entry): array
    {
        return [
            'state' => $entry->state,
            'state_label' => Order::STATE_LABELS[$entry->state] ?? ucfirst($entry->state),
            'changed_at' => $entry->changed_at->format('d/m/Y H:i'),
            'user_name' => $this->adminLabel($entry->admin),
            'reason' => $entry->reason,
        ];
    }

    private function adminLabel(?AdminUser $admin): string
    {
        if (! $admin instanceof AdminUser) {
            return 'Sistema';
        }

        return trim($admin->name.' '.($admin->first_surname ?? '')) ?: ($admin->gmail ?: '—');
    }

    private function eddClass(Order $order, mixed $edd): string
    {
        if (! $edd || in_array($order->state, self::FINAL_STATES, true)) {
            return '';
        }

        return $edd->isPast() ? 'edd-pill edd-late' : ($edd->isToday() ? 'edd-pill edd-today' : '');
    }

    private function fullTextBooleanQuery(string $raw): string
    {
        return collect(preg_split('/\s+/', trim($raw)) ?: [])
            ->map(fn ($term) => trim((string) preg_replace('/[^\pL\pN_]+/u', ' ', $term)))
            ->filter()
            ->map(fn ($term) => '+'.$term.'*')
            ->implode(' ');
    }
}
