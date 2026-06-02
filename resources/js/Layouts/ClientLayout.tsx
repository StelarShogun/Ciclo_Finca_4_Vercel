import { Link, usePage } from '@inertiajs/react';
import type { PropsWithChildren } from 'react';

import type { InertiaSharedProps } from '@/types/models';

export function ClientLayout({ children }: PropsWithChildren) {
  const { auth, cartCount } = usePage<InertiaSharedProps>().props;
  const isCatalog = window.location.pathname.startsWith('/catalog') || window.location.pathname.startsWith('/product');

  return (
    <>
      <header className="cliente-header">
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

            <div className="header-menu-panel" aria-hidden="false">
              <div className="header-nav-slot">
                <nav className="main-nav" aria-label="Navegación principal">
                  <Link href="/" className={`nav-link ${window.location.pathname === '/' ? 'active' : ''}`}>
                    <i className="fas fa-home" aria-hidden="true" />
                    <span>Inicio</span>
                  </Link>
                  <Link href="/catalog" className={`nav-link ${isCatalog ? 'active' : ''}`}>
                    <i className="fas fa-bicycle" aria-hidden="true" />
                    <span>Catálogo</span>
                  </Link>
                </nav>
              </div>

              <div className="header-right-cluster">
                <Link href="/cart" className="cart-btn cart-btn-link" aria-label={`Ver carrito (${cartCount} productos)`}>
                  <i className="fas fa-shopping-cart" aria-hidden="true" />
                  <span className="cart-count">{cartCount > 0 ? cartCount : 0}</span>
                </Link>

                {auth.client ? (
                  <Link href="/profile" className="login-btn">
                    {auth.client.name}
                  </Link>
                ) : (
                  <Link href="/login" className="login-btn">
                    <i className="fas fa-sign-in-alt" aria-hidden="true" />
                    Iniciar sesión
                  </Link>
                )}
              </div>
            </div>
          </div>
        </div>
      </header>

      <main>{children}</main>

      <footer className="cliente-footer">
        <div className="container">
          <p>© 2026 Ciclo Finca 4. Todos los derechos reservados.</p>
          <nav aria-label="Enlaces legales">
            <Link href="/legal/terminos">Términos y condiciones</Link>
            <span> | </span>
            <Link href="/legal/privacidad">Política de privacidad</Link>
            <span> | </span>
            <Link href="/contacto">Contacto</Link>
          </nav>
        </div>
      </footer>
    </>
  );
}
