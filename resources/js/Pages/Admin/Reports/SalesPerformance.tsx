import { Head } from '@inertiajs/react';
import { useCallback, useEffect, useRef, useState } from 'react';

import { AdminLayout } from '@/shared/components/layout/AdminLayout';
import { PageHeader } from '@/shared/components/ui/PageHeader';

import '../../../../css/admin/reports/sales-performance.css';

const money = new Intl.NumberFormat('es-CR', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
function crc(n: number): string {
  return `₡${money.format(Math.round(Number(n) || 0))}`;
}

type Metrics = { sales_count: number; revenue: number };
type Period = { start: string; end: string; label: string };
type MetricsResponse = {
  success: boolean;
  preset: string;
  from: string | null;
  to: string | null;
  current_period: Period;
  previous_period: Period;
  current_metrics: Metrics;
  previous_metrics: Metrics;
};

const PRESETS: Array<{ value: string; label: string }> = [
  { value: 'today', label: 'Hoy' },
  { value: 'week', label: 'Esta semana' },
  { value: 'month', label: 'Este mes' },
  { value: 'year', label: 'Este año' },
  { value: 'custom', label: 'Personalizado' },
];

type PageProps = { initialPreset: string; initialFrom: string; initialTo: string };

function splitDate(value: string): { d: string; m: string; y: string } {
  const m = /^(\d{4})-(\d{2})-(\d{2})$/.exec(value);
  if (!m) return { d: '', m: '', y: '' };
  return { y: m[1], m: m[2], d: m[3] };
}

export default function SalesPerformance({ initialPreset, initialFrom, initialTo }: PageProps) {
  const [preset, setPreset] = useState(initialPreset || 'month');
  const fromParts0 = splitDate(initialFrom);
  const toParts0 = splitDate(initialTo);
  const [fromD, setFromD] = useState(fromParts0.d);
  const [fromM, setFromM] = useState(fromParts0.m);
  const [fromY, setFromY] = useState(fromParts0.y);
  const [toD, setToD] = useState(toParts0.d);
  const [toM, setToM] = useState(toParts0.m);
  const [toY, setToY] = useState(toParts0.y);

  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [data, setData] = useState<MetricsResponse | null>(null);
  const seqRef = useRef(0);

  const fetchMetrics = useCallback(async (params: Record<string, string>) => {
    const seq = ++seqRef.current;
    setLoading(true);
    setError('');
    try {
      const qs = new URLSearchParams(params).toString();
      const res = await fetch(`/reports/ventas/metrics?${qs}`, { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
      const json = await res.json().catch(() => null);
      if (seq !== seqRef.current) return;
      if (!res.ok || !json?.success) {
        setError(json?.message || 'No se pudieron cargar los datos.');
        setData(null);
      } else {
        setData(json);
      }
    } catch {
      if (seq === seqRef.current) {
        setError('Error de conexión. Intentá de nuevo.');
        setData(null);
      }
    } finally {
      if (seq === seqRef.current) setLoading(false);
    }
  }, []);

  // Initial + preset change (non-custom)
  useEffect(() => {
    if (preset !== 'custom') {
      void fetchMetrics({ preset });
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [preset]);

  function applyCustom() {
    const from = `${fromY}-${fromM.padStart(2, '0')}-${fromD.padStart(2, '0')}`;
    const to = `${toY}-${toM.padStart(2, '0')}-${toD.padStart(2, '0')}`;
    if (!/^\d{4}-\d{2}-\d{2}$/.test(from) || !/^\d{4}-\d{2}-\d{2}$/.test(to)) {
      setError('Completá las fechas del rango (DD/MM/AAAA).');
      return;
    }
    void fetchMetrics({ preset: 'custom', from, to });
  }

  const cur = data?.current_metrics;
  const prev = data?.previous_metrics;
  const empty = data && cur && cur.sales_count === 0 && cur.revenue === 0;
  const revenueDiff = cur && prev ? cur.revenue - prev.revenue : 0;
  const countDiff = cur && prev ? cur.sales_count - prev.sales_count : 0;

  return (
    <AdminLayout title="Desempeño de ventas">
      <Head title="Desempeño de ventas - Reportes" />

      <div className="sales-performance-report">
        <nav className="reports-breadcrumb">
          <a href="/reports">Reportes</a>
          <span className="sep">/</span>
          <span>Desempeño de ventas</span>
        </nav>

        <PageHeader title="Desempeño de ventas" kicker="Reportes">
          <p className="sales-performance-lead">Analiza las ventas completadas y los ingresos facturados del periodo seleccionado, comparándolos con el periodo anterior equivalente.</p>
        </PageHeader>

        <div className="sales-perf-layout">
          <aside className="sales-perf-filters" aria-label="Filtros de periodo">
            <p className="sales-perf-filters-title">Periodo</p>
            <div className="period-toggle-wrap">
              <div className="period-toggle" role="group" aria-label="Opciones de periodo">
                {PRESETS.map((p) => (
                  <button type="button" key={p.value} className={`period-btn${preset === p.value ? ' is-active' : ''}`} onClick={() => setPreset(p.value)}>
                    {p.label}
                  </button>
                ))}
              </div>
            </div>
            {preset === 'custom' ? (
              <div className="sales-custom-range">
                <p className="sales-custom-title">Rango</p>
                <div className="sales-date-block">
                  <span className="sales-date-block-label">Desde</span>
                  <div className="sales-date-parts">
                    <input type="text" inputMode="numeric" maxLength={2} className="sales-num-part" placeholder="DD" value={fromD} onChange={(e) => setFromD(e.target.value.replace(/\D/g, ''))} aria-label="Día desde" />
                    <span className="sales-date-sep">/</span>
                    <input type="text" inputMode="numeric" maxLength={2} className="sales-num-part" placeholder="MM" value={fromM} onChange={(e) => setFromM(e.target.value.replace(/\D/g, ''))} aria-label="Mes desde" />
                    <span className="sales-date-sep">/</span>
                    <input type="text" inputMode="numeric" maxLength={4} className="sales-num-part sales-num-part--year" placeholder="AAAA" value={fromY} onChange={(e) => setFromY(e.target.value.replace(/\D/g, ''))} aria-label="Año desde" />
                  </div>
                </div>
                <div className="sales-date-block">
                  <span className="sales-date-block-label">Hasta</span>
                  <div className="sales-date-parts">
                    <input type="text" inputMode="numeric" maxLength={2} className="sales-num-part" placeholder="DD" value={toD} onChange={(e) => setToD(e.target.value.replace(/\D/g, ''))} aria-label="Día hasta" />
                    <span className="sales-date-sep">/</span>
                    <input type="text" inputMode="numeric" maxLength={2} className="sales-num-part" placeholder="MM" value={toM} onChange={(e) => setToM(e.target.value.replace(/\D/g, ''))} aria-label="Mes hasta" />
                    <span className="sales-date-sep">/</span>
                    <input type="text" inputMode="numeric" maxLength={4} className="sales-num-part sales-num-part--year" placeholder="AAAA" value={toY} onChange={(e) => setToY(e.target.value.replace(/\D/g, ''))} aria-label="Año hasta" />
                  </div>
                </div>
                <button type="button" className="sales-apply-btn" onClick={applyCustom}>Aplicar rango</button>
              </div>
            ) : null}
          </aside>

          <div className="sales-perf-main">
            {error ? <div className="sales-performance-error" role="alert">{error}</div> : null}

            <section className="sales-perf-results" aria-live="polite">
              {loading ? (
                <div className="sales-performance-loading">
                  <span className="loading-dot" aria-hidden="true" />
                  <span>Cargando datos…</span>
                </div>
              ) : data ? (
                <div className="sales-performance-content">
                  {empty ? (
                    <div className="sales-empty-state">
                      <i className="fas fa-inbox" aria-hidden="true" />
                      <p><strong>No hay ventas completadas</strong> en el periodo elegido. Probá otro rango o revisá más adelante.</p>
                    </div>
                  ) : null}

                  <div className="sales-metrics-compare-wrap">
                    <div className="sales-metrics-column">
                      <h3 className="sales-col-heading">Periodo elegido</h3>
                      <p className="sales-col-range">{data.current_period?.label}</p>
                      <div className="sales-metrics-grid">
                        <article className="sales-metric-card">
                          <div className="sales-metric-icon" aria-hidden="true"><i className="fas fa-receipt" /></div>
                          <h2 className="sales-metric-title">Ventas</h2>
                          <p className="sales-metric-value">{cur?.sales_count ?? '—'}</p>
                          <p className="sales-metric-hint">Órdenes completadas</p>
                        </article>
                        <article className="sales-metric-card sales-metric-card--primary">
                          <div className="sales-metric-icon" aria-hidden="true"><i className="fas fa-coins" /></div>
                          <h2 className="sales-metric-title">Ingresos</h2>
                          <p className="sales-metric-value">{cur ? crc(cur.revenue) : '—'}</p>
                          <p className="sales-metric-hint">Total facturado</p>
                        </article>
                      </div>
                    </div>
                    <div className="sales-metrics-column sales-metrics-column--previous">
                      <h3 className="sales-col-heading">Periodo anterior <span className="sales-col-heading-note">(misma duración, para comparar)</span></h3>
                      <p className="sales-col-range">{data.previous_period?.label}</p>
                      <div className="sales-metrics-grid">
                        <article className="sales-metric-card sales-metric-card--secondary">
                          <div className="sales-metric-icon" aria-hidden="true"><i className="fas fa-receipt" /></div>
                          <h2 className="sales-metric-title">Ventas</h2>
                          <p className="sales-metric-value">{prev?.sales_count ?? '—'}</p>
                          <p className="sales-metric-hint">Órdenes completadas</p>
                        </article>
                        <article className="sales-metric-card sales-metric-card--secondary sales-metric-card--primary-soft">
                          <div className="sales-metric-icon" aria-hidden="true"><i className="fas fa-coins" /></div>
                          <h2 className="sales-metric-title">Ingresos</h2>
                          <p className="sales-metric-value">{prev ? crc(prev.revenue) : '—'}</p>
                          <p className="sales-metric-hint">Total facturado</p>
                        </article>
                      </div>
                    </div>
                  </div>

                  <section className="sales-comparison-section">
                    <h2 className="sales-comparison-heading sales-comparison-heading--subtle">Diferencia real respecto al periodo anterior</h2>
                    <ul className="sales-comparison-list">
                      <li className="sales-comparison-row">
                        <span className="sales-comparison-label">Ingresos (actual - anterior)</span>
                        <span className="sales-comparison-value">{`${revenueDiff >= 0 ? '+' : '−'}${crc(Math.abs(revenueDiff))}`}</span>
                      </li>
                      <li className="sales-comparison-row">
                        <span className="sales-comparison-label">Ventas (actual - anterior)</span>
                        <span className="sales-comparison-value">{`${countDiff >= 0 ? '+' : '−'}${Math.abs(countDiff)}`}</span>
                      </li>
                    </ul>
                  </section>
                </div>
              ) : null}
            </section>
          </div>
        </div>
      </div>
    </AdminLayout>
  );
}
