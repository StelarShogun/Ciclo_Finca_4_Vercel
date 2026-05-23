<?php

namespace App\Http\Controllers;

use App\Enums\MovementType;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Services\InventoryMovementService;
use App\Support\AdminDateRange;
use App\Support\AdminPerPage;
use Illuminate\Database\Eloquent\Builder;
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
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhereRaw("CONCAT('BK-', LPAD(product_id, 3, '0')) LIKE ?", ["%{$search}%"]);
            });
        }

        $perPage = AdminPerPage::resolve($request->input('per_page', 10));
        $products = $query->paginate($perPage)->withQueryString();

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

        $perPage = AdminPerPage::resolve($request->get('per_page', 10));
        $movements = $query->paginate($perPage)->withQueryString();

        // Reuses the filtered base query to calculate summary metrics.
        $summaryBase = $this->buildBaseQuery($productId, $request);

        $summary = [
            'total_entradas' => (clone $summaryBase)
                ->whereIn('type', [
                    MovementType::ENTRADA->value,
                    MovementType::DEVOLUCION->value,
                    MovementType::CANCELADO->value,
                ])
                ->sum('quantity'),
            'total_salidas' => (clone $summaryBase)
                ->where('type', MovementType::SALIDA->value)
                ->sum('quantity'),
        ];

        return response()->json([
            'success' => true,
            'product' => [
                'product_id' => $product->product_id,
                'name' => $product->name,
                'sku' => $product->displaySku(),
                'stock_current' => $product->stock_current,
            ],
            'data' => $movements->getCollection()->map(fn ($m) => $m instanceof InventoryMovement ? $this->formatMovement($m) : []),
            'summary' => $summary,
            'meta' => [
                'current_page' => $movements->currentPage(),
                'last_page' => $movements->lastPage(),
                'total' => $movements->total(),
                'per_page' => $movements->perPage(),
            ],
        ]);
    }

    // Builds the filtered base query for movements.
    private function buildBaseQuery(int $productId, Request $request): Builder
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

        $dateRange = AdminDateRange::resolvePresetFromRequest(
            $request->input('date_range'),
            $request->input('date_from'),
            $request->input('date_to'),
        );

        if ($dateRange === AdminDateRange::PRESET_TODAY) {
            AdminDateRange::applyDateTimeBetween($q, 'created_at', AdminDateRange::PRESET_TODAY, storedAsUtc: true);
        } else {
            AdminDateRange::applyOptionalDateTimeFromTo(
                $q,
                'created_at',
                $request->input('date_from'),
                $request->input('date_to'),
                storedAsUtc: true,
            );
        }

        return $q;
    }

    // Formats a movement record for the JSON response.
    private function formatMovement(InventoryMovement $m): array
    {
        return [
            'id' => $m->id,
            'type' => $m->type instanceof MovementType
                                    ? $m->type->value
                                    : $m->type,
            'type_label' => $m->typeLabel(),
            'type_badge' => $m->typeBadgeClass(),
            'origin' => $m->origin,
            'origin_label' => $m->originLabel(),
            'quantity' => $m->quantity,
            'stock_before' => $m->stock_before,
            'stock_after' => $m->stock_after,
            'reference_id' => $m->reference_id,
            'reason' => $m->reason,
            'admin' => $m->adminUser ? [
                'id' => $m->adminUser->user_id,
                'name' => $m->adminName(),
            ] : null,
            'created_at' => $m->created_at->toISOString(),
            'created_at_human' => $m->created_at->format('d/m/Y H:i:s'),
        ];
    }
}
