import { Head, router } from '@inertiajs/react';

import { AdminLayout } from '@/shared/components/layout/AdminLayout';
import { PageHeader } from '@/shared/components/ui/PageHeader';

import '../../../../css/admin/reports/reports-hub.css';

const nf = new Intl.NumberFormat('es-CR');

type Row = { product_id: number; name: string; sku: string; hit_count: number };
type PageProps = {
  period: string;
  rows: Row[];
  totalEvents: number;
  uniqueProducts: number;
  topProductName: string | null;
  topProductHits: number | null;
  maxHits: number;
};

const PERIOD_SHORT: Record<string, string> = { '7d': '7 días', '30d': '30 días', '90d': '90 días' };
const PERIOD_LONG: Record<string, string> = { '7d': 'Últimos 7 días', '30d': 'Últimos 30 días', '90d': 'Últimos 90 días' };

export default function CatalogSearch({ period, rows, totalEvents, uniqueProducts, topProductName, topProductHits, maxHits }: PageProps) {
  const periodShort = PERIOD_SHORT[period] ?? '30 días';
  const periodLong = PERIOD_LONG[period] ?? 'Últimos 30 días';
  const safeMax = Math.max(1, maxHits);

  function go(p: string) {
    router.get('/reports/catalogo-busquedas', { period: p }, { preserveScroll: true, replace: true });
  }

  return (
    <AdminLayout title="Productos más buscados">
      <Head title="Productos más buscados - Reportes" />

      <div className="catalog-search-report">
        <nav className="reports-breadcrumb" aria-label="Migas de pan">
          <a href="/reports">Reportes</a>
          <span className="sep">/</span>
          <span>Productos más buscados</span>
        </nav>

        <PageHeader title="Productos más buscados" kicker="Reportes">
          <p className="csr-page-subtitle">Consulta los productos que aparecen con mayor frecuencia en las búsquedas del catálogo público.</p>
        </PageHeader>

        <div className="csr-period-bar">
          <nav className="csr-period-tabs" role="tablist" aria-label="Periodo del reporte">
            {(['7d', '30d', '90d'] as const).map((p) => (
              <button type="button" key={p} className={`csr-period-tab${period === p ? ' is-active' : ''}`} role="tab" aria-selected={period === p} onClick={() => go(p)}>
                {PERIOD_SHORT[p]}
              </button>
            ))}
          </nav>
        </div>

        <div className="csr-kpi-grid">
          <article className="csr-kpi-card">
            <div className="csr-kpi-card-head">
              <div>
                <p className="csr-kpi-label">Apariciones totales</p>
                <p className="csr-kpi-value">{nf.format(totalEvents)}</p>
              </div>
              <span className="csr-kpi-icon" aria-hidden="true"><i className="fas fa-chart-line" /></span>
            </div>
          </article>

          <article className="csr-kpi-card">
            <div className="csr-kpi-card-head">
              <div>
                <p className="csr-kpi-label">Productos distintos</p>
                <p className="csr-kpi-value">{nf.format(uniqueProducts)}</p>
              </div>
              <span className="csr-kpi-icon csr-kpi-icon--muted" aria-hidden="true"><i className="fas fa-box-open" /></span>
            </div>
          </article>

          <article className="csr-kpi-card">
            <div className="csr-kpi-card-head">
              <div>
                <p className="csr-kpi-label">Líder</p>
                {topProductName ? (
                  <>
                    <p className="csr-kpi-leader-name" title={topProductName}>{topProductName}</p>
                    <p className="csr-kpi-sub csr-kpi-sub--pill">
                      <span className="csr-hit-pill csr-hit-pill--compact">{nf.format(topProductHits ?? 0)} apariciones</span>
                    </p>
                  </>
                ) : (
                  <p className="csr-kpi-leader-empty">—</p>
                )}
              </div>
              <span className="csr-kpi-icon csr-kpi-icon--accent" aria-hidden="true"><i className="fas fa-trophy" /></span>
            </div>
          </article>

          <article className="csr-kpi-card">
            <div className="csr-kpi-card-head">
              <div>
                <p className="csr-kpi-label">Periodo</p>
                <p className="csr-kpi-period-text">{periodShort}</p>
                <p className="csr-kpi-period-long">{periodLong}</p>
              </div>
              <span className="csr-kpi-icon" aria-hidden="true"><i className="fas fa-calendar-alt" /></span>
            </div>
          </article>
        </div>

        <section className="csr-ranking-panel" aria-labelledby="csr-ranking-heading">
          <div className="csr-ranking-panel-head">
            <h2 id="csr-ranking-heading" className="csr-ranking-panel-title">Del más al menos buscado</h2>
            <p className="csr-ranking-panel-meta">{periodLong}</p>
          </div>

          {rows.length > 0 ? (
            <div className="csr-ranking-list" role="list">
              {rows.map((row, idx) => {
                const rank = idx + 1;
                const pct = Math.round((row.hit_count / safeMax) * 100);
                const rankClass = rank === 1 ? 'csr-ranking-row--rank1' : rank === 2 ? 'csr-ranking-row--rank2' : rank === 3 ? 'csr-ranking-row--rank3' : '';
                const badgeClass = rank === 1 ? 'csr-rank-badge--1' : rank === 2 ? 'csr-rank-badge--2' : rank === 3 ? 'csr-rank-badge--3' : 'csr-rank-badge--n';
                return (
                  <div className={`csr-ranking-row ${rankClass}`} role="listitem" key={row.product_id}>
                    <span className={`csr-rank-badge ${badgeClass}`} aria-label={`Puesto ${rank}`}>{rank}</span>
                    <div className="csr-ranking-main">
                      <p className="csr-ranking-name">{row.name}</p>
                      <p className="csr-ranking-sku">{row.sku}</p>
                      <div className="csr-popularity-track" aria-hidden="true">
                        <div className="csr-popularity-fill" style={{ width: `${pct}%` }} />
                      </div>
                    </div>
                    <div className="csr-ranking-side">
                      <span className="csr-hit-pill">{nf.format(row.hit_count)}</span>
                    </div>
                  </div>
                );
              })}
            </div>
          ) : (
            <div className="csr-empty">
              <div className="csr-empty-icon" aria-hidden="true"><i className="fas fa-search" /></div>
              <p className="csr-empty-title">Aún no hay datos</p>
              <p className="csr-empty-text">Cuando los visitantes busquen en el catálogo, aquí aparecerá la lista.</p>
            </div>
          )}
        </section>
      </div>
    </AdminLayout>
  );
}
