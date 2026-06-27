import { Link } from '@inertiajs/react';
import { Fragment } from 'react';
import type { ReactNode } from 'react';

export type BreadcrumbItem = {
  label: ReactNode;
  href?: string;
};

type BreadcrumbsProps = {
  items: BreadcrumbItem[];
  className?: string;
};

/**
 * Migas de pan unificadas para el admin. El último ítem es la página actual
 * (aria-current). Usa <Link> de Inertia cuando hay href, texto plano si no.
 */
export function Breadcrumbs({ items, className = '' }: BreadcrumbsProps) {
  return (
    <nav className={`cf4-breadcrumb ${className}`.trim()} aria-label="Migas de pan">
      <ol className="cf4-breadcrumb__list">
        {items.map((item, index) => {
          const isLast = index === items.length - 1;
          return (
            <Fragment key={index}>
              <li className="cf4-breadcrumb__item">
                {item.href && !isLast ? (
                  <Link href={item.href} className="cf4-breadcrumb__link">
                    {item.label}
                  </Link>
                ) : (
                  <span className="cf4-breadcrumb__current" aria-current={isLast ? 'page' : undefined}>
                    {item.label}
                  </span>
                )}
              </li>
              {!isLast ? (
                <li className="cf4-breadcrumb__sep" aria-hidden="true">
                  <i className="fas fa-chevron-right" />
                </li>
              ) : null}
            </Fragment>
          );
        })}
      </ol>
    </nav>
  );
}
