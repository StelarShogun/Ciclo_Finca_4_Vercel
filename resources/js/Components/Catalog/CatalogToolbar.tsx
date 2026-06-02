import { router } from '@inertiajs/react';

const ADMIN_PER_PAGE_OPTIONS = [10, 25, 50] as const;
import type { CatalogBrand, CatalogFilters as CatalogFiltersType, CatalogTaxonomy } from '@/types/catalog';

type CatalogToolbarProps = {
  filters: CatalogFiltersType;
  selectedCategory?: CatalogTaxonomy | null;
  selectedBrand?: CatalogBrand | null;
  paginationFrom?: number | null;
  paginationTo?: number | null;
  paginationTotal: number;
  activeFilterCount: number;
};

export function CatalogToolbar({
  activeFilterCount,
  filters,
  paginationFrom,
  paginationTo,
  paginationTotal,
  selectedBrand,
  selectedCategory,
}: CatalogToolbarProps) {
  function applySortField(name: 'sort' | 'direction' | 'per_page', value: string) {
    router.get('/catalog', compactParams({
      search: filters.search,
      category_id: filters.categoryId,
      brand_id: filters.brandId,
      min_price: filters.minPrice,
      max_price: filters.maxPrice,
      sort: name === 'sort' ? value : filters.sort,
      direction: name === 'direction' ? value : filters.direction,
      per_page: name === 'per_page' ? value : filters.perPage,
    }), { preserveScroll: true });
  }

  function clearBrandFilter() {
    router.get('/catalog', compactParams({
      search: filters.search,
      category_id: filters.categoryId,
      min_price: filters.minPrice,
      max_price: filters.maxPrice,
      sort: filters.sort,
      direction: filters.direction,
      per_page: filters.perPage,
    }), { preserveScroll: true });
  }

  return (
    <div className="catalog-toolbar results-header">
      <div className="catalog-toolbar-primary">
        {selectedCategory ? <p className="catalog-breadcrumb">Catálogo / {selectedCategory.name}</p> : null}

        {selectedBrand ? (
          <button type="button" className="catalog-brand-chip" title="Quitar filtro de marca" onClick={clearBrandFilter}>
            <i className="fas fa-tag" aria-hidden="true" />
            {selectedBrand.name}
            <i className="fas fa-times catalog-brand-chip-remove" aria-hidden="true" />
          </button>
        ) : null}

        <h2 className="catalog-toolbar-heading">
          {selectedCategory ? `Productos en ${selectedCategory.name}` : 'Productos disponibles'}
        </h2>
      </div>

      <div className="catalog-toolbar-meta">
        <p>
          Mostrando {paginationFrom ?? 0}-{paginationTo ?? 0} de {paginationTotal.toLocaleString('es-CR')} productos.
          {activeFilterCount > 0 ? ` ${activeFilterCount} filtros activos.` : ''}
        </p>

        <div className="catalog-toolbar-sort" aria-label="Ordenar resultados">
          <div className="catalog-toolbar-sort-field">
            <label htmlFor="sort">Ordenar por</label>
            <select
              id="sort"
              name="sort"
              className="form-control catalog-toolbar-select"
              form="filter-form"
              value={filters.sort}
              onChange={(event) => applySortField('sort', event.target.value)}
            >
              <option value="created_at">Más recientes</option>
              <option value="price">Precio</option>
              <option value="name">Nombre</option>
            </select>
          </div>
          <div className="catalog-toolbar-sort-field">
            <label htmlFor="direction">Dirección</label>
            <select
              id="direction"
              name="direction"
              className="form-control catalog-toolbar-select"
              form="filter-form"
              value={filters.direction}
              onChange={(event) => applySortField('direction', event.target.value)}
            >
              <option value="desc">Descendente</option>
              <option value="asc">Ascendente</option>
            </select>
          </div>
          <div className="catalog-toolbar-sort-field">
            <label htmlFor="catalog-per-page">Por página</label>
            <select
              id="catalog-per-page"
              name="per_page"
              className="form-control catalog-toolbar-select"
              form="filter-form"
              value={String(filters.perPage)}
              onChange={(event) => applySortField('per_page', event.target.value)}
            >
              {ADMIN_PER_PAGE_OPTIONS.map((size) => (
                <option value={size} key={size}>
                  {size}
                </option>
              ))}
            </select>
          </div>
        </div>
      </div>
    </div>
  );
}

function compactParams(params: Record<string, string | number | null | undefined>) {
  return Object.fromEntries(
    Object.entries(params).filter(([, value]) => value !== null && value !== undefined && value !== ''),
  );
}
