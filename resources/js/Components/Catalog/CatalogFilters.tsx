import { router } from '@inertiajs/react';
import { useState } from 'react';
import type { FormEvent } from 'react';

import type { CatalogBrand, CatalogFilters as CatalogFiltersType } from '@/types/catalog';

type CatalogFiltersProps = {
  brands: CatalogBrand[];
  filters: CatalogFiltersType;
};

export function CatalogFilters({ brands, filters }: CatalogFiltersProps) {
  const [search, setSearch] = useState(filters.search);
  const [brandId, setBrandId] = useState(filters.brandId?.toString() ?? '');
  const [minPrice, setMinPrice] = useState(filters.minPrice);
  const [maxPrice, setMaxPrice] = useState(filters.maxPrice);

  function submitFilters(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    router.get('/catalog', compactParams({
      search,
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

        <form id="filter-form" autoComplete="off" onSubmit={submitFilters}>
          <div className="filter-group">
            <label htmlFor="catalog-search">Buscar</label>
            <input
              type="search"
              id="catalog-search"
              className="form-control"
              value={search}
              onChange={(event) => setSearch(event.target.value)}
              placeholder="Buscar productos"
            />
          </div>

          <div className="filter-group">
            <label htmlFor="min_price">Precio mínimo y máximo (₡)</label>
            <div className="price-range">
              <input
                type="number"
                id="min_price"
                className="form-control"
                min="0"
                step="1"
                placeholder="Mínimo"
                value={minPrice}
                onChange={(event) => setMinPrice(event.target.value)}
              />
              <span className="price-separator">-</span>
              <input
                type="number"
                id="max_price"
                className="form-control"
                min="0"
                step="1"
                placeholder="Máximo"
                value={maxPrice}
                onChange={(event) => setMaxPrice(event.target.value)}
              />
            </div>
          </div>

          <div className="filter-group">
            <label htmlFor="brand_id">
              <i className="fas fa-tag" aria-hidden="true" />
              Marca
            </label>
            <select id="brand_id" className="form-control" value={brandId} onChange={(event) => setBrandId(event.target.value)}>
              <option value="">Todas las marcas</option>
              {brands.map((brand) => (
                <option value={brand.id} key={brand.id}>
                  {brand.name}
                </option>
              ))}
            </select>
          </div>

          <div className="filter-actions">
            <button type="submit" className="btn btn-primary btn-block">
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

function compactParams(params: Record<string, string | number | null | undefined>) {
  return Object.fromEntries(
    Object.entries(params).filter(([, value]) => value !== null && value !== undefined && value !== ''),
  );
}
