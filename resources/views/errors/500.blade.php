@extends('errors.layouts.error')

@section('title', 'Error del servidor — Ciclo Finca 4')

@section('content')
    <x-shared.state-card
        eyebrow="Error interno"
        code="500"
        title="Tuvimos una falla en el taller"
        message="Algo salió mal en nuestros sistemas. Nuestro equipo ya fue notificado. Volvé a intentar en unos minutos o regresá al catálogo."
        scene="workshop"
        :visual-plain="true"
    >
        <x-slot name="visual">
            @include('errors.partials.scenes.workshop-svg')
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

@push('scripts')
    @vite(['resources/js/errors/scenes.js'])
@endpush
