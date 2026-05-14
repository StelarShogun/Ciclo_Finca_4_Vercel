<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Error — Ciclo Finca 4')</title>
    @stack('meta')

    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">

    <link rel="preconnect" href="https://cdnjs.cloudflare.com">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    @vite(['resources/css/client/variables-reset.css'])

    <style>
        .cf4-error-layout {
            min-height: 100dvh;
            display: flex;
            flex-direction: column;
            background:
                radial-gradient(ellipse 70% 55% at 8% 0%, rgba(174, 213, 129, 0.30) 0%, transparent 48%),
                radial-gradient(ellipse 55% 45% at 95% 100%, rgba(142, 182, 155, 0.22) 0%, transparent 42%),
                linear-gradient(160deg, #e8f5e9 0%, #c8e6c9 60%, #a5d6a7 100%);
        }

        /* ---- minimal nav ---- */
        .cf4-error-nav {
            flex: 0 0 auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 28px;
            height: 68px;
            background: rgba(255, 255, 255, 0.62);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.72);
            box-shadow: 0 1px 0 rgba(11, 43, 38, 0.05), 0 4px 16px rgba(11, 43, 38, 0.04);
        }

        .cf4-error-nav-logo {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
        }

        .cf4-error-nav-icon {
            width: 36px;
            height: 36px;
            object-fit: contain;
            display: block;
        }

        .cf4-error-nav-wordmark {
            font-family: 'Poppins', sans-serif;
            font-weight: 700;
            font-size: 1.05rem;
            letter-spacing: 0.04em;
            line-height: 1;
            color: var(--brand-darkest);
        }

        .cf4-error-nav-wordmark em {
            font-style: normal;
            color: var(--brand-light);
        }

        .cf4-error-nav-back {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-family: 'Poppins', sans-serif;
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--brand-medium);
            text-decoration: none;
            padding: 6px 14px;
            border-radius: 999px;
            border: 1.5px solid var(--brand-light);
            background: rgba(255, 255, 255, 0.5);
            transition: background 0.2s, color 0.2s;
        }

        .cf4-error-nav-back:hover {
            background: rgba(255, 255, 255, 0.82);
            color: var(--brand-medium-dark);
        }

        /* ---- main area ---- */
        .cf4-error-main {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 20px 52px;
        }
    </style>

    @stack('styles')

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body class="cf4-error-layout">

    <header class="cf4-error-nav">
        <a href="{{ route('clients.home') }}" class="cf4-error-nav-logo" aria-label="Ir al inicio — Ciclo Finca 4">
            <img
                src="{{ asset('assets/images/brand/logo-ciclo-finca-icon-transparent.png') }}"
                class="cf4-error-nav-icon"
                width="36"
                height="36"
                alt=""
                loading="eager"
                decoding="async"
                onerror="this.src='{{ asset('logo-navbar.svg') }}';"
            >
            <span class="cf4-error-nav-wordmark">CICLO <em>FINCA</em> 4</span>
        </a>

        <a href="{{ route('clients.catalog') }}" class="cf4-error-nav-back">
            <i class="fas fa-bicycle" aria-hidden="true"></i>
            Catálogo
        </a>
    </header>

    <main class="cf4-error-main">
        @yield('content')
    </main>

    @stack('scripts')

</body>
</html>
