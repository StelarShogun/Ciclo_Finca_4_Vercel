import { useState } from 'react';
import type { FormEvent } from 'react';

import { Modal } from '@/shared/components/ui/Modal';
import { useToast } from '@/shared/hooks/useToast';

import type { SaleProductOption } from '../types';
import { ProductCombobox } from './ProductCombobox';

const colones = new Intl.NumberFormat('es-CR', { maximumFractionDigits: 0 });
function formatColones(amount: number): string {
  return `₡${colones.format(Math.round(amount || 0))}`;
}

function roundMoney(n: number): number {
  return Math.round((Number(n) + Number.EPSILON) * 100) / 100;
}

type Row = {
  key: number;
  product: SaleProductOption | null;
  quantity: number;
};

type Props = {
  isOpen: boolean;
  onClose: () => void;
  csrfToken: string;
  onCreated: (saleId: number | null) => void;
};

let rowSeq = 1;

export function NewSaleModal({ isOpen, onClose, csrfToken, onCreated }: Props) {
  const { showToast } = useToast();
  const [buyerName, setBuyerName] = useState('');
  const [buyerEmail, setBuyerEmail] = useState('');
  const [paymentMethod, setPaymentMethod] = useState('');
  const [paymentReference, setPaymentReference] = useState('');
  const [notes, setNotes] = useState('');
  const [discount, setDiscount] = useState(0);
  const [rows, setRows] = useState<Row[]>([{ key: 0, product: null, quantity: 1 }]);
  const [submitting, setSubmitting] = useState(false);
  const [showRowErrors, setShowRowErrors] = useState(false);

  function reset() {
    setBuyerName('');
    setBuyerEmail('');
    setPaymentMethod('');
    setPaymentReference('');
    setNotes('');
    setDiscount(0);
    setRows([{ key: 0, product: null, quantity: 1 }]);
    setShowRowErrors(false);
  }

  function addRow() {
    setRows((prev) => [...prev, { key: rowSeq++, product: null, quantity: 1 }]);
  }

  function removeRow(key: number) {
    setRows((prev) => (prev.length > 1 ? prev.filter((r) => r.key !== key) : prev));
  }

  function updateRow(key: number, patch: Partial<Row>) {
    setRows((prev) => prev.map((r) => (r.key === key ? { ...r, ...patch } : r)));
  }

  const subtotal = roundMoney(
    rows.reduce((sum, r) => sum + (r.product ? roundMoney((Number(r.product.unit_price) || 0) * (r.quantity || 0)) : 0), 0),
  );
  const discountApplied = roundMoney(Math.min(Math.max(0, discount), subtotal));
  const total = roundMoney(subtotal - discountApplied);
  const discountOver = subtotal > 0 && discount > subtotal;

  async function handleSubmit(event: FormEvent) {
    event.preventDefault();

    if (discount > subtotal) {
      showToast({ variant: 'warning', title: 'Descuento inválido', message: `El descuento no puede ser mayor que el subtotal (${formatColones(subtotal)}).` });
      return;
    }

    const hasMissing = rows.some((r) => !r.product);
    if (hasMissing) {
      setShowRowErrors(true);
      showToast({ variant: 'warning', title: 'Producto requerido', message: 'Elegí un producto de la lista en cada línea (buscá por nombre o SKU).' });
      return;
    }

    if (!paymentMethod) {
      showToast({ variant: 'warning', title: 'Método de pago', message: 'Seleccioná un método de pago.' });
      return;
    }

    const fd = new FormData();
    fd.append('buyer_name', buyerName);
    fd.append('buyer_email', buyerEmail);
    fd.append('payment_method', paymentMethod);
    fd.append('payment_reference', paymentReference);
    fd.append('discount', String(discount));
    fd.append('notes', notes);
    rows.forEach((r, i) => {
      const price = Number(r.product?.unit_price) || 0;
      fd.append(`items[${i}][product_id]`, String(r.product?.product_id ?? ''));
      fd.append(`items[${i}][quantity]`, String(r.quantity));
      fd.append(`items[${i}][precio_unitario]`, price.toFixed(2));
      fd.append(`items[${i}][total]`, roundMoney(price * r.quantity).toFixed(2));
    });

    setSubmitting(true);
    try {
      const res = await fetch('/sales', {
        method: 'POST',
        body: fd,
        headers: { 'X-CSRF-TOKEN': csrfToken, Accept: 'application/json' },
      });
      const data = await res.json().catch(() => ({}));
      if (res.ok && data.success) {
        showToast({ variant: 'success', title: 'Venta creada', message: data.message || 'La venta se registró correctamente.' });
        onCreated(data.sale?.sale_id ?? null);
        reset();
        onClose();
      } else {
        showToast({ variant: 'error', title: 'Error', message: data.message || 'No se pudo crear la venta.' });
      }
    } catch {
      showToast({ variant: 'error', title: 'Error de conexión', message: 'Intente nuevamente.' });
    } finally {
      setSubmitting(false);
    }
  }

  return (
    <Modal
      isOpen={isOpen}
      onClose={onClose}
      className="cf4-modal cf4-modal--wide"
      title={<><i className="fas fa-plus-circle" aria-hidden="true" /> Nueva venta</>}
      footer={
        <>
          <button type="button" className="btn btn-secondary" onClick={onClose}>Cancelar</button>
          <button type="submit" form="new-sale-form" className="btn btn-primary" disabled={submitting}>
            <i className="fas fa-save" aria-hidden="true" /> {submitting ? 'Guardando…' : 'Crear Venta'}
          </button>
        </>
      }
    >
      <form id="new-sale-form" onSubmit={handleSubmit}>
        <div className="form-row">
          <div className="form-group">
            <label htmlFor="buyer_name">Nombre (opcional)</label>
            <input id="buyer_name" type="text" value={buyerName} onChange={(e) => setBuyerName(e.target.value)} placeholder="Nombre del comprador (opcional)" />
          </div>
          <div className="form-group">
            <label htmlFor="buyer_email">Correo electrónico (opcional)</label>
            <input id="buyer_email" type="email" value={buyerEmail} onChange={(e) => setBuyerEmail(e.target.value)} placeholder="Correo del comprador (opcional)" />
          </div>
          <div className="form-group">
            <label htmlFor="payment_method">Método de Pago *</label>
            <select id="payment_method" value={paymentMethod} onChange={(e) => setPaymentMethod(e.target.value)} required>
              <option value="">Selecciona un método</option>
              <option value="cash">Efectivo</option>
              <option value="sinpe">SINPE Móvil</option>
              <option value="transfer">Transferencia Bancaria</option>
            </select>
          </div>
        </div>

        <div className="form-group">
          <label htmlFor="payment_reference">Referencia de Pago</label>
          <input id="payment_reference" type="text" value={paymentReference} onChange={(e) => setPaymentReference(e.target.value)} placeholder="Número de referencia (opcional)" />
        </div>

        <div id="productos-container">
          <h4>Productos</h4>
          {rows.map((row) => (
            <div className="product-row" key={row.key}>
              <div className="form-row">
                <div className="form-group form-group--product-combobox">
                  <label>Producto</label>
                  <ProductCombobox
                    selectedLabel={row.product ? row.product.name : ''}
                    invalid={showRowErrors && !row.product}
                    onSelect={(p) => updateRow(row.key, { product: p })}
                    onClear={() => updateRow(row.key, { product: null })}
                  />
                </div>
                <div className="form-group">
                  <label>Cantidad</label>
                  <input
                    type="number"
                    min={1}
                    value={row.quantity}
                    onChange={(e) => updateRow(row.key, { quantity: Math.max(1, parseInt(e.target.value, 10) || 1) })}
                    required
                  />
                </div>
                <div className="form-group">
                  <label>Precio Unitario</label>
                  <input type="number" step="0.01" value={row.product ? Number(row.product.unit_price).toFixed(2) : ''} readOnly />
                </div>
                {rows.length > 1 ? (
                  <div className="form-group">
                    <button type="button" className="remove-product" onClick={() => removeRow(row.key)}>
                      <i className="fas fa-trash" aria-hidden="true" />
                    </button>
                  </div>
                ) : null}
              </div>
            </div>
          ))}
        </div>

        <button type="button" className="btn btn-secondary" onClick={addRow}>
          <i className="fas fa-plus" aria-hidden="true" /> Agregar Producto
        </button>

        <div className={`sale-totals${discountOver ? ' sale-totals--discount-over' : ''}`}>
          <div className="total-row">
            <span>Subtotal:</span>
            <span>{formatColones(subtotal)}</span>
          </div>
          <div className="total-row">
            <span>Descuento:</span>
            <input type="number" value={discount} step="0.01" min={0} onChange={(e) => setDiscount(Math.max(0, parseFloat(e.target.value) || 0))} />
          </div>
          <div className="total-row total-final">
            <span>Total:</span>
            <span>{formatColones(total)}</span>
          </div>
        </div>

        <div className="form-group">
          <label htmlFor="notes">Notas</label>
          <textarea id="notes" rows={3} value={notes} onChange={(e) => setNotes(e.target.value)} placeholder="Notas adicionales (opcional)" />
        </div>
      </form>
    </Modal>
  );
}
