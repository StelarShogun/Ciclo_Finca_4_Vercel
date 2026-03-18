<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Usuario;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class SalesController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->get('status');
        $dateRange = $request->get('date_range');
        $paymentMethod = $request->get('payment_method');
        $search = $request->get('search');

        $query = Sale::with(['customer', 'client', 'saleItems.product', 'seller']);

        $query->notExpired();

        if ($status) {
            $query->where('status', $status);
        }

        if ($dateRange) {
            switch ($dateRange) {
                case 'today':
                    $query->whereDate('sale_date', Carbon::today());
                    break;
                case 'week':
                    $query->whereBetween('sale_date', [
                        Carbon::now()->startOfWeek(),
                        Carbon::now()->endOfWeek()
                    ]);
                    break;
                case 'month':
                    $query->whereMonth('sale_date', Carbon::now()->month)
                          ->whereYear('sale_date', Carbon::now()->year);
                    break;
                case 'custom':
                    break;
            }
        }

        if ($paymentMethod) {
            $query->where('payment_method', $paymentMethod);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('sale_id', 'like', "%{$search}%")
                  ->orWhere('invoice_number', 'like', "%{$search}%")
                  ->orWhereHas('customer', function ($subQ) use ($search) {
                      $subQ->where('nombre', 'like', "%{$search}%")
                           ->orWhere('email', 'like', "%{$search}%");
                  })
                  ->orWhereHas('client', function ($subQ) use ($search) {
                      $subQ->where('name', 'like', "%{$search}%")
                           ->orWhere('first_surname', 'like', "%{$search}%")
                           ->orWhere('gmail', 'like', "%{$search}%");
                  });
            });
        }

        $sales = $query->orderBy('sale_date', 'desc')->paginate(15);

        $dailySales = $this->calculateDailySales();
        $dailySalesTrend = $this->calculateDailySalesTrend();
        $dailyTransactions = $this->calculateDailyTransactions();
        $dailyTransactionsTrend = $this->calculateDailyTransactionsTrend();
        $refunds = $this->calculateRefunds();
        $refundsTrend = $this->calculateRefundsTrend();

        return view('sales.index', compact(
            'sales',
            'dailySales',
            'dailySalesTrend',
            'dailyTransactions',
            'dailyTransactionsTrend',
            'refunds',
            'refundsTrend'
        ));
    }

    public function show($id)
    {
        try {
            $sale = Sale::with(['customer', 'client', 'saleItems.product', 'seller'])->findOrFail($id);

            return response()->json([
                'success' => true,
                'sale' => [
                    'sale_id' => $sale->sale_id,
                    'invoice_number' => $sale->invoice_number,
                    'sale_date' => $sale->sale_date->toISOString(),
                    'status' => $sale->status,
                    'payment_method' => $sale->payment_method,
                    'payment_reference' => $sale->payment_reference,
                    'subtotal' => $sale->subtotal,
                    'iva' => $sale->iva,
                    'discount' => $sale->discount,
                    'total' => $sale->total,
                    'notes' => $sale->notes,
                    'days_remaining_until_expiration' => $sale->days_remaining_until_expiration,
                    'expires_at' => $sale->expires_at->toISOString(),
                    'is_expiry_warning' => $sale->is_expiry_warning,
                    'customer' => $sale->customer ? [
                        'usuario_id' => $sale->customer->usuario_id,
                        'nombre' => $sale->customer->nombre,
                        'apellido' => $sale->customer->apellido,
                        'email' => $sale->customer->email
                    ] : null,
                    'client' => $sale->client ? [
                        'user_id' => $sale->client->user_id,
                        'name' => $sale->client->name,
                        'first_surname' => $sale->client->first_surname,
                        'second_surname' => $sale->client->second_surname,
                        'gmail' => $sale->client->gmail
                    ] : null,
                    'sale_items' => $sale->saleItems->map(function ($item) {
                        return [
                            'id' => $item->id,
                            'product_id' => $item->product_id,
                            'quantity' => $item->quantity,
                            'unit_price' => $item->unit_price,
                            'total' => $item->total,
                            'product' => $item->product ? [
                                'product_id' => $item->product->product_id,
                                'name' => $item->product->name,
                                'sku' => 'BK-' . str_pad($item->product->product_id, 3, '0', STR_PAD_LEFT)
                            ] : null
                        ];
                    })
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error loading sale: ' . $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        // Accept both English (items, customer_id) and legacy (productos, cliente_id) form field names
        $items = $request->items ?? $request->productos ?? [];
        $customerId = $request->customer_id ?? $request->cliente_id;
        $paymentMethod = $request->payment_method ?? $this->mapPaymentMethodToEnglish($request->metodo_pago);
        $paymentReference = $request->payment_reference ?? $request->referencia_pago;
        $discount = $request->discount ?? $request->descuento;
        $notes = $request->notes ?? $request->notas;

        $request->merge([
            'items' => $items,
            'customer_id' => $customerId,
            'payment_method' => $paymentMethod,
            'payment_reference' => $paymentReference,
            'discount' => $discount,
            'notes' => $notes
        ]);

        // Normalize legacy keys before validating
        $normalizedItems = collect($request->items)->map(function ($item) {
            $item['product_id'] = $item['product_id'] ?? $item['producto_id'] ?? null;
            $item['quantity'] = $item['quantity'] ?? $item['cantidad'] ?? 1;
            return $item;
        })->all();
        $request->merge(['items' => $normalizedItems]);

        $request->validate([
            'customer_id' => 'required|exists:usuarios,usuario_id',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,product_id',
            'items.*.producto_id' => 'nullable',
            'items.*.quantity' => 'nullable|integer|min:1',
            'items.*.cantidad' => 'nullable|integer|min:1',
            'items.*.precio_unitario' => 'required|numeric|min:0',
            'items.*.total' => 'required|numeric|min:0',
            'payment_method' => 'required|in:cash,sinpe,transfer',
            'payment_reference' => 'nullable|string|max:255',
            'discount' => 'nullable|numeric|min:0',
            'iva_percentage' => 'nullable|numeric|min:0|max:13',
            'notes' => 'nullable|string|max:500'
        ], [
            'customer_id.required' => 'Customer is required.',
            'items.required' => 'At least one item is required.',
            'payment_method.in' => 'Payment method must be cash, sinpe or transfer.',
        ]);

        // Items are already normalized at this point

        DB::beginTransaction();
        try {
            foreach ($request->items as $item) {
                $product = Product::find($item['product_id']);
                if (!$product || $item['quantity'] > $product->stock_current) {
                    DB::rollBack();
                    $name = $product ? $product->name : 'ID ' . $item['product_id'];
                    $available = $product ? $product->stock_current : 0;
                    return response()->json([
                        'success' => false,
                        'message' => "Insufficient stock for \"{$name}\". Available: {$available}"
                    ], 400);
                }
            }

            $sale = Sale::create([
                'invoice_number' => (new Sale())->generateInvoiceNumber(),
                'customer_id' => $request->customer_id,
                'seller_id' => auth()->id() ?? 1,
                'sale_date' => now(),
                'payment_method' => $request->payment_method,
                'payment_reference' => $request->payment_reference ?? null,
                'status' => 'pending',
                'discount' => $request->discount ?? 0,
                'notes' => $request->notes,
                'total' => 0
            ]);

            $subtotal = 0;

            foreach ($request->items as $item) {
                $product = Product::find($item['product_id']);
                $quantity = $item['quantity'];
                $price = $item['precio_unitario'];
                $itemTotal = $item['total'];

                SaleItem::create([
                    'sale_id' => $sale->sale_id,
                    'product_id' => $item['product_id'],
                    'quantity' => $quantity,
                    'unit_price' => $price,
                    'total' => $itemTotal
                ]);

                $subtotal += $itemTotal;
                $product->decrement('stock_current', $quantity);
            }

            $discount = $request->discount ?? 0;
            $subtotalAfterDiscount = $subtotal - $discount;
            $ivaPercent = (float) ($request->input('iva_percentage', 0));
            $ivaPercent = max(0, min(13, $ivaPercent));
            $iva = $subtotalAfterDiscount * ($ivaPercent / 100);
            $total = $subtotalAfterDiscount + $iva;

            $sale->update([
                'subtotal' => $subtotal,
                'discount' => $discount,
                'iva' => $iva,
                'total' => $total
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Sale created successfully.',
                'sale' => $sale->load(['customer', 'saleItems.product'])
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Error creating sale: ' . $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $sale = Sale::findOrFail($id);

        $request->validate([
            'status' => 'required|in:pending,completed,cancelled,refunded',
            'notes' => 'nullable|string|max:500'
        ]);

        $sale->update([
            'status' => $request->status,
            'notes' => $request->notes
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Sale updated successfully.'
        ]);
    }

    public function destroy($id)
    {
        $sale = Sale::findOrFail($id);

        if ($sale->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Only pending sales can be cancelled.'
            ], 400);
        }

        $sale->update(['status' => 'cancelled']);

        return response()->json([
            'success' => true,
            'message' => 'Sale cancelled successfully.'
        ]);
    }

    public function complete($id)
    {
        try {
            $sale = Sale::findOrFail($id);

            if ($sale->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only pending sales can be completed.'
                ], 400);
            }

            $sale->update(['status' => 'completed']);

            return response()->json([
                'success' => true,
                'message' => 'Sale completed successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error completing sale: ' . $e->getMessage()
            ], 500);
        }
    }

    public function cancel($id)
    {
        try {
            $sale = Sale::findOrFail($id);

            if ($sale->status === 'cancelled') {
                return response()->json([
                    'success' => false,
                    'message' => 'This sale is already cancelled.'
                ], 400);
            }

            if ($sale->status === 'completed') {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot cancel a completed sale. Use refund instead.'
                ], 400);
            }

            $sale->update(['status' => 'cancelled']);

            foreach ($sale->saleItems as $item) {
                if ($item->product) {
                    $item->product->increment('stock_current', $item->quantity);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Sale cancelled successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error cancelling sale: ' . $e->getMessage()
            ], 500);
        }
    }

    public function refund($id)
    {
        $sale = Sale::findOrFail($id);

        if ($sale->status !== 'completed') {
            return response()->json([
                'success' => false,
                'message' => 'Only completed sales can be refunded.'
            ], 400);
        }

        foreach ($sale->saleItems as $item) {
            if ($item->product) {
                $item->product->increment('stock_actual', $item->quantity);
            }
        }

        $sale->update(['status' => 'refunded']);

        return response()->json([
            'success' => true,
            'message' => 'Refund processed successfully.'
        ]);
    }

    public function print($id)
    {
        $sale = Sale::with(['customer', 'saleItems.product', 'seller'])->findOrFail($id);
        return view('sales.print', compact('sale'));
    }

    public function invoice($id)
    {
        $sale = Sale::with(['customer', 'saleItems.product', 'seller'])->findOrFail($id);
        return view('sales.invoice', compact('sale'));
    }

    public function export(Request $request)
    {
        try {
            $sales = Sale::with(['customer', 'saleItems.product', 'seller'])
                ->when($request->start_date, fn ($q) => $q->whereDate('sale_date', '>=', $request->start_date))
                ->when($request->end_date, fn ($q) => $q->whereDate('sale_date', '<=', $request->end_date))
                ->when($request->status, fn ($q) => $q->where('status', $request->status))
                ->when($request->payment_method, fn ($q) => $q->where('payment_method', $request->payment_method))
                ->when($request->search, function ($q) use ($request) {
                    return $q->whereHas('customer', function ($sub) use ($request) {
                        $sub->where('name', 'like', '%' . $request->search . '%')
                            ->orWhere('email', 'like', '%' . $request->search . '%');
                    })->orWhere('sale_id', 'like', '%' . $request->search . '%');
                })
                ->orderBy('sale_date', 'desc')
                ->get();

            $filename = 'sales_' . now()->format('Y-m-d_H-i-s') . '.csv';
            $headers = [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ];

            $callback = function () use ($sales) {
                $file = fopen('php://output', 'w');
                fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));
                fputcsv($file, [
                    'Sale ID', 'Customer', 'Email', 'Date', 'Status', 'Payment', 'Subtotal', 'IVA', 'Discount', 'Total', 'Items', 'Notes'
                ], ';');

                foreach ($sales as $sale) {
                    $items = $sale->saleItems->map(fn ($item) => $item->product->name . ' (x' . $item->quantity . ')')->implode(', ');
                    fputcsv($file, [
                        $sale->sale_id,
                        $sale->customer->nombre ?? 'N/A',
                        $sale->customer->email ?? 'N/A',
                        $sale->sale_date->format('d/m/Y H:i'),
                        ucfirst($sale->status),
                        ucfirst($sale->payment_method),
                        '₡' . number_format((float) $sale->subtotal, 2, ',', '.'),
                        '₡' . number_format((float) $sale->iva, 2, ',', '.'),
                        '₡' . number_format((float) $sale->discount, 2, ',', '.'),
                        '₡' . number_format((float) $sale->total, 2, ',', '.'),
                        $items,
                        $sale->notes ?? ''
                    ], ';');
                }
                fclose($file);
            };

            return response()->stream($callback, 200, $headers);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error exporting sales: ' . $e->getMessage()
            ], 500);
        }
    }

    private function calculateDailySales()
    {
        return Sale::whereDate('sale_date', Carbon::today())
            ->where('status', 'completed')
            ->sum('total');
    }

    private function calculateDailySalesTrend()
    {
        $today = Sale::whereDate('sale_date', Carbon::today())->where('status', 'completed')->sum('total');
        $yesterday = Sale::whereDate('sale_date', Carbon::yesterday())->where('status', 'completed')->sum('total');
        if ($yesterday == 0) return $today > 0 ? 100 : 0;
        return round((($today - $yesterday) / $yesterday) * 100, 1);
    }

    private function calculateDailyTransactions()
    {
        return Sale::whereDate('sale_date', Carbon::today())->where('status', 'completed')->count();
    }

    private function calculateDailyTransactionsTrend()
    {
        $today = Sale::whereDate('sale_date', Carbon::today())->where('status', 'completed')->count();
        $yesterday = Sale::whereDate('sale_date', Carbon::yesterday())->where('status', 'completed')->count();
        if ($yesterday == 0) return $today > 0 ? 100 : 0;
        return round((($today - $yesterday) / $yesterday) * 100, 1);
    }

    private function calculateRefunds()
    {
        return Sale::whereDate('sale_date', Carbon::today())->where('status', 'refunded')->count();
    }

    private function calculateRefundsTrend()
    {
        $today = Sale::whereDate('sale_date', Carbon::today())->where('status', 'refunded')->count();
        $yesterday = Sale::whereDate('sale_date', Carbon::yesterday())->where('status', 'refunded')->count();
        return $today - $yesterday;
    }

    /** Map legacy Spanish payment method to English for DB/store. */
    private function mapPaymentMethodToEnglish($value)
    {
        if (empty($value)) return $value;
        $map = ['efectivo' => 'cash', 'sinpe' => 'sinpe', 'transferencia' => 'transfer'];
        return $map[strtolower($value)] ?? $value;
    }
}
