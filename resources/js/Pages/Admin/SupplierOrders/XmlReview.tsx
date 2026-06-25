import { Head, router } from '@inertiajs/react';
import { useMemo, useState } from 'react';

import { AdminLayout } from '@/shared/components/layout/AdminLayout';
import { PageHeader } from '@/shared/components/ui/PageHeader';

import '../../../../css/admin/orders/orders.css';
import './xml-review.css';

const money = new Intl.NumberFormat('es-CR', { minimumFractionDigits: 2 });
function crc(n: number): string {
  return `₡${money.format(Number(n) || 0)}`;
}

type XmlItem = {
  found: boolean;
  product_id: number | null;
  name: string;
  sku: string | null;
  quantity: number;
  current_price: number;
  xml_price: number;
  difference_amount: number;
  difference_percentage: number;
  has_deviation: boolean;
  suggested_sale_price: number | null;
  current_sale_price: number;
  current_margin_pct: number;
  sale_price_increase: number;
};

type Analysis = {
  items: XmlItem[];
  file_name: string;
  threshold_percentage: number;
};

export default function XmlReview({ analysis }: { analysis: Analysis }) {
  const items = analysis.items;

  const [checked, setChecked] = useState<Set<number>>(() => {
    const s = new Set<number>();
    items.forEach((it) => {
      if (it.found && it.product_id != null && it.has_deviation) s.add(it.product_id);
    });
    return s;
  });
  const [salePrices, setSalePrices] = useState<Record<number, string>>(() => {
    const map: Record<number, string> = {};
    items.forEach((it) => {
      if (it.found && it.product_id != null && it.suggested_sale_price !== null) {
        map[it.product_id] = String(it.suggested_sale_price);
      }
    });
    return map;
  });
  const [reason, setReason] = useState('');
  const [submitting, setSubmitting] = useState(false);

  const stats = useMemo(() => {
    const deviation = items.filter((i) => i.found && i.has_deviation).length;
    const notFound = items.filter((i) => !i.found).length;
    const priceUp = items.filter((i) => i.found && i.suggested_sale_price !== null).length;
    return { total: items.length, deviation, notFound, priceUp };
  }, [items]);

  function toggle(id: number) {
    setChecked((prev) => {
      const next = new Set(prev);
      if (next.has(id)) next.delete(id);
      else next.add(id);
      return next;
    });
  }

  function selectDeviations() {
    const s = new Set<number>();
    items.forEach((it) => it.found && it.product_id != null && it.has_deviation && s.add(it.product_id));
    setChecked(s);
  }
  function selectAll() {
    const s = new Set<number>();
    items.forEach((it) => it.found && it.product_id != null && s.add(it.product_id));
    setChecked(s);
  }
  function deselectAll() {
    setChecked(new Set());
  }

  function submit() {
    setSubmitting(true);
    const prices: Record<number, string> = {};
    Object.entries(salePrices).forEach(([id, val]) => {
      if (val !== '' && checked.has(Number(id))) prices[Number(id)] = val;
    });
    router.post(
      '/supplier-orders/xml-deviation/apply',
      { updates: Array.from(checked), sale_prices: prices, reason },
      { onFinish: () => setSubmitting(false) },
    );
  }

  return (
    <AdminLayout title="Revisión de precios XML">
      <Head title="Revisión de precios XML – Admin" />

      <div className="sales-container xml-review-container">
        <PageHeader
          title="Revisión de precios XML"
          kicker="Proveedores"
          actions={
            <div className="sales-header-actions">
              <a href="/supplier-orders/xml-deviation" className="btn btn-secondary btn-sm"><i className="fas fa-redo" aria-hidden="true" /> Cargar otro XML</a>
              <a href="/supplier-orders" className="btn btn-ghost btn-sm"><i className="fas fa-arrow-left" aria-hidden="true" /> Volver a pedidos</a>
            </div>
          }
        >
          <p>Revisa las diferencias entre los precios actuales y los precios importados desde el XML. Selecciona los productos que deseas actualizar y ajusta el precio de venta cuando corresponda.</p>
        </PageHeader>

        <div className="xml-review-meta">
          <span><strong><i className="fas fa-file-alt" aria-hidden="true" /></strong> {analysis.file_name}</span>
          <span>Umbral: <strong>{analysis.threshold_percentage.toFixed(1)}%</strong></span>
          <span>Total productos: <strong>{stats.total}</strong></span>
          <span>Con desvío: <strong style={{ color: 'var(--color-warning)' }}>{stats.deviation}</strong></span>
          {stats.priceUp ? <span>Con alza en compra: <strong style={{ color: 'var(--color-warning)' }}>{stats.priceUp}</strong></span> : null}
          {stats.notFound ? <span>No encontrados: <strong style={{ color: 'var(--color-danger)' }}>{stats.notFound}</strong></span> : null}
        </div>

        <div className="xml-select-helpers">
          <button type="button" className="btn btn-secondary btn-sm" onClick={selectDeviations}><i className="fas fa-exclamation-triangle" aria-hidden="true" /> Seleccionar con desvío</button>
          <button type="button" className="btn btn-secondary btn-sm" onClick={selectAll}><i className="fas fa-check-double" aria-hidden="true" /> Seleccionar todos</button>
          <button type="button" className="btn btn-ghost btn-sm" onClick={deselectAll}><i className="fas fa-times" aria-hidden="true" /> Deseleccionar todos</button>
        </div>

        <div className="xml-review-table-wrap">
          <table className="xml-review-table admin-table">
            <thead>
              <tr>
                <th style={{ width: 36 }} />
                <th>Producto</th>
                <th>Código</th>
                <th>Cant.</th>
                <th>P. compra actual</th>
                <th>P. compra XML</th>
                <th>Diferencia</th>
                <th>% Desvío</th>
                <th>Precio de venta</th>
                <th>Estado</th>
              </tr>
            </thead>
            <tbody>
              {items.length === 0 ? (
                <tr><td colSpan={10} style={{ textAlign: 'center', padding: '2rem', color: 'var(--text-secondary)' }}>No se encontraron productos en el XML.</td></tr>
              ) : (
                items.map((item, idx) => {
                  const diff = item.difference_amount;
                  const diffClass = diff > 0 ? 'diff-positive' : diff < 0 ? 'diff-negative' : 'diff-zero';
                  const diffSign = diff > 0 ? '+' : '';
                  const hasSuggestion = item.found && item.suggested_sale_price !== null;
                  const rowClass = !item.found ? 'row-not-found' : item.has_deviation ? 'row-deviation' : '';
                  const pid = item.product_id;
                  return (
                    <tr className={rowClass} key={pid ?? `nf-${idx}`}>
                      <td>
                        {item.found && pid != null ? (
                          <input type="checkbox" className="xml-update-checkbox" checked={checked.has(pid)} onChange={() => toggle(pid)} aria-label={`Actualizar ${item.name}`} />
                        ) : (
                          <span title="Producto no encontrado en el sistema">—</span>
                        )}
                      </td>
                      <td>{item.found ? item.name : <span style={{ color: 'var(--text-secondary)' }}>{item.name || '(sin nombre)'}</span>}</td>
                      <td><code style={{ fontSize: '.85em' }}>{item.sku || '—'}</code></td>
                      <td>{item.quantity}</td>
                      <td>{item.found ? crc(item.current_price) : <span style={{ color: 'var(--text-secondary)' }}>—</span>}</td>
                      <td>{crc(item.xml_price)}</td>
                      <td className={diffClass}>{item.found ? `${diffSign}${crc(Math.abs(diff)).replace('₡', '₡')}` : '—'}</td>
                      <td className={diffClass}>{item.found ? `${diffSign}${Math.abs(item.difference_percentage).toFixed(2)}%` : '—'}</td>
                      <td className="sale-price-cell">
                        {hasSuggestion && pid != null ? (
                          <div className="sale-price-suggestion">
                            <div className="suggestion-hint">
                              <span>{crc(item.current_sale_price)}</span>
                              <span className="hint-arrow">→</span>
                              <span style={{ color: 'var(--color-warning)', fontWeight: 600 }}>{crc(item.suggested_sale_price ?? 0)}</span>
                              <span className="hint-margin">{item.current_margin_pct.toFixed(1)}% margen</span>
                              {item.sale_price_increase > 0 ? <span style={{ color: 'var(--color-danger)', fontSize: '.75rem' }}>(+{crc(item.sale_price_increase)})</span> : null}
                            </div>
                            <div style={{ display: 'flex', alignItems: 'center', gap: '.3rem' }}>
                              <div className="sale-price-input-wrap" style={{ flex: 1 }}>
                                <span className="currency-symbol">₡</span>
                                <input
                                  type="number"
                                  className="sale-price-input"
                                  value={salePrices[pid] ?? ''}
                                  min={item.xml_price}
                                  step={1}
                                  onChange={(e) => setSalePrices((prev) => ({ ...prev, [pid]: e.target.value }))}
                                  aria-label={`Nuevo precio de venta para ${item.name}`}
                                />
                              </div>
                              <button type="button" className="sale-price-clear" title="Limpiar — no modificará el precio de venta" onClick={() => setSalePrices((prev) => ({ ...prev, [pid]: '' }))}>
                                <i className="fas fa-times-circle" aria-hidden="true" />
                              </button>
                            </div>
                            <div style={{ fontSize: '.75rem', color: 'var(--text-secondary)' }}><i className="fas fa-info-circle" aria-hidden="true" /> Vacío = precio de venta sin cambios</div>
                          </div>
                        ) : item.found ? (
                          <span className="sale-price-no-change">Sin cambio sugerido</span>
                        ) : (
                          <span style={{ color: 'var(--text-secondary)' }}>—</span>
                        )}
                      </td>
                      <td>
                        {!item.found ? (
                          <span className="badge-not-found"><i className="fas fa-times-circle" aria-hidden="true" /> No encontrado</span>
                        ) : item.has_deviation ? (
                          <span className="badge-deviation"><i className="fas fa-exclamation-triangle" aria-hidden="true" /> Desvío detectado</span>
                        ) : (
                          <span className="badge-ok"><i className="fas fa-check-circle" aria-hidden="true" /> Sin desvío</span>
                        )}
                      </td>
                    </tr>
                  );
                })
              )}
            </tbody>
          </table>
        </div>

        <div className="xml-reason-group">
          <label htmlFor="reason">Motivo / nota del ajuste <span style={{ color: 'var(--text-secondary)', fontWeight: 400 }}>(opcional)</span></label>
          <textarea id="reason" maxLength={500} value={reason} onChange={(e) => setReason(e.target.value)} placeholder="Ej: Ajuste por alza generalizada de precios del proveedor XYZ." />
        </div>

        <div className="xml-actions-bar">
          <button type="button" className="btn btn-primary" onClick={submit} disabled={submitting}>
            <i className="fas fa-check" aria-hidden="true" /> Aplicar cambios seleccionados <span className="xml-count-badge">{checked.size}</span>
          </button>
          <a href="/supplier-orders/xml-deviation" className="btn btn-secondary"><i className="fas fa-times" aria-hidden="true" /> Cancelar</a>
          <span className="xml-selected-count">{checked.size} producto(s) seleccionado(s)</span>
        </div>
      </div>
    </AdminLayout>
  );
}
