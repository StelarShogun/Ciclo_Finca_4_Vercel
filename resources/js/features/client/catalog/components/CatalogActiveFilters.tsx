import { router } from '@inertiajs/react';

import type { CatalogBrand, CatalogFilters, CatalogTaxonomy } from '@/types/catalog';

type CatalogActiveFiltersProps = {
  filters: CatalogFilters;
  selectedCategory?: CatalogTaxonomy | null;
  selectedBrand?: CatalogBrand | null;
};

type FilterChip = {
  key: string;
  label: string;
  iconClass: string;
  omitKeys: string[];
};

function baseParams(filters: CatalogFilters): Record<string, string | number | null | undefined> {
  return {
    search: filters.search,
    category_id: filters.categoryId,
    brand_id: filters.brandId,
    min_price: filters.minPrice,
    max_price: filters.maxPrice,
    sort: filters.sort,
    direction: filters.direction,
    per_page: filters.perPage,
  };
}

function compactParams(params: Record<string, string | number | null | undefined>) {
  return Object.fromEntries(
    Object.entries(params).filter(([, value]) => value !== null && value !== undefined && value !== ''),
  );
}

export function CatalogActiveFilters({ filters, selectedBrand, selectedCategory }: CatalogActiveFiltersProps) {
  const chips: FilterChip[] = [];

  if (filters.search) {
    chips.push({
      key: 'search',
      label: `Búsqueda: “${filters.search}”`,
      iconClass: 'fas fa-search',
      omitKeys: ['search'],
    });
  }

  if (filters.categoryId) {
    chips.push({
      key: 'category',
      label: selectedCategory?.name ?? 'Categoría',
      iconClass: 'fas fa-th',
      omitKeys: ['category_id'],
    });
  }

  if (filters.brandId) {
    chips.push({
      key: 'brand',
      label: selectedBrand?.name ?? 'Marca',
      iconClass: 'fas fa-tag',
      omitKeys: ['brand_id'],
    });
  }

  if (filters.minPrice || filters.maxPrice) {
    const min = filters.minPrice ? `₡${Number(filters.minPrice).toLocaleString('es-CR')}` : null;
    const max = filters.maxPrice ? `₡${Number(filters.maxPrice).toLocaleString('es-CR')}` : null;
    const label = min && max ? `Precio: ${min} – ${max}` : min ? `Precio: desde ${min}` : `Precio: hasta ${max}`;
    chips.push({
      key: 'price',
      label,
      iconClass: 'fas fa-coins',
      omitKeys: ['min_price', 'max_price'],
    });
  }

  if (chips.length === 0) {
    return null;
  }

  function removeChip(chip: FilterChip) {
    const params = baseParams(filters);
    for (const key of chip.omitKeys) {
      delete params[key];
    }
    router.get('/catalog', compactParams(params), { preserveScroll: true });
  }

  function clearAll() {
    router.get(
      '/catalog',
      compactParams({ sort: filters.sort, direction: filters.direction, per_page: filters.perPage }),
      { preserveScroll: true },
    );
  }

  return (
    <div className="catalog-active-filters" aria-label="Filtros activos">
      <span className="catalog-active-filters__label">Filtros:</span>
      {chips.map((chip) => (
        <button
          key={chip.key}
          type="button"
          className="catalog-active-filters__chip"
          title={`Quitar filtro: ${chip.label}`}
          onClick={() => removeChip(chip)}
        >
          <i className={chip.iconClass} aria-hidden="true" />
          <span>{chip.label}</span>
          <i className="fas fa-times catalog-active-filters__chip-remove" aria-hidden="true" />
        </button>
      ))}
      {chips.length > 1 ? (
        <button type="button" className="catalog-active-filters__clear" onClick={clearAll}>
          Limpiar todo
        </button>
      ) : null}
    </div>
  );
}
