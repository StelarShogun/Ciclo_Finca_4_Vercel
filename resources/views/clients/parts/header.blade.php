<header class="cliente-header">
    <div class="header-container">
        <div class="header-content">
            <div class="logo-section">
                <a href="{{ route('clients.home') }}" class="logo-link">
                    <img src="{{ asset('assets/images/logo.png') }}" alt="Ciclo Pérez" class="logo-img"
                        onerror="this.src='{{ asset('favicon.svg') }}'">
                    <span class="logo-text">Ciclo Pérez</span>
                </a>
            </div>

            <nav class="main-nav">
                <a href="{{ route('clients.home') }}"
                    class="nav-link {{ request()->routeIs('clients.home') ? 'active' : '' }}">
                    <i class="fas fa-home"></i>
                    <span>Inicio</span>
                </a>
                <a href="{{ route('clients.catalog') }}"
                    class="nav-link {{ request()->routeIs('clients.catalog') ? 'active' : '' }}">
                    <i class="fas fa-th"></i>
                    <span>Catálogo</span>
                </a>
            </nav>

            <div class="header-actions">
                @auth
                    <a href="{{ route('clients.cart') }}" class="cart-btn cart-btn-link" id="cart-link"
                        data-cart-count="{{ $cartCount ?? 0 }}" title="Ver carrito">
                        <i class="fas fa-shopping-cart"></i>
                        <span class="cart-count" id="cart-count">{{ $cartCount ?? 0 }}</span>
                    </a>
                @else
                    <button class="cart-btn" id="cart-guest" type="button" title="Ver carrito">
                        <i class="fas fa-shopping-cart"></i>
                        <span class="cart-count" id="cart-count">0</span>
                    </button>

                    @if (request()->routeIs('login.show'))
                        <a href="{{ route('clients.home') }}" class="btn btn-outline-secondary btn-sm"
                            style="display:flex;align-items:center;gap:6px;">
                            <i class="fas fa-arrow-left"></i>
                            <span>Regresar</span>
                        </a>
                    @elseif(session('client_id'))
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <div style="display: flex; flex-direction: column; align-items: center;">
                                <i class="fas fa-user-circle" style="font-size: 2rem; color: #218838;"></i>
                                <span
                                    style="font-size: 0.95rem; color: #218838; margin-top: 2px;">{{ session('client_name') }}</span>
                            </div>
                            <form action="{{ route('logout') }}" method="POST" class="logout-form" style="margin: 0;">
                                @csrf
                                <button type="submit" class="user-dropdown-item user-dropdown-logout"
                                    style="color:#dc3545;font-weight:600;background:none;border:none;cursor:pointer;font-size:1rem;display:flex;align-items:center;gap:4px;"
                                    title="Cerrar Sesión">
                                    <i class="fas fa-sign-out-alt"></i>
                                    <span>Cerrar Sesión</span>
                                </button>
                            </form>
                        </div>
                    @else
                        <a href="{{ route('login.show') }}" class="btn btn-primary btn-sm">
                            <i class="fas fa-sign-in-alt"></i>
                            <span>Iniciar Sesión</span>
                        </a>
                    @endif
                @endauth
            </div>
        </div>
    </div>
</header>
