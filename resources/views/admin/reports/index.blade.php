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
        <header class="reports-hub-header">
            <h1>Reportes</h1>
            <p>Consultas analíticas para inventario y ventas.</p>
        </header>

        <div class="reports-cards">
            <a href="{{ route('admin.reports.exports').(request()->getQueryString() !== null && request()->getQueryString() !== '' ? '?'.request()->getQueryString() : '') }}" class="report-card">
                <div class="report-card-icon"><i class="fas fa-file-export"></i></div>
                <div class="report-card-body">
                    <h2>Exportar datos</h2>
                    <p>Descargas centralizadas: PDF y CSV, EXCEL, XML o JSON de inventario y ventas, más proveedores, marcas, pedidos a proveedores, usuarios y encargos.</p>
                </div>
                <span class="report-card-arrow"><i class="fas fa-arrow-right"></i></span>
            </a>
            <a href="{{ route('admin.reports.sales-performance') }}" class="report-card">
                <div class="report-card-icon"><i class="fas fa-chart-line"></i></div>
                <div class="report-card-body">
                    <h2>Desempeño de ventas</h2>
                    <p>Filtrá por día, semana, mes, año o rango propio. Ves totales e ingresos y la variación frente al periodo anterior equivalente.</p>
                </div>
                <span class="report-card-arrow"><i class="fas fa-arrow-right"></i></span>
            </a>
            <a href="{{ route('admin.reports.product-sales', ['period' => '30d', 'sort' => 'revenue', 'dir' => 'desc']) }}" class="report-card">
                <div class="report-card-icon"><i class="fas fa-chart-bar"></i></div>
                <div class="report-card-body">
                    <h2>Productos más vendidos</h2>
                    <p>Consulta cuánto se vendió de cada producto y cuánto ingresó. Busca por nombre o código y descubre cuáles son los favoritos.</p>
                </div>
                <span class="report-card-arrow"><i class="fas fa-arrow-right"></i></span>
            </a>
            <a href="{{ route('sales.reports.byCategory') }}" class="report-card">
                <div class="report-card-icon"><i class="fas fa-chart-pie"></i></div>
                <div class="report-card-body">
                    <h2>Ventas por categoría</h2>
                    <p>Analizá el rendimiento de ventas agrupado por categoría de producto. Identificá cuáles categorías generan más ingresos.</p>
                </div>
                <span class="report-card-arrow"><i class="fas fa-arrow-right"></i></span>
            </a>
        </div>
    </div>
@endsection