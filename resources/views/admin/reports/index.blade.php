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
            <a href="{{ route('admin.reports.product-sales', ['period' => '30d', 'sort' => 'revenue', 'dir' => 'desc']) }}" class="report-card">
                <div class="report-card-icon"><i class="fas fa-chart-bar"></i></div>
                <div class="report-card-body">
                    <h2>Productos más vendidos</h2>
                    <p>Consulta cuánto se vendió de cada producto y cuánto ingresó. Busca por nombre o código y descubre cuáles son los favoritos.</p>
                </div>
                <span class="report-card-arrow"><i class="fas fa-arrow-right"></i></span>
            </a>
        </div>
    </div>
@endsection
