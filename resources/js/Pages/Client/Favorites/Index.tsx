import { Head, Link, usePage } from '@inertiajs/react';
import { useState } from 'react';

import { Pagination } from '@/Components/UI/Pagination';
import { useToast } from '@/hooks/useToast';
import { toggleFavorite } from '@/lib/favorites';
import { ClientLayout } from '@/Layouts/ClientLayout';
import type { InertiaSharedProps } from '@/types/models';

import '../../../../css/client/clients-users.css';

type FavoriteItem = {
  product_id: number;
  name: string;
  url: string;
  image_url?: string | null;
  price_formatted?: string | null;
  category_name?: string | null;
};

type FavoritesPageProps = {
  favorites: FavoriteItem[];
  links: Array<{ url: string | null; label: string; active: boolean }>;
};

export default function FavoritesIndex({ favorites, links }: FavoritesPageProps) {
  const page = usePage<InertiaSharedProps>();
  const { csrfToken } = page.props;
  const { showToast } = useToast();
  const [busyId, setBusyId] = useState<number | null>(null);
  const [items, setItems] = useState(favorites);

  async function removeFavorite(productId: number) {
    if (busyId) return;
    setBusyId(productId);
    try {
      const result = await toggleFavorite(productId, csrfToken);
      if (!result.success) {
        showToast({ variant: 'error', title: 'Error', message: result.message ?? 'No se pudo actualizar tu favorito.' });
        return;
      }
      if (!result.isFavorite) {
        setItems((current) => current.filter((it) => it.product_id !== productId));
      }
      showToast({
        variant: 'success',
        title: 'Listo',
        message: result.message ?? 'Favorito actualizado.',
      });
    } finally {
      setBusyId(null);
    }
  }

  return (
    <ClientLayout>
      <Head title="Mis Favoritos - Ciclo Finca 4" />

      <div className="cf4-invoices-header">
        <div className="cf4-invoices-header-inner">
          <h1><i className="fas fa-heart" /> Mis Favoritos</h1>
          <p>Productos guardados para revisarlos más tarde.</p>
          <nav className="cf4-invoices-escape-nav" aria-label="Seguir en la tienda">
            <Link href="/catalog" className="cf4-invoices-escape-link cf4-invoices-escape-link--primary">
              <i className="fas fa-store" aria-hidden="true" /> Ir al catálogo
            </Link>
          </nav>
        </div>
      </div>

      <div className="cf4-invoices-wrapper">
        <nav className="breadcrumb" aria-label="Migas de pan">
          <Link href="/">Inicio</Link>
          <span>/</span>
          <span>Favoritos</span>
        </nav>

        <div className="cf4-invoices-card">
          {items.length === 0 ? (
            <div className="cf4-invoices-empty cf4-invoices-empty--panel">
              <div className="cf4-invoices-empty-icon"><i className="fas fa-heart-broken" /></div>
              <p>No tienes favoritos.</p>
              <Link href="/catalog" className="btn btn-primary btn-sm">
                <i className="fas fa-bicycle" /> Explorar catálogo
              </Link>
            </div>
          ) : (
            <div className="cf4-favorites-page">
              {items.map((item) => (
                <article key={item.product_id} className="cf4-favorite-row">
                  <div className="cf4-favorite-body">
                    <Link className="cf4-favorite-name" href={item.url}>
                      {item.name}
                    </Link>
                    {item.price_formatted ? <div className="cf4-favorite-price">{item.price_formatted}</div> : null}
                  </div>
                  <button
                    type="button"
                    className="cf4-favorite-remove"
                    aria-label="Quitar de favoritos"
                    disabled={busyId === item.product_id}
                    onClick={() => removeFavorite(item.product_id)}
                  >
                    <i className="fas fa-heart" aria-hidden="true" />
                  </button>
                </article>
              ))}
            </div>
          )}

          <div className="cf4-invoices-pagination-wrap">
            <Pagination links={links} />
          </div>
        </div>
      </div>
    </ClientLayout>
  );
}

