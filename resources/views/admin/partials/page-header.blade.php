@php
    /** @var string $title */
    /** @var string|null $description */
    $title = $title ?? '';
    $description = $description ?? null;
@endphp

<header class="cf4-admin-page-header">
    <div class="cf4-admin-page-header__main">
        <h1 class="cf4-admin-page-header__title">{{ $title }}</h1>

        @php
            $slotDescription = isset($slot) ? trim((string) $slot) : '';
        @endphp

        @if($slotDescription !== '')
            <div class="cf4-admin-page-header__description">{!! $slotDescription !!}</div>
        @elseif(!empty($description))
            <div class="cf4-admin-page-header__description">{{ $description }}</div>
        @endif
    </div>

    @if (isset($actions) && trim((string) $actions) !== '')
        <div class="cf4-admin-page-header__actions">
            {{ $actions }}
        </div>
    @endif
</header>
