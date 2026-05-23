@extends('client.layouts.app')

@section('title', ($legalTitle ?? 'Información legal') . ' - Ciclo Finca 4')

@push('styles')
    @vite(['resources/css/client/clients-page.css', 'resources/css/client/legal-pages.css'])
@endpush

@section('content')
<article class="legal-page" aria-labelledby="legal-page-title">
    <div class="container legal-page-container">
        <header class="legal-page-header">
            <a href="{{ route('clients.home') }}" class="legal-page-back">
                <i class="fas fa-arrow-left" aria-hidden="true"></i>
                Volver al inicio
            </a>
            <h1 id="legal-page-title" class="legal-page-title">{{ $legalTitle ?? 'Información legal' }}</h1>
            @if (! empty($legalUpdated))
                <p class="legal-page-updated">Última actualización: {{ $legalUpdated }}</p>
            @endif
        </header>

        <div class="legal-page-body">
            @yield('legal_content')
        </div>

        <nav class="legal-page-related" aria-label="Documentos relacionados">
            <ul class="legal-page-related-list">
                <li><a href="{{ route('clients.legal.terms') }}">Términos y condiciones</a></li>
                <li><a href="{{ route('clients.legal.privacy') }}">Política de privacidad</a></li>
                <li><a href="{{ route('clients.legal.returns') }}">Cambios y devoluciones</a></li>
                <li><a href="{{ route('clients.contact') }}">Contacto</a></li>
            </ul>
        </nav>
    </div>
</article>
@endsection
