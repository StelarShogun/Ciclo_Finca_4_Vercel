import { Head, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import type { FormEvent } from 'react';

import { AdminLayout } from '@/shared/components/layout/AdminLayout';
import { PageHeader } from '@/shared/components/ui/PageHeader';
import { Breadcrumbs } from '@/shared/components/ui/Breadcrumbs';
import { InertiaListPagination } from '@/shared/components/ui/InertiaListPagination';
import { FiltersSection } from '@/shared/components/ui/FiltersSection';
import { useConfirmDialog } from '@/shared/components/ui/ConfirmDialogProvider';
import { useToast } from '@/shared/hooks/useToast';
import type { InertiaSharedProps } from '@/shared/types/models';
import type { InertiaListPagination as Pagination } from '@/types/pagination';

import { ReasonModal } from '../Orders/components/ReasonModal';
import type { SupplierOrderRow, SupplierOption, SupplierOrdersFilters, SupplierOrderDetail } from './types';
import { ViewOrderModal } from './components/ViewOrderModal';
import { ViewSupplierModal } from './components/ViewSupplierModal';
import { CreateOrderModal } from './components/CreateOrderModal';

import '../../../../css/admin/orders/orders.css';
import '../../../../css/admin/orders/supplier-order-create.css';
import '../../../../css/admin/components/product-combobox.css';

type PageProps = {
  orders: SupplierOrderRow[];
  pagination: Pagination;
  openSupplierOrdersCount: number;
  suppliers: SupplierOption[];
  filters: SupplierOrdersFilters;
};

const colones = new Intl.NumberFormat('es-CR', { maximumFractionDigits: 0 });
function formatColones(amount: number): string {
  return `₡${colones.format(Math.round(amount || 0))}`;
}

const QUICK_PILLS: Array<{ value: string; label: string }> = [
  { value: '', label: 'Todos' },
  { value: 'open', label: 'Abiertas' },
  { value: 'draft', label: 'Borrador' },
  { value: 'pending', label: 'Pendiente' },
  { value: 'confirmed', label: 'Confirmado' },
  { value: 'partial_received', label: 'Recepción parcial' },
  { value: 'delivered', label: 'Entregado' },
  { value: 'cancelled', label: 'Cancelado' },
];

export default function Index({ orders, pagination, openSupplierOrdersCount, suppliers, filters }: PageProps) {
  const { csrfToken } = usePage<InertiaSharedProps>().props;
  const { confirm } = useConfirmDialog();
  const { showToast } = useToast();

  const [form, setForm] = useState<SupplierOrdersFilters>(filters);
  const [viewOrderId, setViewOrderId] = useState<number | null>(null);
  const [viewSupplierId, setViewSupplierId] = useState<number | null>(null);
  const [createOpen, setCreateOpen] = useState(false);
  const [cancelTarget, setCancelTarget] = useState<{ id: number; ref: string } | null>(null);
  const [cancelSubmitting, setCancelSubmitting] = useState(false);
  const [orderModalReloadKey, setOrderModalReloadKey] = useState(0);

  function visit(params: Record<string, string>) {
    router.get('/supplier-orders', params, { preserveScroll: true, preserveState: true, replace: true });
  }

  function submitFilters(event: FormEvent) {
    event.preventDefault();
    visit({ ...form });
  }

  function clearFilters() {
    const empty: SupplierOrdersFilters = { state: '', date_range: '', date_from: '', date_to: '', search: '' };
    setForm(empty);
    visit({});
  }

  function reloadTable() {
    router.reload({ only: ['orders', 'pagination', 'openSupplierOrdersCount'] });
  }

  async function patchState(id: number, state: string, successMsg: string, reason?: string): Promise<boolean> {
    try {
      const res = await fetch(`/supplier-orders/${id}/state`, {
        method: 'PATCH',
        headers: { 'X-CSRF-TOKEN': csrfToken, Accept: 'application/json', 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        body: JSON.stringify(reason !== undefined ? { state, reason } : { state }),
      });
      const data = await res.json().catch(() => ({}));
      if (res.ok && data.success) {
        showToast({ variant: 'success', title: 'Listo', message: data.message || successMsg });
        reloadTable();
        setOrderModalReloadKey((k) => k + 1);
        return true;
      }
      showToast({ variant: 'error', title: 'No se pudo completar', message: data.message || 'No se pudo actualizar.' });
      return false;
    } catch {
      showToast({ variant: 'error', title: 'Error de conexión', message: 'Revisa tu red e inténtalo de nuevo.' });
      return false;
    }
  }

  async function confirmOrder(order: { num_order: number; po_short?: string } | SupplierOrderDetail) {
    const id = 'num_order' in order ? order.num_order : 0;
    const ok = await confirm({
      title: '¿Confirmar este pedido?',
      text: 'El pedido pasará a estado confirmado con el proveedor. Luego podrás registrar la recepción de mercancía al recibirla.',
      icon: 'question',
      confirmText: 'Sí, confirmar',
      cancelText: 'Volver',
    });
    if (ok) await patchState(id, 'confirmed', 'Pedido confirmado correctamente.');
  }

  async function doCancel(reason: string) {
    if (!cancelTarget) return;
    setCancelSubmitting(true);
    const ok = await patchState(cancelTarget.id, 'cancelled', 'El pedido fue cancelado correctamente.', reason);
    setCancelSubmitting(false);
    if (ok) setCancelTarget(null);
  }

  return (
    <AdminLayout title="Pedidos a proveedores">
      <Head title="Pedidos a Proveedores - Ciclo Finca 4 Admin" />

      <div className="sales-container cf4-orders-module cf4-supplier-orders-module">
        <PageHeader
          title="Pedidos a Proveedores"
          kicker="Proveedores"
          icon="fa-clipboard-list"
          breadcrumb={<Breadcrumbs items={[{ label: 'Inicio', href: '/dashboard' }, { label: 'Pedidos a proveedores' }]} />}
          actions={
            <div className="sales-header-actions">
              <a href="/supplier-orders/xml-deviation" className="btn btn-secondary btn-sm">
                <i className="fas fa-file-import" aria-hidden="true" /> Analizar XML de proveedor
              </a>
              <button type="button" className="btn btn-primary btn-sm" onClick={() => setCreateOpen(true)}>
                <i className="fas fa-plus" aria-hidden="true" /> Nuevo pedido
              </button>
            </div>
          }
        >
          <p>
            Gestiona los pedidos de compra realizados a proveedores: confirma la recepción, cancela pedidos y consulta el detalle de los productos solicitados.
            Los pedidos de clientes en línea están en <a href="/orders">Órdenes</a>.
          </p>
        </PageHeader>

        <section className="kpi-grid cf4-orders-kpi-grid" aria-label="Resumen de pedidos a proveedores">
          <button type="button" className="kpi-card kpi-card--button cf4-orders-kpi-card-link" onClick={() => { setForm({ ...form, state: 'open' }); visit({ ...form, state: 'open' }); }}>
            <div className="kpi-icon info"><i className="fas fa-truck-loading" aria-hidden="true" /></div>
            <div className="kpi-content">
              <h3>Pedidos abiertos</h3>
              <p className="kpi-value">{openSupplierOrdersCount}</p>
              <div className="kpi-trend trend-up"><i className="fas fa-arrow-right" aria-hidden="true" /> Ver pedidos no finales</div>
            </div>
          </button>
        </section>

        <FiltersSection
          onSubmit={submitFilters}
          onClear={clearFilters}
          after={
            <div className="filters-quick">
              <span className="filters-quick-label">Filtros rápidos</span>
              <div className="filters-quick-pills">
                {QUICK_PILLS.map((pill) => (
                  <button
                    type="button"
                    key={pill.value || 'all'}
                    className={`btn btn-sm ${form.state === pill.value ? 'btn-primary' : 'btn-secondary'}`}
                    onClick={() => { setForm({ ...form, state: pill.value }); visit({ ...form, state: pill.value }); }}
                  >
                    {pill.label}
                  </button>
                ))}
              </div>
            </div>
          }
        >
          <div className="filter-group">
            <label htmlFor="supplier-orders-state">Estado</label>
            <select id="supplier-orders-state" value={form.state} onChange={(e) => setForm({ ...form, state: e.target.value })}>
              <option value="">Todos</option>
              <option value="open">Abiertas (no finales)</option>
              <option value="draft">Borrador</option>
              <option value="pending">Pendiente</option>
              <option value="confirmed">Confirmado</option>
              <option value="partial_received">Recepción parcial</option>
              <option value="delivered">Entregado</option>
              <option value="cancelled">Cancelado</option>
            </select>
          </div>
          <div className="filter-group">
            <label htmlFor="date_from">Fecha inicial</label>
            <input id="date_from" type="date" value={form.date_from} onChange={(e) => setForm({ ...form, date_from: e.target.value })} />
          </div>
          <div className="filter-group">
            <label htmlFor="date_to">Fecha final</label>
            <input id="date_to" type="date" value={form.date_to} onChange={(e) => setForm({ ...form, date_to: e.target.value })} />
          </div>
          <div className="filter-group">
            <label htmlFor="supplier-orders-search">Buscar</label>
            <input id="supplier-orders-search" type="text" placeholder="PO, proveedor o producto…" value={form.search} onChange={(e) => setForm({ ...form, search: e.target.value })} autoComplete="off" />
          </div>
        </FiltersSection>

        <div className="orders-table-card table-section">
          <div className="sales-table-container">
            <table className="sales-table cf4-purchases-table admin-table">
              <thead>
                <tr>
                  <th>Nº Pedido (PO)</th>
                  <th>Proveedor</th>
                  <th>Fecha de pedido</th>
                  <th>Fecha de entrega estimada</th>
                  <th>Fecha de entrega</th>
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
                    <tr key={order.num_order}>
                      <td><strong className="po-number" title={order.po_full}>{order.po_short}</strong></td>
                      <td>
                        {order.supplier_id ? (
                          <button className="supplier-name-btn" type="button" onClick={() => setViewSupplierId(order.supplier_id)} data-tooltip="Ver datos del proveedor" aria-label="Ver datos del proveedor">
                            {order.supplier_name} <i className="fas fa-external-link-alt" style={{ fontSize: '.75rem', opacity: 0.6 }} aria-hidden="true" />
                          </button>
                        ) : (
                          <span className="text-muted">—</span>
                        )}
                      </td>
                      <td>{order.date_label}</td>
                      <td>{order.edd_class ? <span className={order.edd_class}>{order.edd_label}</span> : order.edd_label}</td>
                      <td>{order.delivered_label ? <span title="Entrega/recepción registrada">{order.delivered_label}</span> : <span className="text-muted">—</span>}</td>
                      <td><span className={`order-status-pill ${order.state}`}>{order.state_label}</span></td>
                      <td>
                        {order.has_received_data && order.has_shorts ? (
                          <>
                            <div><strong>{formatColones(order.received_total)}</strong></div>
                            <div className="text-muted" style={{ fontSize: '.85rem' }}>Pedido: {formatColones(order.initial_total)}</div>
                            {order.shorts_total > 0 ? <div className="text-muted" style={{ fontSize: '.85rem' }}>Faltante: {formatColones(order.shorts_total)}</div> : null}
                          </>
                        ) : (
                          <strong>{formatColones(order.initial_total)}</strong>
                        )}
                      </td>
                      <td className="admin-table__col--actions">
                        <div className="actions-container">
                          <button className="action-btn secondary" type="button" onClick={() => setViewOrderId(order.num_order)} data-tooltip="Ver detalles" aria-label="Ver detalles">
                            <i className="fas fa-eye" aria-hidden="true" />
                          </button>
                          {order.state === 'draft' || order.state === 'pending' ? (
                            <>
                              <button className="action-btn success" type="button" onClick={() => confirmOrder(order)} data-tooltip="Confirmar pedido" aria-label="Confirmar pedido"><i className="fas fa-check" aria-hidden="true" /></button>
                              <button className="action-btn danger" type="button" onClick={() => setCancelTarget({ id: order.num_order, ref: order.po_short })} data-tooltip="Cancelar pedido" aria-label="Cancelar pedido"><i className="fas fa-times" aria-hidden="true" /></button>
                            </>
                          ) : null}
                          {order.state === 'confirmed' ? (
                            <>
                              <a className="action-btn view" href={`/supplier-orders/${order.num_order}/detail`} data-tooltip="Registrar recepción de mercancía" aria-label="Registrar recepción de mercancía"><i className="fas fa-truck" aria-hidden="true" /></a>
                              <button className="action-btn danger" type="button" onClick={() => setCancelTarget({ id: order.num_order, ref: order.po_short })} data-tooltip="Cancelar pedido" aria-label="Cancelar pedido"><i className="fas fa-times" aria-hidden="true" /></button>
                            </>
                          ) : null}
                          {order.state === 'partial_received' ? (
                            <>
                              <a className="action-btn view" href={`/supplier-orders/${order.num_order}/detail`} data-tooltip="Completar recepción de mercancía" aria-label="Completar recepción de mercancía"><i className="fas fa-clipboard-check" aria-hidden="true" /></a>
                              <button className="action-btn danger" type="button" onClick={() => setCancelTarget({ id: order.num_order, ref: order.po_short })} data-tooltip="Cancelar pedido" aria-label="Cancelar pedido"><i className="fas fa-times" aria-hidden="true" /></button>
                            </>
                          ) : null}
                        </div>
                      </td>
                    </tr>
                  ))
                )}
              </tbody>
            </table>

            {orders.length > 0 ? <InertiaListPagination pagination={pagination} label="pedidos" /> : null}
          </div>
        </div>
      </div>

      <ViewOrderModal
        orderId={viewOrderId}
        reloadKey={orderModalReloadKey}
        onClose={() => setViewOrderId(null)}
        onConfirm={(o) => confirmOrder(o)}
        onCancel={(o) => setCancelTarget({ id: o.num_order, ref: o.po_number || `#${o.num_order}` })}
      />
      <ViewSupplierModal supplierId={viewSupplierId} onClose={() => setViewSupplierId(null)} />
      <CreateOrderModal isOpen={createOpen} suppliers={suppliers} onClose={() => setCreateOpen(false)} />
      <ReasonModal
        isOpen={cancelTarget != null}
        title={cancelTarget ? `¿Cancelar pedido ${cancelTarget.ref}?` : 'Cancelar pedido'}
        intro="El pedido se marcará como cancelado. Escribe al menos 4 caracteres para continuar."
        confirmLabel="Sí, cancelar"
        submitting={cancelSubmitting}
        onClose={() => setCancelTarget(null)}
        onConfirm={doCancel}
      />
    </AdminLayout>
  );
}
