@props([
    'paginator',
    // Texto de contexto opcional: ej. "inventario"
    'label' => null,
])

@php
    // Cálculos seguros para "Mostrando X–Y de Z"
    $total      = $paginator->total();
    $perPage    = $paginator->perPage();
    $current    = max(1, $paginator->currentPage());
    $from       = $total ? (($current - 1) * $perPage) + 1 : 0;
    $to         = $total ? min($total, $current * $perPage) : 0;

    // Siempre mostramos el bloque, aunque sea 1 página
    $hasPrev = $current > 1;
    $hasNext = $current < max(1, $paginator->lastPage());
@endphp

<div class="pagination" role="navigation" aria-label="Paginación {{ $label ?? '' }}">
    {{-- Info --}}
    <div class="results-info" aria-live="polite">
        Mostrando {{ $from }} a {{ $to }} de {{ $total }} resultados
    </div>

    {{-- Prev --}}
    <a
        class="button"
        aria-label="Previous"
        href="{{ $hasPrev ? $paginator->previousPageUrl() : '#' }}"
        @if(!$hasPrev) aria-disabled="true" tabindex="-1" @endif
        data-page="{{ $current - 1 }}"
    >
        {{-- Flecha izquierda (SVG minimal) --}}
        <svg width="16" height="16" viewBox="0 0 24 24" aria-hidden="true"><path d="M15 18l-6-6 6-6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
    </a>

    {{-- Página actual / total --}}
    <span class="button button-primary" aria-current="page">
        {{ $current }} / {{ max(1, $paginator->lastPage()) }}
    </span>

    {{-- Next --}}
    <a
        class="button"
        aria-label="Next"
        href="{{ $hasNext ? $paginator->nextPageUrl() : '#' }}"
        @if(!$hasNext) aria-disabled="true" tabindex="-1" @endif
        data-page="{{ $current + 1 }}"
    >
        {{-- Flecha derecha (SVG minimal) --}}
        <svg width="16" height="16" viewBox="0 0 24 24" aria-hidden="true"><path d="M9 6l6 6-6 6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
    </a>

    {{-- Ir a página --}}
    <label class="sr-only" for="goToPageInput">Ir a página</label>
    <input id="goToPageInput" type="number" min="1" step="1" value="{{ $current }}" inputmode="numeric" />
    <button class="go-button" id="goToPageBtn" type="button">Ir</button>
</div>
