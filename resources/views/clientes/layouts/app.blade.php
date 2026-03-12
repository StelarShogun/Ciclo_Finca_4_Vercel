<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Ciclo Pérez - Tienda de Bicicletas')</title>
    
    <!-- Favicons modernos -->
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    
    @vite(['resources/js/app.js'])
    @stack('styles')
    
    <!-- Fuentes e iconos -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="cliente-layout">
    <!-- Header -->
    <header class="cliente-header">
        <div class="header-container">
            <div class="header-content">
                <div class="logo-section">
                    <a href="{{ route('clientes.home') }}" class="logo-link">
                        <img src="{{ asset('assets/images/logo.png') }}" alt="Ciclo Pérez" class="logo-img" onerror="this.src='{{ asset('favicon.svg') }}'">
                        <span class="logo-text">Ciclo Pérez</span>
                    </a>
                </div>
                
                <nav class="main-nav">
                    <a href="{{ route('clientes.home') }}" class="nav-link {{ request()->routeIs('clientes.home') ? 'active' : '' }}">
                        <i class="fas fa-home"></i>
                        <span>Inicio</span>
                    </a>
                    <a href="{{ route('clientes.catalogo') }}" class="nav-link {{ request()->routeIs('clientes.catalogo') ? 'active' : '' }}">
                        <i class="fas fa-th"></i>
                        <span>Catálogo</span>
                    </a>
                </nav>
                
                <div class="header-actions">
                    <button class="cart-btn" id="cart-toggle" data-cart-count="{{ $cartCount ?? 0 }}">
                        <i class="fas fa-shopping-cart"></i>
                        <span class="cart-count" id="cart-count">{{ $cartCount ?? 0 }}</span>
                    </button>
                    @auth
                        @if(session('client_id'))
                            <div class="user-menu" id="user-menu">
                                <button type="button" class="user-menu-trigger" id="user-menu-trigger" aria-haspopup="true" aria-expanded="false" title="Mi perfil">
                                    <i class="fas fa-user-circle"></i>
                                    <span>{{ session('client_name') }} {{ session('client_first_surname') }} {{ session('client_second_surname') }}</span>
                                </button>
                                <form action="{{ route('logout') }}" method="POST" class="logout-form">
                                    @csrf
                                    <button type="submit" class="user-dropdown-item user-dropdown-logout" title="Cerrar Sesión">
                                        <i class="fas fa-sign-out-alt"></i>
                                        <span>Cerrar Sesión</span>
                                    </button>
                                </form>
                            </div>
                        @else
                            <a href="/login" class="btn btn-primary btn-sm">
                                <i class="fas fa-sign-in-alt"></i>
                                <span>Iniciar Sesión</span>
                            </a>
                        @endif
                    @endauth
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="cliente-main">
        @if(session('status'))
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                {{ session('status') }}
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i>
                {{ session('error') }}
            </div>
        @endif

        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="cliente-footer">
        <div class="footer-container">
            <div class="footer-content">
                <div class="footer-section">
                    <h4>Ciclo Pérez</h4>
                    <p>Tu tienda especializada en bicicletas y accesorios para ciclismo.</p>
                </div>
                <div class="footer-section">
                    <h4>Enlaces</h4>
                    <ul class="footer-links">
                        <li><a href="{{ route('clientes.home') }}">Inicio</a></li>
                        <li><a href="{{ route('clientes.catalogo') }}">Catálogo</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h4>Contacto</h4>
                    <p>Visítanos en nuestra tienda física</p>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; {{ date('Y') }} Ciclo Pérez. Todos los derechos reservados.</p>
            </div>
        </div>
    </footer>

    <!-- Carrito Sidebar -->
    <div class="cart-sidebar" id="cart-sidebar">
        <div class="cart-sidebar-header">
            <h3>Carrito de Compras</h3>
            <button class="cart-close" id="cart-close">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="cart-sidebar-content" id="cart-content">
            <!-- Contenido del carrito se carga dinámicamente -->
            <div class="cart-empty">
                <i class="fas fa-shopping-cart"></i>
                <p>Tu carrito está vacío</p>
                <a href="{{ route('clientes.catalogo') }}" class="btn btn-primary">Ver Catálogo</a>
            </div>
        </div>
        <div class="cart-sidebar-footer" id="cart-footer" style="display: none;">
            <div class="cart-total">
                <span>Total:</span>
                <span class="cart-total-amount" id="cart-total">₡0</span>
            </div>
            <a href="{{ route('clientes.carrito') }}" class="btn btn-primary btn-block">Ver Carrito</a>
        </div>
    </div>
    <div class="cart-overlay" id="cart-overlay"></div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    @vite(['resources/js/clientes.js'])
    @stack('scripts')
</body>
</html>