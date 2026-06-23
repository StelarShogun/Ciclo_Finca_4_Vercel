import { router } from '@inertiajs/react';
import { useEffect, useMemo, useRef, useState } from 'react';

import { Modal } from '@/shared/components/ui/Modal';
import { useToast } from '@/shared/hooks/useToast';

import type { ProductSearchItem, SupplierOption } from '../types';

const colones = new Intl.NumberFormat('es-CR', { maximumFractionDigits: 0 });
function formatColones(value: number): string {
  return `₡${colones.format(Math.round(Number(value) || 0))}`;
}

type Line = { product_id: number; name: string; sku: string; unit_price: number; quantity: number };

type Props = {
  isOpen: boolean;
  suppliers: SupplierOption[];
  onClose: () => void;
};

export function CreateOrderModal({ isOpen, suppliers, onClose }: Props) {
  const { showToast } = useToast();
  const [supplierId, setSupplierId] = useState<number | null>(null);
  const [supplierTerm, setSupplierTerm] = useState('');
  const [supplierOpen, setSupplierOpen] = useState(false);
  const [allProducts, setAllProducts] = useState<ProductSearchItem[]>([]);
  const [productTerm, setProductTerm] = useState('');
  const [productOpen, setProductOpen] = useState(false);
  const [lines, setLines] = useState<Line[]>([]);
  const [submitting, setSubmitting] = useState(false);
  const [itemsError, setItemsError] = useState('');

  const supplierWrap = useRef<HTMLDivElement>(null);
  const productWrap = useRef<HTMLDivElement>(null);

  useEffect(() => {
    if (!isOpen) {
      setSupplierId(null);
      setSupplierTerm('');
      setAllProducts([]);
      setProductTerm('');
      setLines([]);
      setItemsError('');
    }
  }, [isOpen]);

  useEffect(() => {
    function onDocClick(e: MouseEvent) {
      if (supplierWrap.current && !supplierWrap.current.contains(e.target as Node)) setSupplierOpen(false);
      if (productWrap.current && !productWrap.current.contains(e.target as Node)) setProductOpen(false);
    }
    document.addEventListener('mousedown', onDocClick);
    return () => document.removeEventListener('mousedown', onDocClick);
  }, []);

  const selectedSupplier = suppliers.find((s) => s.supplier_id === supplierId) ?? null;

  const supplierMatches = useMemo(() => {
    const q = supplierTerm.trim().toLowerCase();
    return q ? suppliers.filter((s) => s.name.toLowerCase().includes(q)) : suppliers;
  }, [supplierTerm, suppliers]);

  const productMatches = useMemo(() => {
    const q = productTerm.trim().toLowerCase();
    return q ? allProducts.filter((p) => p.name.toLowerCase().includes(q) || p.sku.toLowerCase().includes(q)) : allProducts;
  }, [productTerm, allProducts]);

  async function loadSupplierProducts(id: number) {
    try {
      const res = await fetch(`/admin/products/search?${new URLSearchParams({ q: '', supplier_id: String(id) }).toString()}`, {
        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      });
      const data = await res.json().catch(() => ({}));
      setAllProducts(Array.isArray(data.products) ? data.products : []);
    } catch {
      setAllProducts([]);
    }
  }

  function pickSupplier(s: SupplierOption) {
    setSupplierId(s.supplier_id);
    setSupplierTerm(s.name);
    setSupplierOpen(false);
    setLines([]);
    setProductTerm('');
    setItemsError('');
    void loadSupplierProducts(s.supplier_id);
  }

  function addProduct(p: ProductSearchItem) {
    setLines((prev) => {
      const existing = prev.find((l) => l.product_id === p.product_id);
      if (existing) {
        return prev.map((l) => (l.product_id === p.product_id ? { ...l, quantity: l.quantity + 1 } : l));
      }
      return [...prev, { product_id: p.product_id, name: p.name, sku: p.sku, unit_price: Number(p.unit_price) || 0, quantity: 1 }];
    });
    setItemsError('');
    setProductTerm('');
    setProductOpen(false);
  }

  function setQty(productId: number, qty: number) {
    setLines((prev) => prev.map((l) => (l.product_id === productId ? { ...l, quantity: qty } : l)));
  }

  function removeLine(productId: number) {
    setLines((prev) => prev.filter((l) => l.product_id !== productId));
  }

  const total = lines.reduce((a, l) => a + (Number(l.unit_price) || 0) * (Number(l.quantity) || 0), 0);

  function submit() {
    const linesOk = lines.length >= 1 && lines.every((l) => (Number(l.quantity) || 0) >= 1);
    if (!supplierId) {
      showToast({ variant: 'warning', title: 'Proveedor requerido', message: 'Selecciona un proveedor.' });
      return;
    }
    if (!linesOk) {
      setItemsError('Agrega al menos un producto y asegúrate de que todas las cantidades sean mayores a 0.');
      return;
    }
    setSubmitting(true);
    router.post(
      '/supplier-orders',
      { supplier_id: supplierId, items: lines.map((l) => ({ product_id: l.product_id, quantity: l.quantity })) },
      {
        onError: (errors) => {
          const msg = errors.items || errors.supplier_id || 'Revisa los campos del pedido.';
          setItemsError(String(msg));
          showToast({ variant: 'error', title: 'Error', message: String(msg) });
        },
        onFinish: () => setSubmitting(false),
      },
    );
  }

  return (
    <Modal
      isOpen={isOpen}
      onClose={onClose}
      className="cf4-modal cf4-modal--wide"
      title={<><i className="fas fa-plus" aria-hidden="true" /> Nuevo pedido a proveedor</>}
      footer={
        <>
          <button type="button" className="btn btn-primary" onClick={submit} disabled={submitting}>
            <i className="fas fa-save" aria-hidden="true" /> {submitting ? 'Guardando…' : 'Guardar borrador'}
          </button>
          <button type="button" className="btn btn-secondary" onClick={onClose}><i className="fas fa-times" aria-hidden="true" /> Cancelar</button>
        </>
      }
    >
      <div className="supplier-order-create">
        <div className="create-grid">
          <section className="create-card">
            <div className="create-card-head">
              <h2><i className="fas fa-truck" aria-hidden="true" /> Proveedor</h2>
              <span className="required-pill">Obligatorio</span>
            </div>
            <div className="form-group">
              <label htmlFor="supplier-search">Proveedor</label>
              <div className="product-combobox" ref={supplierWrap}>
                <input
                  id="supplier-search"
                  type="text"
                  className="product-combobox-input"
                  placeholder="Escribe para buscar un proveedor…"
                  autoComplete="off"
                  value={supplierTerm}
                  onFocus={() => setSupplierOpen(true)}
                  onChange={(e) => {
                    setSupplierTerm(e.target.value);
                    setSupplierOpen(true);
                    if (supplierId) setSupplierId(null);
                  }}
                />
                <span className="product-combobox-chevron"><i className="fa-solid fa-chevron-down" aria-hidden="true" /></span>
                {supplierOpen ? (
                  <div className="product-combobox-dropdown open" role="listbox">
                    {supplierMatches.length === 0 ? (
                      <div className="product-combobox-no-result">Sin resultados</div>
                    ) : (
                      supplierMatches.map((s) => (
                        <button type="button" key={s.supplier_id} className="product-combobox-option" onClick={() => pickSupplier(s)}>
                          <div className="product-combobox-option-info">
                            <div className="product-combobox-option-name">{s.name}</div>
                          </div>
                        </button>
                      ))
                    )}
                  </div>
                ) : null}
              </div>
            </div>
            {selectedSupplier ? (
              <div className="supplier-preview">
                <div className="k">Contacto</div><div className="v">{selectedSupplier.primary_contact || '—'}</div>
                <div className="k">Correo</div><div className="v">{selectedSupplier.email || '—'}</div>
                <div className="k">Teléfono</div><div className="v">{selectedSupplier.phone || '—'}</div>
              </div>
            ) : null}
          </section>

          <section className="create-card create-card-wide">
            <div className="create-card-head">
              <h2><i className="fas fa-box" aria-hidden="true" /> Productos</h2>
              <span className="required-pill">Obligatorio</span>
            </div>
            <div className="items-toolbar">
              <div className="product-combobox" ref={productWrap}>
                <input
                  type="text"
                  className="product-combobox-input"
                  placeholder={supplierId ? 'Busca por nombre o SKU (BK-001)…' : 'Selecciona un proveedor primero…'}
                  autoComplete="off"
                  disabled={!supplierId}
                  value={productTerm}
                  onFocus={() => setProductOpen(true)}
                  onChange={(e) => {
                    setProductTerm(e.target.value);
                    setProductOpen(true);
                  }}
                />
                <span className="product-combobox-chevron"><i className="fa-solid fa-chevron-down" aria-hidden="true" /></span>
                {productOpen && supplierId ? (
                  <div className="product-combobox-dropdown open" role="listbox">
                    {productMatches.length === 0 ? (
                      <div className="product-combobox-no-result">Sin resultados</div>
                    ) : (
                      productMatches.map((p) => (
                        <div className="product-combobox-option" key={p.product_id}>
                          <div className="product-combobox-option-info">
                            <div className="product-combobox-option-name">{p.name}</div>
                            <div className="product-combobox-option-meta">
                              <code>{p.sku}</code>
                              <span>{formatColones(p.unit_price)}</span>
                            </div>
                          </div>
                          <button type="button" className="product-combobox-add-btn" onClick={() => addProduct(p)} title="Agregar">
                            <i className="fas fa-plus" aria-hidden="true" /> Agregar
                          </button>
                        </div>
                      ))
                    )}
                  </div>
                ) : null}
              </div>
            </div>
            <div className="items-table-wrap">
              <table className="items-table admin-table" aria-label="Líneas del pedido">
                <thead>
                  <tr>
                    <th style={{ width: '46%' }}>Producto</th>
                    <th className="num" style={{ width: '16%' }}>Cantidad</th>
                    <th className="num" style={{ width: '19%' }}>Precio unit.</th>
                    <th className="num" style={{ width: '19%' }}>Total</th>
                    <th style={{ width: '1%' }} />
                  </tr>
                </thead>
                <tbody>
                  {lines.map((l) => (
                    <tr key={l.product_id}>
                      <td>
                        <div style={{ display: 'flex', flexDirection: 'column', gap: 2 }}>
                          <strong>{l.name}</strong>
                          <span style={{ opacity: 0.75 }}><code>{l.sku}</code></span>
                        </div>
                      </td>
                      <td className="num">
                        <input type="number" min={1} step={1} value={l.quantity} className="qty-input" style={{ width: '100%', textAlign: 'right' }} onChange={(e) => setQty(l.product_id, parseInt(e.target.value || '0', 10) || 0)} />
                      </td>
                      <td className="num">
                        <input type="number" value={l.unit_price} className="unit-input" style={{ width: '100%', textAlign: 'right' }} disabled />
                      </td>
                      <td className="num"><strong>{formatColones((Number(l.unit_price) || 0) * (Number(l.quantity) || 0))}</strong></td>
                      <td>
                        <button type="button" className="remove-line" title="Eliminar línea" aria-label="Eliminar" onClick={() => removeLine(l.product_id)}>
                          <i className="fas fa-trash" aria-hidden="true" />
                        </button>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
            <div className="items-footer">
              <div className="items-errors" aria-live="polite">{itemsError ? <p className="field-error">{itemsError}</p> : null}</div>
              <div className="items-summary">
                <div className="summary-line"><span>Líneas</span><strong>{lines.length}</strong></div>
                <div className="summary-line"><span>Total</span><strong>{formatColones(total)}</strong></div>
              </div>
            </div>
          </section>
        </div>
      </div>
    </Modal>
  );
}
