@extends('errors.layouts.error')

@section('title', 'Página no encontrada — Ciclo Finca 4')

@push('styles')
    @vite(['resources/css/errors/404-page.css'])
@endpush

@section('content')
    <x-shared.state-card
        class="cf4-error-404-override"
        eyebrow="Página no encontrada"
        code="404"
        title="Esta ruta se salió del camino"
        message="No encontramos la página que buscabas. Podés volver al catálogo y seguir explorando bicicletas, repuestos y accesorios disponibles."
        scene="wrong_route"
    >
        <x-slot name="visual">
            @include('errors.partials.404-bike-svg')
        </x-slot>
        <x-slot name="fallback">
            <img
                class="cf4-error-fallback"
                src="{{ asset('images/errors/404-bike-illustration-orig.png') }}"
                alt=""
                role="presentation"
                loading="lazy"
            >
        </x-slot>
        <x-slot name="actions">
            <a href="{{ route('clients.catalog') }}" class="cf4-state-btn-primary">
                <i class="fas fa-bicycle" aria-hidden="true"></i>
                Explorar catálogo
            </a>
            <a href="{{ route('clients.home') }}" class="cf4-state-btn-secondary">
                <i class="fas fa-home" aria-hidden="true"></i>
                Ir al inicio
            </a>
        </x-slot>
    </x-shared.state-card>
@endsection

@push('scripts')
    @vite(['resources/js/errors/scenes.js'])
@endpush
