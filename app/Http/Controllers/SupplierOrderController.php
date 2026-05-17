<?php

namespace App\Http\Controllers;

use App\Models\AdminUser;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStateTimeline;
use App\Models\Product;
use App\Models\Supplier;
use App\Services\AuditLogger;
use App\Services\InventoryMovementService;
use App\Support\AdminPerPage;
use App\Services\SupplierDeliveryEstimator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class SupplierOrderController extends Controller
{
    private const FINAL_STATES = ['delivered', 'cancelled'];

    private function fullTextBooleanQuery(string $raw): string
    {
        $trimmed = trim($raw);

        if ($trimmed === '') {
            return '';
        }

        $terms = preg_split('/\s+/', $trimmed) ?: [];
        $terms = array_values(array_filter(array_map('trim', $terms)));

        return collect($terms)
            ->map(function ($term) {
                $term = preg_replace('/[^\pL\pN_]+/u', ' ', $term);
                $term = trim((string) $term);

                if ($term === '') {
                    return null;
                }

                return '+'.$term.'*';
            })
            ->filter()
            ->implode(' ');
    }

    public function searchProducts(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $supplierId = (int) $request->query('supplier_id', 0);

        $products = Product::query()
            ->select(['product_id', 'name', 'purchase_price', 'sale_price', 'sku'])
            ->when($supplierId > 0, fn ($query) => $query->where('supplier_id', $supplierId))
            ->when($q !== '' && mb_strlen($q) >= 2, function ($query) use ($q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('name', 'like', '%'.$q.'%')
                        ->orWhere('sku', 'like', '%'.$q.'%')
                        ->orWhereRaw("CONCAT('BK-', LPAD(product_id, 3, '0')) LIKE ?", ['%'.$q.'%']);
                });
            })
            ->orderBy('name')
            ->limit(20)
            ->get()
            ->map(function (Product $product) {
                $unitPrice = (float) ($product->purchase_price ?: $product->sale_price);

                if ($unitPrice <= 0) {
                    $unitPrice = (float) $product->sale_price;
                }

                return [
                    'product_id' => (int) $product->product_id,
                    'name' => (string) $product->name,
                    'sku' => $product->displaySku(),
                    'unit_price' => round($unitPrice, 2),
                ];
            })
            ->values();

        return response()->json([
            'success' => true,
            'products' => $products,
        ]);
    }

    public function index(Request $request)
    {
        $state = $request->get('state');
        $search = $request->get('search');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        if ($dateFrom && $dateTo && $dateTo < $dateFrom) {
            [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
        }

        $query = Order::with(['supplier', 'orderItems'])
            ->orderBy('orders.date', 'desc');

        if ($state) {
            if ($state === 'open') {
                $query->whereNotIn('state', self::FINAL_STATES);
            } else {
                $query->where('state', $state);
            }
        }

        if ($dateFrom) {
            $query->whereDate('orders.date', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->whereDate('orders.date', '<=', $dateTo);
        }

        if ($search) {
            $search = trim($search);
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

        $perPage = AdminPerPage::resolve($request->input('per_page', 10));
        $orders = $query->paginate($perPage)->withQueryString();
        $openSupplierOrdersCount = Order::query()
            ->whereNotIn('state', self::FINAL_STATES)
            ->count();

        return view('admin.orders.index_supplier', compact('orders', 'openSupplierOrdersCount'));
    }

    public function create()
    {
        $suppliers = Supplier::query()
            ->orderBy('name')
            ->get(['supplier_id', 'name', 'primary_contact', 'email', 'phone']);

        return view('admin.orders.create_supplier', compact('suppliers'));
    }

    public function detail($id)
    {
        $order = Order::with(['supplier', 'orderItems', 'stateTimeline.admin'])
            ->findOrFail($id);

        return view('admin.orders.detail_supplier', compact('order'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'supplier_id' => ['required', 'integer', 'exists:suppliers,supplier_id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,product_id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ], [
            'supplier_id.required' => 'El proveedor es obligatorio.',
            'supplier_id.exists' => 'El proveedor seleccionado no existe.',
            'items.required' => 'Debes agregar al menos un producto.',
            'items.min' => 'Debes agregar al menos un producto.',
            'items.*.product_id.required' => 'Selecciona un producto válido.',
            'items.*.product_id.exists' => 'El producto seleccionado no existe.',
            'items.*.quantity.required' => 'La cantidad es obligatoria.',
            'items.*.quantity.min' => 'La cantidad debe ser mayor a 0.',
        ]);

        $items = $validated['items'];

        return DB::transaction(function () use ($validated, $items) {
            $products = Product::query()
                ->where('supplier_id', (int) $validated['supplier_id'])
                ->whereIn('product_id', collect($items)->pluck('product_id')->all())
                ->get()
                ->keyBy('product_id');

            $lines = [];
            $total = 0.0;

            foreach ($items as $row) {
                $productId = (int) $row['product_id'];
                $quantity = (int) $row['quantity'];
                $product = $products->get($productId);

                if (! $product) {
                    throw ValidationException::withMessages([
                        'items' => ['Uno de los productos seleccionados no existe o no pertenece al proveedor.'],
                    ]);
                }

                if ($quantity < 1) {
                    throw ValidationException::withMessages([
                        'items' => ['No es posible agregar un producto con cantidad 0 o negativa.'],
                    ]);
                }

                $unitPrice = (float) ($product->purchase_price ?: $product->sale_price);

                if ($unitPrice <= 0) {
                    $unitPrice = (float) $product->sale_price;
                }

                $lineTotal = round($unitPrice * $quantity, 2);
                $total += $lineTotal;

                $lines[] = [
                    'product_id' => $productId,
                    'name' => $product->name,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'total' => $lineTotal,
                ];
            }

            $order = Order::create([
                'po_number' => $this->generatePoNumber(),
                'supplier_id' => (int) $validated['supplier_id'],
                'estimated_delivery_date' => null,
                'date' => now(),
                'state' => 'draft',
                'total' => round($total, 2),
            ]);

            foreach ($lines as $line) {
                OrderItem::create(array_merge($line, [
                    'order_num_order' => $order->num_order,
                ]));
            }

            OrderStateTimeline::create([
                'num_order' => (int) $order->num_order,
                'user_id' => (int) auth('admin')->id(),
                'state' => 'draft',
                'changed_at' => now(),
            ]);

            $this->logAuditAction(
                'supplier_order_create',
                'Pedido a proveedor creado en estado draft.',
                [
                    'order_id' => (int) $order->num_order,
                    'po_number' => (string) ($order->po_number ?? ''),
                    'supplier_id' => (int) $order->supplier_id,
                    'items_count' => count($lines),
                    'total' => (float) $order->total,
                ]
            );

            return redirect()
                ->route('admin.supplier-orders.detail', $order->num_order)
                ->with('status', 'Pedido creado correctamente.');
        });
    }

    public function show($id)
    {
        $order = Order::with(['supplier', 'orderItems', 'stateTimeline.admin'])
            ->findOrFail($id);

        $productsPayload = $order->orderItems
            ->map(fn (OrderItem $item) => [
                'id' => $item->id,
                'name' => $item->name,
                'quantity' => (int) $item->quantity,
                'received_quantity' => $item->received_quantity !== null ? (int) $item->received_quantity : null,
                'unit_price' => (float) $item->unit_price,
                'total' => (float) $item->total,
            ])
            ->values();

        return response()->json([
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
                'products' => $productsPayload,
                'date' => $order->date?->format('d/m/Y H:i'),
                'estimated_delivery_date' => $order->estimated_delivery_date?->format('d/m/Y'),
                'received_at' => $order->received_at?->format('d/m/Y H:i'),
                'closed_with_shorts' => (bool) $order->closed_with_shorts,
                'state' => $order->state,
                'total' => (float) $order->total,
                'timeline' => $order->stateTimeline
                    ->map(fn (OrderStateTimeline $timeline) => [
                        'state' => $timeline->state,
                        'changed_at' => $timeline->changed_at->format('d/m/Y H:i'),
                        'user_name' => $timeline->admin instanceof AdminUser
                            ? trim($timeline->admin->name.' '.($timeline->admin->first_surname ?? ''))
                            : 'Sistema',
                        'reason' => $timeline->reason,
                    ])
                    ->values()
                    ->all(),
            ],
        ]);
    }

    public function updateState(
        Request $request,
        int $id,
        InventoryMovementService $inventoryService,
        SupplierDeliveryEstimator $deliveryEstimator,
    ) {
        $order = Order::findOrFail($id);
        $previousState = (string) $order->state;

        $validated = $request->validate([
            'state' => ['required', 'string', 'in:draft,pending,confirmed,delivered,cancelled,close_partial'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $requestedState = $validated['state'];

        // Route partial closure through the dedicated flow to keep shortages auditable.
        if ($requestedState === 'close_partial' || ($requestedState === 'delivered' && $order->state === 'partial_received')) {
            return $this->closePartial($request, $id);
        }

        if (! $order->canTransitionTo($requestedState)) {
            return response()->json([
                'success' => false,
                'message' => 'Transición de estado no permitida.',
            ], 422);
        }

        if ($requestedState === 'delivered' && $order->state === 'confirmed') {
            // CA-05: Only register inventory movements through this path when the order
            // was NEVER processed by receiveOrder (received_quantity is null on all items).
            // If receiveOrder already ran (even partially), movements were already recorded
            // there, so we skip them here to avoid duplicates.
            $items = OrderItem::query()
                ->where('order_num_order', (int) $order->num_order)
                ->get();

            $alreadyProcessedViaReceive = $items->contains(
                fn (OrderItem $item) => $item->received_quantity !== null
            );

            if (! $alreadyProcessedViaReceive) {
                foreach ($items as $item) {
                    $productId = (int) $item->product_id;
                    $quantity = (int) $item->quantity;

                    if ($productId < 1 || $quantity < 1) {
                        continue;
                    }

                    $product = Product::find($productId);

                    if (! $product) {
                        Log::warning('Supplier order delivery skipped missing product.', [
                            'order_id' => $order->num_order,
                            'product_id' => $productId,
                            'quantity' => $quantity,
                        ]);

                        continue;
                    }

                    $inventoryService->recordSupplierEntry(
                        product: $product,
                        quantity: $quantity,
                        orderId: $order->num_order,
                    );

                    $item->update(['received_quantity' => $quantity]);
                }
            } else {
                // receiveOrder already handled movements; just mark remaining items as fully received.
                foreach ($items as $item) {
                    $quantity = (int) $item->quantity;
                    $received = (int) ($item->received_quantity ?? 0);

                    if ($received < $quantity) {
                        $item->update(['received_quantity' => $quantity]);
                    }
                }
            }
        }

        $confirmationDate = now();
        $estimatedDeliveryDate = $requestedState === 'confirmed'
            ? $deliveryEstimator->estimateFor($order, $confirmationDate)
            : $order->estimated_delivery_date;

        $updates = [
            'state' => $requestedState,
            'date' => $requestedState === 'confirmed' ? $confirmationDate : $order->date,
            'estimated_delivery_date' => $estimatedDeliveryDate,
            'delivered_at' => $requestedState === 'delivered' ? now() : $order->delivered_at,
        ];

        if ($requestedState === 'delivered') {
            $updates['received_at'] = $order->received_at ?? now();
            $updates['closed_with_shorts'] = false;
        }

        $order->update($updates);

        OrderStateTimeline::create([
            'num_order' => (int) $order->num_order,
            'user_id' => (int) auth('admin')->id(),
            'state' => $requestedState,
            'reason' => $requestedState === 'cancelled' ? ($request->input('reason') ?? null) : null,
            'changed_at' => now(),
        ]);

        $order->refresh();

        $this->logAuditAction(
            'supplier_order_state_update',
            'Estado de pedido a proveedor actualizado.',
            [
                'order_id' => (int) $order->num_order,
                'po_number' => (string) ($order->po_number ?? ''),
                'from_state' => $previousState,
                'to_state' => (string) $order->state,
                'reason' => (string) ($request->input('reason') ?? ''),
                'estimated_delivery_date' => $order->estimated_delivery_date?->toDateString(),
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Estado actualizado correctamente.',
            'order' => [
                'state' => $order->state,
                'estimated_delivery_date' => $order->estimated_delivery_date?->format('d/m/Y'),
            ],
        ]);
    }

    public function closePartial(Request $request, int $id)
    {
        $order = Order::with('orderItems')->findOrFail($id);
        $previousState = (string) $order->state;

        if ($order->state !== 'partial_received') {
            return response()->json([
                'success' => false,
                'message' => 'Solo se puede cerrar con faltantes un pedido en estado Recepción parcial.',
            ], 422);
        }

        $request->validate([
            'reason' => ['required', 'string', 'min:4', 'max:500'],
        ], [
            'reason.required' => 'Debes indicar un motivo para cerrar el pedido con faltantes.',
            'reason.min' => 'El motivo debe tener al menos 4 caracteres.',
            'reason.max' => 'El motivo no puede superar los 500 caracteres.',
        ]);

        $shortages = $order->orderItems
            ->filter(fn (OrderItem $item) => (int) ($item->received_quantity ?? 0) < (int) $item->quantity)
            ->map(fn (OrderItem $item) => [
                'order_item_id' => (int) $item->id,
                'product_id' => $item->product_id ? (int) $item->product_id : null,
                'name' => (string) $item->name,
                'ordered_quantity' => (int) $item->quantity,
                'received_quantity' => (int) ($item->received_quantity ?? 0),
                'missing_quantity' => (int) $item->quantity - (int) ($item->received_quantity ?? 0),
            ])
            ->values();

        if ($shortages->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Todos los productos están completamente recibidos. El pedido debería marcarse como Entregado de forma normal.',
            ], 422);
        }

        return DB::transaction(function () use ($order, $request, $previousState, $shortages) {
            $reason = trim((string) $request->input('reason'));

            $order->update([
                'state' => 'delivered',
                'delivered_at' => now(),
                'closed_with_shorts' => true,
            ]);

            OrderStateTimeline::create([
                'num_order' => (int) $order->num_order,
                'user_id' => (int) auth('admin')->id(),
                'state' => 'delivered',
                'reason' => '[Cierre con faltantes] '.$reason,
                'changed_at' => now(),
            ]);

            $order->refresh();

            $this->logAuditAction(
                'supplier_order_close_partial',
                'Pedido a proveedor cerrado manualmente con faltantes.',
                [
                    'order_id' => (int) $order->num_order,
                    'po_number' => (string) ($order->po_number ?? ''),
                    'from_state' => $previousState,
                    'to_state' => (string) $order->state,
                    'reason' => $reason,
                    'shortages_count' => $shortages->count(),
                    'shortages' => $shortages->all(),
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'Pedido cerrado con faltantes. Se registró que el proveedor no entregó la totalidad de los productos.',
                'order' => [
                    'state' => 'delivered',
                    'closed_with_shorts' => true,
                    'delivered_at' => $order->delivered_at?->format('d/m/Y H:i'),
                ],
            ]);
        });
    }

    public function receiveOrder(Request $request, $id)
    {
        $order = Order::with('orderItems')->findOrFail($id);
        $previousState = (string) $order->state;

        if (! in_array($order->state, ['confirmed', 'partial_received'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Solo se puede registrar la recepción cuando el pedido está Confirmado o en Recepción parcial.',
            ], 422);
        }

        $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.order_item_id' => ['required', 'integer'],
            'items.*.received_quantity' => ['required', 'integer', 'min:0'],
        ], [
            'items.required' => 'Debes enviar las líneas del pedido.',
            'items.*.order_item_id.required' => 'Cada línea debe tener un identificador.',
            'items.*.received_quantity.required' => 'La cantidad recibida es obligatoria.',
            'items.*.received_quantity.min' => 'La cantidad recibida no puede ser negativa.',
        ]);

        $itemsById = $order->orderItems->keyBy('id');
        $receivedPayload = [];

        foreach ($request->items as $row) {
            $itemId = (int) $row['order_item_id'];
            $receivedQuantity = (int) $row['received_quantity'];
            $item = $itemsById->get($itemId);

            if (! $item) {
                return response()->json([
                    'success' => false,
                    'message' => "La línea #{$itemId} no pertenece a este pedido.",
                ], 422);
            }

            if ($receivedQuantity > (int) $item->quantity) {
                return response()->json([
                    'success' => false,
                    'message' => "La cantidad recibida de \"{$item->name}\" ({$receivedQuantity}) supera la cantidad pedida ({$item->quantity}).",
                ], 422);
            }

            $previousReceived = (int) ($item->received_quantity ?? 0);

            if ($receivedQuantity < $previousReceived) {
                return response()->json([
                    'success' => false,
                    'message' => "La cantidad recibida de \"{$item->name}\" no puede ser menor a la cantidad ya registrada ({$previousReceived}).",
                ], 422);
            }

            $receivedPayload[$itemId] = $receivedQuantity;
        }

        $missingItems = $order->orderItems->pluck('id')->diff(array_keys($receivedPayload));

        if ($missingItems->isNotEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Debes indicar la cantidad recibida para todos los productos del pedido.',
            ], 422);
        }

        return DB::transaction(function () use ($order, $receivedPayload, $previousState) {
            $inventoryService = app(InventoryMovementService::class);
            $totalDelta = 0;

            foreach ($order->orderItems as $item) {
                $receivedQuantity = (int) ($receivedPayload[$item->id] ?? 0);
                $previousQuantity = (int) ($item->received_quantity ?? 0);
                $delta = $receivedQuantity - $previousQuantity;

                $item->update([
                    'received_quantity' => $receivedQuantity,
                ]);

                if ($delta <= 0 || ! $item->product_id) {
                    continue;
                }

                $product = Product::find((int) $item->product_id);

                if (! $product) {
                    Log::warning('Supplier order reception skipped missing product.', [
                        'order_id' => $order->num_order,
                        'item_id' => $item->id,
                        'product_id' => $item->product_id,
                        'delta' => $delta,
                    ]);

                    continue;
                }

                $inventoryService->recordSupplierEntry(
                    product: $product,
                    quantity: $delta,
                    orderId: $order->num_order,
                );

                $totalDelta += $delta;
            }

            $order->refresh();
            $order->load('orderItems');

            $isPartial = $order->orderItems->contains(
                fn (OrderItem $item) => (int) ($item->received_quantity ?? 0) < (int) $item->quantity
            );

            $newState = $isPartial ? 'partial_received' : 'delivered';

            $order->update([
                'state' => $newState,
                'received_at' => now(),
                'delivered_at' => $isPartial ? null : now(),
                'closed_with_shorts' => $isPartial ? (bool) $order->closed_with_shorts : false,
            ]);

            OrderStateTimeline::create([
                'num_order' => (int) $order->num_order,
                'user_id' => (int) auth('admin')->id(),
                'state' => $newState,
                'reason' => null,
                'changed_at' => now(),
            ]);

            $this->logAuditAction(
                'supplier_order_receive',
                $isPartial
                    ? 'Recepción parcial de pedido a proveedor registrada.'
                    : 'Recepción total de pedido a proveedor registrada.',
                [
                    'order_id' => (int) $order->num_order,
                    'po_number' => (string) ($order->po_number ?? ''),
                    'from_state' => $previousState,
                    'to_state' => $newState,
                    'total_delta' => $totalDelta,
                    'is_partial' => $isPartial,
                ]
            );

            $message = $isPartial
                ? 'Recepción parcial registrada. Uno o más productos tienen cantidad menor a la pedida.'
                : 'Recepción total registrada. El pedido ahora está Entregado.';

            return response()->json([
                'success' => true,
                'state' => $newState,
                'message' => $message,
                'received_at' => $order->fresh()->received_at?->format('d/m/Y H:i'),
            ]);
        });
    }

    public function supplierDetails($id)
    {
        $supplier = Supplier::withCount(['products' => fn ($query) => $query->where('status', 'active')])
            ->findOrFail($id);

        return response()->json([
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
        ]);
    }

    private function generatePoNumber(): string
    {
        $year = (string) now()->format('Y');

        $lastPoNumber = Order::query()
            ->whereNotNull('po_number')
            ->where('po_number', 'like', 'PO-'.$year.'-%')
            ->lockForUpdate()
            ->orderByDesc('num_order')
            ->value('po_number');

        $nextSequence = 1;

        if (is_string($lastPoNumber) && preg_match('/^PO-'.preg_quote($year, '/').'-([0-9]{4})$/', $lastPoNumber, $matches)) {
            $nextSequence = ((int) $matches[1]) + 1;
        }

        return 'PO-'.$year.'-'.str_pad((string) $nextSequence, 4, '0', STR_PAD_LEFT);
    }

    private function logAuditAction(string $actionType, string $description, array $meta = []): void
    {
        try {
            app(AuditLogger::class)->logAdminAction($actionType, 'supplier_orders', $description, $meta);
        } catch (\Throwable $exception) {
            Log::warning('Supplier order audit log write failed.', [
                'action_type' => $actionType,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
