import { Head } from '@inertiajs/react';
import { useCallback, useEffect, useRef, useState } from 'react';

import { AdminLayout } from '@/shared/components/layout/AdminLayout';
import { PageHeader } from '@/shared/components/ui/PageHeader';

import '../../../../css/admin/reports/reports-hub.css';

const nf = new Intl.NumberFormat('es-CR');

type Option = { value: string; label: string };
type Movement = {
  id: number;
  type: string;
  type_label: string;
  type_badge: string;
  origin: string;
  origin_label: string;
  quantity: number;
  stock_before: number;
  stock_after: number;
  reason: string | null;
  admin: { name: string } | null;
  created_at_human: string;
};

type PageProps = {
  product: { product_id: number; name: string; sku: string; category_name: string; supplier_name: string | null; stock_current: number };
  availableTypes: Option[];
  availableOrigins: Option[];
  jsonUrl: string;
};

export default function MovementsShow({ product, availableTypes, availableOrigins, jsonUrl }: PageProps) {
  const [type, setType] = useState('');
  const [origin, setOrigin] = useState('');
  const [dateFrom, setDateFrom] = useState('');
  const [dateTo, setDateTo] = useState('');
  const [page, setPage] = useState(1);

  const [movements, setMovements] = useState<Movement[]>([]);
  const [summary, setSummary] = useState<{ total_entradas: number; total_salidas: number }>({ total_entradas: 0, total_salidas: 0 });
  const [meta, setMeta] = useState<{ current_page: number; last_page: number; total: number }>({ current_page: 1, last_page: 1, total: 0 });
  const [loading, setLoading] = useState(false);
  const seqRef = useRef(0);

  const load = useCallback(async (params: Record<string, string>) => {
    const seq = ++seqRef.current;
    setLoading(true);
    try {
      const qs = new URLSearchParams(params).toString();
      const res = await fetch(`${jsonUrl}?${qs}`, { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
      const json = await res.json().catch(() => null);
      if (seq !== seqRef.current) return;
      if (json?.success) {
        setMovements(json.data ?? []);
        setSummary(json.summary ?? { total_entradas: 0, total_salidas: 0 });
        setMeta(json.meta ?? { current_page: 1, last_page: 1, total: 0 });
      }
    } catch {
      /* silent */
    } finally {
      if (seq === seqRef.current) setLoading(false);
    }
  }, [jsonUrl]);

  useEffect(() => {
    const params: Record<string, string> = { page: String(page) };
    if (type) params.type = type;
    if (origin) params.origin = origin;
    if (dateFrom) params.date_from = dateFrom;
    if (dateTo) params.date_to = dateTo;
    void load(params);
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [type, origin, dateFrom, dateTo, page]);

  function clearFilters() {
    setType('');
    setOrigin('');
    setDateFrom('');
    setDateTo('');
    setPage(1);
  }

  return (
    <AdminLayout title={`Movimientos — ${product.name}`}>
      <Head title={`Movimientos — ${product.name} - Reportes`} />

      <div className="inventory-movements-report">
        <nav className="reports-breadcrumb" aria-label="Migas de pan">
          <a href="/reports">Reportes</a>
          <span className="sep">/</span>
          <a href="/inventory/movements">Movimientos de inventario</a>
          <span className="sep">/</span>
          <span>{product.name}</span>
        </nav>

        <PageHeader
          title={product.name}
          kicker={`SKU ${product.sku}`}
          actions={<a href="/inventory/movements" className="btn btn-secondary btn-sm"><i className="fas fa-arrow-left" aria-hidden="true" /> Volver</a>}
        >
          <p>Historial de entradas, salidas y devoluciones · Stock actual: <strong>{nf.format(product.stock_current)}</strong> unid.</p>
        </PageHeader>

        <div className="sales-perf-layout">
          <aside className="inv-mov-filters" aria-label="Filtros de movimientos">
            <p className="inv-mov-filters-title">Tipo</p>
            <div className="inv-mov-btn-group">
              <button type="button" className={`inv-mov-btn${type === '' ? ' is-active' : ''}`} onClick={() => { setType(''); setPage(1); }}>Todos</button>
              {availableTypes.map((t) => (
                <button type="button" key={t.value} className={`inv-mov-btn${type === t.value ? ' is-active' : ''}`} onClick={() => { setType(t.value); setPage(1); }}>{t.label}</button>
              ))}
            </div>

            <p className="inv-mov-filters-title inv-mov-filters-title--spaced">Origen</p>
            <div className="inv-mov-btn-group">
              <button type="button" className={`inv-mov-btn${origin === '' ? ' is-active' : ''}`} onClick={() => { setOrigin(''); setPage(1); }}>Todos</button>
              {availableOrigins.map((o) => (
                <button type="button" key={o.value} className={`inv-mov-btn${origin === o.value ? ' is-active' : ''}`} onClick={() => { setOrigin(o.value); setPage(1); }}>{o.label}</button>
              ))}
            </div>

            <p className="inv-mov-filters-title inv-mov-filters-title--spaced">Rango de fechas</p>
            <div className="filter-group">
              <label htmlFor="mov-from">Desde</label>
              <input id="mov-from" type="date" value={dateFrom} onChange={(e) => { setDateFrom(e.target.value); setPage(1); }} />
            </div>
            <div className="filter-group">
              <label htmlFor="mov-to">Hasta</label>
              <input id="mov-to" type="date" value={dateTo} onChange={(e) => { setDateTo(e.target.value); setPage(1); }} />
            </div>
            <button type="button" className="inv-mov-clear-btn btn btn-secondary btn-sm" onClick={clearFilters} style={{ marginTop: '0.75rem' }}>Limpiar filtros</button>
          </aside>

          <div className="sales-perf-main">
            <div className="inv-metrics-grid">
              <article className="inv-metric-card">
                <h2 className="inv-metric-title">Movimientos</h2>
                <p className="inv-metric-value">{nf.format(meta.total)}</p>
              </article>
              <article className="inv-metric-card inv-metric-card--entrada">
                <h2 className="inv-metric-title">Unidades entradas</h2>
                <p className="inv-metric-value">{nf.format(summary.total_entradas)}</p>
              </article>
              <article className="inv-metric-card inv-metric-card--salida">
                <h2 className="inv-metric-title">Unidades salidas</h2>
                <p className="inv-metric-value">{nf.format(summary.total_salidas)}</p>
              </article>
            </div>

            <div className="table-section">
              <div className="sales-table-container">
                <table className="admin-table">
                  <thead>
                    <tr>
                      <th>Fecha y hora</th>
                      <th>Tipo</th>
                      <th>Origen</th>
                      <th className="text-end">Cantidad</th>
                      <th className="text-end">Stock antes</th>
                      <th className="text-end">Stock después</th>
                      <th>Administrador</th>
                      <th>Motivo</th>
                    </tr>
                  </thead>
                  <tbody>
                    {loading && movements.length === 0 ? (
                      <tr><td colSpan={8} className="empty-cell">Cargando…</td></tr>
                    ) : movements.length === 0 ? (
                      <tr><td colSpan={8} className="empty-cell">No hay movimientos para los filtros seleccionados.</td></tr>
                    ) : (
                      movements.map((m) => (
                        <tr key={m.id}>
                          <td>{m.created_at_human}</td>
                          <td><span className={`order-status-pill ${m.type_badge}`}>{m.type_label}</span></td>
                          <td>{m.origin_label}</td>
                          <td className="text-end">{nf.format(m.quantity)}</td>
                          <td className="text-end">{nf.format(m.stock_before)}</td>
                          <td className="text-end">{nf.format(m.stock_after)}</td>
                          <td>{m.admin?.name ?? <span className="text-muted">Automático</span>}</td>
                          <td>{m.reason ?? <span className="text-muted">—</span>}</td>
                        </tr>
                      ))
                    )}
                  </tbody>
                </table>

                {meta.last_page > 1 ? (
                  <div className="pagination-wrapper">
                    <button type="button" className="btn btn-secondary btn-sm" disabled={meta.current_page <= 1} onClick={() => setPage((p) => Math.max(1, p - 1))}>
                      <i className="fas fa-chevron-left" aria-hidden="true" /> Anterior
                    </button>
                    <span className="pagination-info">Página {meta.current_page} de {meta.last_page} · {meta.total} movimientos</span>
                    <button type="button" className="btn btn-secondary btn-sm" disabled={meta.current_page >= meta.last_page} onClick={() => setPage((p) => Math.min(meta.last_page, p + 1))}>
                      Siguiente <i className="fas fa-chevron-right" aria-hidden="true" />
                    </button>
                  </div>
                ) : null}
              </div>
            </div>
          </div>
        </div>
      </div>
    </AdminLayout>
  );
}
