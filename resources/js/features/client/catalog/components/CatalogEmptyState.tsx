import { Link, router } from '@inertiajs/react';

import type { CatalogCategory, CatalogFilters } from '@/types/catalog';

type CatalogEmptyStateProps = {
  filters: CatalogFilters;
  categories: CatalogCategory[];
  hasActiveFilters: boolean;
  isEmptyCategory: boolean;
};

export function CatalogEmptyState({ categories, filters, hasActiveFilters, isEmptyCategory }: CatalogEmptyStateProps) {
  const suggestions = categories.filter((category) => category.id !== filters.categoryId).slice(0, 4);

  function clearFilters() {
    router.get('/catalog', {}, { preserveScroll: true });
  }

  return (
    <div className="empty-state catalog-empty-state">
      <i className={isEmptyCategory ? 'fas fa-box-open' : 'fas fa-search'} aria-hidden="true" />
      <h2>{isEmptyCategory ? 'No hay productos en esta categoría' : 'No encontramos productos'}</h2>
      <p>
        {filters.search
          ? `No hay resultados para “${filters.search}”. Probá con otra palabra o revisá la ortografía.`
          : 'Ajustá la búsqueda, marca o rango de precios para ver más resultados.'}
      </p>

      {hasActiveFilters ? (
        <button type="button" className="btn btn-primary catalog-empty-state__clear" onClick={clearFilters}>
          <i className="fas fa-filter" aria-hidden="true" />
          Limpiar filtros y ver todo
        </button>
      ) : null}

      {suggestions.length > 0 ? (
        <div className="catalog-empty-state__suggestions">
          <p className="catalog-empty-state__suggestions-title">También podés explorar:</p>
          <div className="catalog-empty-state__suggestions-list">
            {suggestions.map((category) => (
              <Link
                key={category.id}
                href={`/catalog?category_id=${category.id}`}
                className="catalog-empty-state__suggestion"
                preserveScroll
              >
                {category.name}
              </Link>
            ))}
          </div>
        </div>
      ) : null}
    </div>
  );
}
