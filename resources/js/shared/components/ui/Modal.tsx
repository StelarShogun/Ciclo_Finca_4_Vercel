import type { PropsWithChildren, ReactNode } from 'react';
import { useEffect } from 'react';

type ModalProps = PropsWithChildren<{
  isOpen: boolean;
  onClose: () => void;
  title?: ReactNode;
  footer?: ReactNode;
  className?: string;
  closeLabel?: string;
}>;

export function Modal({
  children,
  className = 'cf4-modal',
  closeLabel = 'Cerrar',
  footer,
  isOpen,
  onClose,
  title,
}: ModalProps) {
  useEffect(() => {
    if (!isOpen) {
      return;
    }

    function onKeyDown(event: KeyboardEvent) {
      if (event.key === 'Escape') {
        onClose();
      }
    }

    document.addEventListener('keydown', onKeyDown);

    return () => document.removeEventListener('keydown', onKeyDown);
  }, [isOpen, onClose]);

  if (!isOpen) {
    return null;
  }

  return (
    <div className="cf4-modal-overlay" role="presentation" onClick={onClose}>
      <div
        className={className}
        role="dialog"
        aria-modal="true"
        aria-labelledby={title ? 'cf4-modal-title' : undefined}
        onClick={(event) => event.stopPropagation()}
      >
        {title ? (
          <header className="cf4-modal__header">
            <h2 id="cf4-modal-title">{title}</h2>
            <button type="button" className="cf4-modal__close" aria-label={closeLabel} onClick={onClose}>
              <i className="fas fa-times" aria-hidden="true" />
            </button>
          </header>
        ) : null}
        <div className="cf4-modal__body">{children}</div>
        {footer ? <footer className="cf4-modal__footer">{footer}</footer> : null}
      </div>
    </div>
  );
}
