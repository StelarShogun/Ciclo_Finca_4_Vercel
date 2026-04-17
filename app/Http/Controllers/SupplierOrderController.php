<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Supplier;
use Illuminate\Http\Request;

class SupplierOrderController extends Controller
{
    public function index(Request $request)
    {
        $state    = $request->get('state');
        $search   = $request->get('search');
        $dateFrom = $request->get('date_from');
        $dateTo   = $request->get('date_to');

        // Swap silently if hasta < desde
        if ($dateFrom && $dateTo && $dateTo < $dateFrom) {
            [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
        }

        $query = Order::with(['supplier', 'orderItems'])->orderBy('date', 'desc');

        if ($state) {
            $query->where('state', $state);
        }

        if ($dateFrom) {
            $query->whereDate('date', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->whereDate('date', '<=', $dateTo);
        }

        if ($search) {
            $search = trim($search);
            $query->where(function ($q) use ($search) {
                $q->where('num_order', 'like', "%{$search}%")
                    ->orWhereHas('supplier', fn ($s) => $s->where('name', 'like', "%{$search}%"));
            });
        }

        $orders = $query->paginate(10)->withQueryString();

        return view('admin.orders.index_supplier', compact('orders'));
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
            'order'   => [
                'num_order' => $order->num_order,
                'supplier'  => $order->supplier ? [
                    'supplier_id'     => $order->supplier->supplier_id,
                    'name'            => $order->supplier->name,
                    'primary_contact' => $order->supplier->primary_contact,
                    'email'           => $order->supplier->email,
                    'phone'           => $order->supplier->phone,
                ] : null,
                'products'  => $productsPayload,
                'date'      => $order->date?->format('d/m/Y H:i'),
                'state'     => $order->state,
                'total'     => (float) $order->total,
            ],
        ]);
    }

    public function updateState(Request $request, $id)
    {
        $order = Order::findOrFail($id);

        $request->validate([
            'state' => 'required|in:draft,pending,confirmed,delivered,cancelled',
        ]);

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
}
