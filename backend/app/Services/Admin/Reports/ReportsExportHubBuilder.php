<?php

namespace App\Services\Admin\Reports;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Order;
use App\Models\Supplier;
use App\Services\Admin\AdminBrandsCatalogExportQuery;
use App\Services\Admin\AdminClientOrdersExportQuery;
use App\Services\Admin\AdminInventoryExportQuery;
use App\Services\Admin\AdminSupplierOrdersExportQuery;
use App\Services\Admin\AdminSuppliersCatalogExportQuery;
use Illuminate\Http\Request;

final class ReportsExportHubBuilder
{
    public function build(Request $request): array
    {
        $parentCategories = Category::whereNull('parent_category_id')
            ->orderBy('name')
            ->get(['category_id', 'name'])
            ->unique(fn ($category) => mb_strtolower(trim($category->name)))
            ->values();

        $subcatOptionsMap = [];
        foreach (Category::subcategoriesGroupedByCanonicalParent() as $parentId => $subcats) {
            $subcatOptionsMap[(string) $parentId] = array_merge(
                [['value' => '', 'label' => 'Todas']],
                collect($subcats)->map(fn ($subcategory) => [
                    'value' => (string) $subcategory['category_id'],
                    'label' => $subcategory['name'],
                ])->all(),
            );
        }

        $suppliers = Supplier::orderBy('name')
            ->get(['supplier_id', 'name', 'primary_contact', 'phone', 'email']);
        $brands = Brand::orderBy('name')->get(['id', 'name']);

        $supplierOptions = array_merge(
            [['value' => '', 'label' => 'Todos']],
            $suppliers->map(fn (Supplier $supplier) => [
                'value' => $supplier->name,
                'label' => $supplier->name,
            ])->all(),
        );

        return [
            'exports' => [
                'dashboard' => [
                    'id' => 'dashboard',
                    'title' => 'Dashboard',
                    'subtitle' => 'Exporta el PDF del dashboard para un periodo.',
                    'formatMode' => 'query',
                    'baseUrls' => ['pdf' => route('dashboard.export')],
                    'filters' => [[
                        'name' => 'period',
                        'label' => 'Periodo',
                        'type' => 'select',
                        'default' => '30d',
                        'options' => [
                            ['value' => '7d', 'label' => '7 días'],
                            ['value' => '30d', 'label' => '30 días'],
                            ['value' => '90d', 'label' => '90 días'],
                        ],
                    ]],
                    'staticParams' => [],
                ],
                'inventory' => [
                    'id' => 'inventory',
                    'title' => 'Inventario',
                    'subtitle' => 'Exporta inventario en paquete ZIP (con imágenes), JSON, PDF, Excel o XML.',
                    'formatMode' => 'path',
                    'baseUrls' => [
                        'bundle' => route('products.export', ['format' => 'bundle']),
                        'json' => route('products.export', ['format' => 'json']),
                        'pdf' => route('products.export', ['format' => 'pdf']),
                        'excel' => route('products.export', ['format' => 'excel']),
                        'xml' => route('products.export', ['format' => 'xml']),
                    ],
                    'initialValues' => $request->only(AdminInventoryExportQuery::QUERY_KEYS),
                    'filters' => [
                        ['name' => 'search', 'label' => 'Búsqueda', 'type' => 'text', 'placeholder' => 'Nombre o descripción'],
                        [
                            'name' => 'parent_category_id',
                            'label' => 'Categoría',
                            'type' => 'select',
                            'options' => $this->categoryOptions($parentCategories),
                            'cascades' => 'subcategory_id',
                            'cascadeOptions' => $subcatOptionsMap,
                        ],
                        ['name' => 'subcategory_id', 'label' => 'Subcategoría', 'type' => 'select', 'options' => [['value' => '', 'label' => 'Todas']]],
                        ['name' => 'stock_status', 'label' => 'Stock', 'type' => 'select', 'options' => [
                            ['value' => '', 'label' => 'Todos'],
                            ['value' => 'in-stock', 'label' => 'En stock'],
                            ['value' => 'low', 'label' => 'Stock bajo'],
                            ['value' => 'out', 'label' => 'Agotado'],
                        ]],
                        ['name' => 'status', 'label' => 'Estado', 'type' => 'select', 'options' => [
                            ['value' => '', 'label' => 'Todos'],
                            ['value' => 'active', 'label' => 'Activo'],
                            ['value' => 'inactive', 'label' => 'Inactivo'],
                        ]],
                    ],
                ],
                'sales' => [
                    'id' => 'sales',
                    'title' => 'Ventas',
                    'subtitle' => 'Exporta ventas en PDF o Excel.',
                    'formatMode' => 'query',
                    'baseUrls' => ['pdf' => route('sales.export'), 'excel' => route('sales.export')],
                    'staticParams' => [],
                    'initialValues' => array_merge(
                        ['status' => 'completed', 'date_range' => 'month'],
                        $request->only(['status', 'date_range', 'date_from', 'date_to', 'payment_method', 'search']),
                    ),
                    'filters' => [
                        ['name' => 'status', 'label' => 'Estado', 'type' => 'select', 'options' => [
                            ['value' => 'completed', 'label' => 'Confirmadas'],
                            ['value' => 'cancelled', 'label' => 'Canceladas'],
                            ['value' => 'refunded', 'label' => 'Reembolsadas'],
                            ['value' => 'all', 'label' => 'Todas'],
                        ]],
                        ['name' => 'date_range', 'label' => 'Rango de fecha', 'type' => 'select', 'options' => [
                            ['value' => 'today', 'label' => 'Hoy'],
                            ['value' => 'week', 'label' => 'Esta semana'],
                            ['value' => 'month', 'label' => 'Este mes'],
                            ['value' => 'custom', 'label' => 'Por fechas'],
                        ]],
                        ['name' => 'date_from', 'label' => 'Desde', 'type' => 'date'],
                        ['name' => 'date_to', 'label' => 'Hasta', 'type' => 'date'],
                        ['name' => 'payment_method', 'label' => 'Método de pago', 'type' => 'text', 'placeholder' => 'efectivo, tarjeta, ...'],
                        ['name' => 'search', 'label' => 'Buscar', 'type' => 'text', 'placeholder' => 'Factura, cliente, ...'],
                    ],
                ],
                'productSales' => [
                    'id' => 'productSales',
                    'title' => 'Productos más vendidos',
                    'subtitle' => 'Exporta el reporte de productos más vendidos.',
                    'formatMode' => 'query',
                    'baseUrls' => [
                        'pdf' => route('admin.reports.product-sales.pdf'),
                        'excel' => route('admin.reports.product-sales.excel'),
                    ],
                    'initialValues' => array_merge(
                        ['period' => '30d', 'sort' => 'revenue', 'dir' => 'desc', 'top10' => 'revenue'],
                        $request->only(['period', 'sort', 'dir', 'q', 'top10']),
                    ),
                    'filters' => [
                        ['name' => 'period', 'label' => 'Periodo', 'type' => 'select', 'options' => [
                            ['value' => '7d', 'label' => '7 días'],
                            ['value' => '30d', 'label' => '30 días'],
                            ['value' => '90d', 'label' => '90 días'],
                        ]],
                        ['name' => 'sort', 'label' => 'Ordenar por', 'type' => 'select', 'options' => [
                            ['value' => 'revenue', 'label' => 'Ingresos'],
                            ['value' => 'units', 'label' => 'Unidades'],
                        ]],
                        ['name' => 'dir', 'label' => 'Dirección', 'type' => 'select', 'options' => [
                            ['value' => 'desc', 'label' => 'Descendente'],
                            ['value' => 'asc', 'label' => 'Ascendente'],
                        ]],
                        ['name' => 'q', 'label' => 'Buscar', 'type' => 'text', 'placeholder' => 'Nombre o código'],
                        ['name' => 'top10', 'label' => 'Top 10 por', 'type' => 'select', 'options' => [
                            ['value' => 'revenue', 'label' => 'Ingresos'],
                            ['value' => 'units', 'label' => 'Unidades'],
                        ]],
                    ],
                ],
                'registry.suppliers' => $this->registryExport(
                    'registry.suppliers',
                    'Proveedores',
                    'Listado administrativo de proveedores.',
                    'proveedores',
                    $request->only(AdminSuppliersCatalogExportQuery::QUERY_KEYS),
                    [
                        ['name' => 'name', 'label' => 'Proveedor', 'type' => 'select', 'options' => $supplierOptions, 'autofills' => ['contact'], 'autofillData' => $this->supplierAutofill($suppliers)],
                        ['name' => 'contact', 'label' => 'Contacto', 'type' => 'text', 'placeholder' => 'Se completa al elegir proveedor', 'readonly' => true],
                    ],
                ),
                'registry.brands' => $this->registryExport(
                    'registry.brands',
                    'Marcas',
                    'Listado administrativo de marcas.',
                    'marcas',
                    $request->only(AdminBrandsCatalogExportQuery::QUERY_KEYS),
                    [['name' => 'name', 'label' => 'Marca', 'type' => 'select', 'options' => $this->brandOptions($brands)]],
                ),
                'registry.supplierOrders' => $this->registryExport(
                    'registry.supplierOrders',
                    'Pedidos a proveedores',
                    'Listado de pedidos a proveedores.',
                    'pedidos-proveedores',
                    $request->only(AdminSupplierOrdersExportQuery::QUERY_KEYS),
                    [
                        ['name' => 'state', 'label' => 'Estado', 'type' => 'select', 'options' => $this->orderStateOptions()],
                        ['name' => 'search', 'label' => 'Buscar', 'type' => 'text', 'placeholder' => 'Proveedor, número de pedido, ...'],
                        ['name' => 'date_from', 'label' => 'Desde', 'type' => 'date'],
                        ['name' => 'date_to', 'label' => 'Hasta', 'type' => 'date'],
                    ],
                ),
                'registry.users' => $this->registryExport('registry.users', 'Usuarios', 'Listado de usuarios/clientes.', 'usuarios'),
                'registry.clientOrders' => $this->registryExport(
                    'registry.clientOrders',
                    'Encargos',
                    'Listado de pedidos de clientes (encargos).',
                    'pedidos-clientes',
                    $request->only(AdminClientOrdersExportQuery::QUERY_KEYS),
                    [
                        ['name' => 'status', 'label' => 'Estado', 'type' => 'select', 'options' => [
                            ['value' => '', 'label' => 'Todos'],
                            ['value' => 'pending', 'label' => 'Pendiente'],
                            ['value' => 'confirmed', 'label' => 'Confirmado'],
                            ['value' => 'completed', 'label' => 'Completado'],
                            ['value' => 'cancelled', 'label' => 'Cancelado'],
                        ]],
                        ['name' => 'search', 'label' => 'Buscar', 'type' => 'text', 'placeholder' => 'Nombre, correo, ...'],
                    ],
                ),
            ],
        ];
    }

    private function categoryOptions($categories): array
    {
        return array_merge(
            [['value' => '', 'label' => 'Todas']],
            $categories->map(fn (Category $category) => [
                'value' => (string) $category->category_id,
                'label' => $category->name,
            ])->all(),
        );
    }

    private function brandOptions($brands): array
    {
        return array_merge(
            [['value' => '', 'label' => 'Todas']],
            $brands->map(fn (Brand $brand) => [
                'value' => $brand->name,
                'label' => $brand->name,
            ])->all(),
        );
    }

    private function orderStateOptions(): array
    {
        return array_merge(
            [['value' => '', 'label' => 'Todos']],
            collect(Order::STATE_LABELS)
                ->map(fn ($label, $key) => ['value' => $key, 'label' => $label])
                ->values()
                ->all(),
        );
    }

    private function supplierAutofill($suppliers): array
    {
        $data = [];
        foreach ($suppliers as $supplier) {
            $data[$supplier->name] = ['contact' => $supplier->primary_contact ?? ''];
        }

        return $data;
    }

    private function registryExport(string $id, string $title, string $subtitle, string $slug, array $initialValues = [], array $filters = []): array
    {
        return [
            'id' => $id,
            'title' => $title,
            'subtitle' => $subtitle,
            'formatMode' => 'query',
            'baseUrls' => [
                'pdf' => route('admin.reports.exports.registry', ['slug' => $slug]),
                'excel' => route('admin.reports.exports.registry', ['slug' => $slug]),
                'csv' => route('admin.reports.exports.registry', ['slug' => $slug]),
            ],
            'initialValues' => $initialValues,
            'filters' => $filters,
        ];
    }
}
