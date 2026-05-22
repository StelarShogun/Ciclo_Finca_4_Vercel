<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @stack('extra-meta')
    <title>@yield('Titulo pagina')</title>

    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">

    @vite(['resources/css/admin/shell-base.css', 'resources/css/admin/components/page-header.css'])
    @stack('styles')
</head>

<body class="admin-layout">

    @yield('aside')

    <main class="admin-main">
        @yield('header')
        @yield('contenido')
    </main>

    @vite(['resources/js/admin/shell.js'])
    @stack('vite-body')
    @stack('scripts')

</body>

</html>
