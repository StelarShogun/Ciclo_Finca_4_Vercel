@extends('admin.layouts.admin-shell')

@section('Titulo pagina', 'Exportar datos - Reportes')

@push('styles')
    @vite(['resources/css/admin/reports/reports-hub.css', 'resources/css/admin/reports/exports.css'])
@endpush

@section('aside')
    @include('admin.parts.aside')
@endsection

@section('contenido')
    @php
        $invQuery = \App\Services\Admin\AdminInventoryExportQuery::queryStringFromRequest(request());

        $salesKeys = ['status', 'date_range', 'start_date', 'end_date', 'payment_method', 'search'];
        $salesParams = array_filter(request()->only($salesKeys), fn ($v) => $v !== null && $v !== '');
        if (! isset($salesParams['status']) || $salesParams['status'] === '') {
            $salesParams['status'] = 'completed';
        }
        $salesPdfUrl  = route('sales.export').'?'.http_build_query(array_merge($salesParams, ['format' => 'pdf']));
        $salesCsvUrl  = route('sales.export').'?'.http_build_query(array_merge($salesParams, ['format' => 'csv']));
        $salesExcelUrl = route('sales.export').'?'.http_build_query(array_merge($salesParams, ['format' => 'excel']));

        $productSalesParams = array_filter(
            request()->only(['period', 'sort', 'dir', 'q', 'top10']),
            fn ($v) => $v !== null && $v !== ''
        );
        if (! isset($productSalesParams['period'])) {
            $productSalesParams['period'] = '30d';
        }
        if (! isset($productSalesParams['sort'])) {
            $productSalesParams['sort'] = 'revenue';
        }
        if (! isset($productSalesParams['dir'])) {
            $productSalesParams['dir'] = 'desc';
        }
        if (! isset($productSalesParams['top10'])) {
            $productSalesParams['top10'] = 'revenue';
        }
        $productSalesPdfUrl   = route('admin.reports.product-sales.pdf').'?'.http_build_query($productSalesParams);
        $productSalesExcelUrl = route('admin.reports.product-sales.excel').'?'.http_build_query($productSalesParams);

        $exportFmt = fn (string $fmt) => route('products.export', ['format' => $fmt]).$invQuery;

        $soParams = array_filter(
            request()->only(\App\Services\Admin\AdminSupplierOrdersExportQuery::QUERY_KEYS),
            fn ($v) => $v !== null && $v !== ''
        );
        $coParams = array_filter(
            request()->only(\App\Services\Admin\AdminClientOrdersExportQuery::QUERY_KEYS),
            fn ($v) => $v !== null && $v !== ''
        );
        $spParams = array_filter(
            request()->only(\App\Services\Admin\AdminSuppliersCatalogExportQuery::QUERY_KEYS),
            fn ($v) => $v !== null && $v !== ''
        );
        $brParams = array_filter(
            request()->only(\App\Services\Admin\AdminBrandsCatalogExportQuery::QUERY_KEYS),
            fn ($v) => $v !== null && $v !== ''
        );

        $regUrl = function (string $slug, string $format, array $extra = []) {
            return route('admin.reports.exports.registry', ['slug' => $slug]).'?'.http_build_query(array_merge($extra, ['format' => $format]));
        };
    @endphp

    <div class="reports-hub reports-exports">
        <nav class="reports-breadcrumb">
            <a href="{{ route('admin.reports.index') }}">Reportes</a>
            <span class="sep">/</span>
            <span>Exportar datos</span>
        </nav>

        <header class="reports-hub-header reports-exports-intro">
            <h1>Exportar datos</h1>
            <div class="reports-exports-intro-text">
                <p>Aquí descarga <strong>informes en PDF</strong>, <strong>Excel</strong> y <strong>archivos de inventario y ventas</strong> desde un solo lugar.</p>
                <p>Cada enlace abre el resultado en una <strong>nueva pestaña</strong> del navegador.</p>
            </div>
        </header>

        <div class="reports-exports-layout">

            {{-- ── REPORTES PDF Y EXCEL ─────────────────────────────────────── --}}
            <section class="exports-section exports-section--pdf" aria-labelledby="exports-pdf-title">
                <h2 id="exports-pdf-title" class="exports-section-title">Reportes en PDF y Excel</h2>
                <ul class="exports-link-list">

                    <li>
                        <span class="exports-item-label">Dashboard</span>
                        <span class="exports-item-actions">
                            @foreach (['7d' => '7 días', '30d' => '30 días', '90d' => '90 días'] as $p => $label)
                                <a href="{{ route('dashboard.export') }}?format=pdf&period={{ $p }}"
                                   class="exports-chip exports-chip--period"
                                   title="PDF del dashboard, {{ $label }}"
                                   target="_blank" rel="noopener noreferrer">{{ $label }}</a>
                            @endforeach
                        </span>
                    </li>

                    <li>
                        <span class="exports-item-label">Inventario</span>
                        <span class="exports-item-actions">
                            <a href="{{ $exportFmt('pdf') }}"
                               class="exports-chip exports-chip-primary"
                               title="PDF del inventario"
                               target="_blank" rel="noopener noreferrer">PDF</a>
                            <a href="{{ $exportFmt('excel') }}"
                               class="exports-chip exports-chip--excel"
                               title="Excel del inventario"
                               target="_blank" rel="noopener noreferrer">
                                <i class="fas fa-file-excel" aria-hidden="true"></i> Excel
                            </a>
                        </span>
                    </li>

                    <li>
                        <span class="exports-item-label">Productos más vendidos</span>
                        <span class="exports-item-actions">
                            <a href="{{ $productSalesPdfUrl }}"
                               class="exports-chip exports-chip-primary"
                               title="PDF de productos más vendidos"
                               target="_blank" rel="noopener noreferrer">PDF</a>
                            <a href="{{ $productSalesExcelUrl }}"
                               class="exports-chip exports-chip--excel"
                               title="Excel de productos más vendidos"
                               target="_blank" rel="noopener noreferrer">
                                <i class="fas fa-file-excel" aria-hidden="true"></i> Excel
                            </a>
                        </span>
                    </li>

                    <li>
                        <span class="exports-item-label">Ventas</span>
                        <span class="exports-item-actions">
                            <a href="{{ $salesPdfUrl }}"
                               class="exports-chip exports-chip-primary"
                               title="PDF de ventas"
                               target="_blank" rel="noopener noreferrer">PDF</a>
                            <a href="{{ $salesExcelUrl }}"
                               class="exports-chip exports-chip--excel"
                               title="Excel de ventas"
                               target="_blank" rel="noopener noreferrer">
                                <i class="fas fa-file-excel" aria-hidden="true"></i> Excel
                            </a>
                        </span>
                    </li>

                </ul>
            </section>

            {{-- ── DATOS DE INVENTARIO + VENTAS ────────────────────────────── --}}
            <div class="reports-exports-stack">

                <section class="exports-section" aria-labelledby="exports-data-title">
                    <h2 id="exports-data-title" class="exports-section-title">Datos de inventario</h2>
                    <p class="exports-hint">Exporta el catálogo en varios formatos. Si entra desde Inventario con filtros, la descarga usará los mismos.</p>
                    <div class="exports-button-row">
                        <a href="{{ $exportFmt('xml') }}"  class="exports-btn" target="_blank" rel="noopener noreferrer"><i class="fas fa-file-code" aria-hidden="true"></i> XML</a>
                        <a href="{{ $exportFmt('csv') }}"  class="exports-btn" target="_blank" rel="noopener noreferrer"><i class="fas fa-file-csv" aria-hidden="true"></i> CSV</a>
                        <a href="{{ $exportFmt('json') }}" class="exports-btn" target="_blank" rel="noopener noreferrer"><i class="fas fa-file-alt" aria-hidden="true"></i> JSON</a>
                        <a href="{{ $exportFmt('pdf') }}"  class="exports-btn exports-btn-accent" target="_blank" rel="noopener noreferrer"><i class="fas fa-file-pdf" aria-hidden="true"></i> PDF</a>
                        <a href="{{ $exportFmt('excel') }}" class="exports-btn exports-btn--excel" target="_blank" rel="noopener noreferrer"><i class="fas fa-file-excel" aria-hidden="true"></i> Excel</a>
                    </div>
                </section>

                <section class="exports-section" aria-labelledby="exports-sales-data-title">
                    <h2 id="exports-sales-data-title" class="exports-section-title">Datos de ventas</h2>
                    <p class="exports-hint">Lista de ventas en CSV, PDF o Excel. Si entra desde Ventas con filtros, se aplican a la descarga.</p>
                    <div class="exports-button-row exports-button-row--few">
                        <a href="{{ $salesCsvUrl }}"   class="exports-btn" target="_blank" rel="noopener noreferrer"><i class="fas fa-file-csv" aria-hidden="true"></i> CSV</a>
                        <a href="{{ $salesPdfUrl }}"   class="exports-btn exports-btn-accent" target="_blank" rel="noopener noreferrer"><i class="fas fa-file-pdf" aria-hidden="true"></i> PDF</a>
                        <a href="{{ $salesExcelUrl }}" class="exports-btn exports-btn--excel" target="_blank" rel="noopener noreferrer"><i class="fas fa-file-excel" aria-hidden="true"></i> Excel</a>
                    </div>
                </section>

            </div>

            {{-- ── LISTADOS ADMINISTRATIVOS ─────────────────────────────────── --}}
            <section class="exports-section exports-section--registry" aria-labelledby="exports-registry-title">
                <h2 id="exports-registry-title" class="exports-section-title">Listados administrativos</h2>
                <p class="exports-hint">Proveedores, marcas, pedidos a proveedores, usuarios y encargos. CSV, Excel o PDF; en pedidos y encargos valen los mismos filtros que en sus pantallas.</p>
                <ul class="exports-link-list exports-link-list--compact">

                    <li>
                        <span class="exports-item-label">Proveedores</span>
                        <span class="exports-item-actions">
                            <a href="{{ $regUrl('proveedores', 'csv', $spParams) }}"   class="exports-chip" target="_blank" rel="noopener noreferrer">CSV</a>
                            <a href="{{ $regUrl('proveedores', 'excel', $spParams) }}" class="exports-chip exports-chip--excel" target="_blank" rel="noopener noreferrer"><i class="fas fa-file-excel" aria-hidden="true"></i> Excel</a>
                            <a href="{{ $regUrl('proveedores', 'pdf', $spParams) }}"   class="exports-chip exports-chip-primary" target="_blank" rel="noopener noreferrer">PDF</a>
                        </span>
                    </li>

                    <li>
                        <span class="exports-item-label">Marcas</span>
                        <span class="exports-item-actions">
                            <a href="{{ $regUrl('marcas', 'csv', $brParams) }}"   class="exports-chip" target="_blank" rel="noopener noreferrer">CSV</a>
                            <a href="{{ $regUrl('marcas', 'excel', $brParams) }}" class="exports-chip exports-chip--excel" target="_blank" rel="noopener noreferrer"><i class="fas fa-file-excel" aria-hidden="true"></i> Excel</a>
                            <a href="{{ $regUrl('marcas', 'pdf', $brParams) }}"   class="exports-chip exports-chip-primary" target="_blank" rel="noopener noreferrer">PDF</a>
                        </span>
                    </li>

                    <li>
                        <span class="exports-item-label">Pedidos a proveedores</span>
                        <span class="exports-item-actions">
                            <a href="{{ $regUrl('pedidos-proveedores', 'csv', $soParams) }}"   class="exports-chip" target="_blank" rel="noopener noreferrer">CSV</a>
                            <a href="{{ $regUrl('pedidos-proveedores', 'excel', $soParams) }}" class="exports-chip exports-chip--excel" target="_blank" rel="noopener noreferrer"><i class="fas fa-file-excel" aria-hidden="true"></i> Excel</a>
                            <a href="{{ $regUrl('pedidos-proveedores', 'pdf', $soParams) }}"   class="exports-chip exports-chip-primary" target="_blank" rel="noopener noreferrer">PDF</a>
                        </span>
                    </li>

                    <li>
                        <span class="exports-item-label">Usuarios</span>
                        <span class="exports-item-actions">
                            <a href="{{ $regUrl('usuarios', 'csv') }}"   class="exports-chip" target="_blank" rel="noopener noreferrer">CSV</a>
                            <a href="{{ $regUrl('usuarios', 'excel') }}" class="exports-chip exports-chip--excel" target="_blank" rel="noopener noreferrer"><i class="fas fa-file-excel" aria-hidden="true"></i> Excel</a>
                            <a href="{{ $regUrl('usuarios', 'pdf') }}"   class="exports-chip exports-chip-primary" target="_blank" rel="noopener noreferrer">PDF</a>
                        </span>
                    </li>

                    <li>
                        <span class="exports-item-label">Encargos</span>
                        <span class="exports-item-actions">
                            <a href="{{ $regUrl('pedidos-clientes', 'csv', $coParams) }}"   class="exports-chip" target="_blank" rel="noopener noreferrer">CSV</a>
                            <a href="{{ $regUrl('pedidos-clientes', 'excel', $coParams) }}" class="exports-chip exports-chip--excel" target="_blank" rel="noopener noreferrer"><i class="fas fa-file-excel" aria-hidden="true"></i> Excel</a>
                            <a href="{{ $regUrl('pedidos-clientes', 'pdf', $coParams) }}"   class="exports-chip exports-chip-primary" target="_blank" rel="noopener noreferrer">PDF</a>
                        </span>
                    </li>

                </ul>
            </section>

        </div>

        <p class="exports-footnote">
            Para importar productos use el botón <strong>Importar</strong> en <a href="{{ route('inventory') }}">Inventario</a>.
        </p>
    </div>
@endsection