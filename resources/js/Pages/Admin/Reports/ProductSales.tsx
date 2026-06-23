import { Head } from '@inertiajs/react';
import { useCallback, useEffect, useRef, useState } from 'react';

import { AdminLayout } from '@/shared/components/layout/AdminLayout';
import { PageHeader } from '@/shared/components/ui/PageHeader';

import '../../../../css/admin/reports/product-sales.css';

const money = new Intl.NumberFormat('es-CR', { maximumFractionDigits: 0 });
function crc(n: number): string {
  return `₡${money.format(Math.round(Number(n) || 0))}`;
}

type Row = { product_id: number; name: string; sku: string; units_sold: number; revenue: number };
type TableResponse = {
  success: boolean;
  top10: Row[];
  rows: Row[];
  top10_metric: string;
  pagination: { page: number; per_page: number; total: number; last_page: number };
};

type PageProps = { period: string; sort: string; dir: string; q: string; top10: string };

export default function ProductSales({ period: period0, sort: sort0, dir: dir0, q: q0, top10: top100 }: PageProps) {
  const [period, setPeriod] = useState(period0 || '30d');
  const [sort, setSort] = useState(sort0 || 'revenue');
  const [dir, setDir] = useState(dir0 || 'desc');
  const [q, setQ] = useState(q0 || '');
  const [top10Metric, setTop10Metric] = useState(top100 || 'revenue');
  const [page, setPage] = useState(1);

  const [data, setData] = useState<TableResponse | null>(null);
  const [loading, setLoading] = useState(false);
  const seqRef = useRef(0);

  const load = useCallback(async (params: Record<string, string>) => {
    const seq = ++seqRef.current;
    setLoading(true);
    try {
      const qs = new URLSearchParams(params).toString();
      const res = await fetch(`/reports/productos-vendidos/table?${qs}`, { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
      const json = await res.json().catch(() => null);
      if (seq !== seqRef.current) return;
      setData(json?.success ? json : null);
    } catch {
      if (seq === seqRef.current) setData(null);
    } finally {
      if (seq === seqRef.current) setLoading(false);
    }
  }, []);

  useEffect(() => {
    const params: Record<string, string> = { period, sort, dir, top10: top10Metric, page: String(page) };
    if (q.trim()) params.q = q.trim();
    void load(params);
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [period, sort, dir, top10Metric, page]);

  // Debounced search
  useEffect(() => {
    const t = window.setTimeout(() => {
      setPage(1);
      const params: Record<string, string> = { period, sort, dir, top10: top10Metric, page: '1' };
      if (q.trim()) params.q = q.trim();
      void load(params);
    }, 400);
    return () => window.clearTimeout(t);
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [q]);

  function sortBy(column: string) {
    if (sort === column) {
      setDir(dir === 'asc' ? 'desc' : 'asc');
    } else {
      setSort(column);
      setDir('desc');
    }
    setPage(1);
  }

  const top10 = data?.top10 ?? [];
  const rows = data?.rows ?? [];
  const pag = data?.pagination;

  return (
    <AdminLayout title="Productos más vendidos">
      <Head title="Productos más vendidos - Reportes" />

      <div className="product-sales-report">
        <nav className="reports-breadcrumb">
          <a href="/reports">Reportes</a>
          <span className="sep">/</span>
          <span>Productos más vendidos</span>
        </nav>

        <PageHeader title="Productos más vendidos" kicker="Reportes">
          <p>Analiza los productos con mayor rendimiento por ingresos o unidades vendidas en el periodo seleccionado.</p>
        </PageHeader>

        <div className="product-sales-toolbar">
          <div className="period-toggle" role="group" aria-label="Periodo">
            {['7d', '30d', '90d'].map((p) => (
              <button type="button" key={p} className={`period-btn${period === p ? ' active' : ''}`} onClick={() => { setPeriod(p); setPage(1); }}>
                {p === '7d' ? '7 días' : p === '30d' ? '30 días' : '90 días'}
              </button>
            ))}
          </div>
          <div className="search-wrap">
            <input type="search" className="product-sales-search" placeholder="Filtrar por nombre o SKU…" value={q} onChange={(e) => setQ(e.target.value)} autoComplete="off" />
          </div>
        </div>

        <section className="top10-section">
          <div className="top10-header">
            <h2 className="section-title">Top 10</h2>
            <div className="top10-toggle" role="group" aria-label="Top 10 por">
              <button type="button" className={`top10-btn${top10Metric === 'revenue' ? ' is-active' : ''}`} onClick={() => setTop10Metric('revenue')}>Ingresos</button>
              <button type="button" className={`top10-btn${top10Metric === 'units' ? ' is-active' : ''}`} onClick={() => setTop10Metric('units')}>Unidades</button>
            </div>
          </div>
          <p className="section-hint">Top 10 por {top10Metric === 'units' ? 'unidades' : 'ingresos'} en el periodo.</p>
          <div className="table-wrap">
            <table className="report-table admin-table">
              <thead>
                <tr><th>#</th><th>Producto</th><th>SKU</th><th className="num">Unidades</th><th className="num">Ingresos</th></tr>
              </thead>
              <tbody>
                {loading && top10.length === 0 ? (
                  <tr><td colSpan={5} className="loading-cell">Cargando…</td></tr>
                ) : top10.length === 0 ? (
                  <tr><td colSpan={5} className="loading-cell">Sin datos.</td></tr>
                ) : (
                  top10.map((r, i) => (
                    <tr key={r.product_id}>
                      <td>{i + 1}</td>
                      <td>{r.name}</td>
                      <td><code>{r.sku}</code></td>
                      <td className="num">{r.units_sold}</td>
                      <td className="num">{crc(r.revenue)}</td>
                    </tr>
                  ))
                )}
              </tbody>
            </table>
          </div>
        </section>

        <section className="full-table-section">
          <h2 className="section-title">Todos los productos con ventas</h2>
          <div className="table-wrap">
            <table className="report-table admin-table">
              <thead>
                <tr>
                  <th>Producto</th>
                  <th>SKU</th>
                  <th className="num">
                    <button type="button" className={`nav-sort${sort === 'units' ? ' is-active' : ''}`} onClick={() => sortBy('units')}>
                      Unidades {sort === 'units' ? <i className={`fas fa-sort-${dir === 'asc' ? 'up' : 'down'}`} aria-hidden="true" /> : null}
                    </button>
                  </th>
                  <th className="num">
                    <button type="button" className={`nav-sort${sort === 'revenue' ? ' is-active' : ''}`} onClick={() => sortBy('revenue')}>
                      Ingresos {sort === 'revenue' ? <i className={`fas fa-sort-${dir === 'asc' ? 'up' : 'down'}`} aria-hidden="true" /> : null}
                    </button>
                  </th>
                </tr>
              </thead>
              <tbody>
                {loading && rows.length === 0 ? (
                  <tr><td colSpan={4} className="loading-cell">Cargando…</td></tr>
                ) : rows.length === 0 ? (
                  <tr><td colSpan={4} className="empty-msg">No hay ventas completadas en este periodo para los criterios seleccionados.</td></tr>
                ) : (
                  rows.map((r) => (
                    <tr key={r.product_id}>
                      <td>{r.name}</td>
                      <td><code>{r.sku}</code></td>
                      <td className="num">{r.units_sold}</td>
                      <td className="num">{crc(r.revenue)}</td>
                    </tr>
                  ))
                )}
              </tbody>
            </table>

            {pag && pag.last_page > 1 ? (
              <div className="pagination-wrapper">
                <button type="button" className="btn btn-secondary btn-sm" disabled={pag.page <= 1} onClick={() => setPage((p) => Math.max(1, p - 1))}>
                  <i className="fas fa-chevron-left" aria-hidden="true" /> Anterior
                </button>
                <span className="pagination-info">Página {pag.page} de {pag.last_page} · {pag.total} productos</span>
                <button type="button" className="btn btn-secondary btn-sm" disabled={pag.page >= pag.last_page} onClick={() => setPage((p) => Math.min(pag.last_page, p + 1))}>
                  Siguiente <i className="fas fa-chevron-right" aria-hidden="true" />
                </button>
              </div>
            ) : null}
          </div>
        </section>
      </div>
    </AdminLayout>
  );
}
