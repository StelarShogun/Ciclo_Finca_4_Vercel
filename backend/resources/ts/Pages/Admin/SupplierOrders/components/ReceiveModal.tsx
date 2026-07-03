import { useEffect, useState } from 'react';

import { Modal } from '@/shared/components/ui/Modal';

type Item = { id: number; name: string; quantity: number; received_quantity: number | null };

type Props = {
  isOpen: boolean;
  orderId: number;
  isPartial: boolean;
  items: Item[];
  csrfToken: string;
  onClose: () => void;
  onReceived: (message: string) => void;
};

export function ReceiveModal({ isOpen, orderId, isPartial, items, csrfToken, onClose, onReceived }: Props) {
  const [values, setValues] = useState<Record<number, number>>({});
  const [error, setError] = useState('');
  const [submitting, setSubmitting] = useState(false);

  useEffect(() => {
    if (isOpen) {
      const init: Record<number, number> = {};
      items.forEach((it) => {
        init[it.id] = it.received_quantity ?? it.quantity;
      });
      setValues(init);
      setError('');
    }
  }, [isOpen, items]);

  async function submit() {
    setError('');
    for (const it of items) {
      const val = values[it.id];
      if (!Number.isFinite(val) || val < 0) {
        setError(`La cantidad recibida de "${it.name}" no puede ser negativa.`);
        return;
      }
      if (val > it.quantity) {
        setError(`La cantidad recibida de "${it.name}" (${val}) supera la pedida (${it.quantity}).`);
        return;
      }
    }
    setSubmitting(true);
    try {
      const res = await fetch(`/supplier-orders/${orderId}/receive`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrfToken, 'X-Requested-With': 'XMLHttpRequest' },
        body: JSON.stringify({ items: items.map((it) => ({ order_item_id: it.id, received_quantity: values[it.id] })) }),
      });
      const data = await res.json().catch(() => ({}));
      if (res.status === 422 && data.errors) {
        setError(String(Object.values(data.errors).flat()[0] ?? 'Error de validación.'));
        return;
      }
      if (!res.ok || !data.success) {
        setError(data.message ?? 'No se pudo registrar la recepción.');
        return;
      }
      onReceived(data.message || 'Recepción registrada correctamente.');
    } catch {
      setError('Error de conexión. Verificá tu red e intentá de nuevo.');
    } finally {
      setSubmitting(false);
    }
  }

  return (
    <Modal
      isOpen={isOpen}
      onClose={onClose}
      className="cf4-modal cf4-modal--wide"
      title={<><i className="fas fa-clipboard-check" aria-hidden="true" /> {isPartial ? 'Completar recepción de mercancía' : 'Registrar recepción de mercancía'}</>}
      footer={
        <>
          <button type="button" className="btn btn-secondary" onClick={onClose}><i className="fas fa-times" aria-hidden="true" /> Cancelar</button>
          <button type="button" className="btn btn-primary" onClick={submit} disabled={submitting}><i className="fas fa-check" aria-hidden="true" /> {submitting ? 'Procesando…' : 'Confirmar recepción'}</button>
        </>
      }
    >
      <p style={{ margin: '0 0 16px', color: 'var(--text-secondary)', fontSize: '.9rem' }}>
        {isPartial
          ? 'Este pedido tiene una recepción parcial registrada. Actualiza las cantidades recibidas. Al confirmar, si todos los productos llegan completos el pedido pasará a Entregado; de lo contrario se mantendrá en Recepción parcial.'
          : 'Ingresa la cantidad recibida por cada producto. Si todos los productos se reciben completos el pedido pasará a Entregado; si alguno es menor quedará en Recepción parcial.'}
      </p>
      {error ? <div className="field-error" style={{ marginBottom: 12 }} role="alert">{error}</div> : null}
      <div className="items-table-wrap">
        <table className="items-table admin-table">
          <thead>
            <tr>
              <th>Producto</th>
              <th className="num" style={{ width: 110 }}>Pedido</th>
              <th className="num" style={{ width: 140 }}>Cantidad recibida</th>
            </tr>
          </thead>
          <tbody>
            {items.map((it) => (
              <tr key={it.id}>
                <td>{it.name}</td>
                <td className="num">{it.quantity}</td>
                <td className="num">
                  <input
                    type="number"
                    className="qty-input"
                    min={0}
                    max={it.quantity}
                    value={values[it.id] ?? 0}
                    onChange={(e) => setValues((prev) => ({ ...prev, [it.id]: parseInt(e.target.value, 10) }))}
                    style={{ width: '100%', textAlign: 'right' }}
                    required
                  />
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </Modal>
  );
}
