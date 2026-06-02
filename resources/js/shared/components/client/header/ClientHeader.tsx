import { Link } from '@inertiajs/react';

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
  isCatalog: boolean;
  isMenuOpen: boolean;
  isUserMenuOpen: boolean;
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
  isCatalog,
  isMenuOpen,
  isUserMenuOpen,
  onLogout,
  onMenuToggle,
  onThemeToggle,
  onUserMenuToggle,
  pathname,
}: ClientHeaderProps) {
  return (
    <header className={`cliente-header ${isMenuOpen ? 'menu-open' : ''}`}>
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

          <ClientCartButton cartCount={cartCount} className="cart-btn cart-btn-link header-mobile-cart-btn" />

          <button
            className={`header-menu-toggle ${cartCount > 0 ? 'has-alert' : ''}`}
            type="button"
            aria-label="Abrir menú de navegación"
            aria-controls="header-menu-panel"
            aria-expanded={isMenuOpen}
            onClick={onMenuToggle}
          >
            <i className="fas fa-bars" aria-hidden="true" />
            <span className="header-menu-toggle-badge" hidden={cartCount < 1} aria-hidden="true" />
          </button>

          <div className="header-menu-panel" id="header-menu-panel" aria-hidden={!isMenuOpen}>
            <div className="header-nav-slot">
              <ClientNavigation isCatalog={isCatalog} pathname={pathname} />
            </div>

            <div className="header-right-cluster">
              {isCatalog ? <HeaderCatalogSearch /> : null}
              <div className="header-actions">
                <ClientCartButton cartCount={cartCount} />
                <ClientThemeToggle onToggle={onThemeToggle} />

                {auth.client ? (
                  <ClientUserMenu
                    authClient={auth.client}
                    clientInitials={clientInitials}
                    isOpen={isUserMenuOpen}
                    onLogout={onLogout}
                    onToggle={onUserMenuToggle}
                  />
                ) : (
                  <div className="header-guest-auth">
                    <Link href="/login" className="btn btn-primary btn-sm">
                      <i className="fas fa-sign-in-alt" aria-hidden="true" />
                      <span>Iniciar sesión</span>
                    </Link>
                    <Link href="/register" className="btn btn-outline-secondary btn-sm">
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
