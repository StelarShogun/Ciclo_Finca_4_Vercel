@props([
    'paginator',
    'label' => null,
    /** When true, per_page changes submit a GET form to the current URL. When false, only the select is rendered (JS-driven lists). */
    'perPageSubmit' => true,
])

@php
    $uid = 'apg-'.spl_object_id($paginator);
    $paginator->onEachSide(1);
    $linkRows = $paginator->linkCollection();
    $total = (int) $paginator->total();
    $lastPage = max(1, (int) $paginator->lastPage());
    $perPageCurrent = \App\Support\AdminPerPage::resolve($paginator->perPage());
@endphp

<div
    class="cf4-pagination-toolbar pagination is-compact admin-pagination"
    data-last-page="{{ $lastPage }}"
    role="navigation"
    aria-label="Paginación {{ $label ?? '' }}"
>
    @if ($perPageSubmit)
        <form method="get" action="{{ url()->current() }}" class="admin-pagination-per-page-form">
            {!! \App\Support\AdminPaginationPreserver::hiddenInputsHtml(request()) !!}
            <input type="hidden" name="page" value="1">
            <label class="admin-pagination-per-page-label" for="admin-per-page-{{ $uid }}">Ítems por página</label>
            <select
                name="per_page"
                id="admin-per-page-{{ $uid }}"
                class="admin-pagination-per-page-select"
                onchange="if (this.form.requestSubmit) { this.form.requestSubmit(); } else { this.form.submit(); }"
            >
                @foreach (\App\Support\AdminPerPage::ALLOWED as $size)
                    <option value="{{ $size }}" @selected($perPageCurrent === $size)>{{ $size }}</option>
                @endforeach
            </select>
        </form>
    @else
        <div class="admin-pagination-per-page-form admin-pagination-per-page-form--static">
            <label class="admin-pagination-per-page-label" for="admin-per-page-{{ $uid }}">Ítems por página</label>
            <select
                name="per_page"
                id="admin-per-page-{{ $uid }}"
                class="admin-pagination-per-page-select"
            >
                @foreach (\App\Support\AdminPerPage::ALLOWED as $size)
                    <option value="{{ $size }}" @selected($perPageCurrent === $size)>{{ $size }}</option>
                @endforeach
            </select>
        </div>
    @endif

    <div class="results-info" aria-live="polite">
        @if ($total === 0)
            Mostrando 0 resultados
        @else
            Mostrando {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} de {{ $total }} resultados
        @endif
    </div>

    <div class="admin-pagination-nav">
        @foreach ($linkRows as $link)
            @if ($loop->first)
                <a
                    class="button"
                    aria-label="Anterior"
                    href="{{ $link['url'] ?? '#' }}"
                    @if ($link['url'] === null) aria-disabled="true" tabindex="-1" @endif
                    data-page="{{ $link['page'] }}"
                >
                    <svg width="16" height="16" viewBox="0 0 24 24" aria-hidden="true"><path d="M15 18l-6-6 6-6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </a>
            @elseif ($loop->last)
                <a
                    class="button"
                    aria-label="Siguiente"
                    href="{{ $link['url'] ?? '#' }}"
                    @if ($link['url'] === null) aria-disabled="true" tabindex="-1" @endif
                    data-page="{{ $link['page'] }}"
                >
                    <svg width="16" height="16" viewBox="0 0 24 24" aria-hidden="true"><path d="M9 6l6 6-6 6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </a>
            @elseif (($link['label'] ?? '') === '...')
                <span class="button admin-pagination-ellipsis" aria-hidden="true">…</span>
            @elseif (! empty($link['active']))
                <span class="button button-primary" aria-current="page">{{ $link['label'] }}</span>
            @else
                <a class="button" href="{{ $link['url'] ?? '#' }}" data-page="{{ $link['page'] }}">{{ $link['label'] }}</a>
            @endif
        @endforeach
    </div>

    <label class="sr-only" for="goToPageInput-{{ $uid }}">Ir a página</label>
    <input
        id="goToPageInput-{{ $uid }}"
        class="pagination-go-input"
        type="number"
        min="1"
        max="{{ $lastPage }}"
        step="1"
        value="{{ (int) $paginator->currentPage() }}"
        inputmode="numeric"
    />
    <button class="go-button pagination-go-button" type="button">Ir</button>
</div>
