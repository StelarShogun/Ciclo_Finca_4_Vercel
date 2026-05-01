<?php

namespace App\Http\Controllers;

use App\Models\InventoryMovement;
use App\Models\Product;
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

        // Loads distinct filter values for the sidebar.
        $availableTypes   = InventoryMovement::where('product_id', $productId)
                                ->distinct()->pluck('type')->sort()->values();
        $availableOrigins = InventoryMovement::where('product_id', $productId)
                                ->distinct()->pluck('origin')->filter()->sort()->values();

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
                ->whereIn('type', ['entrada', 'devolucion'])
                ->sum('quantity'),
            'total_salidas'  => (clone $summaryBase)
                ->where('type', 'salida')
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

        // Applies optional filters from the request.
        if ($request->filled('type')) {
            $q->where('type', $request->type);
        }
        if ($request->filled('origin')) {
            $q->where('origin', $request->origin);
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
            'id'           => $m->id,
            'type'         => $m->type instanceof \App\Enums\MovementType
                                ? $m->type->value
                                : $m->type,
            'type_label'   => $m->typeLabel(),
            'type_badge'   => $m->typeBadgeClass(),
            'origin'       => $m->origin,
            'origin_label' => $m->originLabel(),
            'quantity'     => $m->quantity,
            'stock_before' => $m->stock_before,
            'stock_after'  => $m->stock_after,
            'reference_id' => $m->reference_id,
            'reason'       => $m->reason,
            'admin'        => $m->adminUser ? [
                'id'   => $m->adminUser->user_id,
                'name' => $m->adminName(),
            ] : null,
            'created_at'       => $m->created_at->toISOString(),
            'created_at_human' => $m->created_at->format('d/m/Y H:i:s'),
        ];
    }
}