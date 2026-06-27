import { useState } from 'react';
import type { FormEvent } from 'react';

import { Modal } from '@/shared/components/ui/Modal';
import { useToast } from '@/shared/hooks/useToast';

type Props = {
  isOpen: boolean;
  currentHours: number;
  usesEnvDefault: boolean;
  csrfToken: string;
  onClose: () => void;
  onSaved: () => void;
};

export function ExpirationModal({ isOpen, currentHours, usesEnvDefault, csrfToken, onClose, onSaved }: Props) {
  const { showToast } = useToast();
  const [hours, setHours] = useState(String(currentHours));
  const [error, setError] = useState('');
  const [submitting, setSubmitting] = useState(false);

  async function submit(event: FormEvent) {
    event.preventDefault();
    const value = parseInt(hours, 10);
    if (!Number.isFinite(value) || value < 1 || value > 8760) {
      setError('Ingrese un valor entre 1 y 8760 horas.');
      return;
    }
    setError('');
    setSubmitting(true);
    try {
      const res = await fetch('/orders/settings/order-expiration', {
        method: 'PUT',
        headers: { 'X-CSRF-TOKEN': csrfToken, Accept: 'application/json', 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        body: JSON.stringify({ ready_to_pickup_expiration_hours: value }),
      });
      const data = await res.json().catch(() => ({}));
      if (res.ok && (data.success === true || typeof data.success === 'undefined')) {
        showToast({ variant: 'success', title: 'Guardado', message: data.message || 'Plazo actualizado correctamente.' });
        onSaved();
        onClose();
      } else {
        setError(data.message || 'No se pudo guardar el plazo.');
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
      title={<><i className="fas fa-clock" aria-hidden="true" /> Plazo para cancelación automática</>}
      footer={
        <>
          <button type="button" className="btn btn-secondary" onClick={onClose}>
            <i className="fas fa-times" aria-hidden="true" /> Cerrar
          </button>
          <button type="submit" form="order-expiration-form" className="btn btn-primary" disabled={submitting}>
            <i className="fas fa-save" aria-hidden="true" /> {submitting ? 'Guardando…' : 'Guardar'}
          </button>
        </>
      }
    >
      <p className="cf4-order-expiry-modal-intro">
        Cuántas horas tiene el cliente para retirar un pedido marcado como <strong>Listo para recoger</strong>. Si pasa ese tiempo sin recogerlo, el sistema lo cancela automáticamente.
      </p>
      {usesEnvDefault ? (
        <p className="cf4-order-expiry-modal-hint">Si no guarda un valor aquí, se usa el valor por defecto del sistema.</p>
      ) : null}
      <form id="order-expiration-form" onSubmit={submit} noValidate>
        <div className="form-group">
          <label htmlFor="ready_to_pickup_expiration_hours">Horas para recoger el pedido</label>
          <input
            id="ready_to_pickup_expiration_hours"
            type="number"
            min={1}
            max={8760}
            step={1}
            required
            value={hours}
            onChange={(e) => setHours(e.target.value)}
          />
          {error ? <p className="form-error" role="alert">{error}</p> : null}
          <p className="form-help">Ejemplo: 72 horas (3 días). Mínimo 1, máximo 8760.</p>
        </div>
      </form>
    </Modal>
  );
}
