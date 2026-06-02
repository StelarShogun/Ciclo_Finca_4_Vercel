@extends('errors.layouts.error')

@section('title', 'Sesión expirada — Ciclo Finca 4')

@section('content')
    <x-shared.state-card
        eyebrow="Sesión expirada"
        code="419"
        title="Tu sesión se quedó sin aire"
        message="La página estuvo inactiva demasiado tiempo o el token de seguridad caducó. Iniciá sesión de nuevo o recargá la página para continuar."
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
            <a href="{{ route('login.show') }}" class="cf4-state-btn-primary">
                <i class="fas fa-right-to-bracket" aria-hidden="true"></i>
                Iniciar sesión
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
