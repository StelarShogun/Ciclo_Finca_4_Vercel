import type { PropsWithChildren, ReactNode } from 'react';
import { useEffect, useRef } from 'react';

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
  const dialogRef = useRef<HTMLDivElement>(null);
  const lastFocusedRef = useRef<HTMLElement | null>(null);

  useEffect(() => {
    if (!isOpen) {
      return;
    }

    // Recordar el elemento enfocado para devolverle el foco al cerrar.
    lastFocusedRef.current = document.activeElement as HTMLElement | null;

    // Enfocar el primer control del modal al abrir.
    const focusFirst = window.setTimeout(() => {
      const dialog = dialogRef.current;
      if (!dialog) return;
      const focusable = dialog.querySelector<HTMLElement>(
        'input:not([disabled]), select:not([disabled]), textarea:not([disabled]), button:not([disabled]), a[href], [tabindex]:not([tabindex="-1"])',
      );
      (focusable ?? dialog).focus();
    }, 0);

    function onKeyDown(event: KeyboardEvent) {
      if (event.key === 'Escape') {
        onClose();
        return;
      }
      // Focus trap: mantener el tabulado dentro del modal.
      if (event.key === 'Tab' && dialogRef.current) {
        const focusables = Array.from(
          dialogRef.current.querySelectorAll<HTMLElement>(
            'input:not([disabled]), select:not([disabled]), textarea:not([disabled]), button:not([disabled]), a[href], [tabindex]:not([tabindex="-1"])',
          ),
        ).filter((el) => el.offsetParent !== null);
        if (focusables.length === 0) return;
        const first = focusables[0];
        const last = focusables[focusables.length - 1];
        if (event.shiftKey && document.activeElement === first) {
          event.preventDefault();
          last.focus();
        } else if (!event.shiftKey && document.activeElement === last) {
          event.preventDefault();
          first.focus();
        }
      }
    }

    document.addEventListener('keydown', onKeyDown);

    return () => {
      window.clearTimeout(focusFirst);
      document.removeEventListener('keydown', onKeyDown);
      // Devolver el foco al disparador.
      lastFocusedRef.current?.focus?.();
    };
  }, [isOpen, onClose]);

  if (!isOpen) {
    return null;
  }

  return (
    <div className="cf4-modal-overlay" role="presentation" onClick={onClose}>
      <div
        ref={dialogRef}
        className={className}
        role="dialog"
        aria-modal="true"
        tabIndex={-1}
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
