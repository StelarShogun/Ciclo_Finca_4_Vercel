import { Head } from '@inertiajs/react';
import { useCallback, useEffect, useRef, useState } from 'react';

import { AdminLayout } from '@/shared/components/layout/AdminLayout';
import { PageHeader } from '@/shared/components/ui/PageHeader';
import { FiltersSection } from '@/shared/components/ui/FiltersSection';

import '../../../../css/admin/reports/client-purchase-history.css';

const money = new Intl.NumberFormat('es-CR', { maximumFractionDigits: 0 });
function crc(n: number): string {
  return `₡${money.format(Math.round(Number(n) || 0))}`;
}

type Row = { client_id: number; display_name: string; gmail: string; orders_count: number; total_purchased: number; avg_ticket: number };
type TableResponse = { success: boolean; rows: Row[]; pagination: { page: number; per_page: number; total: number; last_page: number } };

type PageProps = { period: string; sort: string; dir: string; q: string };

export default function ClientPurchases({ period: period0, sort: sort0, dir: dir0, q: q0 }: PageProps) {
  const [period, setPeriod] = useState(period0 || '30d');
  const [sort, setSort] = useState(sort0 || 'total_purchased');
  const [dir, setDir] = useState(dir0 || 'desc');
  const [q, setQ] = useState(q0 || '');
  const [page, setPage] = useState(1);

  const [data, setData] = useState<TableResponse | null>(null);
  const [loading, setLoading] = useState(false);
  const seqRef = useRef(0);

  const load = useCallback(async (params: Record<string, string>) => {
    const seq = ++seqRef.current;
    setLoading(true);
    try {
      const qs = new URLSearchParams(params).toString();
      const res = await fetch(`/reports/client-purchases/table?${qs}`, { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
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
    const params: Record<string, string> = { period, sort, dir, page: String(page) };
    if (q.trim()) params.q = q.trim();
    void load(params);
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [period, sort, dir, page]);

  useEffect(() => {
    const t = window.setTimeout(() => {
      setPage(1);
      const params: Record<string, string> = { period, sort, dir, page: '1' };
      if (q.trim()) params.q = q.trim();
      void load(params);
    }, 400);
    return () => window.clearTimeout(t);
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [q]);

  function sortBy(column: string) {
    if (sort === column) setDir(dir === 'asc' ? 'desc' : 'asc');
    else { setSort(column); setDir('desc'); }
    setPage(1);
  }

  function detailUrl(clientId: number): string {
    const p = new URLSearchParams({ back_period: period, back_sort: sort, back_dir: dir, back_page: String(page) });
    if (q.trim()) p.set('back_q', q.trim());
    return `/reports/client-purchases/${clientId}?${p.toString()}`;
  }

  const rows = data?.rows ?? [];
  const pag = data?.pagination;

  function sortIcon(col: string) {
    return sort === col ? <i className={`fas fa-sort-${dir === 'asc' ? 'up' : 'down'}`} aria-hidden="true" /> : null;
  }

  return (
    <AdminLayout title="Compras por cliente">
      <Head title="Compras por cliente - Reportes" />

      <div className="client-purchases-report">
        <nav className="reports-breadcrumb">
          <a href="/reports">Reportes</a>
          <span className="sep">/</span>
          <span>Compras por cliente</span>
        </nav>

        <PageHeader title="Compras por cliente" kicker="Reportes">
          <p>Consulta el total comprado, la cantidad de órdenes y el ticket promedio. También puedes buscar por nombre, apellido o correo.</p>
        </PageHeader>

        <FiltersSection hideActions>
          <div className="filter-group">
            <label>Periodo</label>
            <div className="period-toggle" role="group" aria-label="Periodo">
              {['7d', '30d', '90d'].map((p) => (
                <button type="button" key={p} className={`period-btn${period === p ? ' active' : ''}`} onClick={() => { setPeriod(p); setPage(1); }}>
                  {p === '7d' ? '7 días' : p === '30d' ? '30 días' : '90 días'}
                </button>
              ))}
            </div>
          </div>
          <div className="filter-group filters-grow">
            <label htmlFor="cp-search">Buscar</label>
            <input type="search" id="cp-search" placeholder="Buscar por nombre, apellido o correo…" value={q} onChange={(e) => setQ(e.target.value)} autoComplete="off" />
          </div>
        </FiltersSection>

        <div className="table-section">
          <div className="sales-table-container">
            <table className="report-table admin-table">
              <thead>
                <tr>
                  <th>Cliente</th>
                  <th>Correo</th>
                  <th className="num"><button type="button" className={`nav-sort${sort === 'total_purchased' ? ' is-active' : ''}`} onClick={() => sortBy('total_purchased')}>Total comprado {sortIcon('total_purchased')}</button></th>
                  <th className="num"><button type="button" className={`nav-sort${sort === 'orders_count' ? ' is-active' : ''}`} onClick={() => sortBy('orders_count')}>Órdenes {sortIcon('orders_count')}</button></th>
                  <th className="num"><button type="button" className={`nav-sort${sort === 'avg_ticket' ? ' is-active' : ''}`} onClick={() => sortBy('avg_ticket')}>Ticket promedio {sortIcon('avg_ticket')}</button></th>
                  <th className="col-actions admin-table__col--actions">Acciones</th>
                </tr>
              </thead>
              <tbody>
                {loading && rows.length === 0 ? (
                  <tr><td colSpan={6} className="loading-cell">Cargando…</td></tr>
                ) : rows.length === 0 ? (
                  <tr><td colSpan={6} className="empty-cell">No hay compras de clientes en el periodo seleccionado.</td></tr>
                ) : (
                  rows.map((r) => (
                    <tr key={r.client_id}>
                      <td>{r.display_name}</td>
                      <td>{r.gmail}</td>
                      <td className="num">{crc(r.total_purchased)}</td>
                      <td className="num">{r.orders_count}</td>
                      <td className="num">{crc(r.avg_ticket)}</td>
                      <td className="admin-table__col--actions">
                        <div className="actions-container">
                          <a href={detailUrl(r.client_id)} className="action-btn secondary" data-tooltip="Ver detalle" aria-label="Ver detalle"><i className="fas fa-eye" aria-hidden="true" /></a>
                        </div>
                      </td>
                    </tr>
                  ))
                )}
              </tbody>
            </table>

            {pag && pag.last_page > 1 ? (
              <div className="pagination-wrapper">
                <button type="button" className="btn btn-secondary btn-sm" disabled={pag.page <= 1} onClick={() => setPage((p) => Math.max(1, p - 1))}><i className="fas fa-chevron-left" aria-hidden="true" /> Anterior</button>
                <span className="pagination-info">Página {pag.page} de {pag.last_page} · {pag.total} clientes</span>
                <button type="button" className="btn btn-secondary btn-sm" disabled={pag.page >= pag.last_page} onClick={() => setPage((p) => Math.min(pag.last_page, p + 1))}>Siguiente <i className="fas fa-chevron-right" aria-hidden="true" /></button>
              </div>
            ) : null}
          </div>
        </div>
      </div>
    </AdminLayout>
  );
}
