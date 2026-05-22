@extends('admin.layouts.admin-shell')

@section('Titulo pagina', 'Exportar datos - Reportes')

@push('styles')
    @vite(['resources/css/admin/shell-base.css', 'resources/css/admin/reports/reports-hub.css', 'resources/css/admin/reports/exports.css'])
@endpush

@push('scripts')
    @vite(['resources/js/admin/shell.js', 'resources/js/admin/reports/exports-modal.js'])
@endpush

@section('aside')
    @include('admin.parts.aside')
@endsection

@section('contenido')
    @php
        // ── Opciones dinámicas cargadas desde el controlador ─────────────────────
        // $parentCategories, $subcatsByParent, $suppliers, $brands

        // Construir opciones de categorías padre
        $parentCatOptions = array_merge(
            [['value' => '', 'label' => 'Todas']],
            $parentCategories
                ->map(
                    fn($c) => [
                        'value' => (string) $c->category_id,
                        'label' => $c->name,
                    ],
                )
                ->all(),
        );

        // Construir mapa de subcategorías: { parentId: [{value, label}, ...] }
        $subcatOptionsMap = [];
        foreach ($subcatsByParent as $parentId => $subcats) {
            $subcatOptionsMap[(string) $parentId] = array_merge(
                [['value' => '', 'label' => 'Todas']],
                collect($subcats)
                    ->map(
                        fn($s) => [
                            'value' => (string) $s['category_id'],
                            'label' => $s['name'],
                        ],
                    )
                    ->all(),
            );
        }

        // Opciones de proveedores para el filtro de selección
        $supplierOptions = array_merge(
            [['value' => '', 'label' => 'Todos']],
            $suppliers
                ->map(
                    fn($s) => [
                        'value' => $s->name,
                        'label' => $s->name,
                    ],
                )
                ->all(),
        );

        // Datos de autorrelleno por nombre de proveedor: { name: {contact, phone, email} }
        $supplierAutofillData = [];
        foreach ($suppliers as $s) {
            $supplierAutofillData[$s->name] = [
                'contact' => $s->primary_contact ?? '',
            ];
        }

        // Estados de pedidos a proveedores
        $orderStateOptions = array_merge(
            [['value' => '', 'label' => 'Todos']],
            collect(\App\Models\Order::STATE_LABELS)
                ->map(
                    fn($label, $key) => [
                        'value' => $key,
                        'label' => $label,
                    ],
                )
                ->values()
                ->all(),
        );

        // Marcas
        $brandOptions = array_merge(
            [['value' => '', 'label' => 'Todas']],
            $brands
                ->map(
                    fn($b) => [
                        'value' => $b->name,
                        'label' => $b->name,
                    ],
                )
                ->all(),
        );

        $exportsConfig = [
            'exports' => [
                'dashboard' => [
                    'id' => 'dashboard',
                    'title' => 'Dashboard',
                    'subtitle' => 'Exporta el PDF del dashboard para un periodo.',
                    'formatMode' => 'query',
                    'baseUrls' => [
                        'pdf' => route('dashboard.export'),
                    ],
                    'filters' => [
                        [
                            'name' => 'period',
                            'label' => 'Periodo',
                            'type' => 'select',
                            'default' => '30d',
                            'options' => [
                                ['value' => '7d', 'label' => '7 días'],
                                ['value' => '30d', 'label' => '30 días'],
                                ['value' => '90d', 'label' => '90 días'],
                            ],
                        ],
                    ],
                    'staticParams' => [],
                ],
                'inventory' => [
                    'id' => 'inventory',
                    'title' => 'Inventario',
                    'subtitle' => 'Exporta inventario en PDF, Excel o XML.',
                    'formatMode' => 'path',
                    'baseUrls' => [
                        'pdf' => route('products.export', ['format' => 'pdf']),
                        'excel' => route('products.export', ['format' => 'excel']),
                        'xml' => route('products.export', ['format' => 'xml']),
                    ],
                    'initialValues' => request()->only(\App\Services\Admin\AdminInventoryExportQuery::QUERY_KEYS),
                    'filters' => [
                        [
                            'name' => 'search',
                            'label' => 'Búsqueda',
                            'type' => 'text',
                            'placeholder' => 'Nombre o descripción',
                        ],
                        [
                            'name' => 'parent_category_id',
                            'label' => 'Categoría',
                            'type' => 'select',
                            'options' => $parentCatOptions,
                            'cascades' => 'subcategory_id',
                            'cascadeOptions' => $subcatOptionsMap,
                        ],
                        [
                            'name' => 'subcategory_id',
                            'label' => 'Subcategoría',
                            'type' => 'select',
                            'options' => [['value' => '', 'label' => 'Todas']],
                        ],
                        [
                            'name' => 'stock_status',
                            'label' => 'Stock',
                            'type' => 'select',
                            'options' => [
                                ['value' => '', 'label' => 'Todos'],
                                ['value' => 'in-stock', 'label' => 'En stock'],
                                ['value' => 'low', 'label' => 'Stock bajo'],
                                ['value' => 'out', 'label' => 'Agotado'],
                            ],
                        ],
                        [
                            'name' => 'status',
                            'label' => 'Estado',
                            'type' => 'select',
                            'options' => [
                                ['value' => '', 'label' => 'Todos'],
                                ['value' => 'active', 'label' => 'Activo'],
                                ['value' => 'inactive', 'label' => 'Inactivo'],
                            ],
                        ],
                    ],
                ],
                'sales' => [
                    'id' => 'sales',
                    'title' => 'Ventas',
                    'subtitle' => 'Exporta ventas en PDF o Excel.',
                    'formatMode' => 'query',
                    'baseUrls' => [
                        'pdf' => route('sales.export'),
                        'excel' => route('sales.export'),
                    ],
                    'staticParams' => [],
                    'initialValues' => array_merge(
                        ['status' => 'completed', 'date_range' => 'month'],
                        request()->only(['status', 'date_range', 'date_from', 'date_to', 'payment_method', 'search']),
                    ),
                    'filters' => [
                        [
                            'name' => 'status',
                            'label' => 'Estado',
                            'type' => 'select',
                            'options' => [
                                ['value' => 'completed', 'label' => 'Confirmadas'],
                                ['value' => 'cancelled', 'label' => 'Canceladas'],
                                ['value' => 'refunded', 'label' => 'Reembolsadas'],
                                ['value' => 'all', 'label' => 'Todas'],
                            ],
                        ],
                        [
                            'name' => 'date_range',
                            'label' => 'Rango de fecha',
                            'type' => 'select',
                            'options' => [
                                ['value' => 'today', 'label' => 'Hoy'],
                                ['value' => 'week', 'label' => 'Esta semana'],
                                ['value' => 'month', 'label' => 'Este mes'],
                                ['value' => 'custom', 'label' => 'Por fechas'],
                            ],
                        ],
                        ['name' => 'date_from', 'label' => 'Desde', 'type' => 'date'],
                        ['name' => 'date_to', 'label' => 'Hasta', 'type' => 'date'],
                        [
                            'name' => 'payment_method',
                            'label' => 'Método de pago',
                            'type' => 'text',
                            'placeholder' => 'efectivo, tarjeta, ...',
                        ],
                        [
                            'name' => 'search',
                            'label' => 'Buscar',
                            'type' => 'text',
                            'placeholder' => 'Factura, cliente, ...',
                        ],
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
                        [
                            'period' => '30d',
                            'sort' => 'revenue',
                            'dir' => 'desc',
                            'top10' => 'revenue',
                        ],
                        request()->only(['period', 'sort', 'dir', 'q', 'top10']),
                    ),
                    'filters' => [
                        [
                            'name' => 'period',
                            'label' => 'Periodo',
                            'type' => 'select',
                            'options' => [
                                ['value' => '7d', 'label' => '7 días'],
                                ['value' => '30d', 'label' => '30 días'],
                                ['value' => '90d', 'label' => '90 días'],
                            ],
                        ],
                        [
                            'name' => 'sort',
                            'label' => 'Ordenar por',
                            'type' => 'select',
                            'options' => [
                                ['value' => 'revenue', 'label' => 'Ingresos'],
                                ['value' => 'units', 'label' => 'Unidades'],
                            ],
                        ],
                        [
                            'name' => 'dir',
                            'label' => 'Dirección',
                            'type' => 'select',
                            'options' => [
                                ['value' => 'desc', 'label' => 'Descendente'],
                                ['value' => 'asc', 'label' => 'Ascendente'],
                            ],
                        ],
                        ['name' => 'q', 'label' => 'Buscar', 'type' => 'text', 'placeholder' => 'Nombre o código'],
                        [
                            'name' => 'top10',
                            'label' => 'Top 10 por',
                            'type' => 'select',
                            'options' => [
                                ['value' => 'revenue', 'label' => 'Ingresos'],
                                ['value' => 'units', 'label' => 'Unidades'],
                            ],
                        ],
                    ],
                ],
                'registry.suppliers' => [
                    'id' => 'registry.suppliers',
                    'title' => 'Proveedores',
                    'subtitle' => 'Listado administrativo de proveedores.',
                    'formatMode' => 'query',
                    'baseUrls' => [
                        'pdf' => route('admin.reports.exports.registry', ['slug' => 'proveedores']),
                        'excel' => route('admin.reports.exports.registry', ['slug' => 'proveedores']),
                    ],
                    'initialValues' => request()->only(
                        \App\Services\Admin\AdminSuppliersCatalogExportQuery::QUERY_KEYS,
                    ),
                    'filters' => [
                        [
                            'name' => 'name',
                            'label' => 'Proveedor',
                            'type' => 'select',
                            'options' => $supplierOptions,
                            'autofills' => ['contact'],
                            'autofillData' => $supplierAutofillData,
                        ],
                        [
                            'name' => 'contact',
                            'label' => 'Contacto',
                            'type' => 'text',
                            'placeholder' => 'Se completa al elegir proveedor',
                            'readonly' => true,
                        ],
                    ],
                ],
                'registry.brands' => [
                    'id' => 'registry.brands',
                    'title' => 'Marcas',
                    'subtitle' => 'Listado administrativo de marcas.',
                    'formatMode' => 'query',
                    'baseUrls' => [
                        'pdf' => route('admin.reports.exports.registry', ['slug' => 'marcas']),
                        'excel' => route('admin.reports.exports.registry', ['slug' => 'marcas']),
                    ],
                    'initialValues' => request()->only(\App\Services\Admin\AdminBrandsCatalogExportQuery::QUERY_KEYS),
                    'filters' => [
                        [
                            'name' => 'name',
                            'label' => 'Marca',
                            'type' => 'select',
                            'options' => $brandOptions,
                        ],
                    ],
                ],
                'registry.supplierOrders' => [
                    'id' => 'registry.supplierOrders',
                    'title' => 'Pedidos a proveedores',
                    'subtitle' => 'Listado de pedidos a proveedores.',
                    'formatMode' => 'query',
                    'baseUrls' => [
                        'pdf' => route('admin.reports.exports.registry', ['slug' => 'pedidos-proveedores']),
                        'excel' => route('admin.reports.exports.registry', ['slug' => 'pedidos-proveedores']),
                    ],
                    'initialValues' => request()->only(\App\Services\Admin\AdminSupplierOrdersExportQuery::QUERY_KEYS),
                    'filters' => [
                        [
                            'name' => 'state',
                            'label' => 'Estado',
                            'type' => 'select',
                            'options' => $orderStateOptions,
                        ],
                        [
                            'name' => 'search',
                            'label' => 'Buscar',
                            'type' => 'text',
                            'placeholder' => 'Proveedor, número de pedido, ...',
                        ],
                        ['name' => 'date_from', 'label' => 'Desde', 'type' => 'date'],
                        ['name' => 'date_to', 'label' => 'Hasta', 'type' => 'date'],
                    ],
                ],
                'registry.users' => [
                    'id' => 'registry.users',
                    'title' => 'Usuarios',
                    'subtitle' => 'Listado de usuarios/clientes.',
                    'formatMode' => 'query',
                    'baseUrls' => [
                        'pdf' => route('admin.reports.exports.registry', ['slug' => 'usuarios']),
                        'excel' => route('admin.reports.exports.registry', ['slug' => 'usuarios']),
                    ],
                    'initialValues' => [],
                    'filters' => [],
                ],
                'registry.clientOrders' => [
                    'id' => 'registry.clientOrders',
                    'title' => 'Encargos',
                    'subtitle' => 'Listado de pedidos de clientes (encargos).',
                    'formatMode' => 'query',
                    'baseUrls' => [
                        'pdf' => route('admin.reports.exports.registry', ['slug' => 'pedidos-clientes']),
                        'excel' => route('admin.reports.exports.registry', ['slug' => 'pedidos-clientes']),
                    ],
                    'initialValues' => request()->only(\App\Services\Admin\AdminClientOrdersExportQuery::QUERY_KEYS),
                    'filters' => [
                        [
                            'name' => 'status',
                            'label' => 'Estado',
                            'type' => 'select',
                            'options' => [
                                ['value' => '', 'label' => 'Todos'],
                                ['value' => 'pending', 'label' => 'Pendiente'],
                                ['value' => 'confirmed', 'label' => 'Confirmado'],
                                ['value' => 'completed', 'label' => 'Completado'],
                                ['value' => 'cancelled', 'label' => 'Cancelado'],
                            ],
                        ],
                        [
                            'name' => 'search',
                            'label' => 'Buscar',
                            'type' => 'text',
                            'placeholder' => 'Nombre, correo, ...',
                        ],
                    ],
                ],
            ],
        ];
    @endphp

    <div class="reports-hub reports-exports">
        <script type="application/json" id="cf4-export-config">
            @json($exportsConfig, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)
        </script>

        <nav class="reports-breadcrumb">
            <a href="{{ route('admin.reports.index') }}">Reportes</a>
            <span class="sep">/</span>
            <span>Exportar datos</span>
        </nav>

        @component('admin.partials.page-header', ['title' => 'Exportación de datos'])
            <div 
                <p>
                    Descarga reportes y listados administrativos en PDF, Excel o XML desde un solo lugar.
                </p>
                <p>
                    Puedes exportar información del dashboard, inventario, ventas, productos más vendidos y registros
                    administrativos.
                </p>
            </div>
        @endcomponent

        <div class="reports-exports-layout">

            {{-- ── REPORTES PDF Y EXCEL ─────────────────────────────────────── --}}
            <section class="exports-section exports-section--pdf" aria-labelledby="exports-pdf-title">
                <h2 id="exports-pdf-title" class="exports-section-title">Reportes en PDF y Excel</h2>
                <ul class="exports-link-list">

                    <li>
                        <span class="exports-item-label">Dashboard</span>
                        <span class="exports-item-actions">
                            <a href="#" class="exports-chip exports-chip-primary" data-export-id="dashboard"
                                data-export-format="pdf">PDF</a>
                        </span>
                    </li>

                    <li>
                        <span class="exports-item-label">Inventario</span>
                        <span class="exports-item-actions">
                            <a href="#" class="exports-chip exports-chip-primary" data-export-id="inventory"
                                data-export-format="pdf">PDF</a>
                            <a href="#" class="exports-chip exports-chip--excel" data-export-id="inventory"
                                data-export-format="excel"><i class="fas fa-file-excel" aria-hidden="true"></i> Excel</a>
                            <a href="#" class="exports-chip" data-export-id="inventory"
                                data-export-format="xml">XML</a>
                        </span>
                    </li>

                    <li>
                        <span class="exports-item-label">Productos más vendidos</span>
                        <span class="exports-item-actions">
                            <a href="#" class="exports-chip exports-chip-primary" data-export-id="productSales"
                                data-export-format="pdf">PDF</a>
                            <a href="#" class="exports-chip exports-chip--excel" data-export-id="productSales"
                                data-export-format="excel"><i class="fas fa-file-excel" aria-hidden="true"></i> Excel</a>
                        </span>
                    </li>

                    <li>
                        <span class="exports-item-label">Ventas</span>
                        <span class="exports-item-actions">
                            <a href="#" class="exports-chip exports-chip-primary" data-export-id="sales"
                                data-export-format="pdf">PDF</a>
                            <a href="#" class="exports-chip exports-chip--excel" data-export-id="sales"
                                data-export-format="excel"><i class="fas fa-file-excel" aria-hidden="true"></i> Excel</a>
                        </span>
                    </li>

                </ul>
            </section>

            {{-- ── LISTADOS ADMINISTRATIVOS ─────────────────────────────────── --}}
            <section class="exports-section exports-section--registry" aria-labelledby="exports-registry-title">
                <h2 id="exports-registry-title" class="exports-section-title">Listados administrativos</h2>
                <p class="exports-hint">Proveedores, marcas, pedidos a proveedores, usuarios y encargos. Excel o PDF; en
                    pedidos y encargos valen los mismos filtros que en sus pantallas.</p>
                <ul class="exports-link-list exports-link-list--compact">

                    <li>
                        <span class="exports-item-label">Proveedores</span>
                        <span class="exports-item-actions">
                            <a href="#" class="exports-chip exports-chip-primary" data-export-id="registry.suppliers"
                                data-export-format="pdf">PDF</a>
                            <a href="#" class="exports-chip exports-chip--excel" data-export-id="registry.suppliers"
                                data-export-format="excel"><i class="fas fa-file-excel" aria-hidden="true"></i> Excel</a>
                        </span>
                    </li>

                    <li>
                        <span class="exports-item-label">Marcas</span>
                        <span class="exports-item-actions">
                            <a href="#" class="exports-chip exports-chip-primary" data-export-id="registry.brands"
                                data-export-format="pdf">PDF</a>
                            <a href="#" class="exports-chip exports-chip--excel" data-export-id="registry.brands"
                                data-export-format="excel"><i class="fas fa-file-excel" aria-hidden="true"></i> Excel</a>
                        </span>
                    </li>

                    <li>
                        <span class="exports-item-label">Pedidos a proveedores</span>
                        <span class="exports-item-actions">
                            <a href="#" class="exports-chip exports-chip-primary"
                                data-export-id="registry.supplierOrders" data-export-format="pdf">PDF</a>
                            <a href="#" class="exports-chip exports-chip--excel"
                                data-export-id="registry.supplierOrders" data-export-format="excel"><i
                                    class="fas fa-file-excel" aria-hidden="true"></i> Excel</a>
                        </span>
                    </li>

                    <li>
                        <span class="exports-item-label">Usuarios</span>
                        <span class="exports-item-actions">
                            <a href="#" class="exports-chip exports-chip-primary" data-export-id="registry.users"
                                data-export-format="pdf">PDF</a>
                            <a href="#" class="exports-chip exports-chip--excel" data-export-id="registry.users"
                                data-export-format="excel"><i class="fas fa-file-excel" aria-hidden="true"></i> Excel</a>
                        </span>
                    </li>

                    <li>
                        <span class="exports-item-label">Encargos</span>
                        <span class="exports-item-actions">
                            <a href="#" class="exports-chip exports-chip-primary"
                                data-export-id="registry.clientOrders" data-export-format="pdf">PDF</a>
                            <a href="#" class="exports-chip exports-chip--excel"
                                data-export-id="registry.clientOrders" data-export-format="excel"><i
                                    class="fas fa-file-excel" aria-hidden="true"></i> Excel</a>
                        </span>
                    </li>

                </ul>
            </section>

        </div>

        <p class="exports-footnote">
            Para importar productos use el botón <strong>Importar</strong> en <a
                href="{{ route('inventory') }}">Inventario</a>.
        </p>
    </div>

    @include('admin.reports.partials.export-modal')
@endsection
