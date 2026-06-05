import { Head, usePage } from '@inertiajs/react';
import '../../../../css/client/clients-page.css';

import { CatalogFilters } from '@/features/client/catalog/components/CatalogFilters';
import { CatalogMobileControls } from '@/features/client/catalog/components/CatalogMobileControls';
import { InertiaListPagination } from '@/shared/components/ui/InertiaListPagination';
import { CatalogProductCard } from '@/features/client/catalog/components/CatalogProductCard';
import { CatalogSpotlightCarousel } from '@/features/client/catalog/components/CatalogSpotlightCarousel';
import { CatalogToolbar } from '@/features/client/catalog/components/CatalogToolbar';
import { CategoryRail } from '@/features/client/catalog/components/CategoryRail';
import { ClientLayout } from '@/shared/components/layout/ClientLayout';
import { useCatalogPageInit } from '@/features/client/catalog/hooks/useCatalogPageInit';
import type {
  CatalogBrand,
  CatalogCategory,
  CatalogFilters as CatalogFiltersType,
  CatalogPagination as CatalogPaginationType,
  CatalogProduct,
  CatalogSpotlightItem,
  CatalogTaxonomy,
} from '@/types/catalog';
import type { InertiaSharedProps } from '@/shared/types/models';

type CatalogIndexProps = {
  products: CatalogProduct[];
  pagination: CatalogPaginationType;
  categories: CatalogCategory[];
  brands: CatalogBrand[];
  filters: CatalogFiltersType;
  selectedCategory?: CatalogTaxonomy | null;
  selectedBrand?: CatalogBrand | null;
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
  selectedBrand,
  selectedCategory,
  summary,
}: CatalogIndexProps) {
  const { auth, csrfToken } = usePage<InertiaSharedProps>().props;
  const isAuthenticated = auth.client !== null;

  useCatalogPageInit();

  const hasActiveCatalogFilters = summary.activeFilterCount > 0 || Boolean(filters.categoryId);
  const showCatalogSpotlight = catalogSpotlight.length > 0 && pagination.currentPage === 1 && !hasActiveCatalogFilters;

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
          <div className="catalog-sidebar-stack">
            <CategoryRail categories={categories} activeCategoryId={filters.categoryId ?? null} />
            <CatalogFilters
              key={`${filters.brandId ?? ''}-${filters.minPrice}-${filters.maxPrice}`}
              brands={brands}
              filters={filters}
              activeFilterCount={summary.activeFilterCount}
              currentPage={pagination.currentPage}
            />
          </div>

          <main className="catalog-main catalog-content">
            <CatalogMobileControls
              categories={categories}
              activeCategoryId={filters.categoryId ?? null}
              activeFilterCount={summary.activeFilterCount}
            />

            {showCatalogSpotlight ? (
              <CatalogSpotlightCarousel
                items={catalogSpotlight}
                csrfToken={csrfToken}
                isAuthenticated={isAuthenticated}
              />
            ) : null}

            <div className="catalog-results" data-cf4-ajax-pagination data-cf4-ajax-scroll>
              <div id="cf4-list-fragment">
                <CatalogToolbar
                  filters={filters}
                  selectedCategory={selectedCategory}
                  selectedBrand={selectedBrand}
                  paginationFrom={pagination.from}
                  paginationTo={pagination.to}
                  paginationTotal={pagination.total}
                  activeFilterCount={summary.activeFilterCount}
                />

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

                <InertiaListPagination pagination={pagination} label="productos" />
              </div>
            </div>
          </main>
        </div>
      </section>
    </ClientLayout>
  );
}
