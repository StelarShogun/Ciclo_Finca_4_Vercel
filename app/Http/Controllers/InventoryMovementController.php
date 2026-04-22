<?php

namespace App\Http\Controllers;

use App\Models\InventoryMovement;
use App\Models\Product;
use Illuminate\Http\Request;

/**
 * InventoryMovementController — solo lectura.
 *
 * Este controller NUNCA modifica stock ni registra movimientos.
 * Su única responsabilidad es exponer el historial de auditoría al administrador.
 *
 * Rutas sugeridas (agregar en routes/web.php bajo el middleware 'admin'):
 *
 *   Route::get('/inventory/movements',
 *       [InventoryMovementController::class, 'index'])
 *       ->name('admin.inventory.movements.index');
 *
 *   Route::get('/inventory/movements/{product}',
 *       [InventoryMovementController::class, 'show'])
 *       ->name('admin.inventory.movements.show');
 *
 *   Route::get('/inventory/movements/{product}/json',
 *       [InventoryMovementController::class, 'json'])
 *       ->name('admin.inventory.movements.json');
 *
 * NOTA: el endpoint /json es el que consume inventory-movements.js.
 * Su respuesta incluye tanto los movimientos paginados como el resumen
 * de métricas (total_entradas / total_salidas) para las tarjetas de la vista.
 */
class InventoryMovementController extends Controller
{
    /**
     * GET /inventory/movements
     *
     * Lista todos los productos con acceso rápido a su historial.
     * Vista de entrada para que el administrador elija el producto a auditar.
     */
    public function index(Request $request)
    {
        $query = Product::query()
            ->with(['category', 'supplier'])
            ->orderByDesc('product_id');

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

    /**
     * GET /inventory/movements/{product}
     *
     * Renderiza la vista SPA-like de movimientos de un producto.
     * La vista carga los datos mediante JS (fetch → /json), por lo que
     * este método solo necesita proveer el producto y los valores únicos
     * de tipo/origen para poblar los filtros del aside antes del primer fetch.
     */
    public function show(Request $request, int $productId)
    {
        $product = Product::with(['category', 'supplier'])->findOrFail($productId);

        // Valores únicos para los botones de filtro del aside (renderizados en Blade)
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

    /**
     * GET /inventory/movements/{product}/json
     *
     * Endpoint JSON consumido por inventory-movements.js.
     * Devuelve los movimientos paginados + resumen de métricas para las tarjetas.
     *
     * El resumen respeta los filtros activos para que las tarjetas reflejen
     * siempre el subconjunto visible, no el total histórico del producto.
     */
    public function json(Request $request, int $productId)
    {
        $product = Product::findOrFail($productId);

        $query = $this->buildBaseQuery($productId, $request)
            ->with('adminUser')
            ->orderBy('created_at', 'desc');

        $perPage   = min((int) $request->get('per_page', 30), 100);
        $movements = $query->paginate($perPage)->withQueryString();

        // ── Resumen de métricas (sobre el conjunto filtrado) ─────────────
        // Reutilizamos el mismo base query (sin paginación) para el summary.
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

    /**
     * Construye el query base de movimientos con los filtros del request aplicados.
     * Usado tanto para la paginación como para el resumen de métricas.
     */
    private function buildBaseQuery(int $productId, Request $request): \Illuminate\Database\Eloquent\Builder
    {
        $q = InventoryMovement::where('product_id', $productId);

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

    /**
     * Formatea un InventoryMovement para la respuesta JSON.
     * Usado por json().
     */
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
            'notes'        => $m->notes,
            'admin'        => $m->adminUser ? [
                'id'   => $m->adminUser->user_id,
                'name' => $m->adminName(),
            ] : null,
            'created_at'       => $m->created_at->toISOString(),
            'created_at_human' => $m->created_at->format('d/m/Y H:i:s'),
        ];
    }}