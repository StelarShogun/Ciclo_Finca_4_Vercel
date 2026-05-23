<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductReview;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Notifications\OrderCompletedNotification;
use App\Notifications\OrderReadyToPickupNotification;
use App\Notifications\ProductReviewReminderNotification;
use App\Services\Admin\AdminPdfExportLimits;
use App\Services\Admin\AdminPdfExportService;
use App\Services\Admin\RegistryExcelExport;
use App\Services\Admin\ReportExcelFilename;
use App\Services\AuditLogger;
use App\Services\InventoryMovementService;
use App\Services\OrderCancellationNotifier;
use App\Support\AdminDateRange;
use App\Support\AdminPerPage;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SalesController extends Controller
{
    public function index(Request $request)
    {
        $statusFilter = $request->query('status');
        $salesStatusUi = in_array($statusFilter, ['cancelled', 'returned', 'all'], true)
            ? $statusFilter
            : 'completed';

        $query = Sale::with(['client', 'sellerAdmin', 'saleItems.product']);
        $this->applySalesAdminListFilters($query, $request);

        $perPage = AdminPerPage::resolve($request->input('per_page', 10));
        $sales = $query->orderBy('sale_date', 'desc')->paginate($perPage)->withQueryString();

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
            ->whereIn('status', ['pending', 'ready_to_pickup', 'completed'])
            ->where(function ($q) {
                $q->where('order_source', 'web_cart')
                    ->orWhereNull('order_source');
            })
            ->where('sale_date', '>=', now()->subDays(Sale::getOrderExpirationDays()));

        $hasNew = (clone $baseQuery)
            ->where('sale_id', '>', $since)
            ->exists();

        $latestSaleId = (clone $baseQuery)->max('sale_id') ?? 0;

        return response()->json([
            'hasNew' => $hasNew,
            'latestSaleId' => $latestSaleId,
        ]);
    }

    public function show(int $id)
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
                    'days_remaining_until_expiration' => $sale->days_remaining_until_expiration,
                    'expires_at' => $sale->expires_at->toISOString(),
                    'is_expiry_warning' => $sale->is_expiry_warning,
                    'ready_at' => $sale->ready_at?->toISOString(),
                    'confirmed_at' => $sale->status === 'completed'
                        ? $sale->updated_at?->toISOString()
                        : null,
                    'order_placed_at_label' => $sale->adminOrderPlacedAtLabel(),
                    'ready_at_label' => $sale->adminReadyAtLabel(),
                    'confirmed_at_label' => $sale->adminConfirmedAtLabel(),
                    'sale_date_label' => $sale->adminSaleDateLabel(),
                    'pickup_expires_at' => $sale->pickup_expires_at?->toISOString(),
                    'pickup_time_remaining_label' => $sale->pickup_time_remaining_label,
                    'is_pickup_expired' => $sale->isPickupExpired(),
                    'buyer' => [
                        'name' => $sale->buyer_name,
                        'email' => $sale->buyer_email,
                    ],
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
                                'sku' => $item->product->displaySku(),
                            ] : null,
                        ];
                    }),
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error loading sale: '.$e->getMessage(),
            ], 500);
        }
    }

    public function store(Request $request, InventoryMovementService $inventoryService)
    {
        $items = $request->items ?? $request->productos ?? [];
        $buyerName = $request->buyer_name ?: null;
        $buyerEmail = $request->buyer_email ?: null;
        $clientId = $request->client_id ?: null;

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
        ]);

        DB::beginTransaction();
        try {
            $preparedLines = [];

            foreach ($request->items as $item) {
                $product = Product::find($item['product_id']);
                if (! $product || $item['quantity'] > $product->stock_current) {
                    DB::rollBack();
                    $name = $product ? $product->name : 'ID '.$item['product_id'];
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
                    'message' => 'El descuento no puede ser mayor que el subtotal (₡'.number_format($subtotal, 2, ',', '.').').',
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

                $inventoryService->recordSale(
                    product: $line['product'],
                    quantity: $line['quantity'],
                    saleId: $sale->sale_id,
                );
            }

            $this->ensureReviewPlaceholdersForCompletedSale($sale);

            DB::commit();

            $this->logAuditAction(
                'sale_create',
                'Venta creada desde panel administrativo.',
                [
                    'sale_id' => (int) $sale->sale_id,
                    'invoice_number' => (string) $sale->invoice_number,
                    'status' => (string) $sale->status,
                    'total' => (float) $sale->total,
                    'items_count' => count($preparedLines),
                ]
            );

            // FIX: correo fuera del try principal para no bloquear ni abortar la respuesta.
            // Se ejecuta después de que la respuesta HTTP ya fue enviada al cliente (afterResponse).
            $saleForEmail = $sale;
            app()->terminating(function () use ($saleForEmail) {
                $this->sendProductReviewReminderEmail($saleForEmail);
            });

            return response()->json([
                'success' => true,
                'message' => 'Venta creada correctamente.',
                'sale' => $sale->load(['client', 'sellerAdmin', 'saleItems.product']),
            ]);
        } catch (\Throwable $e) {
            DB::rollback();

            return response()->json([
                'success' => false,
                'message' => 'Error al crear la venta: '.$e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, int $id)
    {
        $sale = Sale::findOrFail($id);
        $previousStatus = (string) $sale->status;

        $request->validate([
            'status' => 'required|in:pending,ready_to_pickup,completed,cancelled,returned',
            'notes' => 'nullable|string|max:500',
        ]);

        $sale->update([
            'status' => $request->status,
            'notes' => $request->notes,
        ]);

        if ($sale->status === 'completed') {
            $this->ensureReviewPlaceholdersForCompletedSale($sale);

            // FIX: correo asíncrono para no bloquear la respuesta HTTP.
            $saleForEmail = $sale;
            app()->terminating(function () use ($saleForEmail) {
                $this->sendProductReviewReminderEmail($saleForEmail);
            });
        }

        $this->logAuditAction(
            'sale_update_status',
            'Estado de venta actualizado.',
            [
                'sale_id' => (int) $sale->sale_id,
                'invoice_number' => (string) ($sale->invoice_number ?? ''),
                'from_status' => $previousStatus,
                'to_status' => (string) $sale->status,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Sale updated successfully.',
        ]);
    }

    public function destroy(Request $request, int $id, InventoryMovementService $inventoryService)
    {
        $request->validate([
            'reason' => 'required|string|min:3|max:500',
        ], [
            'reason.required' => 'Debe ingresar un motivo de cancelación.',
            'reason.min' => 'El motivo debe tener al menos 3 caracteres.',
            'reason.max' => 'El motivo no puede superar los 500 caracteres.',
        ]);

        $sale = Sale::with('saleItems.product')->findOrFail($id);

        if ($sale->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Solo los pedidos pendientes pueden cancelarse desde esta acción.',
            ], 400);
        }

        $reason = trim((string) $request->input('reason'));

        DB::transaction(function () use ($sale, $inventoryService, $reason): void {
            $sale->update(['status' => 'cancelled']);

            foreach ($sale->saleItems as $item) {
                if ($item->product) {
                    $inventoryService->recordOrderCancellation(
                        product: $item->product,
                        quantity: (int) $item->quantity,
                        saleId: $sale->sale_id,
                        reason: $reason,
                    );
                }
            }
        });

        $this->logAuditAction(
            'sale_cancel',
            'Venta cancelada desde endpoint de eliminación.',
            [
                'sale_id' => (int) $sale->sale_id,
                'invoice_number' => (string) ($sale->invoice_number ?? ''),
                'from_status' => 'pending',
                'to_status' => 'cancelled',
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Venta cancelada correctamente y stock liberado.',
        ]);
    }

    // Complete a ready-to-pickup order without duplicating stock output movements.
    public function complete(int $id)
    {
        try {
            $sale = Sale::with('saleItems.product')->findOrFail($id);

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

            if ($sale->status === 'returned') {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede confirmar un pedido devuelto.',
                ], 400);
            }

            if ($sale->status === 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'El pedido debe estar en estado "Listo para recoger" antes de confirmarse.',
                ], 400);
            }

            if ($sale->status !== 'ready_to_pickup') {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo los pedidos listos para recoger pueden confirmarse.',
                ], 400);
            }

            $invoiceNumber = $sale->invoice_number;
            if (empty($invoiceNumber)) {
                $invoiceNumber = (new Sale)->generateInvoiceNumber();
            }

            // FIX: toda la lógica de DB dentro del transaction; el correo queda fuera.
            DB::transaction(function () use ($sale, $invoiceNumber) {
                $sale->update([
                    'status' => 'completed',
                    'invoice_number' => $invoiceNumber,
                    'client_history_seen_at' => null,
                ]);

                $this->ensureReviewPlaceholdersForCompletedSale($sale);
            });

            // Refrescar el modelo DENTRO del try para que cualquier fallo sea atrapado.
            $sale->refresh();

            $this->logAuditAction(
                'sale_complete',
                'Pedido confirmado como venta completada.',
                [
                    'sale_id' => (int) $sale->sale_id,
                    'invoice_number' => (string) $sale->invoice_number,
                    'from_status' => 'ready_to_pickup',
                    'to_status' => 'completed',
                ]
            );

            // FIX: el correo se despacha DESPUÉS de que PHP envía la respuesta HTTP al cliente,
            // usando el hook terminating() del kernel. Así nunca bloquea ni rompe el flujo,
            // aunque el servidor de correo tarde o falle.
            $saleForNotify = $sale;
            app()->terminating(function () use ($saleForNotify) {
                $this->sendOrderCompletedNotification($saleForNotify);
                $this->sendProductReviewReminderEmail($saleForNotify);
            });

            return response()->json([
                'success' => true,
                'message' => 'Pedido confirmado correctamente. La venta quedó registrada con su factura.',
                'sale' => [
                    'sale_id' => $sale->sale_id,
                    'invoice_number' => $sale->invoice_number,
                    'status' => $sale->status,
                ],
            ]);
        } catch (\Throwable $e) {
            // FIX: \Throwable en lugar de \Exception para atrapar también errores fatales.
            return response()->json([
                'success' => false,
                'message' => 'Error al confirmar el pedido: '.$e->getMessage(),
            ], 500);
        }
    }

    public function markReadyToPickup(int $id): JsonResponse
    {
        try {
            $sale = Sale::findOrFail($id);

            if ($sale->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo los pedidos pendientes pueden marcarse como listos para recoger.',
                ], 400);
            }

            $sale->update([
                'status' => 'ready_to_pickup',
                'ready_at' => now(),
            ]);

            $sale->refresh()->load(['client', 'saleItems.product']);

            $this->logAuditAction(
                'sale_ready_to_pickup',
                'Pedido marcado como listo para recoger.',
                [
                    'sale_id' => (int) $sale->sale_id,
                    'invoice_number' => (string) ($sale->invoice_number ?? ''),
                    'from_status' => 'pending',
                    'to_status' => 'ready_to_pickup',
                ]
            );

            $saleForNotify = $sale;
            app()->terminating(function () use ($saleForNotify): void {
                $client = $saleForNotify->client;
                if (! $client) {
                    return;
                }

                try {
                    $client->notify(new OrderReadyToPickupNotification($saleForNotify));
                } catch (\Throwable $e) {
                    Log::warning('Could not notify client (ready-to-pickup).', [
                        'sale_id' => $saleForNotify->sale_id,
                        'client_id' => $client->user_id ?? null,
                        'error' => $e->getMessage(),
                    ]);
                }
            });

            return response()->json([
                'success' => true,
                'message' => 'Pedido marcado como listo para recoger.',
                'sale' => [
                    'sale_id' => $sale->sale_id,
                    'status' => $sale->status,
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el pedido: '.$e->getMessage(),
            ], 500);
        }
    }

    // Cancel a pending or ready-to-pickup order and restore reserved stock.
    public function cancel(Request $request, int $id, InventoryMovementService $inventoryService, OrderCancellationNotifier $notifier)
    {
        $request->validate([
            'reason' => 'required|string|min:3|max:500',
        ], [
            'reason.required' => 'Debe ingresar un motivo de cancelación.',
            'reason.min' => 'El motivo debe tener al menos 3 caracteres.',
            'reason.max' => 'El motivo no puede superar los 500 caracteres.',
        ]);

        try {
            $sale = Sale::with('saleItems.product')->findOrFail($id);
            $cancelledAt = now();
            $reason = trim((string) $request->input('reason'));

            if ($sale->status === 'cancelled') {
                return response()->json(['success' => false, 'message' => 'Este pedido ya está cancelado o rechazado.'], 400);
            }

            if ($sale->status === 'completed') {
                return response()->json(['success' => false, 'message' => 'No se puede rechazar un pedido ya confirmado. Use devolución si aplica.'], 400);
            }

            if ($sale->status === 'returned') {
                return response()->json(['success' => false, 'message' => 'Este pedido ya fue devuelto.'], 400);
            }

            if (! in_array($sale->status, ['pending', 'ready_to_pickup'], true)) {
                return response()->json(['success' => false, 'message' => 'Solo los pedidos pendientes o listos para recoger pueden rechazarse.'], 400);
            }

            $previousStatus = (string) $sale->status;

            DB::transaction(function () use ($sale, $inventoryService, $reason): void {
                $sale->update(['status' => 'cancelled']);

                foreach ($sale->saleItems as $item) {
                    if ($item->product) {
                        $inventoryService->recordOrderCancellation(
                            product: $item->product,
                            quantity: (int) $item->quantity,
                            saleId: $sale->sale_id,
                            reason: $reason,
                        );
                    }
                }
            });

            // FIX: notificación de cancelación también asíncrona.
            $saleForNotify = $sale;
            app()->terminating(function () use ($saleForNotify, $notifier, $reason, $cancelledAt) {
                try {
                    $notifier->notify($saleForNotify, $reason, $cancelledAt);
                } catch (\Throwable $e) {
                    Log::warning('Manual cancellation notification failed.', [
                        'sale_id' => $saleForNotify->sale_id,
                        'error' => $e->getMessage(),
                    ]);
                }
            });

            $this->logAuditAction(
                'sale_cancel',
                'Pedido cancelado y stock liberado.',
                [
                    'sale_id' => (int) $sale->sale_id,
                    'invoice_number' => (string) ($sale->invoice_number ?? ''),
                    'from_status' => $previousStatus,
                    'to_status' => 'cancelled',
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'Pedido rechazado. El stock de los productos fue liberado.',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al rechazar el pedido: '.$e->getMessage(),
            ], 500);
        }
    }

    public function returnSale(int $id, Request $request, InventoryMovementService $inventoryService)
    {
        $request->validate([
            'reason' => 'required|string|min:3|max:500',
        ], [
            'reason.required' => 'Debe ingresar un motivo de devolución.',
            'reason.min' => 'El motivo debe tener al menos 3 caracteres.',
            'reason.max' => 'El motivo no puede superar los 500 caracteres.',
        ]);

        $sale = Sale::with('saleItems.product')->findOrFail($id);

        if ($sale->status !== 'completed') {
            return response()->json([
                'success' => false,
                'message' => 'Solo las ventas confirmadas pueden registrar una devolución.',
            ], 400);
        }

        try {
            DB::transaction(function () use ($sale, $request, $inventoryService) {
                $reason = trim($request->reason);

                $sale->update([
                    'status' => 'returned',
                ]);

                foreach ($sale->saleItems as $item) {
                    if ($item->product) {
                        $inventoryService->recordSaleReturn(
                            product: $item->product,
                            quantity: (int) $item->quantity,
                            saleId: $sale->sale_id,
                            reason: $reason,
                        );
                    }
                }
            });

            $this->logAuditAction(
                'sale_return',
                'Devolución registrada sobre venta completada.',
                [
                    'sale_id' => (int) $sale->sale_id,
                    'invoice_number' => (string) ($sale->invoice_number ?? ''),
                    'from_status' => 'completed',
                    'to_status' => 'returned',
                    'reason' => trim($request->reason),
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'Devolución registrada correctamente. El stock fue reintegrado al inventario.',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->errors()['quantity'][0] ?? 'Error de validación de stock.',
            ], 422);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al registrar la devolución: '.$e->getMessage(),
            ], 500);
        }
    }

    public function print(int $id)
    {
        $sale = Sale::with(['client', 'sellerAdmin', 'saleItems.product'])->findOrFail($id);

        return view('admin.sales.print', compact('sale'));
    }

    public function invoice(int $id)
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
            $format = strtolower((string) $request->get('format', 'pdf'));

            $base = Sale::query();
            if ($request->query('scope') === 'all') {
                $base->notExpired();
            } else {
                $this->applySalesAdminListFilters($base, $request);
            }

            if ($format === 'pdf') {
                return $this->exportSalesPdf($request, $base);
            }

            if ($format === 'excel') {
                return $this->exportSalesExcel($request, $base);
            }

            if ($format === 'csv') {
                $filename = 'sales_'.now()->format('Y-m-d_H-i-s').'.csv';
                $headers = [
                    'Content-Type' => 'text/csv; charset=UTF-8',
                    'Content-Disposition' => 'attachment; filename="'.$filename.'"',
                ];

                $chunkSize = AdminPdfExportLimits::SALES_CSV_CHUNK;

                $callback = function () use ($base, $chunkSize): void {
                    $file = fopen('php://output', 'w');
                    if ($file === false) {
                        return;
                    }
                    fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
                    fputcsv($file, [
                        'Sale ID', 'Customer', 'Email', 'Date', 'Status',
                        'Payment', 'Subtotal', 'IVA', 'Discount', 'Total', 'Items', 'Notes',
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
            }

            return response()->json([
                'success' => false,
                'message' => 'Formato no soportado. Use pdf, excel o csv.',
            ], 400);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error exporting sales: '.$e->getMessage(),
            ], 500);
        }
    }

    private function exportSalesExcel(Request $request, Builder $base): StreamedResponse
    {
        $maxRows = AdminPdfExportLimits::SALES_MAX_ROWS;
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

        $dataRows = $rows->map(function ($sale): array {
            if (! $sale instanceof Sale) {
                return [];
            }

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

        $rows = (clone $base)->with(['client'])->orderBy('sale_date', 'desc')->limit($maxRows)->get();
        $logoPath = public_path('assets/images/brand/logo-ciclo-finca-icon.png');

        return app(AdminPdfExportService::class)->download('admin.sales.sales-pdf', [
            'sales' => $rows,
            'totals' => $totals,
            'pdfTitle' => 'Reporte de ventas',
            'pdfSubtitle' => 'Listado filtrado — Ciclo Finca 4',
            'logoPath' => is_file($logoPath) ? $logoPath : null,
            'filterLines' => $filterLines,
            'generatedFor' => 'Administración',
        ], 'ventas');
    }

    private function salesExportFilterLines(Request $request): array
    {
        $lines = [];
        $status = $request->query('status');

        if (in_array($status, ['cancelled', 'returned', 'all'], true)) {
            $lines[] = 'Estado: '.$status;
        } else {
            $lines[] = 'Estado: confirmadas (completadas)';
        }

        if ($request->filled('date_range')) {
            $lines[] = 'Rango: '.$request->date_range;
        }
        $from = $request->date_from ?: $request->start_date;
        $to = $request->date_to ?: $request->end_date;
        if (($from !== null && $from !== '') || ($to !== null && $to !== '')) {
            $lines[] = 'Fechas: '.($from ?: '…').' — '.($to ?: '…');
        }
        if ($request->filled('payment_method')) {
            $lines[] = 'Método de pago: '.$request->payment_method;
        }
        if ($request->filled('search')) {
            $lines[] = 'Búsqueda: '.$request->search;
        }

        return $lines;
    }

    private function applySalesAdminListFilters(Builder $query, Request $request): void
    {
        $days = Sale::getOrderExpirationDays();
        $limitDate = now()->subDays($days);
        $query->where('sale_date', '>=', $limitDate);

        $this->applyVentasStatusScope($query, $request->query('status'));

        $dateRange = (string) $request->get('date_range', AdminDateRange::PRESET_TODAY);
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
        } elseif (in_array($dateRange, [
            AdminDateRange::PRESET_TODAY,
            AdminDateRange::PRESET_WEEK,
            AdminDateRange::PRESET_MONTH,
        ], true)) {
            AdminDateRange::applyDateTimeBetween($query, 'sale_date', $dateRange, storedAsUtc: true);
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

    // Restrict results to the requested sales status scope.
    private function applyVentasStatusScope(Builder $query, ?string $statusParam): void
    {
        $closed = ['completed', 'cancelled', 'returned'];
        $allVisible = ['completed', 'cancelled', 'returned', 'ready_to_pickup'];

        if ($statusParam === 'all') {
            $query->whereIn('status', $allVisible);

            return;
        }

        if ($statusParam !== null && $statusParam !== '' && in_array($statusParam, $closed, true)) {
            $query->where('status', $statusParam);

            return;
        }

        $query->whereIn('status', ['completed', 'returned']);
    }

    private function calculateDailySales()
    {
        [$start, $end] = AdminDateRange::boundsForUtcColumn(AdminDateRange::PRESET_TODAY);

        return Sale::whereBetween('sale_date', [$start, $end])->where('status', 'completed')->sum('total');
    }

    private function calculateDailySalesTrend()
    {
        [$todayStart, $todayEnd] = AdminDateRange::boundsForUtcColumn(AdminDateRange::PRESET_TODAY);
        $today = Sale::whereBetween('sale_date', [$todayStart, $todayEnd])->where('status', 'completed')->sum('total');
        $yesterday = AdminDateRange::now()->copy()->subDay();
        [$yesterdayStart, $yesterdayEnd] = AdminDateRange::boundsForUtcColumn(
            AdminDateRange::PRESET_CUSTOM,
            $yesterday->toDateString(),
            $yesterday->toDateString(),
        );
        $yesterday = Sale::whereBetween('sale_date', [$yesterdayStart, $yesterdayEnd])->where('status', 'completed')->sum('total');
        if ($yesterday == 0) {
            return $today > 0 ? 100 : 0;
        }

        return round((($today - $yesterday) / $yesterday) * 100, 1);
    }

    private function calculateDailyTransactions()
    {
        [$start, $end] = AdminDateRange::boundsForUtcColumn(AdminDateRange::PRESET_TODAY);

        return Sale::whereBetween('sale_date', [$start, $end])->where('status', 'completed')->count();
    }

    private function calculateDailyTransactionsTrend()
    {
        [$todayStart, $todayEnd] = AdminDateRange::boundsForUtcColumn(AdminDateRange::PRESET_TODAY);
        $today = Sale::whereBetween('sale_date', [$todayStart, $todayEnd])->where('status', 'completed')->count();
        $yesterday = AdminDateRange::now()->copy()->subDay();
        [$yesterdayStart, $yesterdayEnd] = AdminDateRange::boundsForUtcColumn(
            AdminDateRange::PRESET_CUSTOM,
            $yesterday->toDateString(),
            $yesterday->toDateString(),
        );
        $yesterday = Sale::whereBetween('sale_date', [$yesterdayStart, $yesterdayEnd])->where('status', 'completed')->count();
        if ($yesterday == 0) {
            return $today > 0 ? 100 : 0;
        }

        return round((($today - $yesterday) / $yesterday) * 100, 1);
    }

    private function calculateRefunds(): int
    {
        [$start, $end] = AdminDateRange::boundsForUtcColumn(AdminDateRange::PRESET_TODAY);

        return Sale::whereBetween('sale_date', [$start, $end])
            ->where('status', 'returned')
            ->count();
    }

    private function calculateRefundsTrend(): int
    {
        [$todayStart, $todayEnd] = AdminDateRange::boundsForUtcColumn(AdminDateRange::PRESET_TODAY);
        $today = Sale::whereBetween('sale_date', [$todayStart, $todayEnd])->where('status', 'returned')->count();
        $yesterday = AdminDateRange::now()->copy()->subDay();
        [$yesterdayStart, $yesterdayEnd] = AdminDateRange::boundsForUtcColumn(
            AdminDateRange::PRESET_CUSTOM,
            $yesterday->toDateString(),
            $yesterday->toDateString(),
        );
        $yesterday = Sale::whereBetween('sale_date', [$yesterdayStart, $yesterdayEnd])->where('status', 'returned')->count();

        return $today - $yesterday;
    }

    // Map legacy Spanish payment values to internal English keys.
    private function mapPaymentMethodToEnglish(mixed $value): mixed
    {
        if (empty($value)) {
            return $value;
        }
        $map = ['efectivo' => 'cash', 'sinpe' => 'sinpe', 'transferencia' => 'transfer'];

        return $map[strtolower($value)] ?? $value;
    }

    private function roundMoney(float $amount): float
    {
        return round($amount, 2);
    }

    // Create nullable review rows for each purchased product when the sale is completed.
    private function ensureReviewPlaceholdersForCompletedSale(Sale $sale): void
    {
        if ($sale->status !== 'completed' || empty($sale->client_id)) {
            return;
        }

        $productIds = SaleItem::query()
            ->where('sale_id', $sale->sale_id)
            ->pluck('product_id')
            ->unique()
            ->values();

        foreach ($productIds as $productId) {
            ProductReview::query()->firstOrCreate(
                [
                    'client_id' => (int) $sale->client_id,
                    'product_id' => (int) $productId,
                ],
                [
                    'stars' => null,
                ]
            );
        }
    }

    // Notify the client to rate products after order confirmation.
    // NOTE: Always call this via app()->terminating() to avoid blocking the HTTP response.
    private function sendOrderCompletedNotification(Sale $sale): void
    {
        if ((string) $sale->status !== 'completed') {
            return;
        }

        $client = $sale->client ?: $sale->loadMissing('client')->client;
        if (! $client) {
            return;
        }

        try {
            $client->notify(new OrderCompletedNotification($sale));
        } catch (\Throwable $e) {
            Log::warning('Could not notify client (order completed).', [
                'sale_id' => $sale->sale_id ?? null,
                'client_id' => $client->user_id ?? null,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function sendProductReviewReminderEmail(Sale $sale): void
    {
        if ((string) $sale->status !== 'completed') {
            return;
        }

        $client = $sale->client ?: $sale->loadMissing('client')->client;
        if (! $client || empty($client->gmail)) {
            return;
        }

        try {
            $client->notify(new ProductReviewReminderNotification($sale));
        } catch (\Throwable $e) {
            Log::warning('Could not send product review reminder email.', [
                'sale_id' => $sale->sale_id ?? null,
                'client_id' => $client->user_id ?? null,
                'email' => $client->gmail ?? null,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function logAuditAction(string $actionType, string $description, array $meta = []): void
    {
        try {
            app(AuditLogger::class)->logAdminAction($actionType, 'sales', $description, $meta);
        } catch (\Throwable $e) {
            Log::warning('Sales audit log write failed', [
                'action_type' => $actionType,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function byCategory(Request $request)
    {
        $dateRange = $request->input('date_range', 'month');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        if ($dateRange === 'custom') {
            $request->validate([
                'date_from' => 'required|date',
                'date_to' => 'required|date|after_or_equal:date_from',
            ], [
                'date_from.required' => 'La fecha de inicio es obligatoria.',
                'date_from.date' => 'La fecha de inicio no es válida.',
                'date_to.required' => 'La fecha de fin es obligatoria.',
                'date_to.date' => 'La fecha de fin no es válida.',
                'date_to.after_or_equal' => 'La fecha de fin debe ser igual o posterior a la fecha de inicio.',
            ]);
        }

        [$from, $to] = $this->resolveDateRange($dateRange, $dateFrom, $dateTo);

        $rows = SaleItem::query()
            ->join('sales', 'sale_items.sale_id', '=', 'sales.sale_id')
            ->join('products', 'sale_items.product_id', '=', 'products.product_id')
            ->join('categories', 'products.category_id', '=', 'categories.category_id')
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
                'label' => $r->category_name,
                'value' => $r->total_revenue,
                'percent' => $r->percentage,
            ];
        })->values()->toArray();

        return view('admin.sales.reports-by-category', compact(
            'rows', 'grandTotal', 'from', 'to', 'dateRange', 'chartData'
        ));
    }

    private function resolveDateRange(string $range, ?string $dateFrom, ?string $dateTo): array
    {
        return AdminDateRange::boundsAsDateTimeStrings($range, $dateFrom, $dateTo, storedAsUtc: true);
    }
}
