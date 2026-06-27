import { usePage } from '@inertiajs/react';
import { useCallback, useEffect, useState } from 'react';

import {
  fetchFavoriteDrawerPage,
  toggleFavorite,
  type FavoriteDrawerItem,
  type FavoriteDrawerPagination,
} from '@/features/client/favorites/api';
import { FavoriteDrawerItem as FavoriteDrawerItemRow } from '@/features/client/favorites/components/FavoriteDrawerItem';
import { useFavoritesDrawer } from '@/features/client/favorites/context/FavoritesDrawerContext';
import { Drawer } from '@/shared/components/ui/Drawer';
import { useToast } from '@/shared/hooks/useToast';
import type { InertiaSharedProps } from '@/shared/types/models';

type FavoritesShared = {
  indexUrl: string;
  toggleUrl: string;
};

const emptyPagination: FavoriteDrawerPagination = {
  current_page: 1,
  last_page: 1,
  per_page: 10,
  total: 0,
  from: null,
  to: null,
};

export function FavoritesDrawer() {
  const page = usePage<InertiaSharedProps & { favorites: FavoritesShared | null; csrfToken: string }>();
  const favorites = page.props.favorites;
  const csrfToken = page.props.csrfToken;
  const { close, isOpen, refreshToken } = useFavoritesDrawer();
  const { showToast } = useToast();

  const [items, setItems] = useState<FavoriteDrawerItem[]>([]);
  const [pagination, setPagination] = useState<FavoriteDrawerPagination>(emptyPagination);
  const [isLoading, setIsLoading] = useState(false);
  const [isRemoving, setIsRemoving] = useState(false);
  const [activePage, setActivePage] = useState(1);

  const loadPage = useCallback(
    async (targetPage: number) => {
      if (!favorites?.indexUrl) {
        return;
      }

      setIsLoading(true);
      setActivePage(targetPage);

      try {
        const result = await fetchFavoriteDrawerPage(favorites.indexUrl, targetPage, emptyPagination.per_page);
        if (!result) {
          showToast({
            variant: 'error',
            title: 'Favoritos',
            message: 'No se pudieron cargar tus favoritos.',
          });
          return;
        }

        setItems(result.favorites);
        setPagination(result.pagination);
      } finally {
        setIsLoading(false);
      }
    },
    [favorites?.indexUrl, showToast],
  );

  useEffect(() => {
    if (!isOpen || !favorites) {
      return;
    }

    setActivePage(1);
    void loadPage(1);
  }, [favorites, isOpen, loadPage, refreshToken]);

  useEffect(() => {
    function onFavoritesChanged() {
      if (!isOpen) {
        return;
      }

      void loadPage(activePage);
    }

    window.addEventListener('cf4:favorites:changed', onFavoritesChanged);

    return () => window.removeEventListener('cf4:favorites:changed', onFavoritesChanged);
  }, [activePage, isOpen, loadPage]);

  async function removeFavorite(productId: number) {
    setIsRemoving(true);

    try {
      const result = await toggleFavorite(productId, csrfToken);
      if (!result.success) {
        showToast({
          variant: 'error',
          title: 'Favoritos',
          message: result.message ?? 'No se pudo actualizar el favorito.',
        });
        return;
      }

      let nextPage = pagination.current_page;
      if (!result.isFavorite && items.length === 1 && nextPage > 1) {
        nextPage -= 1;
      }

      await loadPage(nextPage);
    } finally {
      setIsRemoving(false);
    }
  }

  if (!favorites) {
    return null;
  }

  const showPagination = pagination.total > 0 && pagination.last_page > 1;

  return (
    <Drawer
      isOpen={isOpen}
      onClose={close}
      title={(
        <>
          <i className="fas fa-heart" /> Mis Favoritos
        </>
      )}
      footer={showPagination ? (
        <>
          <p className="cf4-favorites-pagination-info" aria-live="polite">
            Mostrando {pagination.from}–{pagination.to} de {pagination.total} favoritos
          </p>
          <div className="cf4-favorites-pagination-nav">
            <button
              type="button"
              className="cf4-favorites-page-btn"
              disabled={isLoading || pagination.current_page <= 1}
              onClick={() => void loadPage(Math.max(1, pagination.current_page - 1))}
            >
              <i className="fas fa-chevron-left" aria-hidden="true" /> Anterior
            </button>
            <button
              type="button"
              className="cf4-favorites-page-btn"
              disabled={isLoading || pagination.current_page >= pagination.last_page}
              onClick={() => void loadPage(pagination.current_page + 1)}
            >
              Siguiente <i className="fas fa-chevron-right" aria-hidden="true" />
            </button>
          </div>
        </>
      ) : undefined}
    >
      {isLoading ? (
        <p className="cf4-favorites-loading">Cargando favoritos...</p>
      ) : items.length === 0 ? (
        <div className="cf4-favorites-empty">
          <i className="far fa-heart" />
          <p>
            Aún no tienes productos guardados.
            <br />
            ¡Explora el catálogo!
          </p>
        </div>
      ) : (
        items.map((item) => (
          <FavoriteDrawerItemRow
            key={item.product_id}
            item={item}
            disabled={isRemoving}
            onRemove={(productId) => void removeFavorite(productId)}
          />
        ))
      )}
    </Drawer>
  );
}
