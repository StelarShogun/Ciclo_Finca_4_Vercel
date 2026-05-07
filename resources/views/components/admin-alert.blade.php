@props([
    'type' => 'info', // info|success|warning|error
    'title' => null,
    'message' => null,
    'dismissible' => null,
])

@php
    $normalizedType = in_array($type, ['info', 'success', 'warning', 'error'], true) ? $type : 'info';

    $typeToCss = [
        'info' => 'alert-info',
        'success' => 'alert-success',
        'warning' => 'alert-warning',
        // El CSS del proyecto usa "danger" para error
        'error' => 'alert-danger',
    ];

    $isImportant = in_array($normalizedType, ['warning', 'error'], true);
    $role = $isImportant ? 'alert' : 'status';
    $ariaLive = $isImportant ? 'assertive' : 'polite';

    $isDismissible = is_bool($dismissible)
        ? $dismissible
        : in_array($normalizedType, ['success', 'info'], true);

    $typeToIconClass = [
        'info' => 'fa-circle-info',
        'success' => 'fa-circle-check',
        'warning' => 'fa-triangle-exclamation',
        'error' => 'fa-circle-xmark',
    ];

    $hasSlot = trim((string) $slot) !== '';
@endphp

<div
    {{ $attributes->merge(['class' => 'alert '.$typeToCss[$normalizedType].' admin-alert']) }}
    role="{{ $role }}"
    aria-live="{{ $ariaLive }}"
>
    <span class="admin-alert__icon" aria-hidden="true">
        <i class="fa-solid {{ $typeToIconClass[$normalizedType] }}"></i>
    </span>

    <div class="admin-alert__content">
        @if ($title)
            <div class="admin-alert__title">{{ $title }}</div>
        @endif

        @if (! is_null($message) && $message !== '')
            <div class="admin-alert__message">{{ $message }}</div>
        @endif

        @if ($hasSlot)
            <div class="admin-alert__message">{{ $slot }}</div>
        @endif
    </div>

    @if ($isDismissible)
        <button
            type="button"
            class="admin-alert__close"
            aria-label="Cerrar alerta"
            onclick="this.closest('.admin-alert')?.remove()"
        >
            <span aria-hidden="true">&times;</span>
        </button>
    @endif
</div>
