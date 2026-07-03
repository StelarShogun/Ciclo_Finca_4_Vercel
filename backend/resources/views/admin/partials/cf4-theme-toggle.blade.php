@php
    $variant = $variant ?? 'compact';
    $toggleClass = match ($variant) {
        'floating' => 'theme-toggle-btn theme-toggle-btn--admin-floating',
        default => 'theme-toggle-btn theme-toggle-btn--compact',
    };
@endphp

<button type="button"
        class="{{ $toggleClass }}"
        data-theme-toggle
        aria-label="Cambiar a modo oscuro"
        aria-pressed="false"
        title="Modo oscuro">
    @include('shared.partials.cf4-theme-toggle-track')
</button>
