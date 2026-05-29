<aside class="admin-sidebar expanded">
    <div class="sidebar-header">
        <a href="{{ route('dashboard') }}" class="sidebar-header-brand" title="Ir al panel de administración">
            <picture>
                <source type="image/webp" srcset="{{ asset('assets/images/brand/logo-ciclo-finca-icon-128.webp') }}">
                <img src="{{ asset('assets/images/brand/logo-ciclo-finca-icon-64.png') }}" alt="" class="logo" width="88" height="88" decoding="async"></picture>
            <div class="sidebar-header-text">
                <span class="sidebar-header-admin">Admin</span>
                <span class="sidebar-header-title">Ciclo Finca 4</span>
            </div>
        </a>
    </div>
    <nav class="sidebar-nav">
        <ul>
            <li class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <a href="{{ route('dashboard') }}">
                    <i class="fas fa-chart-line"></i>
                    <span class="sidebar-label">Dashboard</span>
                </a>
            </li>

            <li class="{{ request()->routeIs('sales.*') && !request()->routeIs('sales.reports.*') ? 'active' : '' }}">
                <a href="{{ route('sales.index') }}">
                    <i class="fas fa-cash-register"></i>
                    <span class="sidebar-label">Ventas</span>
                </a>
            </li>
            <li class="{{ request()->routeIs('admin.orders.index') ? 'active' : '' }}">
                <a href="{{ route('admin.orders.index') }}">
                    <i class="fas fa-shopping-cart"></i>
                    <span class="sidebar-label">Encargos</span>
                    <span class="cf4-sidebar-orders-badge" data-cf4-orders-pending-badge hidden
                        aria-label="Encargos pendientes"></span>
                </a>
            </li>
            <li class="{{ request()->routeIs('admin.supplier-orders.*') ? 'active' : '' }}">
                <a href="{{ route('admin.supplier-orders.index') }}">
                    <i class="fas fa-clipboard-list"></i>
                    <span class="sidebar-label">Pedidos proveedores</span>
                </a>
            </li>

            <li class="{{ request()->routeIs('inventory') || request()->routeIs('categories.*') ? 'active' : '' }}">
                <a href="{{ route('inventory') }}">
                    <i class="fas fa-box"></i>
                    <span class="sidebar-label">Inventario</span>
                </a>
            </li>
            <li class="{{ request()->routeIs('admin.product-classifications.*') || request()->routeIs('admin.products.classifications.*') ? 'active' : '' }}">
                <a href="{{ route('admin.product-classifications.index') }}">
                    <i class="fas fa-layer-group"></i>
                    <span class="sidebar-label">Características por producto</span>
                </a>
            </li>
            <li class="{{ request()->routeIs('admin.classifications.*') ? 'active' : '' }}">
                <a href="{{ route('admin.classifications.catalog.index') }}">
                    <i class="fas fa-th-list"></i>
                    <span class="sidebar-label">Opciones por tipo</span>
                </a>
            </li>
            <li class="{{ request()->routeIs('brands.*') ? 'active' : '' }}">
                <a href="{{ route('brands.index') }}">
                    <i class="fas fa-tags"></i>
                    <span class="sidebar-label">Marcas</span>
                </a>
            </li>
            <li class="{{ request()->routeIs('suppliers.*') ? 'active' : '' }}">
                <a href="{{ route('suppliers.index') }}">
                    <i class="fas fa-truck"></i>
                    <span class="sidebar-label">Proveedores</span>
                </a>
            </li>

            <li class="{{ request()->routeIs('admin.clients.*') ? 'active' : '' }}">
                <a href="{{ route('admin.clients.index') }}">
                    <i class="fas fa-users"></i>
                    <span class="sidebar-label">Usuarios</span>
                </a>
            </li>

            <li class="{{ request()->routeIs('admin.reports.*') || request()->routeIs('sales.reports.*') || request()->routeIs('admin.inventory.movements.*') ? 'active' : '' }}">
                <a href="{{ route('admin.reports.index').\App\Services\Admin\AdminReportsHubQuery::sidebarReportsIndexSuffix(request()) }}">
                    <i class="fas fa-file-alt"></i>
                    <span class="sidebar-label">Reportes</span>
                </a>
            </li>
        </ul>
    </nav>
    <div class="sidebar-footer">
        @auth('admin')
            @include('admin.partials.sidebar-account-menu', ['adminUser' => auth('admin')->user()])
        @endauth
    </div>
</aside>
