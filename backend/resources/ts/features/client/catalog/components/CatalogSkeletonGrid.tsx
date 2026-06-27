import { router } from '@inertiajs/react';
import { useEffect, useState } from 'react';

/** True mientras hay una visita Inertia hacia /catalog en curso (filtros, orden, paginación). */
export function useCatalogNavigationPending(): boolean {
  const [isPending, setIsPending] = useState(false);

  useEffect(() => {
    const offStart = router.on('start', (event) => {
      const url = event.detail.visit.url;
      if (url.pathname.startsWith('/catalog')) {
        setIsPending(true);
      }
    });
    const offFinish = router.on('finish', () => setIsPending(false));

    return () => {
      offStart();
      offFinish();
    };
  }, []);

  return isPending;
}

type CatalogSkeletonGridProps = {
  count?: number;
};

export function CatalogSkeletonGrid({ count = 8 }: CatalogSkeletonGridProps) {
  return (
    <div className="products-grid catalog-skeleton-grid" aria-hidden="true">
      {Array.from({ length: count }, (_, index) => (
        <div key={index} className="catalog-skeleton-card">
          <div className="catalog-skeleton-card__image" />
          <div className="catalog-skeleton-card__body">
            <div className="catalog-skeleton-line catalog-skeleton-line--sm" />
            <div className="catalog-skeleton-line catalog-skeleton-line--lg" />
            <div className="catalog-skeleton-line catalog-skeleton-line--md" />
            <div className="catalog-skeleton-card__footer">
              <div className="catalog-skeleton-line catalog-skeleton-line--price" />
              <div className="catalog-skeleton-pill" />
            </div>
          </div>
        </div>
      ))}
    </div>
  );
}
