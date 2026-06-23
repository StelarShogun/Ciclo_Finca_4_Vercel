import { useEffect, useState } from 'react';

import { Modal } from '@/shared/components/ui/Modal';

import type { SaleDetail } from '../types';

const STATUS_LABELS: Record<string, string> = {
  pending: 'Pendiente',
  ready_to_pickup: 'Por recoger',
  completed: 'Confirmado',
  cancelled: 'Rechazado',
  refunded: 'Reembolsado (histórico)',
  returned: 'Devuelta',
};
const PAYMENT_LABELS: Record<string, string> = { cash: 'Efectivo', sinpe: 'SINPE Móvil', transfer: 'Transferencia' };

const money = new Intl.NumberFormat('es-CR', { minimumFractionDigits: 2 });
function crc(n: number): string {
  return `₡${money.format(Number(n) || 0)}`;
}

type Props = {
  saleId: number | null;
  onClose: () => void;
  onPrint: (saleId: number, invoiceLabel: string) => void;
};

export function ViewSaleModal({ saleId, onClose, onPrint }: Props) {
  const [sale, setSale] = useState<SaleDetail | null>(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(false);

  useEffect(() => {
    if (saleId == null) {
      setSale(null);
      setError(false);
      return;
    }
    let active = true;
    setLoading(true);
    setError(false);
    setSale(null);
    fetch(`/sales/${saleId}`, { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
      .then((r) => r.json())
      .then((data) => {
        if (!active) return;
        if (data.success && data.sale) {
          setSale(data.sale);
        } else {
          setError(true);
        }
      })
      .catch(() => active && setError(true))
      .finally(() => active && setLoading(false));
    return () => {
      active = false;
    };
  }, [saleId]);

  const isWebOrder = sale ? sale.order_source === 'web_cart' || sale.order_source == null : false;
  const saleDateLabel = isWebOrder ? 'Fecha de pedido' : 'Fecha de venta';
  const saleDateValue = sale ? (isWebOrder ? sale.order_placed_at_label || sale.sale_date_label || '—' : sale.sale_date_label || '—') : '—';

  let customerName = 'Mostrador / sin datos';
  if (sale?.client) {
    customerName = [sale.client.name, sale.client.first_surname, sale.client.second_surname].filter(Boolean).join(' ');
    if (sale.client.gmail) customerName += ` (${sale.client.gmail})`;
  } else if (sale?.buyer?.name) {
    customerName = sale.buyer.name;
    if (sale.buyer.email) customerName += ` (${sale.buyer.email})`;
  }

  const invoiceLabel = sale ? sale.invoice_number || `#${sale.sale_id}` : '';

  return (
    <Modal
      isOpen={saleId != null}
      onClose={onClose}
      className="cf4-modal cf4-modal--wide"
      title={<><i className="fas fa-eye" aria-hidden="true" /> Detalles de la Venta</>}
      footer={
        <>
          {sale ? (
            <button type="button" className="btn btn-primary" onClick={() => onPrint(sale.sale_id, invoiceLabel)}>
              <i className="fas fa-print" aria-hidden="true" /> Imprimir factura
            </button>
          ) : null}
          <button type="button" className="btn btn-secondary" onClick={onClose}>
            <i className="fas fa-times" aria-hidden="true" /> Cerrar
          </button>
        </>
      }
    >
      {loading ? (
        <div className="loading-spinner" role="status">
          <i className="fas fa-spinner fa-spin fa-2x" aria-hidden="true" />
          <p>Cargando detalles…</p>
        </div>
      ) : error || !sale ? (
        <div className="alert alert-danger">
          <i className="fas fa-exclamation-circle" aria-hidden="true" /> No se pudieron cargar los detalles.
        </div>
      ) : (
        <div className="sale-details">
          <div className="detail-section">
            <h4><i className="fas fa-info-circle" aria-hidden="true" /> Información general</h4>
            <div className="detail-grid">
              <div className="detail-item"><label>Factura:</label><span><strong>{invoiceLabel}</strong></span></div>
              <div className="detail-item"><label>{saleDateLabel}:</label><span>{saleDateValue}</span></div>
              {sale.status === 'completed' ? (
                <div className="detail-item"><label>Fecha de confirmación:</label><span>{sale.confirmed_at_label || '—'}</span></div>
              ) : null}
              <div className="detail-item"><label>Cliente:</label><span>{customerName}</span></div>
              <div className="detail-item"><label>Estado:</label><span className={`status-badge ${sale.status}`}>{STATUS_LABELS[sale.status] || sale.status}</span></div>
              <div className="detail-item"><label>Método de pago:</label><span>{PAYMENT_LABELS[sale.payment_method] || sale.payment_method || '—'}</span></div>
              {sale.payment_reference ? <div className="detail-item"><label>Referencia:</label><span>{sale.payment_reference}</span></div> : null}
            </div>
          </div>

          <div className="detail-section">
            <h4><i className="fas fa-shopping-cart" aria-hidden="true" /> Productos</h4>
            {sale.sale_items.length > 0 ? (
              <table className="sale-products-table admin-table">
                <thead>
                  <tr>
                    <th>Producto</th>
                    <th className="text-center">Cantidad</th>
                    <th className="text-right">Precio unit.</th>
                    <th className="text-right">Total</th>
                  </tr>
                </thead>
                <tbody>
                  {sale.sale_items.map((item) => (
                    <tr key={item.id}>
                      <td>{item.product?.name || 'N/A'}</td>
                      <td className="text-center">{item.quantity}</td>
                      <td className="text-right">{crc(item.unit_price)}</td>
                      <td className="text-right"><strong>{crc(item.total)}</strong></td>
                    </tr>
                  ))}
                </tbody>
              </table>
            ) : (
              <p className="text-muted">Sin productos registrados.</p>
            )}
          </div>

          <div className="detail-section">
            <h4><i className="fas fa-calculator" aria-hidden="true" /> Totales</h4>
            <div className="totals-summary">
              <div className="total-item"><span>Subtotal:</span><span>{crc(sale.subtotal)}</span></div>
              {sale.discount > 0 ? <div className="total-item"><span>Descuento:</span><span>-{crc(sale.discount)}</span></div> : null}
              <div className="total-item total-final"><span><strong>Total:</strong></span><span><strong>{crc(sale.total)}</strong></span></div>
            </div>
          </div>

          {sale.status === 'returned' ? (
            <div className="detail-section">
              <h4><i className="fas fa-rotate-left" aria-hidden="true" /> Datos de la devolución</h4>
              <div className="detail-grid">
                <div className="detail-item"><label>Fecha de devolución:</label><span>{sale.returned_at ? new Date(sale.returned_at).toLocaleString('es-CR') : '—'}</span></div>
                <div className="detail-item"><label>Registrado por:</label><span>{sale.returned_by?.name || 'Administrador'}</span></div>
              </div>
            </div>
          ) : null}

          {sale.notes ? (
            <div className="detail-section">
              <h4><i className="fas fa-sticky-note" aria-hidden="true" /> Notas</h4>
              <p className="sale-notes">{sale.notes}</p>
            </div>
          ) : null}
        </div>
      )}
    </Modal>
  );
}
