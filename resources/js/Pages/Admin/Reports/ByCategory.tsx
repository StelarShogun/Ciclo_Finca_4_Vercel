import { Head, router } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import type { FormEvent } from 'react';
import Chart from 'chart.js/auto';

import { AdminLayout } from '@/shared/components/layout/AdminLayout';
import { PageHeader } from '@/shared/components/ui/PageHeader';
import { FiltersSection } from '@/shared/components/ui/FiltersSection';

import '../../../../css/admin/sales/sales.css';

const nf = new Intl.NumberFormat('es-CR');
function crc(n: number): string {
  return `₡${nf.format(Math.round(Number(n) || 0))}`;
}

type Row = { category_id: number; category_name: string; total_units: number; total_revenue: number; percentage: number };
type ChartPoint = { label: string; value: number; percent: number };
type Filters = { date_range: string; date_from: string; date_to: string };

type PageProps = {
  rows: Row[];
  grandTotal: number;
  totalUnits: number;
  chartData: ChartPoint[];
  filters: Filters;
};

const COLORS = ['#4CAF50', '#2196F3', '#FF9800', '#9C27B0', '#F44336', '#00BCD4', '#795548', '#607D8B', '#E91E63', '#009688'];

export default function ByCategory({ rows, grandTotal, totalUnits, chartData, filters }: PageProps) {
  const [form, setForm] = useState<Filters>(filters);
  const canvasRef = useRef<HTMLCanvasElement>(null);
  const chartRef = useRef<Chart | null>(null);

  useEffect(() => {
    if (!canvasRef.current || chartData.length === 0) return;
    chartRef.current?.destroy();
    chartRef.current = new Chart(canvasRef.current, {
      type: 'pie',
      data: {
        labels: chartData.map((r) => r.label),
        datasets: [{
          data: chartData.map((r) => r.value),
          backgroundColor: COLORS,
          borderWidth: 2,
          borderColor: '#fff',
        }],
      },
      options: {
        responsive: true,
        plugins: {
          legend: { position: 'bottom', labels: { padding: 16, font: { size: 12 } } },
          tooltip: {
            callbacks: {
              label: (ctx) => {
                const d = chartData[ctx.dataIndex];
                return ` ₡${Number(d.value).toLocaleString('es-CR')} (${d.percent}%)`;
              },
            },
          },
        },
      },
    });
    return () => {
      chartRef.current?.destroy();
      chartRef.current = null;
    };
  }, [chartData]);

  function submitFilters(event: FormEvent) {
    event.preventDefault();
    router.get('/sales/reports/by-category', { ...form }, { preserveScroll: true, preserveState: true, replace: true });
  }

  function clearFilters() {
    const empty: Filters = { date_range: 'month', date_from: '', date_to: '' };
    setForm(empty);
    router.get('/sales/reports/by-category', {}, { preserveScroll: true, preserveState: true, replace: true });
  }

  return (
    <AdminLayout title="Ventas por categoría">
      <Head title="Ventas por Categoría" />

      <div className="sales-container">

        <PageHeader title="Ventas por categoría" kicker="Reportes">
          <p>Analiza los ingresos, unidades vendidas y participación de cada categoría en el periodo seleccionado.</p>
        </PageHeader>

        <FiltersSection title="Filtros de búsqueda" onSubmit={submitFilters} onClear={clearFilters}>
          <div className="filter-group">
            <label htmlFor="date-range">Rango de Fecha</label>
            <select
              id="date-range"
              value={form.date_range}
              onChange={(e) => {
                const v = e.target.value;
                setForm((prev) => ({ ...prev, date_range: v, ...(v !== 'custom' ? { date_from: '', date_to: '' } : {}) }));
              }}
            >
              <option value="today">Hoy</option>
              <option value="week">Esta semana</option>
              <option value="month">Este mes</option>
              <option value="custom">Personalizado</option>
            </select>
          </div>
          {form.date_range === 'custom' ? (
            <>
              <div className="filter-group">
                <label htmlFor="date_from">Desde</label>
                <input id="date_from" type="date" value={form.date_from} onChange={(e) => setForm({ ...form, date_from: e.target.value })} />
              </div>
              <div className="filter-group">
                <label htmlFor="date_to">Hasta</label>
                <input id="date_to" type="date" value={form.date_to} onChange={(e) => setForm({ ...form, date_to: e.target.value })} />
              </div>
            </>
          ) : null}
        </FiltersSection>

        {rows.length === 0 ? (
          <div className="report-table-panel">
            <div className="sales-table-container">
              <table className="sales-table admin-table">
                <thead>
                  <tr><th>Categoría</th><th className="text-center">Unidades</th><th className="text-right">Ingresos</th><th className="text-right">Participación</th></tr>
                </thead>
                <tbody>
                  <tr><td colSpan={4}><div className="report-empty-state"><i className="fas fa-inbox fa-2x" aria-hidden="true" /><p>No hay ventas confirmadas en el periodo seleccionado.</p></div></td></tr>
                </tbody>
              </table>
            </div>
          </div>
        ) : (
          <>
            <div className="kpi-grid">
              <div className="kpi-card">
                <div className="kpi-header"><h3 className="kpi-title">Ingresos del Periodo</h3><div className="kpi-icon success"><i className="fas fa-dollar-sign" /></div></div>
                <p className="kpi-value">{crc(grandTotal)}</p>
              </div>
              <div className="kpi-card">
                <div className="kpi-header"><h3 className="kpi-title">Categorías Activas</h3><div className="kpi-icon info"><i className="fas fa-tags" /></div></div>
                <p className="kpi-value">{rows.length}</p>
              </div>
              <div className="kpi-card">
                <div className="kpi-header"><h3 className="kpi-title">Unidades Vendidas</h3><div className="kpi-icon"><i className="fas fa-box" /></div></div>
                <p className="kpi-value">{nf.format(totalUnits)}</p>
              </div>
            </div>

            <div className="report-content-grid">
              <div className="sales-table-container report-chart-panel">
                <h3>Distribución por Categoría</h3>
                <canvas ref={canvasRef} id="category-chart" />
              </div>

              <div className="report-table-panel">
                <div className="sales-table-container">
                  <table className="sales-table admin-table">
                    <thead>
                      <tr><th>Categoría</th><th className="text-center">Unidades</th><th className="text-right">Ingresos</th><th className="text-right">Participación</th></tr>
                    </thead>
                    <tbody>
                      {rows.map((row) => (
                        <tr key={row.category_id}>
                          <td>{row.category_name}</td>
                          <td className="text-center">{nf.format(row.total_units)}</td>
                          <td className="text-right">{crc(row.total_revenue)}</td>
                          <td className="text-right">
                            <span className="pct-cell">
                              <span className="pct-label">{row.percentage}%</span>
                              <span className="pct-bar-track"><span className="pct-bar-fill" style={{ width: `${row.percentage}%` }} /></span>
                            </span>
                          </td>
                        </tr>
                      ))}
                    </tbody>
                    <tfoot>
                      <tr className="tfoot-total">
                        <td>Total</td>
                        <td className="text-center">{nf.format(totalUnits)}</td>
                        <td className="text-right">{crc(grandTotal)}</td>
                        <td className="text-right">100%</td>
                      </tr>
                    </tfoot>
                  </table>
                </div>
              </div>
            </div>
          </>
        )}
      </div>
    </AdminLayout>
  );
}
