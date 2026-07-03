import { Link } from '@inertiajs/react';

import type { CatalogCategory } from '@/types/catalog';

type CatalogMobileControlsProps = {
  categories: CatalogCategory[];
  activeCategoryId?: number | null;
  activeFilterCount: number;
};

export function CatalogMobileControls({
  activeCategoryId = null,
  activeFilterCount,
  categories,
}: CatalogMobileControlsProps) {
  const activeId = activeCategoryId ?? '';

  return (
    <>
      <button
        className={`btn btn-outline-secondary catalog-filter-toggle ${activeFilterCount > 0 ? 'has-active-filters' : ''}`}
        id="catalog-filter-toggle"
        type="button"
        aria-expanded="false"
        aria-controls="catalog-sidebar"
      >
        <i className="fas fa-filter" aria-hidden="true" />
        <span>Filtrar productos</span>
        {activeFilterCount > 0 ? (
          <span className="catalog-filter-active-badge" aria-label={`${activeFilterCount} filtros activos`}>
            {activeFilterCount}
          </span>
        ) : null}
        <i className="fas fa-chevron-down catalog-filter-toggle-caret" aria-hidden="true" />
      </button>

      <div className="catalog-category-toolbar">
        <button
          type="button"
          id="catalog-category-trigger"
          className="btn btn-primary catalog-category-trigger"
          aria-expanded="false"
          aria-haspopup="dialog"
          aria-controls="catalog-category-panel"
        >
          <i className="fas fa-bicycle" aria-hidden="true" />
          Categorías
        </button>
      </div>

      <div id="catalog-category-backdrop" className="catalog-category-backdrop" aria-hidden="true" data-catalog-category-backdrop />

      <dialog
        id="catalog-category-panel"
        className="catalog-category-panel"
        aria-labelledby="catalog-category-panel-title"
        aria-hidden="true"
        data-active-category-id={String(activeId)}
        data-close-delay-ms="150"
      >
        <div className="catalog-category-panel-inner">
          <div className="catalog-category-panel-header">
            <h2 id="catalog-category-panel-title" className="catalog-category-panel-title">
              Categorías
            </h2>
            <button type="button" className="catalog-category-close" id="catalog-category-close" aria-label="Cerrar menú de categorías">
              <i className="fas fa-times" aria-hidden="true" />
            </button>
          </div>
          <div className="catalog-category-panel-body" data-catalog-panel-hover-root>
            <div className="catalog-category-col catalog-category-col--parents">
              <Link
                href="/catalog"
                className={`catalog-category-all-link ${activeCategoryId === null ? 'is-active-route' : ''}`}
              >
                Todas las categorías
              </Link>
              {categories.map((category) => {
                const childActive = category.children.some((child) => child.id === activeCategoryId);
                const parentActive = category.id === activeCategoryId;

                return (
                  <div
                    key={category.id}
                    className={`catalog-category-parent-row ${parentActive || childActive ? 'has-active' : ''}`}
                    data-parent-id={category.id}
                    data-has-children={category.children.length > 0 ? '1' : '0'}
                  >
                    <div className="catalog-category-parent-row-main">
                      <span className="catalog-category-parent-icon" aria-hidden="true">
                        <i className={category.icon} />
                      </span>
                      <Link
                        href={category.url_parent}
                        className={`catalog-category-parent-link ${parentActive && !childActive ? 'is-active-route' : ''}`}
                        data-category-id={category.id}
                      >
                        {category.name}
                      </Link>
                      {category.children.length > 0 ? (
                        <button
                          type="button"
                          className="catalog-category-panel-mobile-expand"
                          aria-expanded="false"
                          aria-controls={`catalog-panel-subs-${category.id}`}
                          aria-label={`Expandir subcategorías de ${category.name}`}
                        >
                          <i className="fas fa-chevron-down" aria-hidden="true" />
                        </button>
                      ) : null}
                    </div>
                    {category.children.length > 0 ? (
                      <div className="catalog-category-parent-mobile-subs" id={`catalog-panel-subs-${category.id}`} hidden>
                        <ul className="catalog-category-mobile-sub-list">
                          {category.children.map((child) => (
                            <li key={child.id}>
                              <Link
                                href={child.url}
                                className={`catalog-category-sub-link ${activeCategoryId === child.id ? 'is-active' : ''}`}
                              >
                                {child.name}
                              </Link>
                            </li>
                          ))}
                        </ul>
                      </div>
                    ) : null}
                  </div>
                );
              })}
            </div>
            <div className="catalog-category-col catalog-category-col--subs" id="catalog-category-subcolumn" aria-live="polite">
              <p className="catalog-category-placeholder">Pasá el cursor sobre una categoría para ver subcategorías.</p>
            </div>
          </div>
        </div>
      </dialog>

      <script type="application/json" id="catalog-category-tree-data">
        {JSON.stringify(categories)}
      </script>
    </>
  );
}
