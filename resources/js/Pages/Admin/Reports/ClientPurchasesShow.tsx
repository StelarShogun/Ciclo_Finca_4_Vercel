import { Head } from '@inertiajs/react';
import { useState } from 'react';

import { AdminLayout } from '@/shared/components/layout/AdminLayout';
import { PageHeader } from '@/shared/components/ui/PageHeader';

import { ViewSaleModal } from '../Sales/components/ViewSaleModal';

import '../../../../css/admin/reports/client-purchase-history.css';

const money = new Intl.NumberFormat('es-CR', { maximumFractionDigits: 0 });
function crc(n: number): string {
  return `₡${money.format(Math.round(Number(n) || 0))}`;
}

type Order = { sale_id: number; invoice_number: string; sale_date: string; total: number };
type PageProps = { clientId: number; displayName: string; gmail: string; orders: Order[]; listUrl: string };

export default function ClientPurchasesShow({ displayName, gmail, orders, listUrl }: PageProps) {
  const [viewId, setViewId] = useState<number | null>(null);
  const total = orders.reduce((a, o) => a + o.total, 0);

  return (
    <AdminLayout title={`Compras — ${displayName}`}>
      <Head title={`Compras — ${displayName} - Reportes`} />

      <div className="client-purchases-report client-purchases-show">

        <PageHeader
          title={displayName}
          kicker="Compras por cliente"
          actions={<a href={listUrl} className="btn btn-secondary btn-sm"><i className="fas fa-arrow-left" aria-hidden="true" /> Volver</a>}
        >
          <p>
            Consulta las ventas completadas registradas para <strong>{displayName}</strong>.
            {gmail ? <span className="client-purchases-show-email"> {gmail}</span> : null}
          </p>
        </PageHeader>

        <div className="table-section">
          <div className="sales-table-container">
            <table className="admin-table">
              <thead>
                <tr>
                  <th>Factura</th>
                  <th>Fecha</th>
                  <th className="num">Total</th>
                  <th className="col-actions admin-table__col--actions">Acción</th>
                </tr>
              </thead>
              <tbody>
                {orders.length === 0 ? (
                  <tr><td colSpan={4} className="empty-cell">Este cliente no tiene ventas completadas.</td></tr>
                ) : (
                  orders.map((o) => (
                    <tr key={o.sale_id}>
                      <td><code className="client-orders-invoice">{o.invoice_number}</code></td>
                      <td>{o.sale_date}</td>
                      <td className="num">{crc(o.total)}</td>
                      <td className="admin-table__col--actions">
                        <div className="actions-container">
                          <button type="button" className="action-btn secondary" data-tooltip="Ver detalle de la venta" aria-label="Ver detalle de la venta" onClick={() => setViewId(o.sale_id)}>
                            <i className="fas fa-eye" aria-hidden="true" />
                          </button>
                        </div>
                      </td>
                    </tr>
                  ))
                )}
              </tbody>
              {orders.length > 0 ? (
                <tfoot>
                  <tr className="tfoot-total">
                    <td colSpan={2}>Total</td>
                    <td className="num">{crc(total)}</td>
                    <td />
                  </tr>
                </tfoot>
              ) : null}
            </table>
          </div>
        </div>
      </div>

      <ViewSaleModal saleId={viewId} onClose={() => setViewId(null)} title="Detalles de la venta" />
    </AdminLayout>
  );
}
