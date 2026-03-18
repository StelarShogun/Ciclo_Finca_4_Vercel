<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Ciclo Finca 4 - Tienda de Bicicletas')</title>
    
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
    @unless(request()->routeIs('login.show'))
        <header class="cliente-header">
            <div class="header-container">
                <div class="header-content">
                    <div class="logo-section">
                        <a href="{{ route('clientes.home') }}" class="logo-link">
                            <img src="{{ asset('assets/images/logo.png') }}" alt="Ciclo Finca 4" class="logo-img">
                            <span class="logo-text">Ciclo Finca 4</span>
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
                        @auth
                        <a href="{{ route('clientes.carrito') }}" class="cart-btn cart-btn-link" id="cart-link" data-cart-count="{{ $cartCount ?? 0 }}" title="Ver carrito">
                            <i class="fas fa-shopping-cart"></i>
                            <span class="cart-count" id="cart-count">{{ $cartCount ?? 0 }}</span>
                        </a>
                        @else
                        <button class="cart-btn" id="cart-guest" type="button" title="Ver carrito">
                            <i class="fas fa-shopping-cart"></i>
                            <span class="cart-count" id="cart-count">0</span>
                        </button>
                        @if(session('client_id'))
                            <div style="display: flex; align-items: center; gap: 12px;">
                                <div style="display: flex; flex-direction: column; align-items: center;">
                                    <i class="fas fa-user-circle" style="font-size: 2rem; color: #218838;"></i>
                                    <span style="font-size: 0.95rem; color: #218838; margin-top: 2px;">{{ session('client_name') }}</span>
                                </div>
                                <form action="{{ route('logout') }}" method="POST" class="logout-form" style="margin: 0;">
                                    @csrf
                                    <button type="submit" class="user-dropdown-item user-dropdown-logout" style="color:#dc3545;font-weight:600;background:none;border:none;cursor:pointer;font-size:1rem;display:flex;align-items:center;gap:4px;" title="Cerrar Sesión">
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
    @endunless

    <!-- Main Content -->
    <main class="cliente-main" @if(request()->routeIs('login.show')) style="padding-top:0;" @endif>
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

    @if(!request()->routeIs('login.show'))
        <!-- Footer -->
        <footer class="cliente-footer">
            <div class="footer-container">
                <div class="footer-content">
                    <div class="footer-section">
                        <h4>Ciclo Finca 4</h4>
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
                    <p>&copy; {{ date('Y') }} Ciclo Finca 4. Todos los derechos reservados.</p>
                </div>
            </div>
        </footer>
    @endif

    <!-- Modal de Login -->
    <div class="modal" id="login-modal">
        <div class="modal-content modal-md">
            <div class="modal-header">
                <h3>Iniciar Sesión</h3>
                <button class="modal-close" id="close-login-modal">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <!-- Formulario de Login -->
                <form id="public-login-form" method="POST" action="{{ route('login') }}">
                    @csrf
                    <div class="form-group">
                        <label for="login-email" class="login-field-label">
                            <i class="fas fa-envelope login-field-icon" aria-hidden="true"></i>
                            Correo Electrónico
                        </label>
                        <input type="email" id="login-email" name="email" class="form-control" required placeholder="ejemplo@correo.com">
                    </div>
                    <div class="form-group">
                        <label for="login-password" class="login-field-label">
                            <i class="fas fa-lock login-field-icon" aria-hidden="true"></i>
                            Contraseña
                        </label>
                        <input type="password" id="login-password" name="password" class="form-control" required placeholder="Ingresa tu contraseña">
                    </div>
                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="remember" id="remember">
                            <span>Recordarme</span>
                        </label>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block btn-lg" id="login-submit-btn">
                        <i class="fas fa-sign-in-alt"></i>
                        <span>Iniciar Sesión</span>
                        <span class="btn-loading hidden" id="login-loading">Iniciando...</span>
                    </button>
                </form>
                
                <div class="login-divider">
                    <span>o</span>
                </div>
                
                <!-- Botones OAuth -->
                <div class="oauth-buttons">
                    <a href="{{ route('auth.google') }}" class="oauth-btn google-btn">
                        <span class="google-g-icon" aria-hidden="true">G</span>
                        <span class="google-text">
                            Continuar con
                            <span class="google-brand" aria-hidden="true">
                                <span class="brand-letter brand-g">G</span>
                                <span class="brand-letter brand-o">o</span>
                                <span class="brand-letter brand-o2">o</span>
                                <span class="brand-letter brand-g2">g</span>
                                <span class="brand-letter brand-l">l</span>
                                <span class="brand-letter brand-e">e</span>
                            </span>
                        </span>
                    </a>
                    <a href="{{ route('auth.facebook') }}" class="oauth-btn facebook-btn">
                        <i class="fab fa-facebook"></i>
                        <span>Continuar con Facebook</span>
                    </a>
                </div>
                
                <div class="login-footer">
                    <p class="login-footer-text">¿No tienes una cuenta?</p>
                    <a href="#" id="show-register-form" class="login-register-btn">
                        <i class="fas fa-user-plus" aria-hidden="true"></i>
                        <span>Crear cuenta gratis</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="modal-overlay" id="login-modal-overlay"></div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    @vite(['resources/js/clientes.js'])
    @stack('scripts')
</body>
</html>