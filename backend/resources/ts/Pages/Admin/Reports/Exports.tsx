import { Head } from '@inertiajs/react';
import { useEffect, useMemo, useRef, useState } from 'react';

import { AdminLayout } from '@/shared/components/layout/AdminLayout';
import { PageHeader } from '@/shared/components/ui/PageHeader';

import '../../../../css/admin/reports/reports-hub.css';
import '../../../../css/admin/reports/exports.css';

type FilterOption = { value: string; label: string };
type FilterDef = {
  name: string;
  label: string;
  type?: string;
  help?: string;
  placeholder?: string;
  readonly?: boolean;
  default?: string;
  options?: FilterOption[];
  cascades?: string;
  cascadeOptions?: Record<string, FilterOption[]>;
  autofills?: string[];
  autofillData?: Record<string, Record<string, string>>;
};
type ExportDef = {
  id: string;
  title: string;
  subtitle?: string;
  formatMode?: 'query' | 'path';
  baseUrls: Record<string, string>;
  filters?: FilterDef[];
  initialValues?: Record<string, string>;
  staticParams?: Record<string, string>;
};
type PageProps = { exportsConfig: { exports: Record<string, ExportDef> } };

const CHIP_META: Record<string, { label: string; cls?: string; icon?: string }> = {
  pdf: { label: 'PDF' },
  excel: { label: 'Excel', cls: 'exports-chip--excel', icon: 'fa-file-excel' },
  bundle: { label: 'ZIP', icon: 'fa-file-archive' },
  json: { label: 'JSON' },
  xml: { label: 'XML' },
};

const PDF_GROUP = ['dashboard', 'inventory', 'productSales', 'sales'];
const REGISTRY_GROUP = ['registry.suppliers', 'registry.brands', 'registry.supplierOrders', 'registry.users', 'registry.clientOrders'];

function buildUrl(def: ExportDef, format: string, scope: string, values: Record<string, string>): string {
  const params = new URLSearchParams();
  if (def.formatMode === 'query') params.set('format', format);
  if (scope === 'all') {
    params.set('scope', 'all');
  } else {
    for (const field of def.filters ?? []) {
      const v = values[field.name];
      if (v !== undefined && v !== null && String(v).trim() !== '') params.set(field.name, String(v));
    }
  }
  for (const [k, v] of Object.entries(def.staticParams ?? {})) {
    if (v !== undefined && v !== null && String(v) !== '') params.set(k, String(v));
  }
  const base = def.baseUrls[format];
  if (!base) throw new Error(`Missing base URL for ${def.id} ${format}`);
  const url = new URL(base, window.location.origin);
  for (const [k, v] of new URLSearchParams(url.search)) {
    if (!params.has(k)) params.set(k, v);
  }
  url.search = params.toString();
  return url.toString();
}

export default function Exports({ exportsConfig }: PageProps) {
  const exportsMap = exportsConfig.exports ?? {};
  const dialogRef = useRef<HTMLDialogElement>(null);
  const [active, setActive] = useState<{ exportId: string; format: string } | null>(null);
  const [scope, setScope] = useState<'all' | 'filtered'>('all');
  const [values, setValues] = useState<Record<string, string>>({});

  const activeDef = active ? exportsMap[active.exportId] : null;

  function openModal(exportId: string, format: string) {
    const def = exportsMap[exportId];
    if (!def) return;
    const init: Record<string, string> = {};
    for (const field of def.filters ?? []) {
      init[field.name] = def.initialValues?.[field.name] ?? field.default ?? '';
    }
    // seed autofill on initial values
    for (const field of def.filters ?? []) {
      if (field.autofills && field.autofillData) {
        const data = field.autofillData[init[field.name]] ?? {};
        for (const target of field.autofills) init[target] = data[target] ?? init[target] ?? '';
      }
    }
    setValues(init);
    setScope('all');
    setActive({ exportId, format });
  }

  useEffect(() => {
    if (active && dialogRef.current && !dialogRef.current.open) dialogRef.current.showModal();
  }, [active]);

  function close() {
    if (dialogRef.current?.open) dialogRef.current.close();
    setActive(null);
  }

  function changeField(field: FilterDef, value: string) {
    setValues((prev) => {
      const next = { ...prev, [field.name]: value };
      // cascade: reset child options' value
      if (field.cascades) next[field.cascades] = '';
      // autofill
      if (field.autofills && field.autofillData) {
        const data = field.autofillData[value] ?? {};
        for (const target of field.autofills) next[target] = data[target] ?? '';
      }
      return next;
    });
  }

  function optionsFor(field: FilterDef): FilterOption[] {
    if (field.cascadeOptions) return field.options ?? [];
    return field.options ?? [];
  }

  function childOptions(def: ExportDef, childName: string): FilterOption[] | null {
    // find a parent filter that cascades into childName
    const parent = (def.filters ?? []).find((f) => f.cascades === childName && f.cascadeOptions);
    if (!parent || !parent.cascadeOptions) return null;
    const parentVal = values[parent.name] ?? '';
    return parent.cascadeOptions[parentVal] ?? [{ value: '', label: 'Todas' }];
  }

  function submit() {
    if (!activeDef || !active) return;
    const url = buildUrl(activeDef, active.format, scope, values);
    window.open(url, '_blank', 'noopener,noreferrer');
    close();
  }

  const pdfCards = useMemo(() => PDF_GROUP.map((id) => exportsMap[id]).filter(Boolean), [exportsMap]);
  const registryCards = useMemo(() => REGISTRY_GROUP.map((id) => exportsMap[id]).filter(Boolean), [exportsMap]);

  function renderChips(def: ExportDef) {
    const formats = Object.keys(def.baseUrls);
    return (
      <span className="exports-item-actions">
        {formats.map((fmt, i) => {
          const meta = CHIP_META[fmt] ?? { label: fmt.toUpperCase() };
          const cls = ['exports-chip', i === 0 ? 'exports-chip-primary' : '', meta.cls ?? ''].filter(Boolean).join(' ');
          return (
            <a href="#" key={fmt} className={cls} onClick={(e) => { e.preventDefault(); openModal(def.id, fmt); }}>
              {meta.icon ? <i className={`fas ${meta.icon}`} aria-hidden="true" /> : null}{meta.icon ? ' ' : ''}{meta.label}
            </a>
          );
        })}
      </span>
    );
  }

  return (
    <AdminLayout title="Exportar datos">
      <Head title="Exportar datos - Reportes" />

      <div className="reports-hub reports-exports">

        <PageHeader title="Exportación de datos" kicker="Reportes">
          <p>Descarga reportes y listados administrativos en PDF, Excel o XML desde un solo lugar.</p>
          <p>Puedes exportar información del dashboard, inventario, ventas, productos más vendidos y registros administrativos.</p>
        </PageHeader>

        <div className="reports-exports-layout">
          <section className="exports-section exports-section--pdf" aria-labelledby="exports-pdf-title">
            <h2 id="exports-pdf-title" className="exports-section-title">Reportes en PDF y Excel</h2>
            <ul className="exports-link-list">
              {pdfCards.map((def) => (
                <li key={def.id}>
                  <span className="exports-item-label">{def.title}</span>
                  {renderChips(def)}
                </li>
              ))}
            </ul>
          </section>

          <section className="exports-section exports-section--registry" aria-labelledby="exports-registry-title">
            <h2 id="exports-registry-title" className="exports-section-title">Listados administrativos</h2>
            <p className="exports-hint">Proveedores, marcas, pedidos a proveedores, usuarios y encargos. Excel o PDF; en pedidos y encargos valen los mismos filtros que en sus pantallas.</p>
            <ul className="exports-link-list exports-link-list--compact">
              {registryCards.map((def) => (
                <li key={def.id}>
                  <span className="exports-item-label">{def.title}</span>
                  {renderChips(def)}
                </li>
              ))}
            </ul>
          </section>
        </div>

        <p className="exports-footnote">
          Para importar productos use el botón <strong>Importar</strong> en <a href="/inventory">Inventario</a>.
        </p>
      </div>

      <dialog ref={dialogRef} className="cf4-export-modal" aria-labelledby="cf4-export-modal-title" onClose={() => setActive(null)} onClick={(e) => { if (e.target === dialogRef.current) close(); }}>
        <div className="cf4-export-modal__inner">
          <header className="cf4-export-modal__header">
            <div>
              <h3 id="cf4-export-modal-title" className="cf4-export-modal__title">{activeDef?.title ?? 'Exportar'}</h3>
              <p className="cf4-export-modal__subtitle">{activeDef?.subtitle ?? ''}</p>
            </div>
            <button type="button" className="cf4-export-modal__close" onClick={close} aria-label="Cerrar"><i className="fas fa-times" aria-hidden="true" /></button>
          </header>

          <div className="cf4-export-modal__body">
            <fieldset className="cf4-export-modal__scope" aria-label="Alcance de la exportación">
              <label className="cf4-export-modal__radio">
                <input type="radio" name="scope" value="all" checked={scope === 'all'} onChange={() => setScope('all')} />
                <span>Todo</span>
              </label>
              <label className="cf4-export-modal__radio">
                <input type="radio" name="scope" value="filtered" checked={scope === 'filtered'} onChange={() => setScope('filtered')} />
                <span>Con filtros</span>
              </label>
            </fieldset>

            {scope === 'filtered' && activeDef ? (
              <div className="cf4-export-modal__filters">
                {(activeDef.filters ?? []).map((field) => {
                  const opts = field.cascades ? optionsFor(field) : (childOptions(activeDef, field.name) ?? field.options ?? null);
                  const selectOpts = field.type === 'select' ? (childOptions(activeDef, field.name) ?? field.options ?? []) : null;
                  return (
                    <div className="cf4-export-modal__field" key={field.name}>
                      {field.help ? <div className="cf4-export-modal__help">{field.help}</div> : null}
                      <label className="cf4-export-modal__label" htmlFor={`cf4-export-field-${field.name}`}>{field.label}</label>
                      {field.type === 'select' ? (
                        <select id={`cf4-export-field-${field.name}`} className="cf4-export-modal__control" value={values[field.name] ?? ''} onChange={(e) => changeField(field, e.target.value)}>
                          {(selectOpts ?? opts ?? []).map((o) => <option key={o.value} value={o.value}>{o.label}</option>)}
                        </select>
                      ) : (
                        <input
                          id={`cf4-export-field-${field.name}`}
                          className="cf4-export-modal__control"
                          type={field.type ?? 'text'}
                          placeholder={field.placeholder}
                          readOnly={field.readonly}
                          value={values[field.name] ?? ''}
                          onChange={(e) => changeField(field, e.target.value)}
                        />
                      )}
                    </div>
                  );
                })}
              </div>
            ) : null}
          </div>

          <footer className="cf4-export-modal__footer">
            <button type="button" className="cf4-export-modal__btn cf4-export-modal__btn--ghost" onClick={close}>Cancelar</button>
            <button type="button" className="cf4-export-modal__btn cf4-export-modal__btn--primary" onClick={submit}>Exportar</button>
          </footer>
        </div>
      </dialog>
    </AdminLayout>
  );
}
