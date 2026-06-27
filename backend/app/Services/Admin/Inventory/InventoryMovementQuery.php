<?php

namespace App\Services\Admin\Inventory;

use App\Enums\Inventory\MovementType;
use App\Http\Resources\Admin\InventoryMovementResource;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Services\Client\Inertia\ListPaginationPayload;
use App\Support\AdminDateRange;
use App\Support\AdminPerPage;
use Illuminate\Database\Eloquent\Builder;

final class InventoryMovementQuery
{
    private const STOCK_LABELS = [
        'success' => 'Normal',
        'warning' => 'Bajo',
        'danger' => 'Sin stock',
    ];

    /**
     * @param  array<string,mixed>  $filters
     * @return array<string,mixed>
     */
    public function indexPayload(array $filters): array
    {
        $query = Product::query()
            ->with(['category', 'supplier'])
            ->orderByDesc('product_id');

        $search = (string) ($filters['search'] ?? '');
        if ($search !== '') {
            $query->where(function (Builder $query) use ($search): void {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhereRaw("CONCAT('BK-', LPAD(product_id, 3, '0')) LIKE ?", ["%{$search}%"]);
            });
        }

        $products = $query
            ->paginate(AdminPerPage::resolve($filters['per_page'] ?? 10))
            ->withQueryString();

        return [
            'products' => collect($products->items())
                ->map(fn (Product $product): array => $this->productRow($product))
                ->values()
                ->all(),
            'pagination' => ListPaginationPayload::from($products),
            'filters' => ['search' => $search],
        ];
    }

    /**
     * @return array<string,mixed>
     */
    public function showPayload(Product $product): array
    {
        $origins = InventoryMovement::query()
            ->where('product_id', $product->product_id)
            ->distinct()
            ->pluck('origin')
            ->filter(fn ($origin) => in_array($origin, InventoryMovementService::VALID_ORIGINS, true))
            ->sort()
            ->values();

        return [
            'product' => [
                'product_id' => (int) $product->product_id,
                'name' => $product->name,
                'sku' => $product->displaySku(),
                'category_name' => $product->category?->name ?? '—',
                'supplier_name' => $product->supplier?->name,
                'stock_current' => (int) $product->stock_current,
            ],
            'availableTypes' => collect(MovementType::cases())
                ->map(fn (MovementType $type): array => [
                    'value' => $type->value,
                    'label' => $type->label(),
                ])
                ->values()
                ->all(),
            'availableOrigins' => $origins
                ->map(fn ($origin): array => [
                    'value' => $origin,
                    'label' => $this->originLabel((string) $origin),
                ])
                ->values()
                ->all(),
            'jsonUrl' => route('admin.inventory.movements.json', $product->product_id),
        ];
    }

    /**
     * @param  array<string,mixed>  $filters
     * @return array<string,mixed>
     */
    public function jsonPayload(Product $product, array $filters): array
    {
        $movements = $this->filteredMovementQuery($product->product_id, $filters)
            ->with('adminUser')
            ->orderByDesc('created_at')
            ->paginate(AdminPerPage::resolve($filters['per_page'] ?? 10))
            ->withQueryString();

        $summaryBase = $this->filteredMovementQuery($product->product_id, $filters);

        return [
            'success' => true,
            'product' => [
                'product_id' => $product->product_id,
                'name' => $product->name,
                'sku' => $product->displaySku(),
                'stock_current' => $product->stock_current,
            ],
            'data' => InventoryMovementResource::collection($movements->getCollection())->resolve(),
            'summary' => [
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
            ],
            'meta' => [
                'current_page' => $movements->currentPage(),
                'last_page' => $movements->lastPage(),
                'total' => $movements->total(),
                'per_page' => $movements->perPage(),
            ],
        ];
    }

    /**
     * @param  array<string,mixed>  $filters
     * @return Builder<InventoryMovement>
     */
    private function filteredMovementQuery(int $productId, array $filters): Builder
    {
        $query = InventoryMovement::query()->where('product_id', $productId);

        if (($filters['type'] ?? null) !== null) {
            $query->where('type', $filters['type']);
        }

        if (($filters['origin'] ?? null) !== null) {
            $query->where('origin', $filters['origin']);
        }

        $dateRange = AdminDateRange::resolvePresetFromRequest(
            $filters['date_range'] ?? null,
            $filters['date_from'] ?? null,
            $filters['date_to'] ?? null,
        );

        if ($dateRange === AdminDateRange::PRESET_TODAY) {
            AdminDateRange::applyDateTimeBetween($query, 'created_at', AdminDateRange::PRESET_TODAY, storedAsUtc: true);

            return $query;
        }

        AdminDateRange::applyOptionalDateTimeFromTo(
            $query,
            'created_at',
            $filters['date_from'] ?? null,
            $filters['date_to'] ?? null,
            storedAsUtc: true,
        );

        return $query;
    }

    private function productRow(Product $product): array
    {
        $badge = $product->adminInventoryStockBadgeClass();

        return [
            'product_id' => (int) $product->product_id,
            'sku' => Product::skuFromId($product->product_id),
            'name' => $product->name,
            'category_name' => $product->category?->name ?? '—',
            'supplier_name' => $product->supplier?->name,
            'stock_badge_class' => $badge,
            'stock_label' => self::STOCK_LABELS[$badge] ?? 'Revisar',
            'stock_current' => (int) $product->stock_current,
        ];
    }

    private function originLabel(string $origin): string
    {
        return match ($origin) {
            'sale_admin' => 'Venta (admin)',
            'sale_web' => 'Venta web',
            'return' => 'Devolución de venta',
            'cancellation' => 'Cancelación de encargo',
            'provider' => 'Entrada de proveedor',
            'manual_adjustment' => 'Ajuste manual',
            default => ucwords(str_replace('_', ' ', $origin)),
        };
    }
}
