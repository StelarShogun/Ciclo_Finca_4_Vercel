import { Head, usePage } from '@inertiajs/react';
import { useEffect } from 'react';

import type { InertiaSharedProps } from '@/types/models';

type FavoritesShared = {
  indexUrl: string;
  toggleUrl: string;
};

export function FavoritesDrawer() {
  const page = usePage<InertiaSharedProps & { favorites: FavoritesShared | null }>();
  const favorites = page.props.favorites;

  useEffect(() => {
    if (!favorites) {
      return;
    }

    let cancelled = false;

    void import('@/client/clients-header-auth.js').then((module) => {
      if (!cancelled) {
        module.initClientHeaderAuth();
      }
    });

    return () => {
      cancelled = true;
    };
  }, [favorites]);

  if (!favorites) {
    return null;
  }

  return (
    <>
      <Head>
        <meta name="cf4-favorites-index-url" content={favorites.indexUrl} />
        <meta name="cf4-favorites-toggle-url" content={favorites.toggleUrl} />
        <meta name="cf4-favorites-initial" content="[]" />
      </Head>

      <div className="cf4-favorites-overlay" id="favorites-overlay" hidden />
      <aside className="cf4-favorites-drawer" id="favorites-drawer" aria-hidden="true">
        <div className="cf4-favorites-drawer-header">
          <h3>
            <i className="fas fa-heart" /> Mis Favoritos
          </h3>
          <button type="button" id="favorites-close-btn" aria-label="Cerrar favoritos">
            <i className="fas fa-times" />
          </button>
        </div>
        <div className="cf4-favorites-drawer-body" id="favorites-drawer-body">
          <div className="cf4-favorites-empty">
            <i className="far fa-heart" />
            <p>
              Aún no tienes productos guardados.
              <br />
              ¡Explora el catálogo!
            </p>
          </div>
        </div>
        <footer className="cf4-favorites-drawer-footer" id="favorites-drawer-pagination" hidden>
          <p className="cf4-favorites-pagination-info" id="favorites-pagination-info" aria-live="polite" />
          <div className="cf4-favorites-pagination-nav">
            <button type="button" className="cf4-favorites-page-btn" id="favorites-page-prev" disabled>
              <i className="fas fa-chevron-left" aria-hidden="true" /> Anterior
            </button>
            <button type="button" className="cf4-favorites-page-btn" id="favorites-page-next" disabled>
              Siguiente <i className="fas fa-chevron-right" aria-hidden="true" />
            </button>
          </div>
        </footer>
      </aside>
    </>
  );
}
