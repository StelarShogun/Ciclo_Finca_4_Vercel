import { useState } from 'react';
import type { FormEvent } from 'react';

import { Modal } from '@/shared/components/ui/Modal';
import { useToast } from '@/shared/hooks/useToast';

export type StockTarget = {
  product_id: number;
  name: string;
  stock: number;
  action: 'add' | 'remove';
};

type StockModalProps = {
  target: StockTarget | null;
  csrfToken: string;
  onClose: () => void;
  onDone: () => void;
};

export function StockModal({ csrfToken, onClose, onDone, target }: StockModalProps) {
  const { showToast } = useToast();
  const [quantity, setQuantity] = useState('');
  const [reason, setReason] = useState('');
  const [saving, setSaving] = useState(false);

  const isAdd = target?.action === 'add';
  const qty = parseInt(quantity, 10);
  const preview = target && !Number.isNaN(qty) && qty > 0
    ? isAdd
      ? target.stock + qty
      : Math.max(0, target.stock - qty)
    : null;

  async function submit(event: FormEvent) {
    event.preventDefault();
    if (!target) {
      return;
    }
    if (Number.isNaN(qty) || qty < 1) {
      showToast({ variant: 'error', title: 'Cantidad inválida', message: 'Ingresá una cantidad mayor a 0.' });
      return;
    }

    setSaving(true);
    const endpoint = isAdd ? `/inventory/add-manual/${target.product_id}` : `/inventory/remove-manual/${target.product_id}`;
    try {
      const response = await fetch(endpoint, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          Accept: 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': csrfToken,
        },
        body: JSON.stringify({ quantity: qty, reason: reason.trim() }),
      });
      const data = await response.json().catch(() => ({}));
      if (!response.ok || data.success === false) {
        showToast({ variant: 'error', title: 'Error', message: data.message ?? 'No se pudo ajustar el stock.' });
        return;
      }
      showToast({ variant: 'success', title: isAdd ? 'Stock agregado' : 'Stock retirado', message: data.message });
      setQuantity('');
      setReason('');
      onDone();
    } catch {
      showToast({ variant: 'error', title: 'Error', message: 'Error de conexión al ajustar el stock.' });
    } finally {
      setSaving(false);
    }
  }

  return (
    <Modal
      isOpen={target !== null}
      onClose={onClose}
      title={isAdd ? 'Agregar stock' : 'Retirar stock'}
      footer={
        <>
          <button type="button" className="btn btn-secondary" onClick={onClose}>
            Cancelar
          </button>
          <button type="submit" form="form-stock" className="btn btn-primary" disabled={saving}>
            {saving ? 'Guardando…' : isAdd ? 'Agregar' : 'Retirar'}
          </button>
        </>
      }
    >
      {target ? (
        <form id="form-stock" onSubmit={submit}>
          <p className="text-muted">
            <strong>{target.name}</strong> — stock actual: {target.stock}
          </p>
          <div className="form-group">
            <label htmlFor="stock-qty">Cantidad *</label>
            <input
              id="stock-qty"
              type="number"
              min={1}
              max={isAdd ? undefined : target.stock}
              value={quantity}
              onChange={(event) => setQuantity(event.target.value)}
              autoFocus
            />
          </div>
          <div className="form-group">
            <label htmlFor="stock-reason">Motivo</label>
            <textarea
              id="stock-reason"
              rows={2}
              maxLength={500}
              value={reason}
              onChange={(event) => setReason(event.target.value)}
              placeholder="Opcional"
            />
          </div>
          {preview !== null ? (
            <p className="text-muted">
              Stock resultante: <strong>{preview}</strong>
            </p>
          ) : null}
        </form>
      ) : null}
    </Modal>
  );
}
