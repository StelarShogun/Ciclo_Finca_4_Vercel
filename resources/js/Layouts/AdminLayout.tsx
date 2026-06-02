import { Link, usePage } from '@inertiajs/react';
import '../../css/admin/shell-base.css';
import '../../css/admin/components/page-header.css';
import '../../css/admin/dashboard/dashboard.css';
import type { PropsWithChildren } from 'react';

import type { InertiaSharedProps } from '@/types/models';

type AdminLayoutProps = PropsWithChildren<{
  title?: string;
}>;

export function AdminLayout({ children, title = 'Panel admin' }: AdminLayoutProps) {
  const { auth } = usePage<InertiaSharedProps>().props;

  return (
    <div className="admin-layout">
      <aside className="admin-sidebar">
        <div className="sidebar-brand">
          <Link href="/dashboard" className="sidebar-brand-link">
            <span>Ciclo Finca 4</span>
          </Link>
        </div>
        <nav className="sidebar-nav" aria-label="Navegación admin">
          <Link href="/dashboard" className="sidebar-link">
            <i className="fas fa-chart-line" aria-hidden="true" />
            Dashboard
          </Link>
          <Link href="/inventory" className="sidebar-link">
            <i className="fas fa-boxes-stacked" aria-hidden="true" />
            Inventario
          </Link>
          <Link href="/sales" className="sidebar-link">
            <i className="fas fa-cash-register" aria-hidden="true" />
            Ventas
          </Link>
          <Link href="/orders" className="sidebar-link">
            <i className="fas fa-clipboard-list" aria-hidden="true" />
            Pedidos
          </Link>
          <Link href="/reports" className="sidebar-link">
            <i className="fas fa-chart-pie" aria-hidden="true" />
            Reportes
          </Link>
        </nav>
        <div className="sidebar-footer">
          <p>{auth.admin ? `${auth.admin.name} ${auth.admin.first_surname ?? ''}` : 'Administrador'}</p>
        </div>
      </aside>

      <main className="admin-main admin-main--content">
        <div className="admin-content-wrapper">
          <header className="page-header">
            <div>
              <h1>{title}</h1>
              <p>Migración piloto Inertia + React + TypeScript.</p>
            </div>
          </header>
          {children}
        </div>
      </main>
    </div>
  );
}
