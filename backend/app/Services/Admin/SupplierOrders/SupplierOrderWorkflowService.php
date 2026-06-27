<?php

namespace App\Services\Admin\SupplierOrders;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStateTimeline;
use App\Models\Product;
use App\Services\Admin\Audit\AuditLogger;
use App\Services\Admin\Inventory\InventoryMovementService;
use App\Services\Shared\Security\SensitiveDataMasker;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

final readonly class SupplierOrderWorkflowService
{
    public function __construct(
        private InventoryMovementService $inventoryService,
        private SupplierDeliveryEstimator $deliveryEstimator,
    ) {}

    public function create(array $validated): RedirectResponse
    {
        $items = $validated['items'];

        return DB::transaction(function () use ($validated, $items) {
            $products = Product::query()
                ->lockForUpdate()
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

            $this->logAuditAction('supplier_order_create', 'Pedido a proveedor creado en estado draft.', [
                'order_id' => (int) $order->num_order,
                'po_number' => (string) ($order->po_number ?? ''),
                'supplier_id' => (int) $order->supplier_id,
                'items_count' => count($lines),
                'total' => (float) $order->total,
            ]);

            return redirect()
                ->route('admin.supplier-orders.detail', $order->num_order)
                ->with('status', 'Pedido creado correctamente.');
        });
    }

    public function updateState(Order $order, array $validated): JsonResponse
    {
        $previousState = (string) $order->state;
        $requestedState = $validated['state'];

        if ($requestedState === 'close_partial' || ($requestedState === 'delivered' && $order->state === 'partial_received')) {
            return $this->closePartial($order, (string) ($validated['reason'] ?? ''));
        }

        if (! $order->canTransitionTo($requestedState)) {
            return response()->json([
                'success' => false,
                'message' => 'Transición de estado no permitida.',
            ], 422);
        }

        if ($requestedState === 'delivered' && $order->state === 'confirmed') {
            $this->receiveConfirmedOrderWithoutReceiveFlow($order);
        }

        $confirmationDate = now();
        $estimatedDeliveryDate = $requestedState === 'confirmed'
            ? $this->deliveryEstimator->estimateFor($order, $confirmationDate)
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
            'reason' => $requestedState === 'cancelled' ? ($validated['reason'] ?? null) : null,
            'changed_at' => now(),
        ]);

        $order->refresh();

        $this->logAuditAction('supplier_order_state_update', 'Estado de pedido a proveedor actualizado.', [
            'order_id' => (int) $order->num_order,
            'po_number' => (string) ($order->po_number ?? ''),
            'from_state' => $previousState,
            'to_state' => (string) $order->state,
            'reason' => (string) ($validated['reason'] ?? ''),
            'estimated_delivery_date' => $order->estimated_delivery_date?->toDateString(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Estado actualizado correctamente.',
            'order' => [
                'state' => $order->state,
                'estimated_delivery_date' => $order->estimated_delivery_date?->format('d/m/Y'),
            ],
        ]);
    }

    public function closePartial(Order $order, string $reason): JsonResponse
    {
        $order->loadMissing('orderItems');
        $previousState = (string) $order->state;
        $reason = trim($reason);

        if ($order->state !== 'partial_received') {
            return response()->json([
                'success' => false,
                'message' => 'Solo se puede cerrar con faltantes un pedido en estado Recepción parcial.',
            ], 422);
        }

        if ($reason === '' || mb_strlen($reason) < 4) {
            return response()->json([
                'success' => false,
                'message' => 'Debes indicar un motivo para cerrar el pedido con faltantes.',
            ], 422);
        }

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

        return DB::transaction(function () use ($order, $previousState, $shortages, $reason) {
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

            $this->logAuditAction('supplier_order_close_partial', 'Pedido a proveedor cerrado manualmente con faltantes.', [
                'order_id' => (int) $order->num_order,
                'po_number' => (string) ($order->po_number ?? ''),
                'from_state' => $previousState,
                'to_state' => (string) $order->state,
                'reason' => $reason,
                'shortages_count' => $shortages->count(),
                'shortages' => $shortages->all(),
            ]);

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

    public function receive(Order $order, array $validated): JsonResponse
    {
        $order->loadMissing('orderItems');
        $previousState = (string) $order->state;

        if (! in_array($order->state, ['confirmed', 'partial_received'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Solo se puede registrar la recepción cuando el pedido está Confirmado o en Recepción parcial.',
            ], 422);
        }

        $receivedPayload = $this->validatedReceivedPayload($order, $validated);

        return DB::transaction(function () use ($order, $receivedPayload, $previousState) {
            $totalDelta = 0;

            foreach ($order->orderItems as $item) {
                $receivedQuantity = (int) ($receivedPayload[$item->id] ?? 0);
                $previousQuantity = (int) ($item->received_quantity ?? 0);
                $delta = $receivedQuantity - $previousQuantity;

                $item->update(['received_quantity' => $receivedQuantity]);

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

                $this->inventoryService->recordSupplierEntry(
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

            return response()->json([
                'success' => true,
                'state' => $newState,
                'message' => $isPartial
                    ? 'Recepción parcial registrada. Uno o más productos tienen cantidad menor a la pedida.'
                    : 'Recepción total registrada. El pedido ahora está Entregado.',
                'received_at' => $order->fresh()->received_at?->format('d/m/Y H:i'),
            ]);
        });
    }

    private function receiveConfirmedOrderWithoutReceiveFlow(Order $order): void
    {
        $items = OrderItem::query()
            ->where('order_num_order', (int) $order->num_order)
            ->get();

        $alreadyProcessedViaReceive = $items->contains(
            fn (OrderItem $item) => $item->received_quantity !== null
        );

        foreach ($items as $item) {
            $quantity = (int) $item->quantity;

            if ($alreadyProcessedViaReceive) {
                if ((int) ($item->received_quantity ?? 0) < $quantity) {
                    $item->update(['received_quantity' => $quantity]);
                }

                continue;
            }

            if ((int) $item->product_id < 1 || $quantity < 1) {
                continue;
            }

            $product = Product::find((int) $item->product_id);

            if (! $product) {
                Log::warning('Supplier order delivery skipped missing product.', [
                    'order_id' => $order->num_order,
                    'product_id' => $item->product_id,
                    'quantity' => $quantity,
                ]);

                continue;
            }

            $this->inventoryService->recordSupplierEntry(
                product: $product,
                quantity: $quantity,
                orderId: $order->num_order,
            );

            $item->update(['received_quantity' => $quantity]);
        }
    }

    private function validatedReceivedPayload(Order $order, array $validated): array
    {
        $itemsById = $order->orderItems->keyBy('id');
        $receivedPayload = [];

        foreach ($validated['items'] as $row) {
            $itemId = (int) $row['order_item_id'];
            $receivedQuantity = (int) $row['received_quantity'];
            $item = $itemsById->get($itemId);

            if (! $item) {
                throw ValidationException::withMessages([
                    'items' => ["La línea #{$itemId} no pertenece a este pedido."],
                ]);
            }

            if ($receivedQuantity > (int) $item->quantity) {
                throw ValidationException::withMessages([
                    'items' => ["La cantidad recibida de \"{$item->name}\" ({$receivedQuantity}) supera la cantidad pedida ({$item->quantity})."],
                ]);
            }

            $previousReceived = (int) ($item->received_quantity ?? 0);

            if ($receivedQuantity < $previousReceived) {
                throw ValidationException::withMessages([
                    'items' => ["La cantidad recibida de \"{$item->name}\" no puede ser menor a la cantidad ya registrada ({$previousReceived})."],
                ]);
            }

            $receivedPayload[$itemId] = $receivedQuantity;
        }

        $missingItems = $order->orderItems->pluck('id')->diff(array_keys($receivedPayload));

        if ($missingItems->isNotEmpty()) {
            throw ValidationException::withMessages([
                'items' => ['Debes indicar la cantidad recibida para todos los productos del pedido.'],
            ]);
        }

        return $receivedPayload;
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
            Log::warning('Supplier order audit log write failed.', SensitiveDataMasker::exceptionContext($exception, [
                'action_type' => $actionType,
            ]));
        }
    }
}
