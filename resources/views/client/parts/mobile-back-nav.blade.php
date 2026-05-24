@php
    $backUrl = $backUrl ?? url()->previous();
    $backLabel = $backLabel ?? 'Volver';
@endphp

<nav class="cf4-mobile-back-nav" aria-label="Volver atrás">
    <a href="{{ $backUrl }}" class="cf4-mobile-back-link">
        <i class="fas fa-arrow-left" aria-hidden="true"></i>
        <span>{{ $backLabel }}</span>
    </a>
</nav>
