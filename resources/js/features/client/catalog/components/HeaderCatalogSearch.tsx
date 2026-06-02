import { usePage } from '@inertiajs/react';
import { useEffect } from 'react';

import type { CatalogFilters as CatalogFiltersType } from '@/types/catalog';
import type { InertiaSharedProps } from '@/shared/types/models';

const PRESERVED_QUERY_KEYS = ['category_id', 'brand_id', 'min_price', 'max_price', 'sort', 'direction', 'per_page'] as const;

export function HeaderCatalogSearch() {
  const page = usePage<InertiaSharedProps>();
  const isCatalogPage = page.component === 'Client/Catalog/Index';
  const filters = isCatalogPage && 'filters' in page.props
    ? (page.props.filters as CatalogFiltersType)
    : undefined;
  const searchValue = filters?.search ?? '';

  useEffect(() => {
    let cancelled = false;

    void import('@/client/header-catalog-search.js').then((module) => {
      if (!cancelled) {
        module.initHeaderCatalogSearch();
      }
    });

    return () => {
      cancelled = true;
      document.querySelector('[data-catalog-suggestions]')?.removeAttribute('data-cf4-search-init');
    };
  }, [page.url]);

  return (
    <div
      className="header-catalog-search"
      data-catalog-suggestions
      data-suggestions-url="/api/products/suggestions"
      data-trending-url="/api/catalog/search-trending"
    >
      <div className="header-catalog-search-track">
        <button
          type="button"
          className="header-catalog-search-toggle"
          aria-expanded="false"
          aria-controls="catalog-nav-search-inner"
          aria-label="Abrir búsqueda en catálogo"
        >
          <i className="fas fa-magnifying-glass" aria-hidden="true" />
          <span className="header-catalog-search-toggle-text">Buscar</span>
        </button>
        <div className="header-catalog-search-inner" id="catalog-nav-search-inner">
          <form
            className="header-catalog-search-form"
            method="GET"
            action="/catalog"
            id="catalog-nav-search-form"
            role="search"
            aria-label="Buscar en catálogo"
          >
            {isCatalogPage && filters
              ? PRESERVED_QUERY_KEYS.map((key) => {
                  const value = queryValueForKey(filters, key);
                  if (value === null || value === '') {
                    return null;
                  }

                  return <input key={key} type="hidden" name={key} value={value} />;
                })
              : null}
            <label className="header-catalog-search-label" htmlFor="catalog-nav-search">
              Buscar en catálogo
            </label>
            <input
              type="search"
              id="catalog-nav-search"
              name="search"
              className="header-catalog-search-input"
              placeholder="Buscar productos…"
              defaultValue={searchValue}
              autoComplete="off"
              autoCorrect="off"
              autoCapitalize="off"
              spellCheck={false}
              role="combobox"
              aria-autocomplete="list"
              aria-expanded="false"
              aria-controls="catalog-search-suggestions"
              maxLength={200}
            />
            <button
              type="submit"
              className="header-catalog-search-submit"
              aria-label="Ir al catálogo con esta búsqueda"
            >
              <i className="fas fa-arrow-right" aria-hidden="true" />
            </button>
          </form>
        </div>
      </div>
      <div
        id="catalog-search-suggestions"
        className="catalog-search-suggestions catalog-search-suggestions--header"
        role="listbox"
        aria-label="Sugerencias de búsqueda"
        aria-hidden="true"
      />
    </div>
  );
}

function queryValueForKey(filters: CatalogFiltersType, key: typeof PRESERVED_QUERY_KEYS[number]): string | null {
  switch (key) {
    case 'category_id':
      return filters.categoryId ? String(filters.categoryId) : null;
    case 'brand_id':
      return filters.brandId ? String(filters.brandId) : null;
    case 'min_price':
      return filters.minPrice || null;
    case 'max_price':
      return filters.maxPrice || null;
    case 'sort':
      return filters.sort || null;
    case 'direction':
      return filters.direction || null;
    case 'per_page':
      return filters.perPage ? String(filters.perPage) : null;
    default:
      return null;
  }
}
