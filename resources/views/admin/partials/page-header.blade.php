{{--
    Admin Page Header (reutilizable)

    Objetivo: unificar el encabezado de todas las vistas del panel admin.

    Uso recomendado (con slots; permite HTML en descripción/acciones):

        @component('admin.partials.page-header', ['title' => 'Título', 'description' => 'Opcional'])
            <p>Descripción en HTML (opcional)</p>

            @slot('actions')
                <a href="{{ route('...') }}" class="btn btn-primary">Acción</a>
            @endslot
        @endcomponent

    Uso alterno (con @include; para acciones HTML pasar HtmlString o un slot ya renderizado):

        @include('admin.partials.page-header', [
            'title' => 'Título',
            'description' => 'Texto opcional',
            'actions' => new \Illuminate\Support\HtmlString('<a class="btn btn-primary" href="#">Acción</a>'),
        ])
--}}

@php
    /** @var string $title */
    /** @var string|null $description */
    /** @var \Illuminate\Contracts\Support\Htmlable|string|null $actions */
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
