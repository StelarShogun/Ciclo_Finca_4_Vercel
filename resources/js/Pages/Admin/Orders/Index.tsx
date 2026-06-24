import { Head, router, usePage } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import type { FormEvent } from 'react';

import { AdminLayout } from '@/shared/components/layout/AdminLayout';
import { PageHeader } from '@/shared/components/ui/PageHeader';
import { InertiaListPagination } from '@/shared/components/ui/InertiaListPagination';
import { FiltersSection } from '@/shared/components/ui/FiltersSection';
import { useConfirmDialog } from '@/shared/components/ui/ConfirmDialogProvider';
import { useToast } from '@/shared/hooks/useToast';
import type { InertiaSharedProps } from '@/shared/types/models';
import type { InertiaListPagination as Pagination } from '@/types/pagination';

import { ViewSaleModal } from '../Sales/components/ViewSaleModal';
import type { OrderRow, OrdersFilters } from './types';
import { ReasonModal } from './components/ReasonModal';
import { ExpirationModal } from './components/ExpirationModal';

import '../../../../css/admin/orders/orders.css';

type PageProps = {
  orders: OrderRow[];
  pagination: Pagination;
  pendingWebOrdersCount: number;
  latestPurchaseSaleId: number;
  readyToPickupExpirationHours: number;
  usesEnvDefaultForExpiry: boolean;
  filters: OrdersFilters;
};

const colones = new Intl.NumberFormat('es-CR', { maximumFractionDigits: 0 });
function formatColones(amount: number): string {
  return `₡${colones.format(Math.round(amount || 0))}`;
}

const HEARTBEAT_URL = '/sales/history/heartbeat';

export default function Index({
  orders,
  pagination,
  pendingWebOrdersCount,
  latestPurchaseSaleId,
  readyToPickupExpirationHours,
  usesEnvDefaultForExpiry,
  filters,
}: PageProps) {
  const { csrfToken } = usePage<InertiaSharedProps>().props;
  const { confirm } = useConfirmDialog();
  const { showToast } = useToast();

  const [form, setForm] = useState<OrdersFilters>(filters);
  const [pending, setPending] = useState(pendingWebOrdersCount);
  const [viewId, setViewId] = useState<number | null>(null);
  const [cancelTarget, setCancelTarget] = useState<OrderRow | null>(null);
  const [cancelSubmitting, setCancelSubmitting] = useState(false);
  const [settingsOpen, setSettingsOpen] = useState(false);

  const latestRef = useRef(latestPurchaseSaleId);
  const readyRef = useRef(false);

  useEffect(() => setPending(pendingWebOrdersCount), [pendingWebOrdersCount]);

  useEffect(() => {
    let cancelled = false;
    async function check() {
      if (document.visibilityState === 'hidden') return;
      try {
        const res = await fetch(`${HEARTBEAT_URL}?since=${latestRef.current}`, { headers: { Accept: 'application/json' } });
        const data = await res.json();
        if (cancelled) return;
        if (typeof data.latestSaleId !== 'undefined') latestRef.current = Number(data.latestSaleId) || latestRef.current;
        if (typeof data.pendingCount !== 'undefined') setPending(Number(data.pendingCount));
        if (data.hasNew && readyRef.current) {
          router.reload({ only: ['orders', 'pagination', 'pendingWebOrdersCount', 'latestPurchaseSaleId'] });
        }
        readyRef.current = true;
      } catch {
        /* silent */
      }
    }
    void check();
    const id = window.setInterval(check, 15000);
    const onVis = () => document.visibilityState === 'visible' && void check();
    document.addEventListener('visibilitychange', onVis);
    return () => {
      cancelled = true;
      window.clearInterval(id);
      document.removeEventListener('visibilitychange', onVis);
    };
  }, []);

  function reloadTable() {
    router.reload({ only: ['orders', 'pagination', 'pendingWebOrdersCount', 'latestPurchaseSaleId'] });
  }

  function visit(params: Record<string, string>) {
    router.get('/orders', params, { preserveScroll: true, preserveState: true, replace: true });
  }

  function submitFilters(event: FormEvent) {
    event.preventDefault();
    visit({ ...form });
  }

  function clearFilters() {
    const empty: OrdersFilters = { status: '', date_range: '', date_from: '', date_to: '', search: '' };
    setForm(empty);
    visit({});
  }

  async function postAction(url: string, method: 'POST' | 'PATCH', successTitle: string, payload?: unknown) {
    try {
      const res = await fetch(url, {
        method,
        headers: { 'X-CSRF-TOKEN': csrfToken, Accept: 'application/json', 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        body: payload ? JSON.stringify(payload) : null,
      });
      const data = await res.json().catch(() => ({}));
      if (res.ok && (data.success === true || typeof data.success === 'undefined')) {
        showToast({ variant: 'success', title: successTitle, message: data.message || '' });
        reloadTable();
        return true;
      }
      showToast({ variant: 'error', title: 'Error', message: data.message || 'No se pudo completar la acción.' });
      return false;
    } catch {
      showToast({ variant: 'error', title: 'Error de red', message: 'No se pudo conectar con el servidor.' });
      return false;
    }
  }

  async function markReady(order: OrderRow) {
    const ok = await confirm({
      title: '¿Marcar como listo para recoger?',
      text: `El pedido ${order.reference} pasará a estado "Listo para recoger". El stock ya fue reservado al crear el pedido.`,
      icon: 'question',
      confirmText: 'Sí, marcar',
      cancelText: 'Cancelar',
    });
    if (ok) await postAction(`/orders/${order.sale_id}/ready-to-pickup`, 'PATCH', 'Actualizado');
  }

  async function completeOrder(order: OrderRow) {
    const ok = await confirm({
      title: `¿Confirmar encargo con factura ${order.reference}?`,
      text: 'El pedido pasará a confirmado. No se volverá a descontar stock porque ya fue reservado en el checkout.',
      icon: 'question',
      confirmText: 'Sí, confirmar',
      cancelText: 'Cancelar',
    });
    if (ok) await postAction(`/sales/${order.sale_id}/complete`, 'POST', 'Encargo confirmado');
  }

  async function confirmCancel(reason: string) {
    if (!cancelTarget) return;
    setCancelSubmitting(true);
    const ok = await postAction(`/sales/${cancelTarget.sale_id}/cancel`, 'POST', 'Encargo rechazado', { reason });
    setCancelSubmitting(false);
    if (ok) setCancelTarget(null);
  }

  async function openInvoice(order: OrderRow) {
    const ok = await confirm({ title: '¿Deseas ver la factura?', text: `Factura: ${order.reference}`, icon: 'question', confirmText: 'Ver factura', cancelText: 'Cancelar' });
    if (ok) window.open(`/sales/${order.sale_id}/invoice?from=orders`, '_blank', 'noopener,noreferrer');
  }

  return (
    <AdminLayout title="Encargos">
      <Head title="Encargos - Ciclo Finca 4 Admin" />

      <div className="sales-container orders-container">
        <PageHeader
          title="Encargos en línea"
          kicker="Encargos"
          icon="fa-shopping-cart"
          actions={
            <button type="button" className="btn btn-secondary btn-sm" onClick={() => setSettingsOpen(true)}>
              <i className="fas fa-clock" aria-hidden="true" /> Plazo de cancelación
            </button>
          }
        >
          <p>
            Gestiona los encargos del carrito web: márcalos como listos para recoger, confirma ventas o rechaza pedidos.
            Las ventas confirmadas se registran en <a href="/sales">Ventas</a>.
          </p>
        </PageHeader>

        <section className="kpi-grid cf4-orders-kpi-grid" aria-label="Resumen de encargos">
          <button type="button" className="kpi-card cf4-orders-kpi-card-link" onClick={() => { setForm({ ...form, status: 'pending' }); visit({ ...form, status: 'pending' }); }}>
            <div className="kpi-header">
              <h3 className="kpi-title">Pendientes web</h3>
              <div className="kpi-icon info"><i className="fas fa-clock" aria-hidden="true" /></div>
            </div>
            <p className="kpi-value">{pending}</p>
            <div className="kpi-trend trend-up">
              <i className="fas fa-arrow-right" aria-hidden="true" /> Ver pendientes
            </div>
          </button>
        </section>

        <FiltersSection onSubmit={submitFilters} onClear={clearFilters}>
          <div className="filter-group">
            <label htmlFor="orders-status">Estado</label>
            <select id="orders-status" value={form.status} onChange={(e) => setForm({ ...form, status: e.target.value })}>
              <option value="">Todos</option>
              <option value="pending">Pendiente</option>
              <option value="ready_to_pickup">Listo para recoger</option>
              <option value="completed">Confirmado</option>
              <option value="cancelled">Rechazado</option>
              <option value="refunded">Reembolsado</option>
            </select>
          </div>
          <div className="filter-group">
            <label htmlFor="orders-date-range">Rango de fecha</label>
            <select
              id="orders-date-range"
              value={form.date_range}
              onChange={(e) => {
                const v = e.target.value;
                setForm((prev) => ({ ...prev, date_range: v, ...(v !== 'custom' ? { date_from: '', date_to: '' } : {}) }));
              }}
            >
              <option value="">Todas las fechas</option>
              <option value="today">Hoy</option>
              <option value="week">Esta semana</option>
              <option value="month">Este mes</option>
              <option value="custom">Personalizado</option>
            </select>
          </div>
          {form.date_range === 'custom' ? (
            <>
              <div className="filter-group filter-group--date-from">
                <label htmlFor="orders-date-from">Fecha inicial</label>
                <input id="orders-date-from" type="date" value={form.date_from} onChange={(e) => setForm({ ...form, date_from: e.target.value })} />
              </div>
              <div className="filter-group filter-group--date-to">
                <label htmlFor="orders-date-to">Fecha final</label>
                <input id="orders-date-to" type="date" value={form.date_to} onChange={(e) => setForm({ ...form, date_to: e.target.value })} />
              </div>
            </>
          ) : null}
          <div className="filter-group">
            <label htmlFor="orders-search">Buscar</label>
            <input id="orders-search" type="text" placeholder="Nº encargo, factura o cliente" value={form.search} onChange={(e) => setForm({ ...form, search: e.target.value })} autoComplete="off" />
          </div>
        </FiltersSection>

        <div className="orders-table-card table-section">
          <div className="sales-table-container">
            <table className="sales-table cf4-purchases-table admin-table">
              <thead>
                <tr>
                  <th>Encargos / Factura</th>
                  <th>Cliente</th>
                  <th>Fecha de pedido</th>
                  <th>Fecha listo para recoger</th>
                  <th>Fecha de confirmación</th>
                  <th>Estado</th>
                  <th>Total</th>
                  <th className="admin-table__col--actions">Acciones</th>
                </tr>
              </thead>
              <tbody>
                {orders.length === 0 ? (
                  <tr>
                    <td colSpan={8}>
                      <div className="orders-empty">
                        <div className="orders-empty-icon"><i className="fas fa-inbox" aria-hidden="true" /></div>
                        <p style={{ margin: 0, fontSize: '1rem' }}>No hay pedidos que coincidan con los filtros.</p>
                      </div>
                    </td>
                  </tr>
                ) : (
                  orders.map((order) => (
                    <tr key={order.sale_id}>
                      <td>
                        <strong>#{order.sale_id}</strong>
                        {order.invoice_number ? <div className="text-muted" style={{ fontSize: '0.85rem' }}>{order.invoice_number}</div> : null}
                      </td>
                      <td>
                        {order.customer}
                        {order.customer_email ? <span className="text-muted"> ({order.customer_email})</span> : null}
                      </td>
                      <td>{order.order_placed_label}</td>
                      <td>{order.ready_label}</td>
                      <td>{order.confirmed_label}</td>
                      <td><span className={`order-status-pill ${order.status}`}>{order.status_label}</span></td>
                      <td><strong>{formatColones(order.total)}</strong></td>
                      <td className="admin-table__col--actions">
                        <div className="actions-container">
                          <button className="action-btn secondary" type="button" onClick={() => setViewId(order.sale_id)} data-tooltip="Ver detalles" aria-label="Ver detalles">
                            <i className="fas fa-eye" aria-hidden="true" />
                          </button>
                          {order.status === 'pending' ? (
                            <>
                              <button className="action-btn warning" type="button" onClick={() => markReady(order)} data-tooltip="Marcar como listo para recoger" aria-label="Marcar como listo para recoger">
                                <i className="fas fa-box" aria-hidden="true" />
                              </button>
                              <button className="action-btn danger" type="button" onClick={() => setCancelTarget(order)} data-tooltip="Rechazar encargo" aria-label="Rechazar encargo">
                                <i className="fas fa-times" aria-hidden="true" />
                              </button>
                            </>
                          ) : null}
                          {order.status === 'ready_to_pickup' ? (
                            <>
                              <button className="action-btn success" type="button" onClick={() => completeOrder(order)} data-tooltip="Confirmar encargo" aria-label="Confirmar encargo">
                                <i className="fas fa-check" aria-hidden="true" />
                              </button>
                              <button className="action-btn danger" type="button" onClick={() => setCancelTarget(order)} data-tooltip="Rechazar encargo" aria-label="Rechazar encargo">
                                <i className="fas fa-times" aria-hidden="true" />
                              </button>
                            </>
                          ) : null}
                          {order.status === 'completed' ? (
                            <button className="action-link-invoice" type="button" onClick={() => openInvoice(order)} data-tooltip="Ver factura en formato estructurado" aria-label="Ver factura en formato estructurado">
                              <i className="fas fa-file-invoice" aria-hidden="true" /> Ver factura
                            </button>
                          ) : null}
                        </div>
                      </td>
                    </tr>
                  ))
                )}
              </tbody>
            </table>

            {orders.length > 0 ? <InertiaListPagination pagination={pagination} label="encargos" /> : null}
          </div>
        </div>
      </div>

      <ViewSaleModal saleId={viewId} onClose={() => setViewId(null)} title="Detalles del pedido" />

      <ReasonModal
        isOpen={cancelTarget != null}
        title={cancelTarget ? `¿Rechazar encargo ${cancelTarget.reference}?` : 'Rechazar encargo'}
        intro="Ingrese el motivo de cancelación. El stock reservado se devolverá al inventario."
        warning="Al confirmar, el encargo pasará a estado Rechazado y el stock reservado se reintegrará al inventario."
        confirmLabel="Sí, rechazar"
        submitting={cancelSubmitting}
        onClose={() => setCancelTarget(null)}
        onConfirm={confirmCancel}
      />

      <ExpirationModal
        isOpen={settingsOpen}
        currentHours={readyToPickupExpirationHours}
        usesEnvDefault={usesEnvDefaultForExpiry}
        csrfToken={csrfToken}
        onClose={() => setSettingsOpen(false)}
        onSaved={reloadTable}
      />
    </AdminLayout>
  );
}
