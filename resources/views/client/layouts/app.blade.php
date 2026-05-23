<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Ciclo Finca 4 - Tienda de Bicicletas')</title>
    @stack('meta')

    {{-- Favicons for multiple resolutions and platforms --}}
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">

    @vite([
        'resources/css/client/fonts.css',
        'resources/css/client/fontawesome.css',
        'resources/css/client/variables-reset.css',
        'resources/css/client/header.css',
        'resources/css/client/footer.css',
    ])

    @stack('styles')
</head>
<body class="cliente-layout">

    {{-- Header and footer are suppressed when the child view defines 'hideNav' --}}
    @unless(View::hasSection('hideNav'))
        @include('client.parts.header')
    @endunless

    <main class="cliente-main">

        @include('client.partials.cf4-flash-swal')

        @yield('content')
    </main>

    @unless(View::hasSection('hideNav') || View::hasSection('hideFooter'))
        @include('client.parts.footer')
    @endunless

    @stack('scripts')
    @if (session('client_success_modal'))
        @vite(['resources/js/client/auth-welcome-toast.js'])
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                if (typeof window.cf4AuthWelcomeToast !== 'function') {
                    return;
                }
                window.cf4AuthWelcomeToast(@json(session('client_success_modal')));
            });
        </script>
    @endif

</body>
</html>