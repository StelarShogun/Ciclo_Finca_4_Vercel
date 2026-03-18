@extends('clientes.layouts.app')

@section('title', 'Error - Detalle del producto - Ciclo Finca 4')

@section('content')
<div class="product-detail-container">
    <div class="container">
        <nav class="breadcrumb">
            <a href="{{ route('clientes.home') }}">Inicio</a>
            <span>/</span>
            <a href="{{ route('clientes.catalogo') }}">Catálogo</a>
            <span>/</span>
            <span>Error</span>
        </nav>

        <div class="empty-state" style="min-height: 40vh; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 2rem;">
            <i class="fas fa-exclamation-triangle" style="font-size: 4rem; color: #dc3545; margin-bottom: 1rem;"></i>
            <h2 class="section-title">No se pudo cargar el detalle del producto</h2>
            <p style="color: #6c757d; margin-bottom: 1.5rem; text-align: center;">
                Ha ocurrido un error al obtener la información del producto. El producto puede no existir o no estar disponible.
            </p>
            <a href="{{ route('clientes.catalogo') }}" class="btn btn-primary">
                <i class="fas fa-th"></i>
                Volver al Catálogo
            </a>
        </div>
    </div>
</div>
@endsection
