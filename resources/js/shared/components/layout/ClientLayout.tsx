import { router, usePage } from '@inertiajs/react';
import '../../../../css/client/fonts.css';
import '../../../../css/client/fontawesome.css';
import '../../../../css/client/variables-reset.css';
import '../../../../css/client/header.css';
import '../../../../css/client/footer.css';
import '../../../../css/client/clients-page.css';
import '../../../../css/client/legal-pages.css';
import { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import type { PropsWithChildren } from 'react';

import { FavoritesDrawer } from '@/features/client/favorites/components/FavoritesDrawer';
import { FavoritesDrawerProvider } from '@/features/client/favorites/context/FavoritesDrawerContext';
import { ClientFooter } from '@/shared/components/client/footer/ClientFooter';
import { ClientHeader } from '@/shared/components/client/header/ClientHeader';
import { useFlashToasts } from '@/shared/hooks/useFlashToasts';
import { useLiveCartCount } from '@/shared/hooks/useLiveCartCount';
import { toggleTheme } from '@/shared/theme-toggle';
import type { InertiaSharedProps } from '@/shared/types/models';

export function ClientLayout({ children }: PropsWithChildren) {
  const page = usePage<InertiaSharedProps>();
  const { auth, cartCount } = page.props;
  const [isMenuOpen, setIsMenuOpen] = useState(false);
  const [isUserMenuOpen, setIsUserMenuOpen] = useState(false);
  const headerRef = useRef<HTMLElement>(null);
  const liveCartCount = useLiveCartCount(cartCount);
  const pathname = page.url.split('?')[0] || '/';
  const isCatalog = pathname.startsWith('/catalog') || pathname.startsWith('/product');
  const clientInitials = useMemo(() => {
    if (!auth.client) {
      return '';
    }

    return `${auth.client.name.charAt(0)}${auth.client.first_surname?.charAt(0) ?? ''}`.toUpperCase();
  }, [auth.client]);

  const logout = useCallback(() => {
    router.post('/logout', {}, { preserveScroll: true });
  }, []);

  useEffect(() => {
    document.body.classList.add('cliente-layout');

    return () => document.body.classList.remove('cliente-layout');
  }, []);

  // Cierra los menús al navegar dentro del mismo componente (sin remount del layout).
  useEffect(() => {
    setIsMenuOpen(false);
    setIsUserMenuOpen(false);
  }, [page.url]);

  const anyMenuOpen = isMenuOpen || isUserMenuOpen;

  useEffect(() => {
    if (!anyMenuOpen) {
      return;
    }

    const closeAll = () => {
      setIsMenuOpen(false);
      setIsUserMenuOpen(false);
    };

    const onPointerDown = (event: PointerEvent) => {
      if (event.target instanceof Node && headerRef.current?.contains(event.target)) {
        return;
      }
      closeAll();
    };

    const onKeyDown = (event: KeyboardEvent) => {
      if (event.key !== 'Escape') {
        return;
      }
      closeAll();
      headerRef.current?.querySelector<HTMLButtonElement>('.header-menu-toggle')?.focus();
    };

    const onResize = () => {
      if (window.innerWidth > 1024) {
        closeAll();
      }
    };

    document.addEventListener('pointerdown', onPointerDown);
    document.addEventListener('keydown', onKeyDown);
    window.addEventListener('resize', onResize);

    return () => {
      document.removeEventListener('pointerdown', onPointerDown);
      document.removeEventListener('keydown', onKeyDown);
      window.removeEventListener('resize', onResize);
    };
  }, [anyMenuOpen]);

  useFlashToasts();

  return (
    <FavoritesDrawerProvider>
      <ClientHeader
        auth={auth}
        cartCount={liveCartCount}
        clientInitials={clientInitials}
        headerRef={headerRef}
        isCatalog={isCatalog}
        isMenuOpen={isMenuOpen}
        isUserMenuOpen={isUserMenuOpen}
        onLogout={logout}
        onMenuToggle={() => setIsMenuOpen((open) => !open)}
        onThemeToggle={toggleTheme}
        onUserMenuToggle={() => setIsUserMenuOpen((open) => !open)}
        pathname={pathname}
      />

      <FavoritesDrawer />

      <main className="cliente-main">{children}</main>

      <ClientFooter auth={auth} />
    </FavoritesDrawerProvider>
  );
}
