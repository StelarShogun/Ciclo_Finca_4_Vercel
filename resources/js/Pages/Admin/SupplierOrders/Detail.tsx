import { Head, router, usePage } from '@inertiajs/react';
import { useState } from 'react';

import { AdminLayout } from '@/shared/components/layout/AdminLayout';
import { PageHeader } from '@/shared/components/ui/PageHeader';
import { Breadcrumbs } from '@/shared/components/ui/Breadcrumbs';
import { useConfirmDialog } from '@/shared/components/ui/ConfirmDialogProvider';
import { useToast } from '@/shared/hooks/useToast';
import type { InertiaSharedProps } from '@/shared/types/models';

import { ReasonModal } from '../Orders/components/ReasonModal';
import { ReceiveModal } from './components/ReceiveModal';

import '../../../../css/admin/orders/orders.css';
import '../../../../css/admin/orders/supplier-order-detail.css';

const money = new Intl.NumberFormat('es-CR', { minimumFractionDigits: 2 });
function crc(n: number): string {
  return `₡${money.format(Number(n) || 0)}`;
}

const TL_CONFIG: Record<string, { label: string; icon: string; color: string }> = {
  draft: { label: 'Borrador', icon: 'fa-pencil-alt', color: 'var(--text-secondary)' },
  pending: { label: 'Pendiente', icon: 'fa-clock', color: 'var(--color-warning)' },
  confirmed: { label: 'Confirmado', icon: 'fa-check', color: 'var(--color-info)' },
  partial_received: { label: 'Recepción parcial', icon: 'fa-clipboard-check', color: 'var(--color-warning)' },
  delivered: { label: 'Entregado', icon: 'fa-truck', color: 'var(--color-success)' },
  cancelled: { label: 'Cancelado', icon: 'fa-times', color: 'var(--color-danger)' },
};

type OrderItem = { id: number; name: string; quantity: number; received_quantity: number | null; unit_price: number; total: number };
type TimelineEntry = { state: string; state_label: string; changed_at: string; user_name: string; reason: string | null };

type OrderDetail = {
  num_order: number;
  po_number: string;
  supplier_name: string;
  date_label: string;
  estimated_delivery_date: string | null;
  delivered_at: string | null;
  received_at: string | null;
  state: string;
  state_label: string;
  closed_with_shorts: boolean;
  total: number;
  has_received_data: boolean;
  has_shorts: boolean;
  initial_total: number;
  received_total: number | null;
  shorts_total: number;
  items: OrderItem[];
  timeline: TimelineEntry[];
  confirm_audit: { changed_at: string; user_name: string } | null;
};

export default function Detail({ order }: { order: OrderDetail }) {
  const { csrfToken } = usePage<InertiaSharedProps>().props;
  const { confirm } = useConfirmDialog();
  const { showToast } = useToast();

  const [receiveOpen, setReceiveOpen] = useState(false);
  const [closePartialOpen, setClosePartialOpen] = useState(false);
  const [cancelOpen, setCancelOpen] = useState(false);
  const [cancelSubmitting, setCancelSubmitting] = useState(false);
  const [closeSubmitting, setCloseSubmitting] = useState(false);

  const showReceivedCol = order.has_received_data;

  async function patchState(state: string, successMsg: string, reason?: string): Promise<boolean> {
    try {
      const res = await fetch(`/supplier-orders/${order.num_order}/state`, {
        method: 'PATCH',
        headers: { 'X-CSRF-TOKEN': csrfToken, Accept: 'application/json', 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        body: JSON.stringify(reason !== undefined ? { state, reason } : { state }),
      });
      const data = await res.json().catch(() => ({}));
      if (res.ok && data.success) {
        showToast({ variant: 'success', title: 'Listo', message: data.message || successMsg });
        router.reload();
        return true;
      }
      showToast({ variant: 'error', title: 'No se pudo completar', message: data.message || 'No se pudo actualizar.' });
      return false;
    } catch {
      showToast({ variant: 'error', title: 'Error de conexión', message: 'Revisa tu red e inténtalo de nuevo.' });
      return false;
    }
  }

  async function confirmOrder() {
    const ok = await confirm({ title: '¿Confirmar este pedido?', text: 'El pedido pasará a estado confirmado con el proveedor.', icon: 'question', confirmText: 'Sí, confirmar', cancelText: 'Volver' });
    if (ok) await patchState('confirmed', 'Pedido confirmado correctamente.');
  }

  async function doCancel(reason: string) {
    setCancelSubmitting(true);
    const ok = await patchState('cancelled', 'El pedido fue cancelado correctamente.', reason);
    setCancelSubmitting(false);
    if (ok) setCancelOpen(false);
  }

  async function doClosePartial(reason: string) {
    setCloseSubmitting(true);
    try {
      const res = await fetch(`/supplier-orders/${order.num_order}/close-partial`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrfToken, 'X-Requested-With': 'XMLHttpRequest' },
        body: JSON.stringify({ reason }),
      });
      const data = await res.json().catch(() => ({}));
      if (res.ok && data.success) {
        showToast({ variant: 'warning', title: 'Pedido cerrado con faltantes', message: data.message || '' });
        setClosePartialOpen(false);
        router.reload();
      } else {
        showToast({ variant: 'error', title: 'Error', message: data.message || 'No se pudo cerrar el pedido.' });
      }
    } catch {
      showToast({ variant: 'error', title: 'Error de conexión', message: 'Verificá tu red e intentá de nuevo.' });
    } finally {
      setCloseSubmitting(false);
    }
  }

  const isPendingLike = order.state === 'draft' || order.state === 'pending';

  return (
    <AdminLayout title={`Pedido ${order.po_number}`}>
      <Head title="Detalle Pedido a Proveedor - Ciclo Finca 4 Admin" />

      <div className="sales-container cf4-orders-module cf4-supplier-orders-module">
        <PageHeader
          title={`Pedido ${order.po_number}`}
          kicker="Proveedores"
          breadcrumb={<Breadcrumbs items={[{ label: 'Inicio', href: '/dashboard' }, { label: 'Pedidos a proveedores', href: '/supplier-orders' }, { label: order.po_number }]} />}
          actions={
            <div className="sales-actions">
              <a href="/supplier-orders" className="btn btn-secondary"><i className="fas fa-arrow-left" aria-hidden="true" /> Volver</a>
              {isPendingLike ? (
                <>
                  <button type="button" className="btn btn-primary" onClick={confirmOrder}><i className="fas fa-check" aria-hidden="true" /> Confirmar</button>
                  <button type="button" className="btn btn-secondary" onClick={() => setCancelOpen(true)}><i className="fas fa-times" aria-hidden="true" /> Cancelar</button>
                </>
              ) : null}
              {order.state === 'confirmed' ? (
                <>
                  <button type="button" className="btn btn-primary" onClick={() => setReceiveOpen(true)}><i className="fas fa-clipboard-check" aria-hidden="true" /> Registrar recepción</button>
                  <button type="button" className="btn btn-secondary" onClick={() => setCancelOpen(true)}><i className="fas fa-times" aria-hidden="true" /> Cancelar</button>
                </>
              ) : null}
              {order.state === 'partial_received' ? (
                <>
                  <button type="button" className="btn btn-primary" onClick={() => setReceiveOpen(true)}><i className="fas fa-clipboard-check" aria-hidden="true" /> Completar recepción</button>
                  <button type="button" className="btn btn-warning" onClick={() => setClosePartialOpen(true)}><i className="fas fa-exclamation-triangle" aria-hidden="true" /> Cerrar con faltantes</button>
                  <button type="button" className="btn btn-secondary" onClick={() => setCancelOpen(true)}><i className="fas fa-times" aria-hidden="true" /> Cancelar</button>
                </>
              ) : null}
            </div>
          }
        >
          <p>Detalle del pedido de compra al proveedor.</p>
        </PageHeader>

        <div className="detail-grid">
          <section className="detail-card">
            <h2><i className="fas fa-info-circle" aria-hidden="true" /> Información</h2>
            <div className="kv">
              <div className="kv-row"><span>Nº pedido (PO)</span><strong>{order.po_number}</strong></div>
              <div className="kv-row"><span>Proveedor</span><strong>{order.supplier_name}</strong></div>
              <div className="kv-row"><span>Fecha en que se realizó el pedido</span><strong>{order.date_label}</strong></div>
              <div className="kv-row">
                <span>Entrega estimada</span>
                <strong>{order.estimated_delivery_date ?? '—'}{order.estimated_delivery_date ? <small style={{ display: 'block', fontWeight: 400, color: 'var(--text-secondary)' }}>Calculada automáticamente</small> : null}</strong>
              </div>
              <div className="kv-row">
                <span>Entregado</span>
                <strong>
                  {order.state === 'cancelled' ? <span style={{ color: 'var(--text-secondary)' }}>Nunca</span> : order.delivered_at ? order.delivered_at : order.received_at ? order.received_at : <span style={{ color: 'var(--color-warning)' }}>En proceso</span>}
                </strong>
              </div>
              <div className="kv-row"><span>Estado</span><strong><span className={`order-status-pill ${order.state}`}>{order.state_label}</span></strong></div>
              {order.closed_with_shorts ? (
                <div className="kv-row"><span>Observación</span><strong style={{ color: 'var(--color-warning)' }}><i className="fas fa-exclamation-triangle" aria-hidden="true" /> Cerrado con faltantes del proveedor</strong></div>
              ) : null}
            </div>
          </section>

          {order.confirm_audit ? (
            <section className="detail-card cf4-supplier-order-audit">
              <h2><i className="fas fa-user-check" aria-hidden="true" /> Confirmación con proveedor</h2>
              <div className="kv">
                <div className="kv-row"><span>Fecha y hora</span><strong>{order.confirm_audit.changed_at}</strong></div>
                <div className="kv-row"><span>Registró</span><strong>{order.confirm_audit.user_name}</strong></div>
              </div>
            </section>
          ) : null}

          <section className="detail-card detail-card-wide">
            <h2><i className="fas fa-box" aria-hidden="true" /> Productos</h2>
            <div className="items-table-wrap">
              <table className="items-table admin-table">
                <thead>
                  <tr>
                    <th>Producto</th>
                    <th className="num">Pedido</th>
                    {showReceivedCol ? <th className="num">Recibido</th> : null}
                    <th className="num">Precio unit.</th>
                    <th className="num">Total</th>
                  </tr>
                </thead>
                <tbody>
                  {order.items.length === 0 ? (
                    <tr><td colSpan={showReceivedCol ? 5 : 4} className="empty-cell">Sin productos</td></tr>
                  ) : (
                    order.items.map((item) => (
                      <tr key={item.id}>
                        <td>{item.name}</td>
                        <td className="num">{item.quantity}</td>
                        {showReceivedCol ? (
                          <td className="num">
                            {item.received_quantity ?? 0}
                            {(item.received_quantity ?? 0) < item.quantity ? <span title="Recepción incompleta" style={{ color: 'var(--color-warning)', marginLeft: 4 }}>⚠</span> : null}
                          </td>
                        ) : null}
                        <td className="num">{crc(item.unit_price)}</td>
                        <td className="num"><strong>{crc(item.total)}</strong></td>
                      </tr>
                    ))
                  )}
                </tbody>
              </table>
            </div>
            <div className="detail-summary">
              {order.has_received_data && order.has_shorts && order.received_total !== null ? (
                <div style={{ display: 'flex', flexDirection: 'column', alignItems: 'flex-end', gap: '0.25rem' }}>
                  <div style={{ display: 'flex', gap: '0.75rem', alignItems: 'baseline' }}><span>Total pedido</span><strong>{crc(order.initial_total)}</strong></div>
                  <div style={{ display: 'flex', gap: '0.75rem', alignItems: 'baseline' }}><span>Total recibido</span><strong>{crc(order.received_total)}</strong></div>
                  <div style={{ display: 'flex', gap: '0.75rem', alignItems: 'baseline' }}><span>Faltante</span><strong style={{ color: 'var(--color-warning)' }}>{crc(order.shorts_total)}</strong></div>
                </div>
              ) : (
                <>
                  <span>Total</span>
                  <strong>{crc(order.total)}</strong>
                </>
              )}
            </div>
          </section>

          <section className="detail-card detail-card-wide">
            <h2><i className="fas fa-history" aria-hidden="true" /> Historial de estados</h2>
            {order.timeline.length === 0 ? (
              <p className="empty-cell" style={{ textAlign: 'left', padding: '0.5rem 0' }}>Sin registros de historial.</p>
            ) : (
              <ol className="order-timeline">
                {order.timeline.map((entry, i) => {
                  const isClosePartial = entry.state === 'delivered' && (entry.reason || '').startsWith('[Cierre con faltantes]');
                  const cfg = isClosePartial ? { label: 'Cerrado con faltantes', icon: 'fa-exclamation-triangle', color: 'var(--color-warning)' } : TL_CONFIG[entry.state] || { label: entry.state_label, icon: 'fa-circle', color: '#94a3b8' };
                  const displayReason = isClosePartial ? (entry.reason || '').replace(/^\[Cierre con faltantes\]\s*/, '') : entry.reason;
                  return (
                    <li className="tl-item" key={i}>
                      <div className="tl-dot" style={{ background: cfg.color }}><i className={`fas ${cfg.icon}`} aria-hidden="true" /></div>
                      <div className="tl-body">
                        <span className="tl-state" style={{ color: cfg.color }}>{cfg.label}</span>
                        <span className="tl-meta"><i className="fas fa-user-circle" aria-hidden="true" /> {entry.user_name} &nbsp;·&nbsp; <i className="fas fa-calendar-alt" aria-hidden="true" /> {entry.changed_at}</span>
                        {displayReason ? <span className="tl-reason"><i className="fas fa-comment-alt" aria-hidden="true" /> {displayReason}</span> : null}
                      </div>
                    </li>
                  );
                })}
              </ol>
            )}
          </section>
        </div>
      </div>

      <ReceiveModal
        isOpen={receiveOpen}
        orderId={order.num_order}
        isPartial={order.state === 'partial_received'}
        items={order.items}
        csrfToken={csrfToken}
        onClose={() => setReceiveOpen(false)}
        onReceived={(msg) => {
          showToast({ variant: 'success', title: 'Recepción registrada', message: msg });
          setReceiveOpen(false);
          router.reload();
        }}
      />

      <ReasonModal
        isOpen={cancelOpen}
        title={`¿Cancelar pedido ${order.po_number}?`}
        intro="El pedido se marcará como cancelado. Escribe al menos 4 caracteres para continuar."
        confirmLabel="Sí, cancelar"
        minLength={4}
        submitting={cancelSubmitting}
        onClose={() => setCancelOpen(false)}
        onConfirm={doCancel}
      />

      <ReasonModal
        isOpen={closePartialOpen}
        title="Cerrar pedido con faltantes"
        intro="Vas a cerrar este pedido aunque no se hayan recibido todos los productos. El estado pasará a Entregado y se registrará que hubo faltantes del proveedor. El stock ya ingresado no se revertirá."
        confirmLabel="Confirmar cierre con faltantes"
        minLength={4}
        danger={false}
        submitting={closeSubmitting}
        onClose={() => setClosePartialOpen(false)}
        onConfirm={doClosePartial}
      />
    </AdminLayout>
  );
}
