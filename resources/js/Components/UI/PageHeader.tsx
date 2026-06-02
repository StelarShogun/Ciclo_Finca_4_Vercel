import type { PropsWithChildren, ReactNode } from 'react';

type PageHeaderProps = PropsWithChildren<{
  title: string;
  kicker?: string;
  actions?: ReactNode;
}>;

export function PageHeader({ actions, children, kicker, title }: PageHeaderProps) {
  return (
    <header className="page-header dashboard-header">
      <div>
        {kicker ? <p className="catalog-kicker">{kicker}</p> : null}
        <h1>{title}</h1>
        {children}
      </div>
      {actions ? <div className="header-actions">{actions}</div> : null}
    </header>
  );
}
