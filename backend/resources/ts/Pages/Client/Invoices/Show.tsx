import { Head, Link } from '@inertiajs/react';

import { InvoiceLineThumb } from '@/features/client/invoices/components/InvoiceLineThumb';
import { ClientLayout } from '@/shared/components/layout/ClientLayout';
import { StatusBadge } from '@/shared/components/ui/StatusBadge';
import type { InvoiceDetailPageProps } from '@/types/invoices';

import '../../../../css/client/clients-users.css';
import '../../../../css/client/invoice-detail.css';
import '../../../../css/admin/sales/invoice-document.css';

export default function InvoiceShow(props: InvoiceDetailPageProps) {
  return (
    <ClientLayout>
      <Head title="Detalle de pedido - Ciclo Finca 4" />

      <meta name="cf4-invoice-count" content={String(props.invoiceCount)} />

      <div className="cf4-invoice-detail-page">
        <div className="cf4-invoices-header">
          <div className="cf4-invoices-header-inner">
            <h1><i className="fas fa-file-invoice" /> Detalle del pedido</h1>
            <p>
              {props.invoiceNumber ? (
                <>Factura <strong>{props.invoiceNumber}</strong></>
              ) : (
                <>Pedido sin número de factura asignado</>
              )}
            </p>
          </div>
        </div>

        <div className="cf4-invoices-wrapper">
          <div className="mobile-back-nav">
            <Link href={props.backUrl} className="mobile-back-link">
              <i className="fas fa-arrow-left" aria-hidden="true" /> Volver a Mis Facturas
            </Link>
          </div>

          <nav className="breadcrumb" aria-label="Migas de pan">
            <Link href="/">Inicio</Link>
            <span>/</span>
            <Link href="/invoices">Mis Facturas</Link>
            <span>/</span>
            <span>Detalle</span>
          </nav>

          <div className="cf4-invoices-card">
            <div className="cf4-invoice-detail-toolbar">
              <div className="cf4-invoice-detail-status">
                <StatusBadge
                  variant="pill"
                  pillClass={props.orderMeta.statusPillClass}
                  icon={<i className={`fas ${props.orderMeta.statusIconClass}`} aria-hidden="true" />}
                >
                  {props.orderMeta.statusLabel}
                </StatusBadge>
                {props.orderMeta.cancellationReason ? (
                  <p className="cf4-invoice-cancellation-reason">{props.orderMeta.cancellationReason}</p>
                ) : null}
              </div>
              <div className="cf4-invoice-detail-actions">
                <a href={props.printUrl} className="btn btn-secondary btn-sm" target="_blank" rel="noreferrer">
                  <i className="fas fa-print" aria-hidden="true" /> Imprimir
                </a>
              </div>
            </div>

            <div className="invoice-document">
              <header className="invoice-document__head">
                <div>
                  <h2 className="invoice-document__title">{props.documentTitle}</h2>
                  <p className="invoice-document__subtitle">Pedido #{props.orderMeta.saleId}</p>
                </div>
                <dl className="invoice-document__meta">
                  <div>
                    <dt>Fecha</dt>
                    <dd>{props.orderMeta.saleDateLabel}</dd>
                  </div>
                  <div>
                    <dt>Pago</dt>
                    <dd>{props.orderMeta.paymentDisplay}</dd>
                  </div>
                  <div>
                    <dt>Origen</dt>
                    <dd>{props.orderMeta.sourceDisplay}</dd>
                  </div>
                </dl>
              </header>

              <div className="invoice-document__table">
                <table>
                  <thead>
                    <tr>
                      <th>Producto</th>
                      <th>Cant.</th>
                      <th>Precio</th>
                      <th>Total</th>
                    </tr>
                  </thead>
                  <tbody>
                    {props.items.map((item) => (
                      <tr key={item.productId}>
                        <td>
                          <div className="cf4-product-cell">
                            <InvoiceLineThumb item={item} />
                            <span>{item.name}</span>
                          </div>
                        </td>
                        <td>{item.quantity}</td>
                        <td>{item.unitPriceFormatted}</td>
                        <td>{item.totalFormatted}</td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>

              <footer className="invoice-document__totals">
                <dl>
                  <div>
                    <dt>Subtotal</dt>
                    <dd>{props.totals.subtotalFormatted}</dd>
                  </div>
                  <div>
                    <dt>IVA</dt>
                    <dd>{props.totals.ivaFormatted}</dd>
                  </div>
                  <div>
                    <dt>Descuento</dt>
                    <dd>{props.totals.discountFormatted}</dd>
                  </div>
                  <div className="invoice-document__totals-total">
                    <dt>Total</dt>
                    <dd>{props.totals.totalFormatted}</dd>
                  </div>
                </dl>
                <p className="invoice-document__totals-items">Productos: {props.totals.itemsCount}</p>
              </footer>
            </div>
          </div>
        </div>
      </div>
    </ClientLayout>
  );
}

