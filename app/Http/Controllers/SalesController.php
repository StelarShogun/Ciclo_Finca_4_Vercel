<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Services\Admin\AdminPdfExportLimits;
use App\Services\Admin\ReportExcelFilename;
use App\Services\Admin\ReportPdfFilename;
use App\Services\Admin\RegistryExcelExport;
use Barryvdh\DomPDF\Facade\Pdf as PDF;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SalesController extends Controller
{
    public function index(Request $request)
    {
        $statusFilter = $request->query('status');
        $salesStatusUi = in_array($statusFilter, ['cancelled', 'refunded', 'all'], true)
            ? $statusFilter
            : 'completed';

        $query = Sale::with(['client', 'sellerAdmin', 'saleItems.product']);
        $this->applySalesAdminListFilters($query, $request);

        $sales = $query->orderBy('sale_date', 'desc')->paginate(15)->withQueryString();

        $dailySales = $this->calculateDailySales();
        $dailySalesTrend = $this->calculateDailySalesTrend();
        $dailyTransactions = $this->calculateDailyTransactions();
        $dailyTransactionsTrend = $this->calculateDailyTransactionsTrend();
        $refunds = $this->calculateRefunds();
        $refundsTrend = $this->calculateRefundsTrend();

        return view('admin.sales.index', compact(
            'sales',
            'dailySales',
            'dailySalesTrend',
            'dailyTransactions',
            'dailyTransactionsTrend',
            'refunds',
            'refundsTrend',
            'salesStatusUi'
        ));
    }

    public function historyHeartbeat(Request $request)
    {
        $since = (int) $request->query('since', 0);

        $baseQuery = Sale::query()
            ->whereIn('status', ['pending', 'completed'])
            ->where(function ($q) {
                $q->where('order_source', 'web_cart')
                    ->orWhereNull('order_source');
            })
            ->notExpired();

        // Check for any sale newer than the last known ID seen by the client
        $hasNew = (clone $baseQuery)
            ->where('sale_id', '>', $since)
            ->exists();

        $latestSaleId = (clone $baseQuery)->max('sale_id') ?? 0;

        return response()->json([
            'hasNew' => $hasNew,
            'latestSaleId' => $latestSaleId,
        ]);
    }

    public function show($id)
    {
        try {
            $sale = Sale::with(['client', 'sellerAdmin', 'saleItems.product'])->findOrFail($id);

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
                    'order_source' => $sale->order_source,
                    'buyer' => [
                        'name' => $sale->buyer_name,
                        'email' => $sale->buyer_email,
                    ],
                    'days_remaining_until_expiration' => $sale->days_remaining_until_expiration,
                    'expires_at' => $sale->expires_at->toISOString(),
                    'is_expiry_warning' => $sale->is_expiry_warning,
                    'client' => $sale->client ? [
                        'user_id' => $sale->client->user_id,
                        'name' => $sale->client->name,
                        'first_surname' => $sale->client->first_surname,
                        'second_surname' => $sale->client->second_surname,
                        'gmail' => $sale->client->gmail,
                    ] : null,
                    'sale_items' => $sale->saleItems->map(function (SaleItem $item) {
                        return [
                            'id' => $item->id,
                            'product_id' => $item->product_id,
                            'quantity' => $item->quantity,
                            'unit_price' => $item->unit_price,
                            'total' => $item->total,
                            'product' => $item->product ? [
                                'product_id' => $item->product->product_id,
                                'name' => $item->product->name,
                                'sku' => Product::skuFromId((int) $item->product->product_id),
                            ] : null,
                        ];
                    }),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error loading sale: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function store(Request $request)
    {
        // Walk-in sales store optional buyer_name/buyer_email; web-cart sales also carry client_id
        $items = $request->items ?? $request->productos ?? [];
        $buyerName = $request->buyer_name ?: null;
        $buyerEmail = $request->buyer_email ?: null;
        $clientId = $request->client_id ?: null;

        // Accept both English and legacy Spanish field names from older API consumers
        $paymentMethod = $request->payment_method ?? $this->mapPaymentMethodToEnglish($request->metodo_pago);
        $paymentReference = $request->payment_reference ?? $request->referencia_pago;
        $discount = $request->discount ?? $request->descuento;
        $notes = $request->notes ?? $request->notas;

        $request->merge([
            'items' => $items,
            'payment_method' => $paymentMethod,
            'payment_reference' => $paymentReference,
            'discount' => $discount,
            'notes' => $notes,
        ]);

        // Normalize legacy Spanish item keys before validation runs
        $normalizedItems = collect($request->items)->map(function ($item) {
            $item['product_id'] = $item['product_id'] ?? $item['producto_id'] ?? null;
            $item['quantity'] = $item['quantity'] ?? $item['cantidad'] ?? 1;

            return $item;
        })->all();
        $request->merge(['items' => $normalizedItems]);

        $request->validate([
            'buyer_name' => 'nullable|string|max:120',
            'buyer_email' => 'nullable|email|max:150',
            'client_id' => 'nullable|exists:client_table,user_id',
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
            'notes' => 'nullable|string|max:500',
        ], [
            'items.required' => 'At least one item is required.',
            'payment_method.in' => 'Payment method must be cash, sinpe or transfer.',
        ]);

        DB::beginTransaction();
        try {
            $preparedLines = [];

            foreach ($request->items as $item) {
                $product = Product::find($item['product_id']);
                if (! $product || $item['quantity'] > $product->stock_current) {
                    DB::rollBack();
                    $name = $product ? $product->name : 'ID ' . $item['product_id'];
                    $available = $product ? $product->stock_current : 0;

                    return response()->json([
                        'success' => false,
                        'message' => "Insufficient stock for \"{$name}\". Available: {$available}",
                    ], 400);
                }

                $quantity = (int) $item['quantity'];
                $unitPrice = $this->roundMoney((float) $item['precio_unitario']);
                $lineTotal = $this->roundMoney($quantity * $unitPrice);

                $preparedLines[] = [
                    'product' => $product,
                    'product_id' => (int) $item['product_id'],
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'total' => $lineTotal,
                ];
            }

            $subtotal = 0.0;
            foreach ($preparedLines as $line) {
                $subtotal = $this->roundMoney($subtotal + $line['total']);
            }

            $discount = $this->roundMoney(max(0.0, (float) ($request->discount ?? 0)));
            if ($discount > $subtotal) {
                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => 'El descuento no puede ser mayor que el subtotal (₡' . number_format($subtotal, 2, ',', '.') . ').',
                ], 422);
            }

            $ivaPercent = (float) ($request->input('iva_percentage', 0));
            $ivaPercent = max(0.0, min(13.0, $ivaPercent));
            $taxableBase = $this->roundMoney($subtotal - $discount);
            $iva = $this->roundMoney($taxableBase * ($ivaPercent / 100));
            $total = $this->roundMoney($taxableBase + $iva);

            $orderSource = $clientId ? 'web_cart' : 'walk_in';
            $sale = Sale::create([
                'invoice_number' => (new Sale)->generateInvoiceNumber(),
                'client_id' => $clientId,
                'seller_admin_id' => Auth::guard('admin')->id(),
                'sale_date' => now(),
                'payment_method' => $request->payment_method,
                'payment_reference' => $request->payment_reference ?? null,
                'status' => 'completed',
                'discount' => $discount,
                'notes' => $request->notes,
                'buyer_name' => $buyerName,
                'buyer_email' => $buyerEmail,
                'order_source' => $orderSource,
                'subtotal' => $subtotal,
                'iva' => $iva,
                'total' => $total,
            ]);

            foreach ($preparedLines as $line) {
                SaleItem::create([
                    'sale_id' => $sale->sale_id,
                    'product_id' => $line['product_id'],
                    'quantity' => $line['quantity'],
                    'unit_price' => $line['unit_price'],
                    'total' => $line['total'],
                ]);

                $line['product']->decrement('stock_current', $line['quantity']);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Sale created successfully.',
                'sale' => $sale->load(['client', 'sellerAdmin', 'saleItems.product']),
            ]);
        } catch (\Exception $e) {
            DB::rollback();

            return response()->json([
                'success' => false,
                'message' => 'Error creating sale: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $sale = Sale::findOrFail($id);

        $request->validate([
            'status' => 'required|in:pending,completed,cancelled,refunded',
            'notes' => 'nullable|string|max:500',
        ]);

        $sale->update([
            'status' => $request->status,
            'notes' => $request->notes,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Sale updated successfully.',
        ]);
    }

    public function destroy($id)
    {
        $sale = Sale::findOrFail($id);

        if ($sale->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Solo los pedidos pendientes pueden cancelarse desde esta acción.',
            ], 400);
        }

        $sale->update(['status' => 'cancelled']);

        return response()->json([
            'success' => true,
            'message' => 'Venta cancelada correctamente.',
        ]);
    }

    public function complete($id)
    {
        try {
            $sale = Sale::findOrFail($id);

            if ($sale->status === 'completed') {
                return response()->json([
                    'success' => false,
                    'message' => 'Este pedido ya está confirmado. No puede confirmarse de nuevo.',
                ], 400);
            }

            if ($sale->status === 'cancelled') {
                return response()->json([
                    'success' => false,
                    'message' => 'Este pedido fue rechazado o cancelado. No puede confirmarse.',
                ], 400);
            }

            if ($sale->status === 'refunded') {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede confirmar un pedido reembolsado.',
                ], 400);
            }

            if ($sale->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo los pedidos pendientes pueden confirmarse.',
                ], 400);
            }

            $invoiceNumber = $sale->invoice_number;
            if (empty($invoiceNumber)) {
                $invoiceNumber = (new Sale)->generateInvoiceNumber();
            }

            $sale->update([
                'status' => 'completed',
                'invoice_number' => $invoiceNumber,
            ]);

            $sale->refresh();

            return response()->json([
                'success' => true,
                'message' => 'Pedido confirmado correctamente. La venta quedó registrada con su factura.',
                'sale' => [
                    'sale_id' => $sale->sale_id,
                    'invoice_number' => $sale->invoice_number,
                    'status' => $sale->status,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al confirmar el pedido: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function cancel($id)
    {
        try {
            $sale = Sale::with('saleItems.product')->findOrFail($id);

            if ($sale->status === 'cancelled') {
                return response()->json([
                    'success' => false,
                    'message' => 'Este pedido ya está cancelado o rechazado.',
                ], 400);
            }

            if ($sale->status === 'completed') {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede rechazar un pedido ya confirmado. Use reembolso si aplica.',
                ], 400);
            }

            if ($sale->status === 'refunded') {
                return response()->json([
                    'success' => false,
                    'message' => 'Este pedido ya fue reembolsado.',
                ], 400);
            }

            if ($sale->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo los pedidos pendientes pueden rechazarse o cancelarse.',
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
                'message' => 'Pedido rechazado. El stock de los productos fue liberado.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al rechazar el pedido: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function refund($id)
    {
        $sale = Sale::findOrFail($id);

        if ($sale->status !== 'completed') {
            return response()->json([
                'success' => false,
                'message' => 'Only completed sales can be refunded.',
            ], 400);
        }

        // Restore inventory for every item included in the refund
        foreach ($sale->saleItems as $item) {
            if ($item->product) {
                $item->product->increment('stock_current', $item->quantity);
            }
        }

        $sale->update(['status' => 'refunded']);

        return response()->json([
            'success' => true,
            'message' => 'Refund processed successfully.',
        ]);
    }

    public function print($id)
    {
        $sale = Sale::with(['client', 'sellerAdmin', 'saleItems.product'])->findOrFail($id);

        return view('admin.sales.print', compact('sale'));
    }

    public function invoice($id)
    {
        $sale = Sale::with(['client', 'sellerAdmin', 'saleItems.product'])->findOrFail($id);

        if ($sale->status !== 'completed') {
            abort(403, 'La factura solo está disponible para ventas confirmadas.');
        }

        return view('admin.sales.invoice', compact('sale'));
    }

    public function export(Request $request)
    {
        try {
            $format = strtolower((string) $request->get('format', 'csv'));

            $base = Sale::query();
            $this->applySalesAdminListFilters($base, $request);

            if ($format === 'pdf') {
                return $this->exportSalesPdf($request, $base);
            }

            if ($format === 'excel') {
                return $this->exportSalesExcel($request, $base);
            }

            $filename = 'sales_' . now()->format('Y-m-d_H-i-s') . '.csv';
            $headers = [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ];

            $chunkSize = AdminPdfExportLimits::SALES_CSV_CHUNK;

            $callback = function () use ($base, $chunkSize): void {
                $file = fopen('php://output', 'w');
                if ($file === false) {
                    return;
                }
                fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM for correct Excel rendering
                fputcsv($file, [
                    'Sale ID',
                    'Customer',
                    'Email',
                    'Date',
                    'Status',
                    'Payment',
                    'Subtotal',
                    'IVA',
                    'Discount',
                    'Total',
                    'Items',
                    'Notes',
                ], ';');

                (clone $base)
                    ->with(['client', 'sellerAdmin', 'saleItems.product'])
                    ->orderBy('sale_id')
                    ->chunkById($chunkSize, function ($sales) use ($file): void {
                        foreach ($sales as $sale) {
                            $items = $sale->saleItems->map(function (SaleItem $item): string {
                                $label = $item->product !== null ? $item->product->name : '?';

                                return $label.' (x'.$item->quantity.')';
                            })->implode(', ');

                            $customerDisplayName = $sale->client
                                ? trim($sale->client->name.' '.$sale->client->first_surname.' '.($sale->client->second_surname ?: ''))
                                : ($sale->buyer_name ?: 'Walk-in / Sin datos');

                            $customerEmail = $sale->client
                                ? $sale->client->gmail
                                : ($sale->buyer_email ?: 'N/A');

                            $saleDate = $sale->sale_date;

                            fputcsv($file, [
                                $sale->sale_id,
                                $customerDisplayName,
                                $customerEmail,
                                $saleDate !== null ? $saleDate->format('d/m/Y H:i') : '',
                                ucfirst((string) $sale->status),
                                ucfirst((string) $sale->payment_method),
                                '₡'.number_format((float) $sale->subtotal, 2, ',', '.'),
                                '₡'.number_format((float) $sale->iva, 2, ',', '.'),
                                '₡'.number_format((float) $sale->discount, 2, ',', '.'),
                                '₡'.number_format((float) $sale->total, 2, ',', '.'),
                                $items,
                                $sale->notes ?? '',
                            ], ';');
                        }
                    }, 'sale_id');

                fclose($file);
            };

            return response()->stream($callback, 200, $headers);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error exporting sales: ' . $e->getMessage(),
            ], 500);
        }
    }

    private function exportSalesExcel(Request $request, Builder $base): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $maxRows     = AdminPdfExportLimits::SALES_MAX_ROWS;
        $totalMatching = (clone $base)->count();

        $filterLines = $this->salesExportFilterLines($request);
        if ($totalMatching > $maxRows) {
            $filterLines[] = 'Nota: el Excel incluye como máximo '.$maxRows.' filas ('.$totalMatching.' ventas coinciden con los filtros).';
        }

        $rows = (clone $base)
            ->with(['client', 'saleItems.product'])
            ->orderBy('sale_date', 'desc')
            ->limit($maxRows)
            ->get();

        $headers = ['ID Venta', 'Factura', 'Cliente', 'Email', 'Fecha', 'Estado', 'Método pago', 'Subtotal (₡)', 'IVA (₡)', 'Descuento (₡)', 'Total (₡)', 'Ítems', 'Notas'];

        $dataRows = $rows->map(function (Sale $sale): array {
            $customer = $sale->client
                ? trim($sale->client->name.' '.($sale->client->first_surname ?? '').' '.($sale->client->second_surname ?? ''))
                : ($sale->buyer_name ?: 'Walk-in / Sin datos');
            $email = $sale->client ? $sale->client->gmail : ($sale->buyer_email ?: '');
            $items = $sale->saleItems->map(function (SaleItem $item): string {
                return ($item->product !== null ? $item->product->name : '?').' (×'.$item->quantity.')';
            })->implode(', ');
            $saleDate = $sale->sale_date;

            return [
                (string) $sale->sale_id,
                (string) ($sale->invoice_number ?? ''),
                $customer,
                $email,
                $saleDate !== null ? $saleDate->format('d/m/Y H:i') : '',
                ucfirst((string) $sale->status),
                ucfirst((string) $sale->payment_method),
                number_format((float) $sale->subtotal, 2, '.', ''),
                number_format((float) $sale->iva, 2, '.', ''),
                number_format((float) $sale->discount, 2, '.', ''),
                number_format((float) $sale->total, 2, '.', ''),
                $items,
                (string) ($sale->notes ?? ''),
            ];
        })->values()->all();

        return app(RegistryExcelExport::class)->download(
            'Ventas',
            'Listado de ventas — Ciclo Finca 4',
            $headers,
            $dataRows,
            $filterLines,
            ReportExcelFilename::make('ventas'),
        );
    }

    private function exportSalesPdf(Request $request, Builder $base)
    {
        $maxRows = AdminPdfExportLimits::SALES_MAX_ROWS;
        $totalMatching = (clone $base)->count();

        $filterLines = $this->salesExportFilterLines($request);
        if ($totalMatching > $maxRows) {
            $filterLines[] = 'Nota: el PDF incluye como máximo '.$maxRows.' filas ('.$totalMatching.' ventas coinciden con los filtros).';
        }

        $aggregate = (clone $base)
            ->selectRaw('COUNT(*) as agg_count')
            ->selectRaw('COALESCE(SUM(total), 0) as agg_sum_total')
            ->selectRaw('COALESCE(SUM(subtotal), 0) as agg_sum_subtotal')
            ->selectRaw('COALESCE(SUM(iva), 0) as agg_sum_iva')
            ->selectRaw('COALESCE(SUM(discount), 0) as agg_sum_discount')
            ->first();

        $agg = $aggregate !== null ? $aggregate->getAttributes() : [];

        $totals = [
            'count' => (int) ($agg['agg_count'] ?? 0),
            'sum_total' => (float) ($agg['agg_sum_total'] ?? 0.0),
            'sum_subtotal' => (float) ($agg['agg_sum_subtotal'] ?? 0.0),
            'sum_iva' => (float) ($agg['agg_sum_iva'] ?? 0.0),
            'sum_discount' => (float) ($agg['agg_sum_discount'] ?? 0.0),
        ];

        $rows = (clone $base)
            ->with(['client'])
            ->orderBy('sale_date', 'desc')
            ->limit($maxRows)
            ->get();

        $logoPath = public_path('assets/images/brand/logo-ciclo-finca-icon.png');

        $pdf = PDF::loadView('admin.sales.sales-pdf', [
            'sales' => $rows,
            'totals' => $totals,
            'pdfTitle' => 'Reporte de ventas',
            'pdfSubtitle' => 'Listado filtrado — Ciclo Finca 4',
            'logoPath' => is_file($logoPath) ? $logoPath : null,
            'filterLines' => $filterLines,
            'generatedFor' => 'Administración',
        ]);

        return $pdf->download(ReportPdfFilename::make('ventas'));
    }

    /**
     * @return array<int, string>
     */
    private function salesExportFilterLines(Request $request): array
    {
        $lines = [];

        $status = $request->query('status');
        if (in_array($status, ['cancelled', 'refunded', 'all'], true)) {
            $lines[] = 'Estado: '.$status;
        } else {
            $lines[] = 'Estado: confirmadas (completadas)';
        }

        if ($request->filled('date_range')) {
            $lines[] = 'Rango: '.$request->date_range;
        }
        if ($request->filled('start_date') || $request->filled('end_date')) {
            $lines[] = 'Fechas: '.($request->start_date ?: '…').' — '.($request->end_date ?: '…');
        }

        if ($request->filled('payment_method')) {
            $lines[] = 'Método de pago: '.$request->payment_method;
        }

        if ($request->filled('search')) {
            $lines[] = 'Búsqueda: '.$request->search;
        }

        return $lines;
    }

    /**
     * @param  Builder<Sale>  $query
     */
    private function applySalesAdminListFilters(Builder $query, Request $request): void
    {
        $query->notExpired();

        $statusFilter = $request->query('status');
        $this->applyVentasStatusScope($query, $statusFilter);

        if ($request->filled('start_date') || $request->filled('end_date')) {
            if ($request->filled('start_date')) {
                $query->whereDate('sale_date', '>=', $request->start_date);
            }
            if ($request->filled('end_date')) {
                $query->whereDate('sale_date', '<=', $request->end_date);
            }
        } else {
            $dateRange = $request->get('date_range');
            if ($dateRange) {
                switch ($dateRange) {
                    case 'today':
                        $query->whereDate('sale_date', Carbon::today());
                        break;
                    case 'week':
                        $query->whereBetween('sale_date', [
                            Carbon::now()->startOfWeek(),
                            Carbon::now()->endOfWeek(),
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
        }

        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('sale_id', 'like', "%{$search}%")
                    ->orWhere('invoice_number', 'like', "%{$search}%")
                    ->orWhere('buyer_name', 'like', "%{$search}%")
                    ->orWhere('buyer_email', 'like', "%{$search}%")
                    ->orWhereHas('client', function ($subQ) use ($search) {
                        $subQ->where('name', 'like', "%{$search}%")
                            ->orWhere('first_surname', 'like', "%{$search}%")
                            ->orWhere('gmail', 'like', "%{$search}%");
                    });
            });
        }
    }

    /**
     * Ventas (módulo admin): solo estados cerrados — pendientes se gestionan en Pedidos.
     * Por defecto: confirmadas (completed). Valores: completed, cancelled, refunded, all.
     */
    private function applyVentasStatusScope($query, ?string $statusParam): void
    {
        $closed = ['completed', 'cancelled', 'refunded'];

        if ($statusParam === 'all') {
            $query->whereIn('status', $closed);

            return;
        }

        if ($statusParam !== null && $statusParam !== '' && in_array($statusParam, $closed, true)) {
            $query->where('status', $statusParam);

            return;
        }

        $query->where('status', 'completed');
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
        if ($yesterday == 0) {
            return $today > 0 ? 100 : 0;
        }

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
        if ($yesterday == 0) {
            return $today > 0 ? 100 : 0;
        }

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

        // Returns an absolute delta rather than a percentage since refund counts are typically small
        return $today - $yesterday;
    }

    private function mapPaymentMethodToEnglish($value)
    {
        if (empty($value)) {
            return $value;
        }
        $map = ['efectivo' => 'cash', 'sinpe' => 'sinpe', 'transferencia' => 'transfer'];

        return $map[strtolower($value)] ?? $value;
    }

    /**
     * Montos en colones almacenados con 2 decimales (columnas decimal(10,2)).
     * IVA sobre base imponible = subtotal − descuento, luego total = base + IVA.
     */
    private function roundMoney(float $amount): float
    {
        return round($amount, 2);
    }

    public function byCategory(Request $request)
    {
        $dateRange = $request->input('date_range', 'month');
        $dateFrom  = $request->input('date_from');
        $dateTo    = $request->input('date_to');

        if ($dateRange === 'custom') {
            $request->validate([
                'date_from' => 'required|date',
                'date_to'   => 'required|date|after_or_equal:date_from',
            ], [
                'date_from.required'      => 'La fecha de inicio es obligatoria.',
                'date_from.date'          => 'La fecha de inicio no es válida.',
                'date_to.required'        => 'La fecha de fin es obligatoria.',
                'date_to.date'            => 'La fecha de fin no es válida.',
                'date_to.after_or_equal'  => 'La fecha de fin debe ser igual o posterior a la fecha de inicio.',
            ]);
        }

        [$from, $to] = $this->resolveDateRange($dateRange, $dateFrom, $dateTo);

        $rows = SaleItem::query()
            ->join('sales',      'sale_items.sale_id',    '=', 'sales.sale_id')
            ->join('products',   'sale_items.product_id', '=', 'products.product_id')
            ->join('categories', 'products.category_id',  '=', 'categories.category_id')
            ->where('sales.status', 'completed')
            ->whereBetween('sales.sale_date', [$from, $to])
            ->groupBy('categories.category_id', 'categories.name')
            ->selectRaw('
                categories.category_id,
                categories.name          AS category_name,
                SUM(sale_items.quantity) AS total_units,
                SUM(sale_items.total)    AS total_revenue
            ')
            ->orderByDesc('total_revenue')
            ->get();

        $grandTotal = $rows->sum('total_revenue');

        $rows->transform(function ($row) use ($grandTotal) {
            $row->percentage = $grandTotal > 0
                ? round(($row->total_revenue / $grandTotal) * 100, 1)
                : 0;
            return $row;
        });

        $chartData = $rows->map(function ($r) {
            return [
                'label'   => $r->category_name,
                'value'   => $r->total_revenue,
                'percent' => $r->percentage,
            ];
        })->values()->toArray();

        return view('admin.sales.reports-by-category', compact(
            'rows', 'grandTotal', 'from', 'to', 'dateRange', 'chartData'
        ));
    }

    private function resolveDateRange(string $range, ?string $dateFrom, ?string $dateTo): array
    {
        switch ($range) {
            case 'today':
                return [
                    now()->startOfDay()->toDateTimeString(),
                    now()->endOfDay()->toDateTimeString(),
                ];
            case 'week':
                return [
                    now()->startOfWeek()->startOfDay()->toDateTimeString(),
                    now()->endOfWeek()->endOfDay()->toDateTimeString(),
                ];
            case 'month':
                return [
                    now()->startOfMonth()->startOfDay()->toDateTimeString(),
                    now()->endOfMonth()->endOfDay()->toDateTimeString(),
                ];
            case 'custom':
                return [
                    $dateFrom ? Carbon::parse($dateFrom)->startOfDay()->toDateTimeString() : now()->startOfDay()->toDateTimeString(),
                    $dateTo   ? Carbon::parse($dateTo)->endOfDay()->toDateTimeString()     : now()->endOfDay()->toDateTimeString(),
                ];
            default:
                return [
                    now()->startOfMonth()->startOfDay()->toDateTimeString(),
                    now()->endOfMonth()->endOfDay()->toDateTimeString(),
                ];
        }
    }
}