@extends('admin.layouts.admin-shell')

@section('Titulo pagina', 'Reportes - Ciclo Finca 4 Admin')

@push('styles')
    @vite(['resources/css/admin/reports/reports-hub.css'])
@endpush

@section('aside')
    @include('admin.parts.aside')
@endsection

@section('contenido')
    <div class="reports-hub">
        @component('admin.partials.page-header', [
            'title' => 'Centro de reportes',
            'description' =>
                'Consulta reportes analíticos de ventas, inventario, clientes, búsquedas y actividad administrativa.',
        ])
        @endcomponent

        <div class="reports-cards">
            <a href="{{ route('admin.reports.exports') . (request()->getQueryString() !== null && request()->getQueryString() !== '' ? '?' . request()->getQueryString() : '') }}"
                class="report-card">
                <div class="report-card-icon"><i class="fas fa-file-export"></i></div>
                <div class="report-card-body">
                    <h2>Exportar datos</h2>
                    <p>Descargas centralizadas: PDF, Excel y XML de inventario y ventas, más proveedores, marcas, pedidos a
                        proveedores, usuarios y encargos.</p>
                </div>
                <span class="report-card-arrow"><i class="fas fa-arrow-right"></i></span>
            </a>
            <a href="{{ route('admin.reports.sales-performance') }}" class="report-card">
                <div class="report-card-icon"><i class="fas fa-chart-line"></i></div>
                <div class="report-card-body">
                    <h2>Desempeño de ventas</h2>
                    <p>Filtra por día, semana, mes, año o un rango personalizado. Consulta totales e ingresos y la variación
                        frente al periodo anterior equivalente.</p>
                </div>
                <span class="report-card-arrow"><i class="fas fa-arrow-right"></i></span>
            </a>
            <a href="{{ route('admin.reports.client-purchases') }}" class="report-card">
                <div class="report-card-icon"><i class="fas fa-user-clock"></i></div>
                <div class="report-card-body">
                    <h2>Compras por cliente</h2>
                    <p>Historial de ventas completadas por usuario: totales, cantidad de órdenes y ticket promedio; detalle
                        por periodo.</p>
                </div>
                <span class="report-card-arrow"><i class="fas fa-arrow-right"></i></span>
            </a>
            <a href="{{ route('admin.reports.product-sales', ['period' => '30d', 'sort' => 'revenue', 'dir' => 'desc']) }}"
                class="report-card">
                <div class="report-card-icon"><i class="fas fa-chart-bar"></i></div>
                <div class="report-card-body">
                    <h2>Productos más vendidos</h2>
                    <p>Consulta cuánto se vendió de cada producto y cuánto ingresó. Busca por nombre o código y descubre
                        cuáles son los favoritos.</p>
                </div>
                <span class="report-card-arrow"><i class="fas fa-arrow-right"></i></span>
            </a>
            <a href="{{ route('admin.reports.catalog-search-products', ['period' => '30d']) }}" class="report-card">
                <div class="report-card-icon"><i class="fas fa-search"></i></div>
                <div class="report-card-body">
                    <h2>Productos más buscados</h2>
                    <p>Ranking según apariciones en el catálogo cuando los clientes buscan por texto (últimos 7, 30 o 90
                        días).</p>
                </div>
                <span class="report-card-arrow"><i class="fas fa-arrow-right"></i></span>
            </a>
            <a href="{{ route('admin.inventory.movements.index') }}" class="report-card">
                <div class="report-card-icon"><i class="fas fa-clock-rotate-left"></i></div>
                <div class="report-card-body">
                    <h2>Movimientos de inventario</h2>
                    <p>Consulta el historial completo de entradas, salidas y devoluciones por producto. Filtra por tipo,
                        origen y rango de fechas.</p>
                </div>
                <span class="report-card-arrow"><i class="fas fa-arrow-right"></i></span>
            </a>
            <a href="{{ route('admin.reports.audit-log') }}" class="report-card">
                <div class="report-card-icon"><i class="fas fa-user-shield"></i></div>
                <div class="report-card-body">
                    <h2>Bitácora de auditoría</h2>
                    <p>Consulta acciones administrativas por usuario, tipo de evento, módulo afectado y fecha para detectar
                        irregularidades.</p>
                </div>
                <span class="report-card-arrow"><i class="fas fa-arrow-right"></i></span>
            </a>
            <a href="{{ route('sales.reports.byCategory') }}" class="report-card">
                <div class="report-card-icon"><i class="fas fa-chart-pie"></i></div>
                <div class="report-card-body">
                    <h2>Ventas por categoría</h2>
                    <p>Analiza el rendimiento de ventas agrupado por categoría de producto. Identifica cuáles categorías
                        generan más ingresos.</p>
                </div>
                <span class="report-card-arrow"><i class="fas fa-arrow-right"></i></span>
            </a>
        </div>
    </div>
@endsection
