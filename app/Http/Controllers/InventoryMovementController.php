<?php

namespace App\Http\Controllers;

use App\Enums\MovementType;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Services\InventoryMovementService;
use Illuminate\Http\Request;

// Read-only controller for inventory movement history.
class InventoryMovementController extends Controller
{
    // Lists products with quick access to their movement history.
    public function index(Request $request)
    {
        $query = Product::query()
            ->with(['category', 'supplier'])
            ->orderByDesc('product_id');

        // Applies product search by name or formatted SKU.
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhereRaw("CONCAT('BK-', LPAD(product_id, 3, '0')) LIKE ?", ["%{$search}%"]);
            });
        }

        $products = $query->paginate(20)->withQueryString();

        return view('admin.reports.movements.index', compact('products'));
    }

    // Renders the movement history view for a specific product.
    public function show(Request $request, int $productId)
    {
        $product = Product::with(['category', 'supplier'])->findOrFail($productId);

        // Exposes all enum cases for the type filter — consistent regardless
        // of what movements exist for this product.
        $availableTypes = MovementType::cases();

        // Loads distinct origins restricted to currently valid origins,
        // filtering out legacy values like 'damage' or 'refund'.
        $availableOrigins = InventoryMovement::where('product_id', $productId)
            ->distinct()
            ->pluck('origin')
            ->filter(fn ($o) => in_array($o, InventoryMovementService::VALID_ORIGINS, true))
            ->sort()
            ->values();

        return view('admin.reports.movements.show', compact(
            'product',
            'availableTypes',
            'availableOrigins',
        ));
    }

    // Returns paginated movement data and summary metrics as JSON.
    public function json(Request $request, int $productId)
    {
        $product = Product::findOrFail($productId);

        $query = $this->buildBaseQuery($productId, $request)
            ->with('adminUser')
            ->orderBy('created_at', 'desc');

        $perPage   = min((int) $request->get('per_page', 30), 100);
        $movements = $query->paginate($perPage)->withQueryString();

        // Reuses the filtered base query to calculate summary metrics.
        $summaryBase = $this->buildBaseQuery($productId, $request);

        $summary = [
            'total_entradas' => (clone $summaryBase)
                ->whereIn('type', [
                    MovementType::ENTRADA->value,
                    MovementType::DEVOLUCION->value,
                ])
                ->sum('quantity'),
            'total_salidas'  => (clone $summaryBase)
                ->where('type', MovementType::SALIDA->value)
                ->sum('quantity'),
        ];

        return response()->json([
            'success' => true,
            'product' => [
                'product_id'    => $product->product_id,
                'name'          => $product->name,
                'sku'           => Product::skuFromId((int) $product->product_id),
                'stock_current' => $product->stock_current,
            ],
            'data'    => $movements->map(fn ($m) => $this->formatMovement($m)),
            'summary' => $summary,
            'meta'    => [
                'current_page' => $movements->currentPage(),
                'last_page'    => $movements->lastPage(),
                'total'        => $movements->total(),
                'per_page'     => $movements->perPage(),
            ],
        ]);
    }

    // Builds the filtered base query for movements.
    private function buildBaseQuery(int $productId, Request $request): \Illuminate\Database\Eloquent\Builder
    {
        $q = InventoryMovement::where('product_id', $productId);

        // Applies optional type filter validated against the enum.
        if ($request->filled('type')) {
            $validTypes = array_column(MovementType::cases(), 'value');
            if (in_array($request->type, $validTypes, true)) {
                $q->where('type', $request->type);
            }
        }

        // Applies optional origin filter restricted to valid origins.
        if ($request->filled('origin')) {
            if (in_array($request->origin, InventoryMovementService::VALID_ORIGINS, true)) {
                $q->where('origin', $request->origin);
            }
        }

        if ($request->filled('date_from')) {
            $q->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $q->whereDate('created_at', '<=', $request->date_to);
        }

        return $q;
    }

    // Formats a movement record for the JSON response.
    private function formatMovement(InventoryMovement $m): array
    {
        return [
            'id'               => $m->id,
            'type'             => $m->type instanceof MovementType
                                    ? $m->type->value
                                    : $m->type,
            'type_label'       => $m->typeLabel(),
            'type_badge'       => $m->typeBadgeClass(),
            'origin'           => $m->origin,
            'origin_label'     => $m->originLabel(),
            'quantity'         => $m->quantity,
            'stock_before'     => $m->stock_before,
            'stock_after'      => $m->stock_after,
            'reference_id'     => $m->reference_id,
            'reason'           => $m->reason,   // ← columna renombrada de notes a reason
            'admin'            => $m->adminUser ? [
                'id'   => $m->adminUser->user_id,
                'name' => $m->adminName(),
            ] : null,
            'created_at'       => $m->created_at->toISOString(),
            'created_at_human' => $m->created_at->format('d/m/Y H:i:s'),
        ];
    }
}