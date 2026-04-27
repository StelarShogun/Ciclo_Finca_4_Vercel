<?php

namespace App\Http\Controllers;

use App\Models\AdminUser;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStateTimeline;
use App\Models\Product;
use App\Models\Supplier;
use App\Services\InventoryMovementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class SupplierOrderController extends Controller
{
    private function fullTextBooleanQuery(string $raw): string
    {
        $trimmed = trim($raw);
        if ($trimmed === '') {
            return '';
        }

        $terms = preg_split('/\s+/', $trimmed) ?: [];
        $terms = array_values(array_filter(array_map('trim', $terms)));

        // Build a boolean full-text query that requires all terms and allows prefix matches.
        // Example: "grasa ceram" => "+grasa* +ceram*"
        return collect($terms)
            ->map(function ($t) {
                $t = preg_replace('/[^\pL\pN_]+/u', ' ', $t);
                $t = trim((string) $t);
                if ($t === '') {
                    return null;
                }

                return '+' . $t . '*';
            })
            ->filter()
            ->implode(' ');
    }

    public function searchProducts(Request $request)
    {
        $q          = trim((string) $request->query('q', ''));
        $supplierId = (int) $request->query('supplier_id', 0);

        $products = Product::query()
            ->select(['product_id', 'name', 'purchase_price', 'sale_price'])
            ->when($supplierId > 0, fn ($q) => $q->where('supplier_id', $supplierId))
            ->when($q !== '' && mb_strlen($q) >= 2, function ($query) use ($q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('name', 'like', '%' . $q . '%')
                        ->orWhereRaw("CONCAT('BK-', LPAD(product_id, 3, '0')) LIKE ?", ['%' . $q . '%']);
                });
            })
            ->orderBy('name')
            ->limit(20)
            ->get()
            ->map(function (Product $p) {
                // Prefer purchase price and fall back to sale price when needed.
                $unit = (float) ($p->purchase_price ?: $p->sale_price);
                if ($unit <= 0) {
                    $unit = (float) $p->sale_price;
                }

                return [
                    'product_id' => (int) $p->product_id,
                    'name'       => (string) $p->name,
                    'sku'        => Product::skuFromId((int) $p->product_id),
                    'unit_price' => round($unit, 2),
                ];
            })
            ->values();

        return response()->json(['success' => true, 'products' => $products]);
    }

    public function index(Request $request)
    {
        $state    = $request->get('state');
        $search   = $request->get('search');
        $dateFrom = $request->get('date_from');
        $dateTo   = $request->get('date_to');

        // Normalize the range when the end date is earlier than the start date.
        if ($dateFrom && $dateTo && $dateTo < $dateFrom) {
            [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
        }

        $query = Order::with(['supplier', 'orderItems', 'confirmedBy'])->orderBy('orders.date', 'desc');

        if ($state) {
            $query->where('state', $state);
        }

        if ($dateFrom) {
            $query->whereDate('orders.date', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->whereDate('orders.date', '<=', $dateTo);
        }

        if ($search) {
            $search = trim($search);

            // Join related tables to support supplier and line-item search efficiently.
            $driver = DB::connection()->getDriverName();
            $bool   = $driver === 'mysql' ? $this->fullTextBooleanQuery($search) : '';

            $query
                ->leftJoin('suppliers as s', 's.supplier_id', '=', 'orders.supplier_id')
                ->leftJoin('order_items as oi', 'oi.order_num_order', '=', 'orders.num_order')
                ->select('orders.*')
                ->distinct()
                ->where(function ($q) use ($search, $driver, $bool) {
                    $q->where('orders.num_order', 'like', "%{$search}%")
                        ->orWhere('orders.po_number', 'like', "%{$search}%")
                        ->orWhere('s.name', 'like', "%{$search}%");

                    if ($driver === 'mysql' && $bool !== '') {
                        $q->orWhereRaw('MATCH(s.`name`) AGAINST (? IN BOOLEAN MODE)', [$bool]);
                    }

                    if ($driver === 'mysql' && $bool !== '') {
                        $q->orWhereRaw('MATCH(oi.`name`) AGAINST (? IN BOOLEAN MODE)', [$bool]);
                    } else {
                        $q->orWhere('oi.name', 'like', "%{$search}%");
                    }
                });
        }

        $orders = $query->paginate(10)->withQueryString();

        return view('admin.orders.index_supplier', compact('orders'));
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
        $order = Order::with(['supplier', 'orderItems', 'stateTimeline.admin', 'confirmedBy'])->findOrFail($id);

        return view('admin.orders.detail_supplier', compact('order'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'supplier_id'             => ['required', 'integer', 'exists:suppliers,supplier_id'],
            'estimated_delivery_date' => ['required', 'date', 'after:today'],
            'items'                   => ['required', 'array', 'min:1'],
            'items.*.product_id'      => ['required', 'integer', 'exists:products,product_id'],
            'items.*.quantity'        => ['required', 'integer', 'min:1'],
        ], [
            'supplier_id.required'             => 'El proveedor es obligatorio.',
            'supplier_id.exists'               => 'El proveedor seleccionado no existe.',
            'estimated_delivery_date.required' => 'La fecha estimada de entrega es obligatoria.',
            'estimated_delivery_date.date'     => 'La fecha estimada no tiene un formato válido.',
            'estimated_delivery_date.after'    => 'La fecha estimada debe ser posterior al día de hoy.',
            'items.required'                   => 'Debes agregar al menos un producto.',
            'items.min'                        => 'Debes agregar al menos un producto.',
            'items.*.product_id.required'      => 'Selecciona un producto válido.',
            'items.*.product_id.exists'        => 'El producto seleccionado no existe.',
            'items.*.quantity.required'        => 'La cantidad es obligatoria.',
            'items.*.quantity.min'             => 'La cantidad debe ser mayor a 0.',
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
                $pid     = (int) $row['product_id'];
                $qty     = (int) $row['quantity'];
                $product = $products->get($pid);

                if (! $product) {
                    throw ValidationException::withMessages([
                        'items' => ['Uno de los productos seleccionados no existe o no pertenece al proveedor.'],
                    ]);
                }

                if ($qty < 1) {
                    throw ValidationException::withMessages([
                        'items' => ['No es posible agregar un producto con cantidad 0 o negativa.'],
                    ]);
                }

                // Prefer purchase price and fall back to sale price when needed.
                $unit = (float) ($product->purchase_price ?: $product->sale_price);
                if ($unit <= 0) {
                    $unit = (float) $product->sale_price;
                }

                $lineTotal = round($unit * $qty, 2);
                $total    += $lineTotal;

                $lines[] = [
                    'product_id' => $pid,
                    'name'       => $product->name,
                    'quantity'   => $qty,
                    'unit_price' => $unit,
                    'total'      => $lineTotal,
                ];
            }

            $po = $this->generatePoNumber();

            $order = Order::create([
                'po_number'               => $po,
                'supplier_id'             => (int) $validated['supplier_id'],
                'estimated_delivery_date' => $validated['estimated_delivery_date'],
                'date'                    => now(),
                'state'                   => 'draft',
                'total'                   => round($total, 2),
            ]);

            foreach ($lines as $line) {
                OrderItem::create(array_merge($line, ['order_num_order' => $order->num_order]));
            }

            OrderStateTimeline::create([
                'num_order'  => (int) $order->num_order,
                'user_id'    => (int) auth('admin')->id(),
                'state'      => 'draft',
                'changed_at' => now(),
            ]);

            return redirect()->route('admin.supplier-orders.detail', $order->num_order)
                ->with('status', 'Pedido creado correctamente.');
        });
    }

    public function show($id)
    {
        $order = Order::with(['supplier', 'orderItems', 'stateTimeline.admin', 'confirmedBy'])->findOrFail($id);

        $productsPayload = $order->orderItems->map(fn ($item) => [
            'id'                => $item->id,
            'name'              => $item->name,
            'quantity'          => (int) $item->quantity,
            'received_quantity' => $item->received_quantity !== null ? (int) $item->received_quantity : null,
            'unit_price'        => (float) $item->unit_price,
            'total'             => (float) $item->total,
        ])->values();

        return response()->json([
            'success' => true,
            'order'   => [
                'num_order'               => $order->num_order,
                'po_number'               => $order->po_number,
                'supplier'                => $order->supplier ? [
                    'supplier_id'     => $order->supplier->supplier_id,
                    'name'            => $order->supplier->name,
                    'primary_contact' => $order->supplier->primary_contact,
                    'email'           => $order->supplier->email,
                    'phone'           => $order->supplier->phone,
                ] : null,
                'products'                => $productsPayload,
                'date'                    => $order->date?->format('d/m/Y H:i'),
                'estimated_delivery_date' => $order->estimated_delivery_date?->format('d/m/Y'),
                'received_at'             => $order->received_at?->format('d/m/Y H:i'),
                'closed_with_shorts'      => (bool) $order->closed_with_shorts,
                'state'                   => $order->state,
                'total'                   => (float) $order->total,
                'timeline'                => $order->stateTimeline->map(fn ($t) => [
                    'state'      => $t->state,
                    'changed_at' => $t->changed_at->format('d/m/Y H:i'),
                    'user_name'  => $t->admin
                        ? trim($t->admin->name . ' ' . ($t->admin->first_surname ?? ''))
                        : 'Sistema',
                    'reason'     => $t->reason,
                ])->values()->all(),
                'confirmed_at'       => $order->confirmed_at?->format('d/m/Y H:i'),
                'confirmed_by_label' => $this->adminDisplayName($order->confirmedBy),
            ],
        ]);
    }

    /**
     * PATCH /supplier-orders/{id}/state
     *
     * Gestiona transiciones de estado explícitas (confirmar, cancelar, entregar).
     * El estado "partial_received" lo asigna receiveOrder() automáticamente;
     * no se acepta como destino aquí para evitar que se salte la recepción real.
     *
     * El valor "close_partial" NO es un estado persistido; es una señal de la UI
     * para que este método lo intercepte y delege a closePartial().
     */
    public function updateState(Request $request, int $id, InventoryMovementService $inventoryService)
    {
        $order = Order::findOrFail($id);

        $request->validate([
            'state'  => ['required', 'string', 'in:draft,pending,confirmed,delivered,cancelled,close_partial'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $requested = $request->state;

        // "close_partial" es una acción especial: delegar a closePartial().
        if ($requested === 'close_partial') {
            return $this->closePartial($request, $id);
        }

        $new = $requested;

        if (! $order->canTransitionTo($new)) {
            return response()->json([
                'success' => false,
                'message' => 'Transición de estado no permitida.',
            ], 422);
        }

        // ── confirmed ───────────────────────────────────────────────────────────
        if ($new === 'confirmed') {
            $adminId = auth('admin')->id();
            $updates = [];

            if (! $order->confirmed_at) {
                $updates['confirmed_at'] = now();
            }
            if (! $order->confirmed_by && $adminId) {
                $updates['confirmed_by'] = (int) $adminId;
            }

            if ($updates !== []) {
                $order->fill($updates);
            }
        }

        // ── delivered (transición directa, sin recepción previa por línea) ─────
        // Solo aplica cuando el estado origen es confirmed (flujo legacy/rápido).
        // Cuando el origen es partial_received se usa closePartial() en su lugar,
        // ya que el stock parcial ya fue ingresado por receiveOrder().
        if ($new === 'delivered') {
            if ($order->state === 'confirmed') {
                // Flujo directo: ingresar el total pedido al inventario.
                $items = OrderItem::query()
                    ->where('order_num_order', (int) $order->num_order)
                    ->get(['product_id', 'quantity']);

                // Compatibilidad con órdenes históricas sin order_items normalizados.
                if ($items->isEmpty() && is_array($order->products) && $order->products !== []) {
                    $items = collect($order->products)->map(function ($row) {
                        return (object) [
                            'product_id' => (int) ($row['product_id'] ?? 0),
                            'quantity'   => (int) ($row['quantity'] ?? 0),
                        ];
                    })->filter(fn ($r) => $r->product_id > 0 && $r->quantity > 0);
                }

                foreach ($items as $it) {
                    $productId = (int) $it->product_id;
                    $quantity  = (int) $it->quantity;

                    if ($productId < 1 || $quantity < 1) {
                        continue;
                    }

                    $product = Product::find($productId);

                    if (! $product) {
                        Log::warning(
                            "SupplierOrderController::updateState — producto #{$productId} no encontrado al procesar orden #{$order->num_order}"
                        );
                        continue;
                    }

                    $inventoryService->recordSupplierEntry(
                        product: $product,
                        quantity: $quantity,
                        orderId: $order->num_order,
                    );
                }
            }
            // Si el origen fuera partial_received llegaríamos aquí solo como
            // fallback, pero el flujo normal pasa por closePartial().
        }

        $order->update([
            'state'        => $new,
            'date'         => $new === 'confirmed' ? now() : $order->date,
            'delivered_at' => $new === 'delivered' ? now() : $order->delivered_at,
        ]);

        OrderStateTimeline::create([
            'num_order'  => (int) $order->num_order,
            'user_id'    => (int) auth('admin')->id(),
            'state'      => $new,
            'reason'     => $new === 'cancelled' ? ($request->input('reason') ?? null) : null,
            'changed_at' => now(),
        ]);

        $order->refresh();
        $order->load('confirmedBy');

        return response()->json([
            'success' => true,
            'message' => 'Estado actualizado correctamente.',
            'order'   => [
                'state'              => $order->state,
                'confirmed_at'       => $order->confirmed_at?->format('d/m/Y H:i'),
                'confirmed_by_label' => $this->adminDisplayName($order->confirmedBy),
            ],
        ]);
    }

    /**
     * POST /supplier-orders/{id}/close-partial
     *
     * Cierra manualmente un pedido en estado `partial_received` marcándolo como
     * `delivered` con `closed_with_shorts = true`.
     *
     * Reglas de negocio:
     * - Solo disponible desde el estado `partial_received`.
     * - NO mueve stock: el stock ya fue ingresado en las llamadas previas a receiveOrder().
     * - Requiere un motivo (reason) de al menos 4 caracteres para auditoría.
     * - Registra en la timeline que el cierre fue con faltantes del proveedor.
     */
    public function closePartial(Request $request, int $id)
    {
        $order = Order::with('orderItems')->findOrFail($id);

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
            'reason.min'      => 'El motivo debe tener al menos 4 caracteres.',
            'reason.max'      => 'El motivo no puede superar los 500 caracteres.',
        ]);

        // Verificar que realmente hay faltantes; si todo está recibido el flujo
        // normal de receiveOrder() ya debió haberlo marcado como delivered.
        $hasShorts = $order->orderItems->contains(
            fn ($item) => (int) ($item->received_quantity ?? 0) < (int) $item->quantity
        );

        if (! $hasShorts) {
            return response()->json([
                'success' => false,
                'message' => 'Todos los productos están completamente recibidos. El pedido debería marcarse como Entregado de forma normal.',
            ], 422);
        }

        return DB::transaction(function () use ($order, $request) {
            $reason = trim($request->input('reason'));

            $order->update([
                'state'              => 'delivered',
                'delivered_at'       => now(),
                'closed_with_shorts' => true,
            ]);

            OrderStateTimeline::create([
                'num_order'  => (int) $order->num_order,
                'user_id'    => (int) auth('admin')->id(),
                'state'      => 'delivered',
                'reason'     => '[Cierre con faltantes] ' . $reason,
                'changed_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Pedido cerrado con faltantes. Se registró que el proveedor no entregó la totalidad de los productos.',
                'order'   => [
                    'state'              => 'delivered',
                    'closed_with_shorts' => true,
                    'delivered_at'       => $order->fresh()->delivered_at?->format('d/m/Y H:i'),
                ],
            ]);
        });
    }

    /**
     * POST /supplier-orders/{id}/receive
     *
     * Registra la recepción de mercancía (total o parcial) y actualiza el stock.
     * Disponible desde los estados `confirmed` y `partial_received`.
     *
     * - Si todas las líneas se reciben completas → estado `delivered`.
     * - Si alguna línea queda por debajo de lo pedido → estado `partial_received`.
     * - Solo mueve el delta de stock (recvQty − previousReceivedQty) para evitar
     *   duplicar entradas cuando se registra una segunda recepción parcial.
     */
    public function receiveOrder(Request $request, $id)
    {
        $order = Order::with('orderItems')->findOrFail($id);

        if (! in_array($order->state, ['confirmed', 'partial_received'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Solo se puede registrar la recepción cuando el pedido está Confirmado o en Recepción parcial.',
            ], 422);
        }

        $request->validate([
            'items'                     => ['required', 'array', 'min:1'],
            'items.*.order_item_id'     => ['required', 'integer'],
            'items.*.received_quantity' => ['required', 'integer', 'min:0'],
        ], [
            'items.required'                     => 'Debes enviar las líneas del pedido.',
            'items.*.order_item_id.required'     => 'Cada línea debe tener un identificador.',
            'items.*.received_quantity.required' => 'La cantidad recibida es obligatoria.',
            'items.*.received_quantity.min'      => 'La cantidad recibida no puede ser negativa.',
        ]);

        $itemsById = $order->orderItems->keyBy('id');

        $receivedPayload = [];
        foreach ($request->items as $row) {
            $itemId  = (int) $row['order_item_id'];
            $recvQty = (int) $row['received_quantity'];

            $item = $itemsById->get($itemId);

            if (! $item) {
                return response()->json([
                    'success' => false,
                    'message' => "La línea #{$itemId} no pertenece a este pedido.",
                ], 422);
            }

            if ($recvQty > (int) $item->quantity) {
                return response()->json([
                    'success' => false,
                    'message' => "La cantidad recibida de \"{$item->name}\" ({$recvQty}) supera la cantidad pedida ({$item->quantity}).",
                ], 422);
            }

            $receivedPayload[$itemId] = $recvQty;
        }

        $missingItems = $order->orderItems->pluck('id')->diff(array_keys($receivedPayload));
        if ($missingItems->isNotEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Debes indicar la cantidad recibida para todos los productos del pedido.',
            ], 422);
        }

        return DB::transaction(function () use ($order, $receivedPayload) {
            /** @var \App\Services\InventoryMovementService $inventoryService */
            $inventoryService = app(InventoryMovementService::class);

            foreach ($order->orderItems as $item) {
                $recvQty     = (int) ($receivedPayload[$item->id] ?? 0);
                $previousQty = (int) ($item->received_quantity ?? 0);
                $delta       = $recvQty - $previousQty;

                $item->update(['received_quantity' => $recvQty]);

                // Solo registrar movimiento de inventario por el delta positivo,
                // evitando duplicar stock cuando se actualiza una recepción parcial.
                if ($delta > 0 && $item->product_id) {
                    $product = Product::find((int) $item->product_id);

                    if (! $product) {
                        Log::warning('receiveOrder: producto no encontrado para incrementar stock', [
                            'order_id'   => $order->num_order,
                            'item_id'    => $item->id,
                            'product_id' => $item->product_id,
                            'delta'      => $delta,
                        ]);
                        continue;
                    }

                    $inventoryService->recordSupplierEntry(
                        product: $product,
                        quantity: $delta,
                        orderId: $order->num_order,
                    );
                }
            }

            // Determinar si la recepción quedó completa o parcial.
            $order->refresh();
            $isPartial = $order->orderItems->contains(
                fn ($item) => (int) ($item->received_quantity ?? 0) < (int) $item->quantity
            );
            $newState = $isPartial ? 'partial_received' : 'delivered';

            $order->update([
                'state'       => $newState,
                'received_at' => now(),
                // Si la recepción queda completa, registrar la fecha real de entrega.
                // Si queda parcial, delivered_at debe permanecer null.
                'delivered_at' => $isPartial ? null : now(),
                // Si ahora quedó completo limpiar el flag de cierre parcial previo (si existía).
                'closed_with_shorts' => $isPartial ? $order->closed_with_shorts : false,
            ]);

            OrderStateTimeline::create([
                'num_order'  => (int) $order->num_order,
                'user_id'    => (int) auth('admin')->id(),
                'state'      => $newState,
                'reason'     => null,
                'changed_at' => now(),
            ]);

            $message = $isPartial
                ? 'Recepción parcial registrada. Uno o más productos tienen cantidad menor a la pedida.'
                : 'Recepción total registrada. El pedido ahora está Entregado.';

            return response()->json([
                'success'     => true,
                'state'       => $newState,
                'message'     => $message,
                'received_at' => $order->fresh()->received_at?->format('d/m/Y H:i'),
            ]);
        });
    }

    public function supplierDetails($id)
    {
        $supplier = Supplier::withCount(['products' => fn ($q) => $q->where('status', 'active')])
            ->findOrFail($id);

        return response()->json([
            'success'  => true,
            'supplier' => [
                'supplier_id'     => $supplier->supplier_id,
                'name'            => $supplier->name,
                'primary_contact' => $supplier->primary_contact,
                'phone'           => $supplier->phone,
                'email'           => $supplier->email,
                'address'         => $supplier->address,
                'delivery_time'   => $supplier->delivery_time,
                'rating'          => $supplier->rating,
                'status'          => $supplier->status,
                'products_count'  => $supplier->products_count,
            ],
        ]);
    }

    private function adminDisplayName(?AdminUser $admin): ?string
    {
        if (! $admin) {
            return null;
        }

        $full = trim(implode(' ', array_filter([
            $admin->name,
            $admin->first_surname,
            $admin->second_surname,
        ])));

        if ($full !== '') {
            return $full;
        }

        $email = trim((string) ($admin->gmail ?? ''));

        return $email !== '' ? $email : null;
    }

    private function generatePoNumber(): string
    {
        $year = (string) now()->format('Y');

        // Lock the matching purchase order range to generate the next sequential number safely.
        $last = Order::query()
            ->whereNotNull('po_number')
            ->where('po_number', 'like', 'PO-' . $year . '-%')
            ->lockForUpdate()
            ->orderByDesc('num_order')
            ->value('po_number');

        $nextSeq = 1;
        if (is_string($last) && preg_match('/^PO-' . preg_quote($year, '/') . '-([0-9]{4})$/', $last, $m)) {
            $nextSeq = ((int) $m[1]) + 1;
        }

        return 'PO-' . $year . '-' . str_pad((string) $nextSeq, 4, '0', STR_PAD_LEFT);
    }
}