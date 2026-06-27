<?php

namespace App\Services\Admin\Sales;

use App\Models\Client;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Notifications\OrderCompletedNotification;
use App\Notifications\OrderReadyToPickupNotification;
use App\Notifications\ProductReviewReminderNotification;
use App\Services\Admin\Audit\AuditLogger;
use App\Services\Admin\Inventory\InventoryMovementService;
use App\Services\Shared\Security\SensitiveDataMasker;
use App\Support\DashboardTodaySales;
use App\Support\DeferAfterResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

final readonly class AdminSalesWorkflowService
{
    public function __construct(
        private InventoryMovementService $inventoryService,
        private OrderCancellationNotifier $cancellationNotifier,
    ) {}

    public function store(array $validated): JsonResponse
    {
        $buyerName = $validated['buyer_name'] ?? null;
        $buyerEmail = $validated['buyer_email'] ?? null;
        $clientId = $validated['client_id'] ?? null;

        DB::beginTransaction();

        try {
            $preparedLines = [];

            foreach ($validated['items'] as $item) {
                $product = Product::query()
                    ->lockForUpdate()
                    ->find($item['product_id']);

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

            $discount = $this->roundMoney(max(0.0, (float) ($validated['discount'] ?? 0)));
            if ($discount > $subtotal) {
                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => 'El descuento no puede ser mayor que el subtotal (₡'.number_format($subtotal, 2, ',', '.').').',
                ], 422);
            }

            $ivaPercent = max(0.0, min(13.0, (float) ($validated['iva_percentage'] ?? 0)));
            $taxableBase = $this->roundMoney($subtotal - $discount);
            $iva = $this->roundMoney($taxableBase * ($ivaPercent / 100));
            $total = $this->roundMoney($taxableBase + $iva);

            $sale = Sale::create([
                'invoice_number' => (new Sale)->generateInvoiceNumber(),
                'client_id' => $clientId,
                'seller_admin_id' => Auth::guard('admin')->id(),
                'sale_date' => now(),
                'payment_method' => $validated['payment_method'],
                'payment_reference' => $validated['payment_reference'] ?? null,
                'status' => 'completed',
                'discount' => $discount,
                'notes' => $validated['notes'] ?? null,
                'buyer_name' => $buyerName,
                'buyer_email' => $buyerEmail,
                'order_source' => $clientId ? 'web_cart' : 'walk_in',
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

                $this->inventoryService->recordSale(
                    product: $line['product'],
                    quantity: $line['quantity'],
                    saleId: $sale->sale_id,
                );
            }

            $this->ensureReviewPlaceholdersForCompletedSale($sale);

            DB::commit();

            $this->logAuditAction('sale_create', 'Venta creada desde panel administrativo.', [
                'sale_id' => (int) $sale->sale_id,
                'invoice_number' => (string) $sale->invoice_number,
                'status' => (string) $sale->status,
                'total' => (float) $sale->total,
                'items_count' => count($preparedLines),
            ]);

            DeferAfterResponse::run(fn () => $this->sendProductReviewReminderEmail($sale));
            DashboardTodaySales::forgetDashboardCache();

            return response()->json([
                'success' => true,
                'message' => 'Venta creada correctamente.',
                'sale' => $sale->load(['client', 'sellerAdmin', 'saleItems.product']),
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            return $this->technicalJsonError($e, 'sale_create_failed', 'No fue posible crear la venta. Inténtalo nuevamente.');
        }
    }

    public function update(Sale $sale, array $validated): JsonResponse
    {
        $previousStatus = (string) $sale->status;

        try {
            DB::transaction(function () use ($sale, $validated, $previousStatus): void {
                $sale->update([
                    'status' => $validated['status'],
                    'notes' => $validated['notes'] ?? null,
                ]);

                if ($sale->status === 'completed') {
                    $this->ensureReviewPlaceholdersForCompletedSale($sale);
                    DeferAfterResponse::run(fn () => $this->sendProductReviewReminderEmail($sale));
                    DashboardTodaySales::forgetDashboardCache();
                }

                $this->logAuditAction('sale_update_status', 'Estado de venta actualizado.', [
                    'sale_id' => (int) $sale->sale_id,
                    'invoice_number' => (string) ($sale->invoice_number ?? ''),
                    'from_status' => $previousStatus,
                    'to_status' => (string) $sale->status,
                ]);
            });
        } catch (\Throwable $e) {
            return $this->technicalJsonError($e, 'sale_update_failed', 'No fue posible actualizar la venta. Inténtalo nuevamente.');
        }

        return response()->json([
            'success' => true,
            'message' => 'Sale updated successfully.',
        ]);
    }

    public function destroy(Sale $sale, string $reason): JsonResponse
    {
        if ($sale->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Solo los pedidos pendientes pueden cancelarse desde esta acción.',
            ], 400);
        }

        DB::transaction(function () use ($sale, $reason): void {
            $sale->update(['status' => 'cancelled']);

            foreach ($sale->saleItems as $item) {
                if ($item->product) {
                    $this->inventoryService->recordOrderCancellation(
                        product: $item->product,
                        quantity: (int) $item->quantity,
                        saleId: $sale->sale_id,
                        reason: $reason,
                    );
                }
            }
        });

        $this->logAuditAction('sale_cancel', 'Venta cancelada desde endpoint de eliminación.', [
            'sale_id' => (int) $sale->sale_id,
            'invoice_number' => (string) ($sale->invoice_number ?? ''),
            'from_status' => 'pending',
            'to_status' => 'cancelled',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Venta cancelada correctamente y stock liberado.',
        ]);
    }

    public function complete(Sale $sale): JsonResponse
    {
        try {
            if ($sale->status === 'completed') {
                return response()->json(['success' => false, 'message' => 'Este pedido ya está confirmado. No puede confirmarse de nuevo.'], 400);
            }

            if ($sale->status === 'cancelled') {
                return response()->json(['success' => false, 'message' => 'Este pedido fue rechazado o cancelado. No puede confirmarse.'], 400);
            }

            if ($sale->status === 'returned') {
                return response()->json(['success' => false, 'message' => 'No se puede confirmar un pedido devuelto.'], 400);
            }

            if ($sale->status === 'pending') {
                return response()->json(['success' => false, 'message' => 'El pedido debe estar en estado "Listo para recoger" antes de confirmarse.'], 400);
            }

            if ($sale->status !== 'ready_to_pickup') {
                return response()->json(['success' => false, 'message' => 'Solo los pedidos listos para recoger pueden confirmarse.'], 400);
            }

            $invoiceNumber = $sale->invoice_number ?: (new Sale)->generateInvoiceNumber();

            DB::transaction(function () use ($sale, $invoiceNumber): void {
                $sale->update([
                    'status' => 'completed',
                    'invoice_number' => $invoiceNumber,
                    'client_history_seen_at' => null,
                ]);

                $this->ensureReviewPlaceholdersForCompletedSale($sale);
            });

            $sale->refresh();

            $this->logAuditAction('sale_complete', 'Pedido confirmado como venta completada.', [
                'sale_id' => (int) $sale->sale_id,
                'invoice_number' => (string) $sale->invoice_number,
                'from_status' => 'ready_to_pickup',
                'to_status' => 'completed',
            ]);

            DeferAfterResponse::run(function () use ($sale): void {
                $this->sendOrderCompletedNotification($sale);
                $this->sendProductReviewReminderEmail($sale);
            });

            DashboardTodaySales::forgetDashboardCache();

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
            return $this->technicalJsonError($e, 'sale_complete_failed', 'No fue posible confirmar el pedido.');
        }
    }

    public function markReadyToPickup(Sale $sale): JsonResponse
    {
        try {
            if ($sale->status === 'ready_to_pickup') {
                return response()->json([
                    'success' => true,
                    'already_done' => true,
                    'message' => 'El pedido ya estaba marcado como listo para recoger.',
                    'sale' => [
                        'sale_id' => $sale->sale_id,
                        'status' => $sale->status,
                    ],
                ]);
            }

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

            $this->logAuditAction('sale_ready_to_pickup', 'Pedido marcado como listo para recoger.', [
                'sale_id' => (int) $sale->sale_id,
                'invoice_number' => (string) ($sale->invoice_number ?? ''),
                'from_status' => 'pending',
                'to_status' => 'ready_to_pickup',
            ]);

            $saleId = (int) $sale->sale_id;
            $clientId = $sale->client_id;
            DeferAfterResponse::run(function () use ($saleId, $clientId): void {
                if (! $clientId) {
                    return;
                }

                $client = Client::query()->find($clientId);
                $freshSale = Sale::query()->with(['client', 'saleItems.product'])->find($saleId);
                if (! $client || ! $freshSale) {
                    return;
                }

                try {
                    $client->notify(new OrderReadyToPickupNotification($freshSale));
                } catch (\Throwable $e) {
                    Log::warning('Could not notify client (ready-to-pickup).', SensitiveDataMasker::exceptionContext($e, [
                        'sale_id' => $saleId,
                        'client_id' => $clientId,
                    ]));
                }
            });

            DashboardTodaySales::forgetDashboardCache();

            return response()->json([
                'success' => true,
                'message' => 'Pedido marcado como listo para recoger.',
                'sale' => [
                    'sale_id' => $sale->sale_id,
                    'status' => $sale->status,
                ],
            ]);
        } catch (\Throwable $e) {
            return $this->technicalJsonError($e, 'sale_mark_ready_failed', 'No fue posible actualizar el pedido.');
        }
    }

    public function cancel(Sale $sale, string $reason): JsonResponse
    {
        try {
            $cancelledAt = now();

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

            DB::transaction(function () use ($sale, $reason): void {
                $sale->update(['status' => 'cancelled']);

                foreach ($sale->saleItems as $item) {
                    if ($item->product) {
                        $this->inventoryService->recordOrderCancellation(
                            product: $item->product,
                            quantity: (int) $item->quantity,
                            saleId: $sale->sale_id,
                            reason: $reason,
                        );
                    }
                }
            });

            DeferAfterResponse::run(function () use ($sale, $reason, $cancelledAt): void {
                try {
                    $this->cancellationNotifier->notify($sale, $reason, $cancelledAt);
                } catch (\Throwable $e) {
                    Log::warning('Manual cancellation notification failed.', SensitiveDataMasker::exceptionContext($e, [
                        'sale_id' => $sale->sale_id,
                    ]));
                }
            });

            DashboardTodaySales::forgetDashboardCache();

            $this->logAuditAction('sale_cancel', 'Pedido cancelado y stock liberado.', [
                'sale_id' => (int) $sale->sale_id,
                'invoice_number' => (string) ($sale->invoice_number ?? ''),
                'from_status' => $previousStatus,
                'to_status' => 'cancelled',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Pedido rechazado. El stock de los productos fue liberado.',
            ]);
        } catch (\Throwable $e) {
            return $this->technicalJsonError($e, 'sale_cancel_failed', 'No fue posible rechazar el pedido.');
        }
    }

    public function returnSale(Sale $sale, string $reason): JsonResponse
    {
        if ($sale->status !== 'completed') {
            return response()->json([
                'success' => false,
                'message' => 'Solo las ventas confirmadas pueden registrar una devolución.',
            ], 400);
        }

        try {
            DB::transaction(function () use ($sale, $reason): void {
                $sale->update(['status' => 'returned']);

                foreach ($sale->saleItems as $item) {
                    if ($item->product) {
                        $this->inventoryService->recordSaleReturn(
                            product: $item->product,
                            quantity: (int) $item->quantity,
                            saleId: $sale->sale_id,
                            reason: $reason,
                        );
                    }
                }
            });

            $this->logAuditAction('sale_return', 'Devolución registrada sobre venta completada.', [
                'sale_id' => (int) $sale->sale_id,
                'invoice_number' => (string) ($sale->invoice_number ?? ''),
                'from_status' => 'completed',
                'to_status' => 'returned',
                'reason' => $reason,
            ]);

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
            return $this->technicalJsonError($e, 'sale_return_failed', 'No fue posible registrar la devolución.');
        }
    }

    private function roundMoney(float $amount): float
    {
        return round($amount, 2);
    }

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
                ['stars' => null],
            );
        }
    }

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
            Log::warning('Could not notify client (order completed).', SensitiveDataMasker::exceptionContext($e, [
                'sale_id' => $sale->sale_id ?? null,
                'client_id' => $client->user_id ?? null,
            ]));
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
            Log::warning('Could not send product review reminder email.', SensitiveDataMasker::exceptionContext($e, [
                'sale_id' => $sale->sale_id ?? null,
                'client_id' => $client->user_id ?? null,
            ]));
        }
    }

    private function logAuditAction(string $actionType, string $description, array $meta = []): void
    {
        try {
            app(AuditLogger::class)->logAdminAction($actionType, 'sales', $description, $meta);
        } catch (\Throwable $e) {
            Log::warning('Sales audit log write failed', SensitiveDataMasker::exceptionContext($e, [
                'action_type' => $actionType,
            ]));
        }
    }

    private function technicalJsonError(\Throwable $e, string $event, string $message): JsonResponse
    {
        Log::error($event, SensitiveDataMasker::exceptionContext($e, [
            'admin_id' => Auth::guard('admin')->id(),
        ]));

        return response()->json([
            'success' => false,
            'message' => $message,
        ], 500);
    }
}
