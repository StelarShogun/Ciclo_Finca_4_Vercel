import { router } from '@inertiajs/react';
import { useState } from 'react';
import type { FormEvent } from 'react';

import { Select } from '@/shared/components/ui/Select';
import type { CatalogBrand, CatalogFilters as CatalogFiltersType } from '@/types/catalog';

type CatalogFiltersProps = {
  brands: CatalogBrand[];
  filters: CatalogFiltersType;
  activeFilterCount?: number;
  currentPage?: number;
};

type FilterDraft = {
  brandId?: string;
  minPrice?: string | null;
  maxPrice?: string | null;
};

export function CatalogFilters({
  activeFilterCount = 0,
  brands,
  currentPage = 1,
  filters,
}: CatalogFiltersProps) {
  const [draft, setDraft] = useState<FilterDraft>({});

  const brandId = draft.brandId ?? filters.brandId?.toString() ?? '';
  const minPrice = draft.minPrice !== undefined ? draft.minPrice : filters.minPrice;
  const maxPrice = draft.maxPrice !== undefined ? draft.maxPrice : filters.maxPrice;

  const selectedBrandLabel = !brandId
    ? 'Todas las marcas'
    : (brands.find((brand) => String(brand.id) === brandId)?.name ?? 'Todas las marcas');

  function submitFilters(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    router.get('/catalog', compactParams({
      search: readHeaderSearchValue(),
      category_id: filters.categoryId,
      brand_id: brandId,
      min_price: minPrice,
      max_price: maxPrice,
      sort: filters.sort,
      direction: filters.direction,
      per_page: filters.perPage,
    }), { preserveScroll: true });
  }

  return (
    <aside className="catalog-filters catalog-sidebar" id="catalog-sidebar">
      <div className="filters-card">
        <h3 className="filters-title">
          <i className="fas fa-filter" aria-hidden="true" />
          <span className="filters-title-text">Refinar búsqueda</span>
        </h3>

        {activeFilterCount > 0 ? (
          <p className="filters-active-summary" role="status">
            <strong>
              {activeFilterCount} filtro{activeFilterCount === 1 ? '' : 's'} activo{activeFilterCount === 1 ? '' : 's'}.
            </strong>{' '}
            Ajustá precio o marca y tocá «Ver resultados».
          </p>
        ) : (
          <p className="filters-active-summary" role="note">
            Elegí precio y/o marca para acotar los productos del catálogo.
          </p>
        )}

        <form id="filter-form" method="GET" action="/catalog" autoComplete="off" onSubmit={submitFilters}>
          <input type="hidden" name="search" id="catalog-filter-search-fallback" defaultValue={filters.search} />
          <input type="hidden" name="page" id="catalog-list-page" defaultValue={String(currentPage)} />
          {filters.categoryId ? <input type="hidden" name="category_id" value={filters.categoryId} /> : null}
          <input type="hidden" name="sort" value={filters.sort} />
          <input type="hidden" name="direction" value={filters.direction} />
          <input type="hidden" name="per_page" value={filters.perPage} />

          <div className="filter-group">
            <label htmlFor="min_price">Precio mínimo y máximo (₡)</label>
            <div className="price-range">
              <input
                type="number"
                id="min_price"
                name="min_price"
                className="form-control"
                min="0"
                step="1"
                placeholder="Mínimo"
                value={minPrice ?? ''}
                onChange={(event) => setDraft((current) => ({ ...current, minPrice: event.target.value }))}
              />
              <span className="price-separator" aria-hidden="true">
                -
              </span>
              <label htmlFor="max_price" className="sr-only">
                Precio máximo (₡)
              </label>
              <input
                type="number"
                id="max_price"
                name="max_price"
                className="form-control"
                min="0"
                step="1"
                placeholder="Máximo"
                value={maxPrice ?? ''}
                onChange={(event) => setDraft((current) => ({ ...current, maxPrice: event.target.value }))}
              />
            </div>
          </div>

          <div className="filter-group">
            <label htmlFor="brand_id-trigger">
              <i className="fas fa-tag" aria-hidden="true" />
              Marca
            </label>
            <div className="catalog-filter-select" data-catalog-filter-select>
              <Select
                id="brand_id"
                name="brand_id"
                className="catalog-filter-select__native"
                value={brandId}
                onChange={(event) => setDraft((current) => ({ ...current, brandId: event.target.value }))}
              >
                <option value="">Todas las marcas</option>
                {brands.map((brand) => (
                  <option value={brand.id} key={brand.id}>
                    {brand.name}
                  </option>
                ))}
              </Select>
              <button
                type="button"
                className="catalog-filter-select__trigger"
                id="brand_id-trigger"
                aria-expanded="false"
                aria-haspopup="listbox"
                aria-controls="brand_id-menu"
              >
                <span className="catalog-filter-select__label">{selectedBrandLabel}</span>
                <i className="fas fa-chevron-down catalog-filter-select__chevron" aria-hidden="true" />
              </button>
              <div className="catalog-filter-select__menu" id="brand_id-menu" role="listbox" aria-label="Filtrar por marca" hidden>
                <li role="presentation">
                  <button
                    type="button"
                    className={`catalog-filter-select__option ${brandId === '' ? 'is-active' : ''}`}
                    role="option"
                    data-value=""
                    aria-selected={brandId === ''}
                    onClick={() => setDraft((current) => ({ ...current, brandId: '' }))}
                  >
                    Todas las marcas
                  </button>
                </li>
                {brands.map((brand) => (
                  <li role="presentation" key={brand.id}>
                    <button
                      type="button"
                      className={`catalog-filter-select__option ${brandId === String(brand.id) ? 'is-active' : ''}`}
                      role="option"
                      data-value={brand.id}
                      aria-selected={brandId === String(brand.id)}
                      onClick={() => setDraft((current) => ({ ...current, brandId: String(brand.id) }))}
                    >
                      {brand.name}
                    </button>
                  </li>
                ))}
              </div>
            </div>
          </div>

          <div className="filter-actions">
            <button type="submit" className="btn btn-primary btn-block" id="filter-submit-btn">
              <i className="fas fa-sliders" aria-hidden="true" />
              <span className="btn-text">Ver resultados</span>
            </button>
            <button type="button" className="btn btn-secondary btn-block" onClick={() => router.get('/catalog')}>
              <i className="fas fa-rotate-left" aria-hidden="true" />
              <span className="btn-text">Quitar filtros</span>
            </button>
          </div>
        </form>
      </div>
    </aside>
  );
}

function readHeaderSearchValue(): string {
  const navSearch = document.getElementById('catalog-nav-search') as HTMLInputElement | null;
  if (navSearch) {
    return navSearch.value.trim();
  }

  const fallback = document.getElementById('catalog-filter-search-fallback') as HTMLInputElement | null;
  return fallback?.value.trim() ?? '';
}

function compactParams(params: Record<string, string | number | null | undefined>) {
  return Object.fromEntries(
    Object.entries(params).filter(([, value]) => value !== null && value !== undefined && value !== ''),
  );
}
