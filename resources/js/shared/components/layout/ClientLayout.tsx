import { router, usePage } from '@inertiajs/react';
import '../../../../css/client/fonts.css';
import '../../../../css/client/fontawesome.css';
import '../../../../css/client/variables-reset.css';
import '../../../../css/client/header.css';
import '../../../../css/client/footer.css';
import '../../../../css/client/clients-page.css';
import '../../../../css/client/legal-pages.css';
import { useCallback, useEffect, useMemo, useState } from 'react';
import type { PropsWithChildren } from 'react';

import { FavoritesDrawer } from '@/features/client/favorites/components/FavoritesDrawer';
import { FavoritesDrawerProvider } from '@/features/client/favorites/context/FavoritesDrawerContext';
import { ClientFooter } from '@/shared/components/client/footer/ClientFooter';
import { ClientHeader } from '@/shared/components/client/header/ClientHeader';
import { useFlashToasts } from '@/shared/hooks/useFlashToasts';
import { useLiveCartCount } from '@/shared/hooks/useLiveCartCount';
import type { InertiaSharedProps } from '@/shared/types/models';

export function ClientLayout({ children }: PropsWithChildren) {
  const page = usePage<InertiaSharedProps>();
  const { auth, cartCount } = page.props;
  const [isMenuOpen, setIsMenuOpen] = useState(false);
  const [isUserMenuOpen, setIsUserMenuOpen] = useState(false);
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

  useFlashToasts();

  const toggleTheme = useCallback(() => {
    const current = document.documentElement.dataset.theme === 'dark' ? 'dark' : 'light';
    const next = current === 'dark' ? 'light' : 'dark';
    localStorage.setItem('cf4-theme', next);
    document.documentElement.dataset.theme = next;
    document.documentElement.style.colorScheme = next;
    document.querySelector('#cf4-theme-color')?.setAttribute('content', next === 'dark' ? '#051F20' : '#DAF1DE');
  }, []);

  return (
    <FavoritesDrawerProvider>
      <ClientHeader
        auth={auth}
        cartCount={liveCartCount}
        clientInitials={clientInitials}
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
