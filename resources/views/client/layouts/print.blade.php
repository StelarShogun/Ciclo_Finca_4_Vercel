<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Imprimir — Ciclo Finca 4')</title>

    @vite([
        'resources/css/client/fonts.css',
        'resources/css/client/fontawesome.css',
        'resources/css/client/variables-reset.css',
        'resources/css/client/invoice-print.css',
        'resources/css/admin/sales/invoice-document.css',
    ])

    @stack('styles')
</head>
<body class="cliente-layout cliente-layout--print">
    <main class="cliente-main cliente-main--print">
        @yield('content')
    </main>

    @stack('scripts')
</body>
</html>
