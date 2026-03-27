<header class="cliente-header">
    <div class="header-container">
        <div class="header-content">

            {{-- Logo --}}
            <div class="logo-section">
                <a href="{{ route('clients.home') }}" class="logo-link">
                    {{-- Ícono circular completo (PNG 500×500, sin texto); wordmark en HTML --}}
                    <span class="logo-icon-wrap" aria-hidden="true">
                        <img src="{{ asset('assets/images/brand/logo-ciclo-finca-icon.png') }}" alt=""
                            width="500" height="500" class="logo-img logo-img--icon-only" loading="eager" decoding="async"
                            data-fallback-src="{{ asset('logo-navbar.svg') }}"
                            onerror="this.src=this.dataset.fallbackSrc;">
                    </span>
                    <span class="logo-wordmark">
                        <span class="logo-text logo-text--dark">CICLO</span><span class="logo-text logo-text--green"> FINCA</span><span class="logo-text logo-text--dark"> 4</span>
                    </span>
                </a>
            </div>

            {{-- Main navigation --}}
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

            {{-- Header actions: cart and user menu --}}
            <div class="header-actions">

                @auth('clients')
                    {{-- Cart button with item counter --}}
                    <a href="{{ route('clients.cart') }}" class="cart-btn cart-btn-link" id="cart-link"
                        data-cart-count="{{ $cartCount ?? 0 }}" title="Ver carrito">
                        <i class="fas fa-shopping-cart"></i>
                        <span class="cart-count" id="cart-count">{{ $cartCount ?? 0 }}</span>
                    </a>

                    {{-- Dropdown menu for the authenticated user --}}
                    <div class="user-menu-wrap" id="user-menu">
                        <button class="user-menu-trigger" id="user-menu-trigger" type="button" aria-expanded="false"
                            aria-haspopup="true" title="Mi cuenta">
                            {{-- Avatar with the client's initials --}}
                            <div class="user-avatar-bubble">
                                {{ strtoupper(substr(Auth::guard('clients')->user()->name, 0, 1)) }}{{ strtoupper(substr(Auth::guard('clients')->user()->first_surname, 0, 1)) }}
                            </div>
                            <span class="user-trigger-name">
                                {{ Auth::guard('clients')->user()->name }}
                            </span>
                            <i class="fas fa-chevron-down user-trigger-caret"></i>
                        </button>

                        <div class="user-dropdown-panel" id="user-dropdown" aria-hidden="true" role="menu">

                            {{-- Dropdown header: full name and email --}}
                            <div class="user-dropdown-head">
                                <p class="user-dropdown-fullname">
                                    {{ Auth::guard('clients')->user()->name }}
                                    {{ Auth::guard('clients')->user()->first_surname }}
                                </p>
                                <p class="user-dropdown-email">
                                    {{ Auth::guard('clients')->user()->gmail }}
                                </p>
                            </div>

                            {{-- Profile link --}}
                            <div class="user-dropdown-body">
                                <a href="{{ route('clients.profile') }}"
                                    class="user-dropdown-item {{ request()->routeIs('clients.profile') ? 'active' : '' }}"
                                    role="menuitem">
                                    <i class="fas fa-user-circle"></i>
                                    Mi Perfil
                                </a>
                            </div>

                            {{-- Dropdown footer: logout --}}
                            <div class="user-dropdown-foot">
                                <form action="{{ route('logout') }}" method="POST" class="logout-form">
                                    @csrf
                                    <button type="submit" class="user-dropdown-item user-dropdown-logout" role="menuitem">
                                        <i class="fas fa-sign-out-alt"></i>
                                        Cerrar Sesión
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                @else
                    {{-- Cart button for unauthenticated guests --}}
                    <button class="cart-btn" id="cart-guest" type="button" data-cart-count="0" title="Ver carrito">
                        <i class="fas fa-shopping-cart"></i>
                        <span class="cart-count" id="cart-count">0</span>
                    </button>

                    @if (request()->routeIs('login.show'))
                        {{-- Back button shown only on the login page --}}
                        <a href="{{ route('clients.home') }}" class="btn btn-outline-secondary btn-sm"
                            style="display:flex;align-items:center;gap:6px;">
                            <i class="fas fa-arrow-left"></i>
                            <span>Regresar</span>
                        </a>
                    @elseif(session('client_id'))
                        {{-- Fallback: session-based user with no active Auth guard --}}
                        <a href="{{ route('clients.profile') }}" class="user-dropdown-item" title="Mi Perfil"
                            style="display:flex;flex-direction:column;align-items:center;
                                  text-decoration:none;color:var(--color-primary);">
                            <i class="fas fa-user-circle" style="font-size:2rem;"></i>
                            <span style="font-size:.9rem;margin-top:2px;">
                                {{ session('client_name') }}
                            </span>
                        </a>
                        <form action="{{ route('logout') }}" method="POST" class="logout-form" style="margin:0;">
                            @csrf
                            <button type="submit" class="user-dropdown-item user-dropdown-logout" title="Cerrar Sesión">
                                <i class="fas fa-sign-out-alt"></i>
                                <span>Cerrar Sesión</span>
                            </button>
                        </form>
                    @else
                        {{-- Login button for unauthenticated guests --}}
                        <a href="{{ route('login.show') }}" class="btn btn-primary btn-sm">
                            <i class="fas fa-sign-in-alt"></i>
                            <span>Iniciar Sesión</span>
                        </a>
                    @endif
                @endauth

            </div>{{-- /header-actions --}}
        </div>
    </div>
</header>

@vite('resources/js/client/clients-users.js')
