import { Link } from '@inertiajs/react';
import type { RefObject } from 'react';

import { HeaderCatalogSearch } from '@/features/client/catalog/components/HeaderCatalogSearch';
import { ClientCartButton } from '@/shared/components/client/header/ClientCartButton';
import { ClientNavigation } from '@/shared/components/client/header/ClientNavigation';
import { ClientThemeToggle } from '@/shared/components/client/header/ClientThemeToggle';
import { ClientUserMenu } from '@/shared/components/client/header/ClientUserMenu';
import type { InertiaSharedProps } from '@/shared/types/models';

type ClientHeaderProps = {
  auth: InertiaSharedProps['auth'];
  cartCount: number;
  clientInitials: string;
  headerRef: RefObject<HTMLElement | null>;
  isCatalog: boolean;
  isMenuOpen: boolean;
  isUserMenuOpen: boolean;
  notificationCount: number;
  onLogout: () => void;
  onMenuToggle: () => void;
  onThemeToggle: () => void;
  onUserMenuToggle: () => void;
  pathname: string;
};

export function ClientHeader({
  auth,
  cartCount,
  clientInitials,
  headerRef,
  isCatalog,
  isMenuOpen,
  isUserMenuOpen,
  notificationCount,
  onLogout,
  onMenuToggle,
  onThemeToggle,
  onUserMenuToggle,
  pathname,
}: ClientHeaderProps) {
  // Admin previewing the store has no client session, so the cart (auth:clients)
  // isn't usable for them — hide it instead of linking to the client login.
  const isStorePreview = auth.client === null && auth.admin !== null;

  return (
    <header ref={headerRef} className={`cliente-header ${isMenuOpen ? 'menu-open' : ''}`}>
      <div className="header-container">
        <div className="header-content">
          <div className="logo-section">
            <Link href="/" className="logo-link" aria-label="Marca Ciclo Finca 4">
              <span className="logo-icon-wrap" aria-hidden="true">
                <img src="/assets/images/brand/logo-ciclo-finca-icon-64.png" alt="" width={56} height={56} className="logo-img logo-img--icon-only" />
              </span>
              <span className="logo-wordmark">
                <span className="logo-text logo-text--dark">CICLO</span>
                <span className="logo-text logo-text--green">FINCA</span>
                <span className="logo-text logo-text--dark">4</span>
              </span>
            </Link>
          </div>

          {isStorePreview ? null : (
            <ClientCartButton cartCount={cartCount} className="cart-btn cart-btn-link header-mobile-cart-btn" />
          )}

          <button
            className={`header-menu-toggle ${cartCount > 0 ? 'has-alert' : ''}`}
            type="button"
            aria-label={isMenuOpen ? 'Cerrar menú de navegación' : 'Abrir menú de navegación'}
            aria-controls="header-menu-panel"
            aria-expanded={isMenuOpen}
            onClick={onMenuToggle}
          >
            <i className={`fas ${isMenuOpen ? 'fa-times' : 'fa-bars'}`} aria-hidden="true" />
            <span className="header-menu-toggle-badge" hidden={cartCount < 1} aria-hidden="true" />
          </button>

          <div className="header-menu-panel" id="header-menu-panel">
            <div className="header-nav-slot">
              <ClientNavigation isCatalog={isCatalog} pathname={pathname} />
            </div>

            <div className="header-right-cluster">
              {isCatalog ? <HeaderCatalogSearch /> : null}
              <div className="header-actions">
                {isStorePreview ? null : <ClientCartButton cartCount={cartCount} />}
                <ClientThemeToggle onToggle={onThemeToggle} />

                {auth.client ? (
                  <ClientUserMenu
                    authClient={auth.client}
                    clientInitials={clientInitials}
                    isOpen={isUserMenuOpen}
                    notificationCount={notificationCount}
                    onLogout={onLogout}
                    onToggle={onUserMenuToggle}
                  />
                ) : auth.admin ? (
                  <div className="header-admin-return">
                    <span className="header-admin-return__badge" title={`Sesión de administrador: ${auth.admin.gmail}`}>
                      <i className="fas fa-user-shield" aria-hidden="true" />
                      <span>{auth.admin.name}</span>
                    </span>
                    <a href="/admin/catalog-exit" className="btn btn-primary btn-sm">
                      <i className="fas fa-arrow-left" aria-hidden="true" />
                      <span>Volver al panel</span>
                    </a>
                  </div>
                ) : (
                  <div className="header-guest-auth">
                    <Link href="/login" className="btn btn-primary btn-sm">
                      <i className="fas fa-sign-in-alt" aria-hidden="true" />
                      <span>Iniciar sesión</span>
                    </Link>
                    <Link href="/register" className="btn btn-outline-secondary btn-sm">
                      <i className="fas fa-user-plus" aria-hidden="true" />
                      <span>Crear cuenta</span>
                    </Link>
                  </div>
                )}
              </div>
            </div>
          </div>
        </div>
      </div>
    </header>
  );
}
