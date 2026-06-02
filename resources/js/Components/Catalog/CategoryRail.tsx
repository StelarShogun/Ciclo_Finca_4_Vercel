import { Link } from '@inertiajs/react';

import type { CatalogCategory } from '@/types/catalog';

type CategoryRailProps = {
  categories: CatalogCategory[];
  activeCategoryId?: number | null;
};

export function CategoryRail({ activeCategoryId = null, categories }: CategoryRailProps) {
  return (
    <div className="catalog-sidebar-stack">
      <div className="catalog-rail-wrap">
        <nav
          className="category-rail catalog-category-sidebar"
          id="catalog-category-sidebar"
          aria-label="Categorías del catálogo"
        >
          <div className="category-rail-header">
            <button
              id="catalog-category-sidebar-toggle"
              className="category-rail-toggle catalog-category-sidebar-brand"
              type="button"
              aria-expanded="false"
              aria-label="Expandir menú de categorías"
            >
              <i className="fas fa-bars" aria-hidden="true" />
            </button>
            <span className="category-rail-title">Categorías</span>
          </div>
          <div className="category-rail-scroll">
            <Link
              href="/catalog"
              className={`catalog-category-sidebar-all catalog-category-item ${activeCategoryId === null ? 'is-active' : ''}`}
              title="Todos los productos"
              aria-label="Todos los productos"
            >
              <span className="catalog-category-item-icon" aria-hidden="true">
                <i className="fas fa-list" />
              </span>
              <span className="catalog-category-item-label">Todos</span>
            </Link>

            {categories.map((category) => {
              const childActive = category.children.some((child) => child.id === activeCategoryId);
              const parentActive = category.id === activeCategoryId;

              return (
                <div
                  className={`catalog-category-sidebar-item ${parentActive || childActive ? 'has-active' : ''}`}
                  data-parent-id={category.id}
                  data-has-children={category.children.length > 0 ? '1' : '0'}
                  key={category.id}
                >
                  <div className="catalog-category-sidebar-item-row">
                    <Link
                      href={category.url_parent}
                      className={`catalog-category-item catalog-category-sidebar-link ${parentActive && !childActive ? 'is-active' : ''}`}
                      title={category.name}
                      aria-label={category.name}
                    >
                      <span className="catalog-category-item-icon" aria-hidden="true">
                        <i className={category.icon} />
                      </span>
                      <span className="catalog-category-item-label">{category.name}</span>
                    </Link>
                  </div>

                  {category.children.length > 0 ? (
                    <div className="catalog-category-flyout" role="region" aria-label={`Subcategorías de ${category.name}`}>
                      <div className="catalog-category-flyout-title">{category.name}</div>
                      <ul className="catalog-category-flyout-list">
                        {category.children.map((child) => (
                          <li key={child.id}>
                            <Link
                              href={child.url}
                              className={`catalog-category-subitem ${activeCategoryId === child.id ? 'is-active' : ''}`}
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
        </nav>
      </div>
    </div>
  );
}
