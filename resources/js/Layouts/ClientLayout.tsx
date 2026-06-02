import { Link, usePage } from '@inertiajs/react';
import '../../css/client/fonts.css';
import '../../css/client/fontawesome.css';
import '../../css/client/variables-reset.css';
import '../../css/client/header.css';
import '../../css/client/footer.css';
import '../../css/client/clients-page.css';
import '../../css/client/legal-pages.css';
import { useEffect, useMemo, useState } from 'react';
import type { PropsWithChildren } from 'react';

import { HeaderCatalogSearch } from '@/Components/Catalog/HeaderCatalogSearch';
import type { InertiaSharedProps } from '@/types/models';

export function ClientLayout({ children }: PropsWithChildren) {
  const page = usePage<InertiaSharedProps>();
  const { auth, cartCount, flash } = page.props;
  const [isMenuOpen, setIsMenuOpen] = useState(false);
  const [liveCartCount, setLiveCartCount] = useState(cartCount);
  const [isUserMenuOpen, setIsUserMenuOpen] = useState(false);
  const pathname = page.url.split('?')[0] || '/';
  const isCatalog = pathname.startsWith('/catalog') || pathname.startsWith('/product');
  const clientInitials = useMemo(() => {
    if (!auth.client) {
      return '';
    }

    return `${auth.client.name.charAt(0)}${auth.client.first_surname?.charAt(0) ?? ''}`.toUpperCase();
  }, [auth.client]);

  useEffect(() => {
    document.body.classList.add('cliente-layout');

    return () => document.body.classList.remove('cliente-layout');
  }, []);

  useEffect(() => {
    setLiveCartCount(cartCount);
  }, [cartCount]);

  useEffect(() => {
    function handleCartCount(event: Event) {
      const customEvent = event as CustomEvent<{ count?: number }>;
      if (typeof customEvent.detail?.count === 'number') {
        setLiveCartCount(customEvent.detail.count);
      }
    }

    window.addEventListener('cf4:cart-count', handleCartCount);

    return () => window.removeEventListener('cf4:cart-count', handleCartCount);
  }, []);

  function toggleTheme() {
    const current = document.documentElement.dataset.theme === 'dark' ? 'dark' : 'light';
    const next = current === 'dark' ? 'light' : 'dark';
    localStorage.setItem('cf4-theme', next);
    document.documentElement.dataset.theme = next;
    document.documentElement.style.colorScheme = next;
    document.querySelector('#cf4-theme-color')?.setAttribute('content', next === 'dark' ? '#051F20' : '#DAF1DE');
  }

  return (
    <>
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

            <Link
              href="/cart"
              className="cart-btn cart-btn-link header-mobile-cart-btn"
              aria-label={`Ver carrito (${liveCartCount} productos)`}
              title="Carrito"
            >
              <i className="fas fa-shopping-cart" aria-hidden="true" />
              <span className="cart-count">{liveCartCount > 0 ? liveCartCount : 0}</span>
            </Link>

            <button
              className={`header-menu-toggle ${liveCartCount > 0 ? 'has-alert' : ''}`}
              type="button"
              aria-label="Abrir menú de navegación"
              aria-controls="header-menu-panel"
              aria-expanded={isMenuOpen}
              onClick={() => setIsMenuOpen((open) => !open)}
            >
              <i className="fas fa-bars" aria-hidden="true" />
              <span className="header-menu-toggle-badge" hidden={liveCartCount < 1} aria-hidden="true" />
            </button>

            <div className="header-menu-panel" id="header-menu-panel" aria-hidden={!isMenuOpen}>
              <div className="header-nav-slot">
                <nav className="main-nav" aria-label="Navegación principal">
                  <Link href="/" className={`nav-link ${pathname === '/' ? 'active' : ''}`}>
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
                {isCatalog ? <HeaderCatalogSearch /> : null}
                <div className="header-actions">
                  <Link href="/cart" className="cart-btn cart-btn-link" aria-label={`Ver carrito (${liveCartCount} productos)`} title="Ver carrito">
                    <i className="fas fa-shopping-cart" aria-hidden="true" />
                    <span className="cart-count">{liveCartCount > 0 ? liveCartCount : 0}</span>
                  </Link>

                  <button
                    type="button"
                    className="theme-toggle-btn theme-toggle-btn--compact"
                    aria-label="Cambiar tema"
                    onClick={toggleTheme}
                  >
                    <span className="theme-toggle-btn__track" aria-hidden="true">
                      <span className="theme-toggle-btn__icon theme-toggle-btn__icon--sun">
                        <i className="fas fa-sun" />
                      </span>
                      <span className="theme-toggle-btn__icon theme-toggle-btn__icon--moon">
                        <i className="fas fa-moon" />
                      </span>
                    </span>
                  </button>

                  {auth.client ? (
                    <div className={`user-menu-wrap ${isUserMenuOpen ? 'open' : ''}`}>
                      <button
                        className="user-menu-trigger"
                        type="button"
                        aria-expanded={isUserMenuOpen}
                        aria-haspopup="true"
                        title="Mi cuenta"
                        onClick={() => setIsUserMenuOpen((open) => !open)}
                      >
                        <span className="user-avatar-bubble">{clientInitials}</span>
                        <span className="user-trigger-name">{auth.client.name}</span>
                        <i className="fas fa-chevron-down user-trigger-caret" aria-hidden="true" />
                      </button>
                      <div className="user-dropdown-panel" aria-hidden={!isUserMenuOpen} role="menu">
                        <div className="user-dropdown-head">
                          <p className="user-dropdown-fullname">
                            {auth.client.name} {auth.client.first_surname}
                          </p>
                          <p className="user-dropdown-email">{auth.client.gmail}</p>
                        </div>
                        <div className="user-dropdown-body">
                          <Link className="user-dropdown-item" href="/profile" role="menuitem">
                            <i className="fas fa-user" aria-hidden="true" />
                            <span>Mi perfil</span>
                          </Link>
                          <Link className="user-dropdown-item" href="/invoices" role="menuitem">
                            <i className="fas fa-file-invoice" aria-hidden="true" />
                            <span>Mis facturas</span>
                          </Link>
                          <Link className="user-dropdown-item" href="/notifications" role="menuitem">
                            <i className="fas fa-bell" aria-hidden="true" />
                            <span>Notificaciones</span>
                          </Link>
                        </div>
                      </div>
                    </div>
                  ) : (
                    <>
                      <Link href="/login" className="login-btn">
                        <i className="fas fa-sign-in-alt" aria-hidden="true" />
                        Iniciar sesión
                      </Link>
                      <Link href="/register" className="login-btn">
                        Crear cuenta
                      </Link>
                    </>
                  )}
                </div>
              </div>
            </div>
          </div>
        </div>
      </header>

      <main className="cliente-main">
        {flash.success ? <div className="cf4-flash cf4-flash--success">{flash.success}</div> : null}
        {flash.error ? <div className="cf4-flash cf4-flash--error">{flash.error}</div> : null}
        {children}
      </main>

      <footer className="cliente-footer" aria-label="Pie de página">
        <div className="footer-container">
          <div className="footer-top">
            <div className="footer-brand">
              <div className="footer-brand-link" aria-label="Marca Ciclo Finca 4">
                <span className="footer-brand-media" aria-hidden="true">
                  <img src="/assets/images/brand/logo-ciclo-finca-icon-transparent.png" alt="" className="footer-logo-img" width={96} height={96} loading="lazy" decoding="async" />
                </span>
                <div>
                  <h3 className="footer-brand-title">Ciclo Finca 4</h3>
                  <p className="footer-brand-text">Especialistas en bicicletas, componentes y retiro en tienda.</p>
                </div>
              </div>
            </div>

            <FooterColumn
              title="Navegación"
              links={
                auth.client
                  ? [
                      ['Inicio', '/'],
                      ['Catálogo', '/catalog'],
                      ['Carrito', '/cart'],
                      ['Mi perfil', '/profile'],
                    ]
                  : [
                      ['Inicio', '/'],
                      ['Catálogo', '/catalog'],
                      ['Iniciar sesión', '/login'],
                      ['Crear cuenta', '/register'],
                    ]
              }
            />
            <div className="footer-col">
              <h4>Servicio</h4>
              <ul className="footer-links">
                <li><span className="footer-static-item">Asesoría personalizada</span></li>
                <li><span className="footer-static-item">Preparación en taller</span></li>
                <li><span className="footer-static-item">Retiro en tienda</span></li>
                <li><span className="footer-static-item">Soporte post-retiro</span></li>
              </ul>
            </div>
            <div className="footer-col">
              <h4>Contacto</h4>
              <ul className="footer-links footer-contact">
                <li><i className="fas fa-store" aria-hidden="true" /><span>Tienda física - retiro de pedidos</span></li>
                <li><i className="fas fa-file-lines" aria-hidden="true" /><Link href="/contacto">Formulario e información de contacto</Link></li>
              </ul>
            </div>
          </div>

          <div className="footer-bottom">
            <div className="footer-bottom-start">
              <p>© 2026 Ciclo Finca 4. Todos los derechos reservados.</p>
              <nav className="footer-legal" aria-label="Información legal">
                <Link href="/legal/terminos">Términos y condiciones</Link>
                <span className="footer-legal-sep" aria-hidden="true">|</span>
                <Link href="/legal/privacidad">Política de privacidad</Link>
                <span className="footer-legal-sep" aria-hidden="true">|</span>
                <Link href="/legal/cambios-devoluciones">Cambios y devoluciones</Link>
                <span className="footer-legal-sep" aria-hidden="true">|</span>
                <Link href="/contacto">Contacto</Link>
              </nav>
            </div>
            <Link href="/catalog" className="footer-bottom-cta">
              Explorar catálogo <i className="fas fa-arrow-right" aria-hidden="true" />
            </Link>
          </div>
        </div>
      </footer>
    </>
  );
}

function FooterColumn({ links, title }: { links: [string, string][]; title: string }) {
  return (
    <div className="footer-col">
      <h4>{title}</h4>
      <ul className="footer-links">
        {links.map(([label, href]) => (
          <li key={href}>
            <Link href={href}>{label}</Link>
          </li>
        ))}
      </ul>
    </div>
  );
}
