import { Head, router, usePage } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import type { FormEvent } from 'react';

import { AdminLayout } from '@/shared/components/layout/AdminLayout';
import { PageHeader } from '@/shared/components/ui/PageHeader';
import { InertiaListPagination } from '@/shared/components/ui/InertiaListPagination';
import { useConfirmDialog } from '@/shared/components/ui/ConfirmDialogProvider';
import type { InertiaSharedProps } from '@/shared/types/models';
import type { InertiaListPagination as Pagination } from '@/types/pagination';

import type { SaleRow, SalesKpis, SalesFilters } from './types';
import { NewSaleModal } from './components/NewSaleModal';
import { ViewSaleModal } from './components/ViewSaleModal';
import { ReturnSaleModal } from './components/ReturnSaleModal';

import '../../../../css/admin/sales/sales.css';
import '../../../../css/admin/components/product-combobox.css';

type PageProps = {
  sales: SaleRow[];
  pagination: Pagination;
  kpis: SalesKpis;
  salesStatusUi: string;
  latestHistorySaleId: number;
  filters: SalesFilters;
};

const colones = new Intl.NumberFormat('es-CR', { maximumFractionDigits: 0 });
function formatColones(amount: number): string {
  return `₡${colones.format(Math.round(amount || 0))}`;
}

const HEARTBEAT_URL = '/sales/history/heartbeat';

export default function Index({ sales, pagination, kpis, latestHistorySaleId, filters }: PageProps) {
  const { csrfToken } = usePage<InertiaSharedProps>().props;
  const { confirm } = useConfirmDialog();

  const [form, setForm] = useState<SalesFilters>(filters);
  const [liveKpis, setLiveKpis] = useState<SalesKpis>(kpis);
  const [dateError, setDateError] = useState('');

  const [newOpen, setNewOpen] = useState(false);
  const [viewId, setViewId] = useState<number | null>(null);
  const [returnTarget, setReturnTarget] = useState<{ saleId: number; invoiceLabel: string } | null>(null);

  const latestRef = useRef<number>(latestHistorySaleId);
  const readyRef = useRef(false);

  useEffect(() => {
    setLiveKpis(kpis);
  }, [kpis]);

  // Heartbeat: refresh KPIs and reload list when new sales appear.
  useEffect(() => {
    let cancelled = false;

    async function check() {
      if (document.visibilityState === 'hidden') return;
      try {
        const res = await fetch(`${HEARTBEAT_URL}?since=${latestRef.current}`, { headers: { Accept: 'application/json' } });
        const data = await res.json();
        if (cancelled) return;
        if (typeof data.latestSaleId !== 'undefined') {
          latestRef.current = Number(data.latestSaleId) || latestRef.current;
        }
        setLiveKpis((prev) => ({
          ...prev,
          dailySales: data.dailySales ?? prev.dailySales,
          dailySalesTrend: data.dailySalesTrend ?? prev.dailySalesTrend,
          dailyTransactions: data.dailyTransactions ?? prev.dailyTransactions,
          dailyTransactionsTrend: data.dailyTransactionsTrend ?? prev.dailyTransactionsTrend,
        }));
        if (data.hasNew && readyRef.current) {
          router.reload({ only: ['sales', 'pagination', 'kpis', 'latestHistorySaleId'] });
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

  function visit(params: Record<string, string>) {
    router.get('/sales', params, { preserveScroll: true, preserveState: true, replace: true });
  }

  function validateDateRange(): boolean {
    if (form.date_range !== 'custom') return true;
    const { date_from: from, date_to: to } = form;
    const today = new Date().toISOString().split('T')[0];
    const minDate = '2020-01-01';
    if ((from && from < minDate) || (to && to < minDate)) {
      setDateError('Las fechas no pueden ser anteriores al 1 de enero de 2020.');
      return false;
    }
    if ((from && from > today) || (to && to > today)) {
      setDateError('Las fechas no pueden ser posteriores al día de hoy.');
      return false;
    }
    if (from && to && from > to) {
      setDateError('La fecha inicial no puede ser mayor que la fecha final. Por favor corrija el rango.');
      return false;
    }
    setDateError('');
    return true;
  }

  function submitFilters(event: FormEvent) {
    event.preventDefault();
    if (!validateDateRange()) return;
    visit({ ...form });
  }

  function clearFilters() {
    const empty: SalesFilters = { status: 'completed', date_range: 'today', payment_method: '', search: '', date_from: '', date_to: '' };
    setForm(empty);
    setDateError('');
    visit({});
  }

  async function openInvoice(sale: SaleRow) {
    const ok = await confirm({ title: '¿Deseas ver la factura?', text: `Factura: ${sale.invoice_number}`, icon: 'question', confirmText: 'Ver factura', cancelText: 'Cancelar' });
    if (ok) {
      window.open(`/sales/${sale.sale_id}/invoice?from=sales`, '_blank', 'noopener,noreferrer');
    }
  }

  async function printSale(saleId: number, invoiceLabel: string) {
    const ok = await confirm({ title: '¿Deseas imprimir esta factura?', text: `Factura: ${invoiceLabel}`, icon: 'question', confirmText: 'Imprimir', cancelText: 'Cancelar' });
    if (ok) {
      window.open(`/sales/${saleId}/print`, '_blank', 'noopener,noreferrer');
    }
  }

  function onSaleCreated(saleId: number | null) {
    if (saleId) latestRef.current = saleId;
    router.reload({ only: ['sales', 'pagination', 'kpis', 'latestHistorySaleId'] });
  }

  return (
    <AdminLayout title="Ventas">
      <Head title="Ventas - Ciclo Finca 4 Admin" />

      <div className="sales-container">
        <PageHeader
          title="Gestión de ventas"
          kicker="Ventas"
          actions={
            <button className="btn btn-primary" onClick={() => setNewOpen(true)}>
              <i className="fas fa-plus" aria-hidden="true" /> Nueva venta
            </button>
          }
        >
          <p>
            Administra las ventas confirmadas, registra devoluciones y crea nuevas ventas desde el módulo administrativo.
            Los encargos pendientes del carrito web se gestionan en <a href="/orders">Encargos</a>.
          </p>
        </PageHeader>

        <div className="kpi-grid">
          <div className="kpi-card">
            <div className="kpi-header">
              <h3 className="kpi-title">Ventas del Día</h3>
              <div className="kpi-icon success"><i className="fas fa-chart-line" aria-hidden="true" /></div>
            </div>
            <p className="kpi-value">{formatColones(liveKpis.dailySales)}</p>
            <div className={`kpi-trend ${liveKpis.dailySalesTrend >= 0 ? 'trend-up' : 'trend-down'}`}>
              <i className={`fas fa-arrow-${liveKpis.dailySalesTrend >= 0 ? 'up' : 'down'}`} aria-hidden="true" /> {Math.abs(liveKpis.dailySalesTrend)}%
            </div>
          </div>
          <div className="kpi-card">
            <div className="kpi-header">
              <h3 className="kpi-title">Transacciones</h3>
              <div className="kpi-icon info"><i className="fas fa-receipt" aria-hidden="true" /></div>
            </div>
            <p className="kpi-value">{liveKpis.dailyTransactions}</p>
            <div className={`kpi-trend ${liveKpis.dailyTransactionsTrend >= 0 ? 'trend-up' : 'trend-down'}`}>
              <i className={`fas fa-arrow-${liveKpis.dailyTransactionsTrend >= 0 ? 'up' : 'down'}`} aria-hidden="true" /> {Math.abs(liveKpis.dailyTransactionsTrend)}%
            </div>
          </div>
          <div className="kpi-card">
            <div className="kpi-header">
              <h3 className="kpi-title">Devoluciones</h3>
              <div className="kpi-icon danger"><i className="fas fa-rotate-left" aria-hidden="true" /></div>
            </div>
            <p className="kpi-value">{liveKpis.refunds}</p>
            <div className={`kpi-trend ${liveKpis.refundsTrend >= 0 ? 'trend-up' : 'trend-down'}`}>
              <i className={`fas fa-arrow-${liveKpis.refundsTrend >= 0 ? 'up' : 'down'}`} aria-hidden="true" /> {Math.abs(liveKpis.refundsTrend)}
            </div>
          </div>
        </div>

        <form className="cf4-filters filter-form" onSubmit={submitFilters}>
          <div className="filter-group">
            <label htmlFor="status">Estado</label>
            <select id="status" value={form.status} onChange={(e) => setForm({ ...form, status: e.target.value })}>
              <option value="completed">Confirmada (completada)</option>
              <option value="returned">Devuelta</option>
              <option value="cancelled">Cancelada / rechazada</option>
              <option value="all">Todas las cerradas</option>
            </select>
          </div>
          <div className="filter-group">
            <label htmlFor="date-range">Rango de Fecha</label>
            <select
              id="date-range"
              value={form.date_range}
              onChange={(e) => {
                const v = e.target.value;
                setDateError('');
                setForm((prev) => ({ ...prev, date_range: v, ...(v !== 'custom' ? { date_from: '', date_to: '' } : {}) }));
              }}
            >
              <option value="today">Hoy</option>
              <option value="week">Esta semana</option>
              <option value="month">Este mes</option>
              <option value="custom">Personalizado</option>
            </select>
          </div>
          <div className="filter-group">
            <label htmlFor="payment-method">Método de Pago</label>
            <select id="payment-method" value={form.payment_method} onChange={(e) => setForm({ ...form, payment_method: e.target.value })}>
              <option value="">Todos los métodos</option>
              <option value="cash">Efectivo</option>
              <option value="sinpe">SINPE Móvil</option>
              <option value="transfer">Transferencia Bancaria</option>
            </select>
          </div>
          <div className="filter-group">
            <label htmlFor="search-sale">Buscar</label>
            <input id="search-sale" type="text" placeholder="Buscar por cliente o factura..." value={form.search} onChange={(e) => setForm({ ...form, search: e.target.value })} />
          </div>

          {form.date_range === 'custom' ? (
            <div className="date-range-custom-row">
              <div className="filter-group">
                <label htmlFor="date-from">Fecha inicial</label>
                <input id="date-from" type="date" value={form.date_from} onChange={(e) => setForm({ ...form, date_from: e.target.value })} />
              </div>
              <div className="filter-group">
                <label htmlFor="date-to">Fecha final</label>
                <input id="date-to" type="date" value={form.date_to} onChange={(e) => setForm({ ...form, date_to: e.target.value })} />
              </div>
            </div>
          ) : null}

          {dateError ? (
            <div className="alert alert-danger">
              <i className="fas fa-exclamation-circle" aria-hidden="true" /> <span>{dateError}</span>
            </div>
          ) : null}

          <div className="filter-actions">
            <button type="submit" className="btn btn-primary">
              <i className="fas fa-search" aria-hidden="true" /> Filtrar
            </button>
            <button type="button" className="btn btn-secondary" onClick={clearFilters}>Limpiar</button>
          </div>
        </form>

        <div className="table-section">
          <div className={`sales-table-container${sales.length === 0 ? ' sales-table-container--empty' : ''}`}>
            <table className="sales-table admin-table">
              <thead>
                <tr>
                  <th>Número de factura</th>
                  <th>Cliente</th>
                  <th>Fecha de venta</th>
                  <th>Estado</th>
                  <th>Método de Pago</th>
                  <th>Total</th>
                  <th className="admin-table__col--actions">Acciones</th>
                </tr>
              </thead>
              {sales.length > 0 ? (
                <tbody>
                  {sales.map((sale) => (
                    <tr key={sale.sale_id}>
                      <td><strong>{sale.invoice_number}</strong></td>
                      <td>
                        {sale.customer}
                        {sale.customer_email ? <span className="text-muted"> ({sale.customer_email})</span> : null}
                      </td>
                      <td>{sale.sale_date_label}</td>
                      <td><span className={`status-badge ${sale.status}`}>{sale.status_label}</span></td>
                      <td>{sale.payment_label}</td>
                      <td><strong>{formatColones(sale.total)}</strong></td>
                      <td className="admin-table__col--actions">
                        <div className="actions-container">
                          <button className="action-btn view" onClick={() => setViewId(sale.sale_id)} title="Ver detalles">
                            <i className="fas fa-eye" aria-hidden="true" />
                          </button>
                          {sale.status === 'completed' ? (
                            <>
                              <button className="action-link-invoice" onClick={() => openInvoice(sale)} title="Ver factura en formato estructurado">
                                <i className="fas fa-file-invoice" aria-hidden="true" /> Ver factura
                              </button>
                              <button className="action-btn info" onClick={() => setReturnTarget({ saleId: sale.sale_id, invoiceLabel: sale.invoice_number })} title="Registrar devolución">
                                <i className="fas fa-rotate-left" aria-hidden="true" />
                              </button>
                            </>
                          ) : null}
                          {sale.status !== 'cancelled' ? (
                            <button className="action-btn secondary" onClick={() => printSale(sale.sale_id, sale.invoice_number)} title="Imprimir">
                              <i className="fas fa-print" aria-hidden="true" />
                            </button>
                          ) : null}
                        </div>
                      </td>
                    </tr>
                  ))}
                </tbody>
              ) : null}
            </table>
            {sales.length === 0 ? (
              <div className="table-empty-state table-empty-state--fill" role="status">
                <i className="fas fa-shopping-cart table-empty-icon" aria-hidden="true" />
                <p>No hay ventas para los filtros seleccionados.</p>
              </div>
            ) : null}

            <InertiaListPagination pagination={pagination} label="ventas" />
          </div>
        </div>
      </div>

      <NewSaleModal isOpen={newOpen} onClose={() => setNewOpen(false)} csrfToken={csrfToken} onCreated={onSaleCreated} />
      <ViewSaleModal saleId={viewId} onClose={() => setViewId(null)} onPrint={printSale} />
      <ReturnSaleModal
        target={returnTarget}
        onClose={() => setReturnTarget(null)}
        csrfToken={csrfToken}
        onReturned={() => router.reload({ only: ['sales', 'pagination', 'kpis', 'latestHistorySaleId'] })}
      />
    </AdminLayout>
  );
}
