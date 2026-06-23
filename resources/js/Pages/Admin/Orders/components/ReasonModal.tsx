import { useState } from 'react';

import { Modal } from '@/shared/components/ui/Modal';

type Props = {
  isOpen: boolean;
  title: string;
  intro: string;
  warning?: string;
  confirmLabel: string;
  submitting: boolean;
  minLength?: number;
  danger?: boolean;
  onClose: () => void;
  onConfirm: (reason: string) => void;
};

export function ReasonModal({ isOpen, title, intro, warning, confirmLabel, submitting, minLength = 3, danger = true, onClose, onConfirm }: Props) {
  const [reason, setReason] = useState('');
  const [showError, setShowError] = useState(false);

  function close() {
    setReason('');
    setShowError(false);
    onClose();
  }

  function confirm() {
    if (reason.trim().length < minLength) {
      setShowError(true);
      return;
    }
    onConfirm(reason.trim());
    setReason('');
    setShowError(false);
  }

  return (
    <Modal
      isOpen={isOpen}
      onClose={close}
      title={<><i className="fas fa-times-circle" aria-hidden="true" /> {title}</>}
      footer={
        <>
          <button type="button" className="btn btn-secondary" onClick={close}>Cancelar</button>
          <button type="button" className={danger ? 'btn btn-danger' : 'btn btn-warning'} onClick={confirm} disabled={submitting}>
            {submitting ? 'Procesando…' : confirmLabel}
          </button>
        </>
      }
    >
      <p className="sale-notes" style={{ marginBottom: '1rem' }}>{intro}</p>
      <div className="form-group">
        <label htmlFor="reason-input">Motivo <span style={{ color: 'var(--color-danger)' }}>*</span></label>
        <textarea
          id="reason-input"
          rows={4}
          maxLength={500}
          value={reason}
          onChange={(e) => {
            setReason(e.target.value);
            if (e.target.value.trim().length >= minLength) setShowError(false);
          }}
          placeholder="Describa el motivo (obligatorio)…"
          style={{ width: '100%', resize: 'vertical' }}
        />
        {showError ? (
          <small className="text-danger">
            <i className="fas fa-exclamation-circle" aria-hidden="true" /> El motivo es obligatorio y debe tener al menos {minLength} caracteres.
          </small>
        ) : null}
      </div>
      {warning ? (
        <div className="alert alert-warning" style={{ marginTop: '0.75rem' }}>
          <i className="fas fa-exclamation-triangle" aria-hidden="true" /> {warning}
        </div>
      ) : null}
    </Modal>
  );
}
