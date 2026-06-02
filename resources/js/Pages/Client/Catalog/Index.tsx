import { Head, router, usePage } from '@inertiajs/react';
import '../../../../css/client/clients-page.css';

import { CatalogFilters } from '@/Components/Catalog/CatalogFilters';
import { CatalogPagination } from '@/Components/Catalog/CatalogPagination';
import { CatalogProductCard } from '@/Components/Catalog/CatalogProductCard';
import { CategoryRail } from '@/Components/Catalog/CategoryRail';
import { ClientLayout } from '@/Layouts/ClientLayout';
import type {
  CatalogBrand,
  CatalogCategory,
  CatalogFilters as CatalogFiltersType,
  CatalogPagination as CatalogPaginationType,
  CatalogProduct,
  CatalogSpotlightItem,
  CatalogTaxonomy,
} from '@/types/catalog';
import type { InertiaSharedProps } from '@/types/models';

type CatalogIndexProps = {
  products: CatalogProduct[];
  pagination: CatalogPaginationType;
  categories: CatalogCategory[];
  brands: CatalogBrand[];
  filters: CatalogFiltersType;
  selectedCategory?: CatalogTaxonomy | null;
  subcategories: CatalogTaxonomy[];
  parentCategoryForSubcats?: CatalogTaxonomy | null;
  catalogSpotlight: CatalogSpotlightItem[];
  favoriteProductIds: number[];
  emptyCategoryNoProducts: boolean;
  catalogVersion: string;
  summary: {
    totalProducts: number;
    totalCategories: number;
    activeFilterCount: number;
  };
};

export default function CatalogIndex({
  brands,
  catalogSpotlight,
  catalogVersion,
  categories,
  emptyCategoryNoProducts,
  filters,
  pagination,
  products,
  selectedCategory,
  summary,
}: CatalogIndexProps) {
  const { auth, csrfToken } = usePage<InertiaSharedProps>().props;
  const isAuthenticated = auth.client !== null;

  function updateSort(sort: string, direction: string) {
    router.get('/catalog', compactParams({
      ...filtersToQuery(filters),
      sort,
      direction,
    }), { preserveScroll: true });
  }

  return (
    <ClientLayout>
      <Head title="Catálogo - Ciclo Finca 4">
        <meta name="cf4-catalog-heartbeat-url" content="/api/catalog/heartbeat" />
        <meta name="cf4-catalog-initial-version" content={catalogVersion} />
      </Head>

      <section className="catalog-shell">
        <header className="catalog-hero">
          <div className="catalog-hero-content">
            <span className="catalog-kicker">Ciclo Finca 4</span>
            <h1>Catálogo de Productos</h1>
            <p className="catalog-subtitle">Explora nuestra amplia selección de productos</p>
            <div className="catalog-hero-stats" aria-label="Resumen del catálogo">
              <span><strong>{summary.totalProducts.toLocaleString('es-CR')}</strong> productos</span>
              <span><strong>{summary.totalCategories.toLocaleString('es-CR')}</strong> categorías</span>
            </div>
          </div>
        </header>

        <div className="catalog-container">
          <CategoryRail categories={categories} activeCategoryId={filters.categoryId ?? null} />
          <CatalogFilters brands={brands} filters={filters} />

          <main className="catalog-main catalog-content">
            {catalogSpotlight.length > 0 ? (
              <section className="catalog-spotlight catalog-spotlight-section" aria-label="Destacados y novedades">
                <div className="catalog-spotlight-header">
                  <h2 className="catalog-spotlight-title">Destacados y novedades</h2>
                  <p className="catalog-spotlight-subtitle">Productos seleccionados y recién agregados.</p>
                </div>
                <div className="catalog-spotlight-grid">
                  {catalogSpotlight.slice(0, 4).map((item) => (
                    <CatalogProductCard
                      key={`${item.kind}-${item.product.id}`}
                      product={item.product}
                      csrfToken={csrfToken}
                      isAuthenticated={isAuthenticated}
                    />
                  ))}
                </div>
              </section>
            ) : null}

            <div className="catalog-toolbar">
              <div className="catalog-toolbar-meta">
                <h2>{selectedCategory ? selectedCategory.name : 'Todos los productos'}</h2>
                <p>
                  Mostrando {pagination.from ?? 0}-{pagination.to ?? 0} de {pagination.total.toLocaleString('es-CR')} productos.
                  {summary.activeFilterCount > 0 ? ` ${summary.activeFilterCount} filtros activos.` : ''}
                </p>
              </div>

              <div className="catalog-toolbar-sort" aria-label="Ordenamiento del catálogo">
                <button type="button" className="btn btn-secondary" onClick={() => updateSort('created_at', 'desc')}>
                  Recientes
                </button>
                <button type="button" className="btn btn-secondary" onClick={() => updateSort('price', 'asc')}>
                  Menor precio
                </button>
                <button type="button" className="btn btn-secondary" onClick={() => updateSort('price', 'desc')}>
                  Mayor precio
                </button>
              </div>
            </div>

            {emptyCategoryNoProducts ? (
              <div className="empty-state">
                <i className="fas fa-box-open" aria-hidden="true" />
                <h2>No hay productos en esta categoría</h2>
                <p>Probá con otra categoría o quitá los filtros aplicados.</p>
              </div>
            ) : products.length === 0 ? (
              <div className="empty-state">
                <i className="fas fa-search" aria-hidden="true" />
                <h2>No encontramos productos</h2>
                <p>Ajustá la búsqueda, marca o rango de precios para ver más resultados.</p>
              </div>
            ) : (
              <div className="products-grid">
                {products.map((product) => (
                  <CatalogProductCard
                    key={product.id}
                    product={product}
                    csrfToken={csrfToken}
                    isAuthenticated={isAuthenticated}
                  />
                ))}
              </div>
            )}

            <CatalogPagination pagination={pagination} />
          </main>
        </div>
      </section>
    </ClientLayout>
  );
}

function filtersToQuery(filters: CatalogFiltersType) {
  return {
    search: filters.search,
    category_id: filters.categoryId,
    brand_id: filters.brandId,
    min_price: filters.minPrice,
    max_price: filters.maxPrice,
    per_page: filters.perPage,
  };
}

function compactParams(params: Record<string, string | number | null | undefined>) {
  return Object.fromEntries(
    Object.entries(params).filter(([, value]) => value !== null && value !== undefined && value !== ''),
  );
}
