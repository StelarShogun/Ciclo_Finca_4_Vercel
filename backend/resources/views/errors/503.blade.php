@extends('errors.layouts.error')

@section('title', 'En mantenimiento — Ciclo Finca 4')

@section('content')
    <x-shared.state-card
        eyebrow="Mantenimiento"
        code="503"
        title="Estamos ajustando la cadena"
        message="El sitio está en mantenimiento programado o sobrecargado. Volvé a intentar en unos minutos. Gracias por tu paciencia."
        scene="workshop"
        :visual-plain="true"
    >
        <x-slot name="visual">
            @include('errors.partials.scenes.workshop-svg')
        </x-slot>
        <x-slot name="actions">
            <a href="{{ route('clients.home') }}" class="cf4-state-btn-primary">
                <i class="fas fa-rotate-right" aria-hidden="true"></i>
                Reintentar
            </a>
            <a href="{{ route('clients.catalog') }}" class="cf4-state-btn-secondary">
                <i class="fas fa-bicycle" aria-hidden="true"></i>
                Catálogo
            </a>
        </x-slot>
    </x-shared.state-card>
@endsection

@push('scripts')
    @vite(['resources/ts/errors/scenes.ts'])
@endpush
