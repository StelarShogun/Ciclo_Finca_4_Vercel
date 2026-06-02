import { Head, Link, usePage } from '@inertiajs/react';

import { ClientLayout } from '@/shared/components/layout/ClientLayout';
import { Pagination } from '@/shared/components/ui/Pagination';
import type { InertiaSharedProps } from '@/types/models';
import type { InvoiceListPageProps, InvoicesTab } from '@/types/invoices';

import '../../../../css/client/clients-users.css';

function headerDescription(tab: InvoicesTab) {
  if (tab === 'historial') return 'Compras completadas por la tienda.';
  if (tab === 'canceladas') return 'Pedidos cancelados.';
  return 'Pedidos pendientes o listos para recoger.';
}

const statusClass: Record<string, string> = {
  pending: 'cf4-invoice-status-pending',
  ready: 'cf4-invoice-status-ready',
  cancelled: 'cf4-invoice-status-cancelled',
  completed: 'cf4-invoice-status-completed',
  default: 'cf4-invoice-status-default',
};

export default function InvoicesIndex(props: InvoiceListPageProps) {
  const page = usePage<InertiaSharedProps>();
  const { auth } = page.props;

  return (
    <ClientLayout>
      <Head title="Mis Facturas - Ciclo Finca 4" />

      <div className="cf4-invoices-header">
        <div className="cf4-invoices-header-inner">
          <h1><i className="fas fa-file-invoice" /> Mis Facturas</h1>
          <p>{headerDescription(props.tab)}</p>
          <nav className="cf4-invoices-escape-nav" aria-label="Seguir en la tienda">
            <Link href="/catalog" className="cf4-invoices-escape-link cf4-invoices-escape-link--primary">
              <i className="fas fa-store" aria-hidden="true" /> Seguir comprando
            </Link>
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
          <span>Mis Facturas</span>
        </nav>

        <nav className="cf4-invoices-tab-cards" aria-label="Filtrar facturas">
          <Link
            href="/invoices?tab=facturas"
            className={`cf4-invoices-tab-card ${props.tab === 'facturas' ? 'is-active' : ''}`.trim()}
            aria-current={props.tab === 'facturas' ? 'page' : undefined}
          >
            <i className="fas fa-file-invoice cf4-invoices-tab-card__icon" aria-hidden="true" />
            <span className="cf4-invoices-tab-card__label">Pendientes / Por recoger</span>
          </Link>
          <Link
            href="/invoices?tab=canceladas"
            className={`cf4-invoices-tab-card ${props.tab === 'canceladas' ? 'is-active' : ''}`.trim()}
            aria-current={props.tab === 'canceladas' ? 'page' : undefined}
          >
            <i className="fas fa-ban cf4-invoices-tab-card__icon" aria-hidden="true" />
            <span className="cf4-invoices-tab-card__label">Canceladas</span>
          </Link>
          <Link
            href="/invoices?tab=historial"
            className={`cf4-invoices-tab-card ${props.tab === 'historial' ? 'is-active' : ''}`.trim()}
            aria-current={props.tab === 'historial' ? 'page' : undefined}
          >
            <i className="fas fa-history cf4-invoices-tab-card__icon" aria-hidden="true" />
            <span className="cf4-invoices-tab-card__label">Historial de compras</span>
            {props.unseenHistoryCount > 0 && props.tab !== 'historial' ? (
              <span className="cf4-invoices-tab-card__badge" id="history-tab-badge" title="Compras nuevas en Historial" />
            ) : null}
          </Link>
        </nav>

        {props.readyToPickupCount > 0 && props.tab === 'facturas' ? (
          <div className="cf4-ready-pickup-banner" role="status" aria-live="polite">
            <div className="cf4-ready-pickup-banner__icon" aria-hidden="true">
              <i className="fas fa-box-open" />
            </div>
            <div className="cf4-ready-pickup-banner__body">
              <strong>Tu pedido ya está listo para retirar</strong>
              <p>
                {props.readyToPickupCount === 1 ? 'Tienes 1 pedido' : `Tienes ${props.readyToPickupCount} pedidos`} listo{props.readyToPickupCount === 1 ? '' : 's'} en tienda.
              </p>
            </div>
          </div>
        ) : null}

        <div className="cf4-invoices-card">
          {props.orders.length === 0 ? (
            <div className="cf4-invoices-empty cf4-invoices-empty--panel">
              <div className="cf4-invoices-empty-icon"><i className="fas fa-file-invoice" /></div>
              <p>
                {props.tab === 'historial'
                  ? 'No has realizado ninguna compra aún.'
                  : props.tab === 'canceladas'
                    ? 'No tienes facturas canceladas.'
                    : 'No tienes facturas pendientes o por recoger.'}
              </p>
              <Link href="/catalog" className="btn btn-primary btn-sm">
                <i className="fas fa-bicycle" /> Ir al catálogo
              </Link>
            </div>
          ) : (
            <div className="sales-table-container cf4-invoices-table-scroll">
              <table className="sales-table cf4-invoices-list-table admin-table">
                <thead>
                  <tr>
                    <th>Factura</th>
                    <th>Fecha</th>
                    <th>Estado</th>
                    <th>{props.tab === 'historial' ? 'Total pagado' : 'Total'}</th>
                    <th className="cf4-invoices-th-actions">Acciones</th>
                  </tr>
                </thead>
                <tbody>
                  {props.orders.map((sale) => (
                    <tr key={sale.id}>
                      <td data-label="Factura">
                        {sale.invoiceNumber ? <strong>{sale.invoiceNumber}</strong> : <span className="cf4-invoice-muted">Sin número asignado</span>}
                      </td>
                      <td data-label="Fecha">{sale.saleDateLabel}</td>
                      <td data-label="Estado">
                        <span className={`cf4-invoice-status-badge ${statusClass[sale.statusTone] ?? statusClass.default}`.trim()}>
                          {sale.statusLabel}
                        </span>
                      </td>
                      <td data-label={props.tab === 'historial' ? 'Total pagado' : 'Total'}>
                        <strong>{sale.totalFormatted}</strong>
                      </td>
                      <td className="cf4-invoices-td-actions" data-label="Acciones">
                        <Link href={sale.showUrl} className="btn btn-primary btn-sm cf4-invoice-detail-btn" aria-label={`Ver pedido${sale.invoiceNumber ? ` ${sale.invoiceNumber}` : ''}`}>
                          Ver pedido
                        </Link>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          )}

          {props.pagination.lastPage > 1 ? (
            <div className="cf4-invoices-pagination-wrap">
              <Pagination pagination={props.pagination} label="facturas" />
            </div>
          ) : null}
        </div>
      </div>

      {auth.client ? (
        <>
          <meta name="cf4-invoice-count" content={String(props.invoiceCount)} />
          <meta name="cf4-unseen-history-count" content={String(props.unseenHistoryCount)} />
          <meta name="cf4-invoice-revision" content={String(props.invoicesRevision)} />
          <meta name="cf4-invoice-heartbeat-url" content={props.heartbeatUrl} />
        </>
      ) : null}
    </ClientLayout>
  );
}

