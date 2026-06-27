{{--
    Standard admin listing filters (reference: Ventas).

    @param string $action     Form action URL (GET).
    @param string $clearUrl   URL for the "Limpiar" button (resets query string).
    @param string $title      Section title (default: Filtros de Búsqueda).
    @param string $method     HTTP method (default: GET).
    @param string|null $formId Optional form id for page scripts.
    @param string $formClass   Extra classes on the form element.
    @param bool $preservePerPage Include hidden per_page field (default: true).
    @slot fields              Filter inputs (filter-group markup).
    @slot footer              Optional content inside the form (e.g. date validation alert).
    @slot after               Optional content after the form (e.g. quick-filter pills).
--}}
@php
    $title = $title ?? 'Filtros de Búsqueda';
    $method = strtoupper($method ?? 'GET');
    $formClass = trim('admin-filters-form ' . ($formClass ?? ''));
@endphp

<div class="filters-section">
    <div class="filters-header">
        <h2 class="filters-title">{{ $title }}</h2>
    </div>

    <form method="{{ $method }}" action="{{ $action }}" @if (!empty($formId)) id="{{ $formId }}" @endif
        class="{{ $formClass }}">
        @if ($preservePerPage ?? true)
            <input type="hidden" name="per_page"
                value="{{ \App\Support\AdminPerPage::resolve(request('per_page', 10)) }}">
        @endif
        <div class="filters-grid">
            <div class="filters-grid__fields">
                {{ $fields }}
            </div>

            <div class="filter-group filter-buttons">
                <button type="submit" class="btn btn-primary filter-btn">
                    <i class="fas fa-search" aria-hidden="true"></i> Aplicar Filtros
                </button>
                <a href="{{ $clearUrl }}" class="btn btn-primary filter-btn">
                    <i class="fas fa-times" aria-hidden="true"></i> Limpiar
                </a>
            </div>
        </div>

        @if (!empty($footer))
            <div class="filters-footer">
                {{ $footer }}
            </div>
        @endif
    </form>

    @if (!empty($after))
        {{ $after }}
    @endif
</div>
