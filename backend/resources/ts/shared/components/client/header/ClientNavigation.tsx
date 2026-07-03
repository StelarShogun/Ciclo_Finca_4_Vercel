import { Link } from '@inertiajs/react';

type ClientNavigationProps = {
  isCatalog: boolean;
  pathname: string;
};

export function ClientNavigation({ isCatalog, pathname }: ClientNavigationProps) {
  return (
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
  );
}
