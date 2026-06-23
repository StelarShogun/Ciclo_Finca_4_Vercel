import { useEffect, useState } from 'react';

import { Modal } from '@/shared/components/ui/Modal';

import type { SupplierOrderDetail } from '../types';

const STATE_LABELS: Record<string, string> = {
  draft: 'Borrador',
  pending: 'Pendiente',
  confirmed: 'Confirmado',
  partial_received: 'Recepción parcial',
  delivered: 'Entregado',
  cancelled: 'Cancelado',
};

const TL_CONFIG: Record<string, { label: string; icon: string; color: string }> = {
  draft: { label: 'Borrador', icon: 'fa-pencil-alt', color: '#64748b' },
  pending: { label: 'Pendiente', icon: 'fa-clock', color: '#f59e0b' },
  confirmed: { label: 'Confirmado', icon: 'fa-check', color: '#3b82f6' },
  partial_received: { label: 'Recepción parcial', icon: 'fa-clipboard-check', color: '#f97316' },
  delivered: { label: 'Entregado', icon: 'fa-truck', color: '#235347' },
  cancelled: { label: 'Cancelado', icon: 'fa-times', color: '#ef4444' },
};

const money = new Intl.NumberFormat('es-CR', { minimumFractionDigits: 2 });
function crc(n: number): string {
  return `₡${money.format(Number(n) || 0)}`;
}

type Props = {
  orderId: number | null;
  onClose: () => void;
  onConfirm: (order: SupplierOrderDetail) => void;
  onCancel: (order: SupplierOrderDetail) => void;
  reloadKey: number;
};

export function ViewOrderModal({ orderId, onClose, onConfirm, onCancel, reloadKey }: Props) {
  const [order, setOrder] = useState<SupplierOrderDetail | null>(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(false);

  useEffect(() => {
    if (orderId == null) {
      setOrder(null);
      return;
    }
    let active = true;
    setLoading(true);
    setError(false);
    setOrder(null);
    fetch(`/supplier-orders/${orderId}`, { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
      .then((r) => r.json())
      .then((data) => {
        if (!active) return;
        if (data.success && data.order) setOrder(data.order);
        else setError(true);
      })
      .catch(() => active && setError(true))
      .finally(() => active && setLoading(false));
    return () => {
      active = false;
    };
  }, [orderId, reloadKey]);

  const showRecvCol = order ? order.products.some((p) => p.received_quantity !== null) : false;
  const initialFromLines = order ? order.products.reduce((a, p) => a + (Number(p.total) || 0), 0) : 0;
  const initialTotal = order ? (initialFromLines > 0 ? initialFromLines : Number(order.total) || 0) : 0;
  const receivedTotal = showRecvCol && order ? order.products.reduce((a, p) => a + Math.round(((Number(p.unit_price) || 0) * (Number(p.received_quantity ?? 0) || 0) + Number.EPSILON) * 100) / 100, 0) : null;
  const shortsTotal = showRecvCol && receivedTotal !== null ? Math.max(initialTotal - receivedTotal, 0) : 0;

  const detailUrl = order ? `/supplier-orders/${order.num_order}/detail` : '#';
  const canConfirm = order ? order.state === 'draft' || order.state === 'pending' : false;
  const canCancel = order ? ['draft', 'pending', 'confirmed', 'partial_received'].includes(order.state) : false;
  const canReceive = order ? order.state === 'confirmed' || order.state === 'partial_received' : false;

  return (
    <Modal
      isOpen={orderId != null}
      onClose={onClose}
      className="cf4-modal cf4-modal--wide"
      title={<><i className="fas fa-box" aria-hidden="true" /> Detalles del pedido</>}
      footer={<button type="button" className="btn btn-secondary" onClick={onClose}><i className="fas fa-times" aria-hidden="true" /> Cerrar</button>}
    >
      {loading ? (
        <div className="loading-spinner" role="status"><i className="fas fa-spinner fa-spin fa-2x" aria-hidden="true" /><p>Cargando detalles…</p></div>
      ) : error || !order ? (
        <div className="alert alert-danger"><i className="fas fa-exclamation-circle" aria-hidden="true" /> No se pudieron cargar los detalles.</div>
      ) : (
        <div className="sale-details">
          <div className="detail-section">
            <h4><i className="fas fa-info-circle" aria-hidden="true" /> Información general</h4>
            <div className="detail-grid">
              <div className="detail-item"><label>Nº Pedido:</label><span><strong>{order.po_number || `#${order.num_order}`}</strong></span></div>
              <div className="detail-item"><label>Proveedor:</label><span>{order.supplier?.name ?? '—'}</span></div>
              <div className="detail-item"><label>Fecha:</label><span>{order.date}</span></div>
              <div className="detail-item"><label>Entrega estimada:</label><span>{order.estimated_delivery_date || '—'}</span></div>
              {order.received_at ? <div className="detail-item"><label>Fecha recepción:</label><span>{order.received_at}</span></div> : null}
              <div className="detail-item"><label>Estado:</label><span className={`status-badge ${order.state}`}>{STATE_LABELS[order.state] || order.state}</span></div>
              {order.closed_with_shorts ? (
                <div className="detail-item" style={{ color: '#b45309' }}>
                  <label>Observación:</label>
                  <span><i className="fas fa-exclamation-triangle" aria-hidden="true" /> Cerrado con faltantes del proveedor</span>
                </div>
              ) : null}
            </div>
            <div style={{ marginTop: 12, display: 'flex', gap: 10, flexWrap: 'wrap' }}>
              {canConfirm ? <button type="button" className="btn btn-success" onClick={() => onConfirm(order)}><i className="fas fa-check" aria-hidden="true" /> Confirmar</button> : null}
              {canReceive ? <a className="btn btn-primary" href={detailUrl}><i className="fas fa-truck" aria-hidden="true" /> Registrar recepción</a> : null}
              {canCancel ? <button type="button" className="btn btn-danger" onClick={() => onCancel(order)}><i className="fas fa-times" aria-hidden="true" /> Cancelar</button> : null}
              <a className="btn btn-secondary" href={detailUrl} title="Ver página de detalle"><i className="fas fa-external-link-alt" aria-hidden="true" /> Ir a detalle</a>
            </div>
          </div>

          {order.products.length > 0 ? (
            <div className="detail-section">
              <h4><i className="fas fa-box" aria-hidden="true" /> Productos pedidos</h4>
              <table className="sale-products-table admin-table">
                <thead>
                  <tr>
                    <th>Producto</th>
                    <th className="text-center">Pedido</th>
                    {showRecvCol ? <th className="text-center">Recibido</th> : null}
                    <th className="text-right">Precio unit.</th>
                    <th className="text-right">Total</th>
                  </tr>
                </thead>
                <tbody>
                  {order.products.map((item) => (
                    <tr key={item.id}>
                      <td>{item.name || 'N/A'}</td>
                      <td className="text-center">{item.quantity}</td>
                      {showRecvCol ? <td className="text-center">{item.received_quantity !== null ? item.received_quantity : 0}</td> : null}
                      <td className="text-right">{crc(item.unit_price)}</td>
                      <td className="text-right"><strong>{crc(item.total)}</strong></td>
                    </tr>
                  ))}
                </tbody>
              </table>
              <div className="sale-totals">
                {showRecvCol && shortsTotal > 0.009 && receivedTotal !== null ? (
                  <>
                    <div className="total-item"><span><strong>Total pedido:</strong></span><span><strong>{crc(initialTotal)}</strong></span></div>
                    <div className="total-item"><span><strong>Total recibido:</strong></span><span><strong>{crc(receivedTotal)}</strong></span></div>
                    <div className="total-item total-final"><span><strong>Faltante:</strong></span><span><strong>{crc(shortsTotal)}</strong></span></div>
                  </>
                ) : (
                  <div className="total-item total-final"><span><strong>Total:</strong></span><span><strong>{crc(initialTotal)}</strong></span></div>
                )}
              </div>
            </div>
          ) : null}

          {order.timeline.length > 0 ? (
            <div className="detail-section">
              <h4><i className="fas fa-history" aria-hidden="true" /> Historial de estados</h4>
              <ol className="order-timeline" style={{ marginTop: 8 }}>
                {order.timeline.map((t, i) => {
                  const isClosePartial = t.state === 'delivered' && (t.reason || '').startsWith('[Cierre con faltantes]');
                  const cfg = isClosePartial
                    ? { label: 'Cerrado con faltantes', icon: 'fa-exclamation-triangle', color: '#f59e0b' }
                    : TL_CONFIG[t.state] || { label: t.state, icon: 'fa-circle', color: '#94a3b8' };
                  const displayReason = isClosePartial ? (t.reason || '').replace(/^\[Cierre con faltantes\]\s*/, '') : t.reason;
                  return (
                    <li className="tl-item" key={i}>
                      <div className="tl-dot" style={{ background: cfg.color }}><i className={`fas ${cfg.icon}`} aria-hidden="true" /></div>
                      <div className="tl-body">
                        <span className="tl-state" style={{ color: cfg.color }}>{cfg.label}</span>
                        <span className="tl-meta">
                          <i className="fas fa-user-circle" aria-hidden="true" /> {t.user_name} &nbsp;·&nbsp; <i className="fas fa-calendar-alt" aria-hidden="true" /> {t.changed_at}
                        </span>
                        {displayReason ? <span className="tl-reason"><i className="fas fa-comment-alt" aria-hidden="true" /> {displayReason}</span> : null}
                      </div>
                    </li>
                  );
                })}
              </ol>
            </div>
          ) : null}
        </div>
      )}
    </Modal>
  );
}
