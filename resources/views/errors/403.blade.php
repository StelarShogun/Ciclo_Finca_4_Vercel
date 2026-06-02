@extends('errors.layouts.error')

@section('title', 'Acceso no autorizado — Ciclo Finca 4')

@section('content')
    <x-shared.state-card
        eyebrow="Acceso restringido"
        code="403"
        title="Esta ruta es solo para personal autorizado"
        message="No tenés permiso para ver este recurso. Si creés que es un error, volvé al inicio o contactá al equipo de Ciclo Finca 4."
        :static-visual="true"
        :visual-plain="true"
    >
        <x-slot name="visual">
            @include('errors.partials.scenes.lock-svg')
        </x-slot>
        <x-slot name="actions">
            <a href="{{ route('clients.home') }}" class="cf4-state-btn-primary">
                <i class="fas fa-home" aria-hidden="true"></i>
                Ir al inicio
            </a>
            <a href="{{ route('clients.catalog') }}" class="cf4-state-btn-secondary">
                <i class="fas fa-bicycle" aria-hidden="true"></i>
                Ver catálogo
            </a>
        </x-slot>
    </x-shared.state-card>
@endsection
