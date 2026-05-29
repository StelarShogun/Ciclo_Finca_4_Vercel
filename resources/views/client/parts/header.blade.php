<header class="cliente-header">
    <div class="header-container">
        <div class="header-content">

            {{-- Logo --}}
            <div class="logo-section">
                <a href="{{ route('clients.home') }}" class="logo-link" aria-label="Marca Ciclo Finca 4">
                    <span class="logo-icon-wrap" aria-hidden="true">
                        <picture>
                            <source
                                type="image/webp"
                                media="(max-width: 767px)"
                                srcset="{{ asset('assets/images/brand/logo-ciclo-finca-icon-64.webp') }}">
                            <source
                                type="image/webp"
                                srcset="{{ asset('assets/images/brand/logo-ciclo-finca-icon-128.webp') }}">
                            <img
                                src="{{ asset('assets/images/brand/logo-ciclo-finca-icon-64.png') }}"
                                alt=""
                                width="56"
                                height="56"
                                class="logo-img logo-img--icon-only"
                                loading="eager"
                                decoding="async"
                                data-fallback-src="{{ asset('logo-navbar.svg') }}"
                                onerror="this.src=this.dataset.fallbackSrc;">
                        </picture>
                    </span>
                    <span class="logo-wordmark">
                        <span class="logo-text logo-text--dark">CICLO</span>
                        <span class="logo-text logo-text--green">FINCA</span>
                        <span class="logo-text logo-text--dark">4</span>
                    </span>
                </a>
            </div>

            {{-- Hamburger toggle: only visible on mobile (CSS controls display) --}}
            @php
                $cf4HeaderMenuAlert = false;
                if (auth('clients')->check()) {
                    $cf4HeaderMenuUserId = (int) auth('clients')->user()->user_id;
                    $cf4HeaderMenuAlert = ($cartCount ?? 0) > 0
                        || \App\Models\Sale::countActiveClientInvoices($cf4HeaderMenuUserId) > 0
                        || \App\Models\Sale::countUnseenInClientHistory($cf4HeaderMenuUserId) > 0
                        || auth('clients')->user()->unreadNotifications()->count() > 0;
                } elseif (! session('admin_catalog_mode')) {
                    $cf4HeaderMenuAlert = ($cartCount ?? 0) > 0;
                }
            @endphp
            @if(! session('admin_catalog_mode'))
                <a href="{{ route('clients.cart') }}"
                   class="cart-btn cart-btn-link header-mobile-cart-btn"
                   id="header-mobile-cart-link"
                   data-cart-count="{{ $cartCount ?? 0 }}"
                   aria-label="Ver carrito{{ ($cartCount ?? 0) > 0 ? ' ('.$cartCount.' productos)' : '' }}"
                   title="Carrito">
                    <i class="fas fa-shopping-cart" aria-hidden="true"></i>
                    <span class="cart-count" id="header-mobile-cart-count">{{ ($cartCount ?? 0) > 0 ? $cartCount : 0 }}</span>
                </a>
            @endif

            <button @class(['header-menu-toggle', 'has-alert' => $cf4HeaderMenuAlert]) id="header-menu-toggle" type="button"
                aria-label="{{ $cf4HeaderMenuAlert ? 'Abrir menú de navegación (tienes novedades)' : 'Abrir menú de navegación' }}" aria-controls="header-menu-panel" aria-expanded="false">
                <i class="fas fa-bars" aria-hidden="true"></i>
                <span class="header-menu-toggle-badge" @if(! $cf4HeaderMenuAlert) hidden @endif aria-hidden="true"></span>
            </button>

            {{-- Collapsible panel: nav + actions. aria-hidden toggled by JS. --}}
            <div class="header-menu-panel" id="header-menu-panel" aria-hidden="true">
                {{-- Solo Inicio + Catálogo: centrados en la barra (columna media del grid) --}}
                <div class="header-nav-slot">
                    <nav class="main-nav">
                        <a href="{{ route('clients.home') }}"
                            class="nav-link {{ request()->routeIs('clients.home') ? 'active' : '' }}">
                            <i class="fas fa-home"></i>
                            <span>Inicio</span>
                        </a>
                        <a href="{{ route('clients.catalog') }}"
                            class="nav-link {{ request()->routeIs('clients.catalog') ? 'active' : '' }}">
                            <i class="fas fa-bicycle" aria-hidden="true"></i>
                            <span>Catálogo</span>
                        </a>
                    </nav>
                </div>

                @php
                    $cf4SearchSuggestionsUrl = route('api.products.suggestions');
                    $cf4SearchTrendingUrl = route('api.catalog.search-trending');
                    $cf4ShowHeaderCatalogSearch = request()->routeIs('clients.catalog', 'clients.product');
                @endphp
                {{-- Búsqueda: catálogo y detalle de producto (envía al listado con ?search=). --}}
                <div class="header-right-cluster">
                    @if($cf4ShowHeaderCatalogSearch)
                    <div class="header-catalog-search"
                         data-catalog-suggestions
                         data-suggestions-url="{{ $cf4SearchSuggestionsUrl }}"
                         data-trending-url="{{ $cf4SearchTrendingUrl }}">
                        <div class="header-catalog-search-track">
                            <button type="button"
                                    class="header-catalog-search-toggle"
                                    aria-expanded="false"
                                    aria-controls="catalog-nav-search-inner"
                                    aria-label="Abrir búsqueda en catálogo">
                                <i class="fas fa-magnifying-glass" aria-hidden="true"></i>
                                <span class="header-catalog-search-toggle-text">Buscar</span>
                            </button>
                            <div class="header-catalog-search-inner" id="catalog-nav-search-inner">
                                <form class="header-catalog-search-form"
                                      method="GET"
                                      action="{{ route('clients.catalog') }}"
                                      id="catalog-nav-search-form"
                                      role="search"
                                      aria-label="Buscar en catálogo">
                                    @if(request()->routeIs('clients.catalog'))
                                        @foreach (['category_id', 'brand_id', 'min_price', 'max_price', 'sort', 'direction', 'per_page'] as $catalogNavKey)
                                            @if(request()->filled($catalogNavKey))
                                                <input type="hidden" name="{{ $catalogNavKey }}" value="{{ request($catalogNavKey) }}">
                                            @endif
                                        @endforeach
                                    @endif
                                    <label class="header-catalog-search-label" for="catalog-nav-search">Buscar en catálogo</label>
                                    <input type="search"
                                           id="catalog-nav-search"
                                           name="search"
                                           class="header-catalog-search-input"
                                           placeholder="Buscar productos…"
                                           value="{{ request()->routeIs('clients.catalog') ? request('search', '') : '' }}"
                                           autocomplete="off"
                                           autocorrect="off"
                                           autocapitalize="off"
                                           spellcheck="false"
                                           role="combobox"
                                           aria-autocomplete="list"
                                           aria-expanded="false"
                                           aria-controls="catalog-search-suggestions"
                                           maxlength="200">
                                    <button type="submit"
                                            class="header-catalog-search-submit"
                                            aria-label="Ir al catálogo con esta búsqueda">
                                        <i class="fas fa-arrow-right" aria-hidden="true"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                        <div id="catalog-search-suggestions"
                             class="catalog-search-suggestions catalog-search-suggestions--header"
                             role="listbox"
                             aria-label="Sugerencias de búsqueda"
                             aria-hidden="true"></div>
                    </div>
                    @endif

                    {{-- Header actions: cart and user menu --}}
                    <div class="header-actions">

                    @if(auth('clients')->check())
                        @php
                            $clientUserId = (int) auth('clients')->user()->user_id;
                            $activeInvoiceCount = \App\Models\Sale::countActiveClientInvoices($clientUserId);
                            $unseenHistoryCount = \App\Models\Sale::countUnseenInClientHistory($clientUserId);
                            $unreadNotificationCount = auth('clients')->user()->unreadNotifications()->count();
                            $notificationBadgeLabel = $unreadNotificationCount > 9 ? '9+' : (string) $unreadNotificationCount;
                        @endphp

                        {{-- Cart button with item counter --}}
                        <a href="{{ route('clients.cart') }}" class="cart-btn cart-btn-link" id="cart-link"
                            data-cart-count="{{ $cartCount ?? 0 }}" title="Ver carrito">
                            <i class="fas fa-shopping-cart"></i>
                            <span class="cart-count" id="cart-count">{{ $cartCount ?? 0 }}</span>
                        </a>

                        {{-- Invoices button with pending count badge --}}
                        <a href="{{ route('clients.invoices') }}"
                           class="cf4-invoices-btn {{ request()->routeIs('clients.invoices') ? 'active' : '' }}"
                           id="invoices-link"
                           title="Mis facturas (pendientes y por recoger)">
                            <i class="fas fa-file-invoice"></i>
                            @if($activeInvoiceCount > 0)
                                <span class="cf4-invoice-count" id="invoice-count">{{ $activeInvoiceCount }}</span>
                            @endif
                            @if($unseenHistoryCount > 0)
                                <span class="cf4-history-badge" id="history-badge" title="Compras nuevas en Historial" aria-label="Historial con compras nuevas"></span>
                            @endif
                        </a>

                        {{-- Dropdown menu for the authenticated client --}}
                        <div class="user-menu-wrap" id="user-menu">
                            <button class="user-menu-trigger" id="user-menu-trigger" type="button" aria-expanded="false"
                                aria-haspopup="true" title="Mi cuenta">
                                <span class="user-menu-trigger-avatar-wrap">
                                    <span class="user-avatar-bubble">
                                        {{ strtoupper(substr(Auth::guard('clients')->user()->name, 0, 1)) }}{{ strtoupper(substr(Auth::guard('clients')->user()->first_surname, 0, 1)) }}
                                    </span>
                                    <span class="cf4-invoice-count user-menu-trigger-notification-badge"
                                          id="nav-notification-badge"
                                          style="{{ $unreadNotificationCount > 0 ? '' : 'display:none' }}">{{ $notificationBadgeLabel ?: '0' }}</span>
                                </span>
                                <span class="user-trigger-name">
                                    {{ Auth::guard('clients')->user()->name }}
                                </span>
                                <i class="fas fa-chevron-down user-trigger-caret"></i>
                            </button>

                            <div class="user-dropdown-panel" id="user-dropdown" aria-hidden="true" role="menu">
                                <div class="user-dropdown-head">
                                    <p class="user-dropdown-fullname">
                                        {{ Auth::guard('clients')->user()->name }}
                                        {{ Auth::guard('clients')->user()->first_surname }}
                                    </p>
                                    <p class="user-dropdown-email">
                                        {{ Auth::guard('clients')->user()->gmail }}
                                    </p>
                                </div>
                                <div class="user-dropdown-body">
                                    <button type="button"
                                            class="user-dropdown-item cf4-favorites-open-trigger"
                                            role="menuitem">
                                        <i class="far fa-heart"></i>
                                        <span>Mis favoritos</span>
                                    </button>
                                    <a href="{{ route('clients.notifications') }}"
                                        class="user-dropdown-item user-dropdown-item--with-badge {{ request()->routeIs('clients.notifications') ? 'active' : '' }}"
                                        role="menuitem">
                                        <i class="fas fa-bell"></i>
                                        <span>Notificaciones</span>
                                        <span class="cf4-nav-badge cf4-nav-badge--inline"
                                              style="{{ $unreadNotificationCount > 0 ? '' : 'display:none' }}">{{ $notificationBadgeLabel ?: '0' }}</span>
                                    </a>
                                    <a href="{{ route('clients.profile') }}"
                                        class="user-dropdown-item {{ request()->routeIs('clients.profile') ? 'active' : '' }}"
                                        role="menuitem">
                                        <i class="fas fa-user-circle"></i>
                                        Mi Perfil
                                    </a>
                                </div>
                                <div class="user-dropdown-foot">
                                    @include('shared.partials.cf4-theme-dropdown-row')
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

                    @elseif(session('admin_catalog_mode'))
                        {{-- Admin browsing the client catalog — shows admin identity, no cart, "Volver" instead of logout --}}
                        @php $adminSession = session('admin_catalog_mode'); @endphp
                        <div class="user-menu-wrap" id="user-menu">
                            <button class="user-menu-trigger" id="user-menu-trigger" type="button" aria-expanded="false"
                                aria-haspopup="true" title="Mi cuenta">
                                <div class="user-avatar-bubble">
                                    {{ strtoupper(substr($adminSession['name'], 0, 1)) }}{{ strtoupper(substr($adminSession['first_surname'], 0, 1)) }}
                                </div>
                                <span class="user-trigger-name">{{ $adminSession['name'] }}</span>
                                <i class="fas fa-chevron-down user-trigger-caret"></i>
                            </button>

                            <div class="user-dropdown-panel" id="user-dropdown" aria-hidden="true" role="menu">
                                <div class="user-dropdown-head">
                                    <p class="user-dropdown-fullname">
                                        {{ $adminSession['name'] }} {{ $adminSession['first_surname'] }}
                                    </p>
                                    <p class="user-dropdown-email">{{ $adminSession['gmail'] }}</p>
                                </div>
                                <div class="user-dropdown-body">
                                    <span class="user-dropdown-item" style="cursor:default;opacity:.7;">
                                        <i class="fas fa-shield-alt"></i>
                                        Administrador
                                    </span>
                                </div>
                                <div class="user-dropdown-foot">
                                    @include('shared.partials.cf4-theme-dropdown-row')
                                    <a href="{{ route('admin.catalog.exit') }}" class="user-dropdown-item"
                                       role="menuitem" style="color:var(--color-primary);text-decoration:none;">
                                        <i class="fas fa-arrow-left"></i>
                                        Volver al panel admin
                                    </a>
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
                            <a href="{{ route('clients.profile') }}" class="user-dropdown-item session-profile-link" title="Mi Perfil">
                                <i class="fas fa-user-circle"></i>
                                <span>{{ session('client_name') }}</span>
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
                    @endif

                    </div>{{-- /header-actions --}}
                </div>{{-- /header-right-cluster --}}
            </div>{{-- /header-menu-panel --}}
        </div>
    </div>
</header>

@auth('clients')
@php
    $favoritesIndexUrl = \Illuminate\Support\Facades\Route::has('clients.favorites.index')
        ? route('clients.favorites.index')
        : url('/favorites');
    $favoritesToggleUrl = \Illuminate\Support\Facades\Route::has('clients.favorites.toggle')
        ? route('clients.favorites.toggle')
        : url('/favorites/toggle');
    $initialFavorites = [];
@endphp
<div class="cf4-favorites-overlay" id="favorites-overlay" hidden></div>
<aside class="cf4-favorites-drawer" id="favorites-drawer" aria-hidden="true">
    <div class="cf4-favorites-drawer-header">
        <h3><i class="fas fa-heart"></i> Mis Favoritos</h3>
        <button type="button" id="favorites-close-btn" aria-label="Cerrar favoritos">
            <i class="fas fa-times"></i>
        </button>
    </div>
    <div class="cf4-favorites-drawer-body" id="favorites-drawer-body">
        <div class="cf4-favorites-empty">
            <i class="far fa-heart"></i>
            <p>Aún no tienes productos guardados.<br>¡Explora el catálogo!</p>
        </div>
    </div>
    <footer class="cf4-favorites-drawer-footer" id="favorites-drawer-pagination" hidden>
        <p class="cf4-favorites-pagination-info" id="favorites-pagination-info" aria-live="polite"></p>
        <div class="cf4-favorites-pagination-nav">
            <button type="button" class="cf4-favorites-page-btn" id="favorites-page-prev" disabled>
                <i class="fas fa-chevron-left" aria-hidden="true"></i> Anterior
            </button>
            <button type="button" class="cf4-favorites-page-btn" id="favorites-page-next" disabled>
                Siguiente <i class="fas fa-chevron-right" aria-hidden="true"></i>
            </button>
        </div>
    </footer>
</aside>

<meta name="cf4-favorites-index-url" content="{{ $favoritesIndexUrl }}">
<meta name="cf4-favorites-toggle-url" content="{{ $favoritesToggleUrl }}">
<meta name="cf4-favorites-initial" content='@json($initialFavorites)'>
@endauth

{{-- Header alert state for hamburger badge (catalog, facturas, etc.) — not tied to menu panel visibility. --}}
@if(auth('clients')->check())
    @php
        $cf4HeaderAlertInvoices = $activeInvoiceCount ?? \App\Models\Sale::countActiveClientInvoices((int) auth('clients')->user()->user_id);
        $cf4HeaderAlertHistory = $unseenHistoryCount ?? \App\Models\Sale::countUnseenInClientHistory((int) auth('clients')->user()->user_id);
        $cf4HeaderAlertNotifications = auth('clients')->user()->unreadNotifications()->count();
    @endphp
    <meta name="cf4-header-alert-cart" content="{{ $cartCount ?? 0 }}">
    <meta name="cf4-header-alert-invoices" content="{{ $cf4HeaderAlertInvoices }}">
    <meta name="cf4-header-alert-history" content="{{ $cf4HeaderAlertHistory }}">
    <meta name="cf4-header-alert-notifications" content="{{ $cf4HeaderAlertNotifications }}">
@elseif(! session('admin_catalog_mode'))
    <meta name="cf4-header-alert-cart" content="{{ $cartCount ?? 0 }}">
    <meta name="cf4-header-alert-invoices" content="0">
    <meta name="cf4-header-alert-history" content="0">
    <meta name="cf4-header-alert-notifications" content="0">
@endif

{{-- Invoice + notification heartbeats: badge + toast polling on all auth pages. --}}
@auth('clients')
@if(! session('admin_catalog_mode'))
<meta name="cf4-notifications-heartbeat-url" content="{{ route('clients.notifications.heartbeat') }}">
<meta name="cf4-invoice-heartbeat-url" content="{{ route('clients.invoices.heartbeat') }}">
<meta name="cf4-invoice-initial-count" content="{{ $activeInvoiceCount ?? \App\Models\Sale::countActiveClientInvoices((int) auth('clients')->user()->user_id) }}">
<meta name="cf4-unseen-history-initial-count" content="{{ $unseenHistoryCount ?? \App\Models\Sale::countUnseenInClientHistory((int) auth('clients')->user()->user_id) }}">
@endif
@endauth

{{-- Ventana para retiro tras "listo para recoger" (copia post-checkout; respeta AppSetting / READY_TO_PICKUP_EXPIRATION_HOURS). --}}
<meta name="cf4-ready-to-pickup-expiration-hours" content="{{ \App\Models\Sale::getReadyToPickupExpirationHours() }}">

@vite('resources/js/client/clients-header.js')