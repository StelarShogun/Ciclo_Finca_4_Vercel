import { Head, Link } from '@inertiajs/react';

import { ClientLayout } from '@/shared/components/layout/ClientLayout';
import { Pagination } from '@/shared/components/ui/Pagination';
import type { NotificationsPageProps } from '@/types/notifications';

import '../../../../css/client/clients-users.css';

export default function NotificationsIndex(props: NotificationsPageProps) {
  return (
    <ClientLayout>
      <Head title="Mis Notificaciones - Ciclo Finca 4" />

      <div className="cf4-invoices-header">
        <div className="cf4-invoices-header-inner">
          <h1><i className="fas fa-bell" /> Mis Notificaciones</h1>
          <p>Historial de avisos enviados por el sistema.</p>
          <nav className="cf4-invoices-escape-nav" aria-label="Navegación">
            <Link href="/" className="cf4-invoices-escape-link">
              <i className="fas fa-home" aria-hidden="true" /> Ir al inicio
            </Link>
          </nav>
        </div>
      </div>

      <div className="cf4-invoices-wrapper">
        <nav className="breadcrumb" aria-label="Migas de pan">
          <Link href="/">Inicio</Link>
          <span>/</span>
          <span>Notificaciones</span>
        </nav>

        <div className="cf4-invoices-card">
          <div className="sales-table-container">
            <table className="sales-table cf4-purchases-table admin-table">
              <thead>
                <tr>
                  <th>Fecha</th>
                  <th>Mensaje</th>
                </tr>
              </thead>
              <tbody>
                {props.notifications.length === 0 ? (
                  <tr>
                    <td colSpan={2}>
                      <div className="cf4-invoices-empty">
                        <div className="cf4-invoices-empty-icon"><i className="fas fa-bell-slash" /></div>
                        <p>No tienes notificaciones.</p>
                      </div>
                    </td>
                  </tr>
                ) : (
                  props.notifications.map((n) => (
                    <tr key={n.id}>
                      <td data-label="Fecha">{n.createdAtLabel}</td>
                      <td data-label="Mensaje">
                        <div className="cf4-notification-message">{n.message}</div>
                        {n.actionUrl ? (
                          <div className="cf4-notification-action">
                            <a href={n.actionUrl}>{n.actionLabel || 'Abrir enlace'}</a>
                          </div>
                        ) : null}
                      </td>
                    </tr>
                  ))
                )}
              </tbody>
            </table>
          </div>

          {props.pagination.lastPage > 1 ? (
            <div className="cf4-invoices-pagination-wrap">
              <Pagination pagination={props.pagination} label="notificaciones" />
            </div>
          ) : null}
        </div>
      </div>
    </ClientLayout>
  );
}

