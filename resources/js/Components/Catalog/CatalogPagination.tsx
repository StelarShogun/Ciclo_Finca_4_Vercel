import { Link } from '@inertiajs/react';

import type { CatalogPagination as CatalogPaginationType } from '@/types/catalog';

type CatalogPaginationProps = {
  pagination: CatalogPaginationType;
};

export function CatalogPagination({ pagination }: CatalogPaginationProps) {
  if (pagination.lastPage <= 1) {
    return null;
  }

  return (
    <nav className="pagination-wrapper" aria-label="Paginación del catálogo">
      <ul className="pagination">
        {pagination.links.map((link, index) => (
          <li className={`page-item ${link.active ? 'active' : ''} ${!link.url ? 'disabled' : ''}`} key={`${link.label}-${index}`}>
            {link.url ? (
              <Link className="page-link" href={link.url} preserveScroll dangerouslySetInnerHTML={{ __html: link.label }} />
            ) : (
              <span className="page-link" dangerouslySetInnerHTML={{ __html: link.label }} />
            )}
          </li>
        ))}
      </ul>
    </nav>
  );
}
