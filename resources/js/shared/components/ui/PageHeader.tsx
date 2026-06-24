import type { PropsWithChildren, ReactNode } from 'react';

type PageHeaderProps = PropsWithChildren<{
  title: string;
  kicker?: string;
  actions?: ReactNode;
  /** Icono FontAwesome (p. ej. "fa-box") mostrado como medallón a la izquierda. */
  icon?: string;
  /** Migas de pan opcionales, renderizadas encima del kicker (reportes). */
  breadcrumb?: ReactNode;
}>;

export function PageHeader({ actions, breadcrumb, children, icon, kicker, title }: PageHeaderProps) {
  return (
    <header className="page-header dashboard-header">
      <div className="page-header__lead">
        {icon ? (
          <span className="page-header__media" aria-hidden="true">
            <i className={`fas ${icon}`} />
          </span>
        ) : null}
        <div className="page-header__text">
          {breadcrumb ? <nav className="page-header__breadcrumb" aria-label="Migas de pan">{breadcrumb}</nav> : null}
          {kicker ? <p className="catalog-kicker">{kicker}</p> : null}
          <h1>{title}</h1>
          {children}
        </div>
      </div>
      {actions ? <div className="header-actions">{actions}</div> : null}
    </header>
  );
}
