import { useState } from 'react';

import { Modal } from '@/shared/components/ui/Modal';
import { useToast } from '@/shared/hooks/useToast';

type Target = { saleId: number; invoiceLabel: string };

type Props = {
  target: Target | null;
  onClose: () => void;
  csrfToken: string;
  onReturned: () => void;
};

export function ReturnSaleModal({ target, onClose, csrfToken, onReturned }: Props) {
  const { showToast } = useToast();
  const [reason, setReason] = useState('');
  const [showError, setShowError] = useState(false);
  const [submitting, setSubmitting] = useState(false);

  function close() {
    setReason('');
    setShowError(false);
    onClose();
  }

  async function confirmReturn() {
    if (!target) return;
    const trimmed = reason.trim();
    if (trimmed.length < 3) {
      setShowError(true);
      return;
    }
    setShowError(false);
    setSubmitting(true);
    try {
      const res = await fetch(`/sales/${target.saleId}/return`, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrfToken, Accept: 'application/json', 'Content-Type': 'application/json' },
        body: JSON.stringify({ reason: trimmed }),
      });
      const data = await res.json().catch(() => ({}));
      if (res.ok && data.success) {
        showToast({ variant: 'success', title: 'Devolución registrada', message: data.message || 'La devolución fue procesada correctamente.' });
        setReason('');
        onReturned();
        close();
      } else {
        showToast({ variant: 'error', title: 'Error', message: data.message || 'No se pudo registrar la devolución.' });
      }
    } catch {
      showToast({ variant: 'error', title: 'Error de conexión', message: 'No se pudo conectar con el servidor. Intente nuevamente.' });
    } finally {
      setSubmitting(false);
    }
  }

  return (
    <Modal
      isOpen={target != null}
      onClose={close}
      title={<><i className="fas fa-rotate-left" aria-hidden="true" /> Registrar Devolución</>}
      footer={
        <>
          <button type="button" className="btn btn-secondary" onClick={close}>
            <i className="fas fa-times" aria-hidden="true" /> Cancelar
          </button>
          <button type="button" className="btn btn-danger" onClick={confirmReturn} disabled={submitting}>
            <i className="fas fa-rotate-left" aria-hidden="true" /> {submitting ? 'Procesando…' : 'Confirmar devolución'}
          </button>
        </>
      }
    >
      <p className="sale-notes" style={{ marginBottom: '1rem' }}>
        {target ? `Venta: ${target.invoiceLabel}. Complete el motivo para continuar.` : ''}
      </p>

      <div className="form-group">
        <label htmlFor="return-reason-input">
          Motivo de la devolución <span style={{ color: 'var(--color-danger)' }}>*</span>
        </label>
        <textarea
          id="return-reason-input"
          rows={4}
          maxLength={500}
          value={reason}
          onChange={(e) => {
            setReason(e.target.value);
            if (e.target.value.trim().length >= 3) setShowError(false);
          }}
          placeholder="Describa el motivo de la devolución (obligatorio)..."
          style={{ width: '100%', resize: 'vertical' }}
        />
        {showError ? (
          <small className="text-danger">
            <i className="fas fa-exclamation-circle" aria-hidden="true" /> El motivo es obligatorio y debe tener al menos 3 caracteres.
          </small>
        ) : null}
      </div>

      <div className="alert alert-warning" style={{ marginTop: '0.75rem' }}>
        <i className="fas fa-exclamation-triangle" aria-hidden="true" /> Al confirmar, la venta pasará a estado <strong>Devuelta</strong> y el stock de todos los productos será reintegrado al inventario automáticamente.
      </div>
    </Modal>
  );
}
