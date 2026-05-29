@php
    /** @var \App\Models\AdminUser $adminUser */
    $adminUser = $adminUser ?? auth('admin')->user();
    $adminDisplayName = trim($adminUser->name . ' ' . ($adminUser->first_surname ?? ''));
    $nameInitial = mb_strtoupper(mb_substr($adminUser->name ?? 'A', 0, 1));
    $surnameInitial = mb_strtoupper(mb_substr($adminUser->first_surname ?? $adminUser->name ?? 'A', 0, 1));
    $adminInitials = $nameInitial . $surnameInitial;
@endphp

<div class="sidebar-account-menu" data-sidebar-account-menu>
    <button type="button"
            class="sidebar-account-trigger"
            data-sidebar-account-trigger
            aria-expanded="false"
            aria-controls="sidebar-account-panel"
            aria-haspopup="true"
            title="Opciones de cuenta">
        <span class="sidebar-account-avatar" aria-hidden="true">{{ $adminInitials }}</span>
        <span class="sidebar-account-meta">
            <span class="sidebar-account-name">{{ $adminDisplayName }}</span>
            <span class="sidebar-account-email">{{ $adminUser->gmail }}</span>
        </span>
        <i class="fas fa-chevron-up sidebar-account-chevron" aria-hidden="true"></i>
    </button>

    <div class="sidebar-account-panel"
         id="sidebar-account-panel"
         data-sidebar-account-panel
         role="menu"
         aria-label="Opciones de cuenta"
         hidden>
        <div class="sidebar-account-row sidebar-account-row--theme" role="none">
            <span class="sidebar-account-row__label" data-theme-toggle-label>Modo oscuro</span>
            @include('admin.partials.cf4-theme-toggle', ['variant' => 'compact'])
        </div>

        <a href="{{ route('admin.visit-store') }}"
           class="sidebar-account-row sidebar-account-row--link"
           role="menuitem"
           title="Abrir la página principal del sitio web (mantiene la sesión de administrador)">
            <i class="fas fa-globe" aria-hidden="true"></i>
            <span>Ir a sitio web</span>
        </a>

        <form action="{{ route('admin.logout') }}" method="POST" class="sidebar-account-logout-form">
            @csrf
            <button type="submit" class="sidebar-account-row sidebar-account-row--logout" role="menuitem">
                <i class="fas fa-sign-out-alt" aria-hidden="true"></i>
                <span>Cerrar sesión</span>
            </button>
        </form>
    </div>
</div>
