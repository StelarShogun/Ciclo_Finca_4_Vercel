<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Supplier;
use App\Services\InventoryMovementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

                return '+'.$t.'*';
            })
            ->filter()
            ->implode(' ');
    }

    public function searchProducts(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $supplierId = (int) $request->query('supplier_id', 0);

        if ($supplierId < 1) {
            return response()->json([
                'success' => false,
                'message' => 'Debes seleccionar un proveedor para buscar productos.',
                'products' => [],
            ], 422);
        }

        if ($q === '' || mb_strlen($q) < 2) {
            return response()->json(['success' => true, 'products' => []]);
        }

        $products = Product::query()
            ->select(['product_id', 'name', 'purchase_price', 'sale_price'])
            ->where('supplier_id', $supplierId)
            ->where(function ($query) use ($q) {
                $query->where('name', 'like', '%'.$q.'%')
                    ->orWhereRaw("CONCAT('BK-', LPAD(product_id, 3, '0')) LIKE ?", ['%'.$q.'%']);
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
                    'name' => (string) $p->name,
                    'sku' => Product::skuFromId((int) $p->product_id),
                    'unit_price' => round($unit, 2),
                ];
            })
            ->values();

        return response()->json(['success' => true, 'products' => $products]);
    }

    public function index(Request $request)
    {
        $state = $request->get('state');
        $search = $request->get('search');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        // Normalize the range when the end date is earlier than the start date.
        if ($dateFrom && $dateTo && $dateTo < $dateFrom) {
            [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
        }

        $query = Order::with(['supplier', 'orderItems'])->orderBy('orders.date', 'desc');

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
            $bool = $driver === 'mysql' ? $this->fullTextBooleanQuery($search) : '';

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
        $order = Order::with(['supplier', 'orderItems'])->findOrFail($id);

        return view('admin.orders.detail_supplier', compact('order'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'supplier_id' => ['required', 'integer', 'exists:suppliers,supplier_id'],
            'estimated_delivery_date' => ['required', 'date'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,product_id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ], [
            'supplier_id.required' => 'El proveedor es obligatorio.',
            'supplier_id.exists' => 'El proveedor seleccionado no existe.',
            'estimated_delivery_date.required' => 'La fecha estimada de entrega es obligatoria.',
            'estimated_delivery_date.date' => 'La fecha estimada no tiene un formato válido.',
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
                $pid = (int) $row['product_id'];
                $qty = (int) $row['quantity'];
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
                $total = round($total + $lineTotal, 2);

                $lines[] = [
                    'product_id' => $product->product_id,
                    'name' => $product->name,
                    'quantity' => $qty,
                    'unit_price' => round($unit, 2),
                    'total' => $lineTotal,
                ];
            }

            $po = $this->generatePoNumber();

            $order = Order::create([
                'po_number' => $po,
                'supplier_id' => (int) $validated['supplier_id'],
                'date' => now(),
                'estimated_delivery_date' => $validated['estimated_delivery_date'],
                'state' => 'draft',
                'total' => $total,
            ]);

            foreach ($lines as $line) {
                OrderItem::create([
                    'order_num_order' => (int) $order->num_order,
                    'product_id' => (int) $line['product_id'],
                    'name' => (string) $line['name'],
                    'quantity' => (int) $line['quantity'],
                    'unit_price' => (float) $line['unit_price'],
                    'total' => (float) $line['total'],
                ]);
            }

            return redirect()->route('admin.supplier-orders.detail', $order->num_order);
        });
    }

    public function show($id)
    {
        $order = Order::with(['supplier', 'orderItems'])->findOrFail($id);

        $productsPayload = $order->orderItems->map(fn ($line) => [
            'name' => $line->name,
            'quantity' => (int) $line->quantity,
            'unit_price' => (float) $line->unit_price,
            'total' => (float) $line->total,
            'product_id' => (int) $line->product_id,
        ])->values()->all();

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
                'state' => $order->state,
                'total' => (float) $order->total,
            ],
        ]);
    }

    public function updateState(Request $request, int $id, InventoryMovementService $inventoryService)
    {
        $order = Order::findOrFail($id);

        $request->validate([
            'state' => 'required|in:draft,pending,confirmed,delivered,cancelled',
        ]);

        // Define the allowed state transitions for supplier orders.
        $transitions = [
            'draft'     => ['pending', 'cancelled'],
            'pending'   => ['confirmed', 'cancelled'],
            'confirmed' => ['delivered', 'cancelled'],
        ];

        $new = $request->state;

        if (! isset($transitions[$order->state]) || ! in_array($new, $transitions[$order->state], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Transición de estado no permitida.',
            ], 422);
        }

        if ($new === 'delivered') {
            $items = OrderItem::query()
                ->where('order_num_order', (int) $order->num_order)
                ->get(['product_id', 'quantity']);

            // Fall back to legacy JSON items when order_items is empty.
            if ($items->isEmpty() && is_array($order->products) && $order->products !== []) {
                $items = collect($order->products)->map(function ($row) {
                    return (object) [
                        'product_id' => (int) ($row['product_id'] ?? 0),
                        'quantity'   => (int) ($row['quantity']   ?? 0),
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
                    // Log missing products without interrupting the delivery flow.
                    \Illuminate\Support\Facades\Log::warning(
                        "SupplierOrderController::updateState — producto #{$productId} no encontrado al procesar orden #{$order->num_order}"
                    );
                    continue;
                }

                $inventoryService->recordSupplierEntry(
                    product:  $product,
                    quantity: $quantity,
                    orderId:  $order->num_order,
                );
            }
        }

        $order->update(['state' => $new]);

        return response()->json([
            'success' => true,
            'message' => 'Estado actualizado correctamente.',
        ]);
    }

    public function supplierDetails($id)
    {
        $supplier = Supplier::withCount(['products' => fn ($q) => $q->where('status', 'active')])
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

        // Lock the matching purchase order range to generate the next sequential number safely.
        $last = Order::query()
            ->whereNotNull('po_number')
            ->where('po_number', 'like', 'PO-'.$year.'-%')
            ->lockForUpdate()
            ->orderByDesc('num_order')
            ->value('po_number');

        $nextSeq = 1;
        if (is_string($last) && preg_match('/^PO-'.preg_quote($year, '/').'-([0-9]{4})$/', $last, $m)) {
            $nextSeq = ((int) $m[1]) + 1;
        }

        return 'PO-'.$year.'-'.str_pad((string) $nextSeq, 4, '0', STR_PAD_LEFT);
    }
}