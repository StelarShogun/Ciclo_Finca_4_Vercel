import type { PropsWithChildren, ReactNode } from 'react';
import { createContext, useCallback, useContext, useEffect, useRef, useState } from 'react';

import { Button } from '@/shared/components/ui/Button';
import { Modal } from '@/shared/components/ui/Modal';

export type ConfirmDialogOptions = {
  title?: string;
  text?: ReactNode;
  confirmText?: string;
  cancelText?: string;
  icon?: 'warning' | 'error' | 'info' | 'question';
  confirmButtonColor?: string;
};

type ConfirmDialogContextValue = {
  confirm: (options?: ConfirmDialogOptions) => Promise<boolean>;
};

const ConfirmDialogContext = createContext<ConfirmDialogContextValue | null>(null);

type PendingConfirm = {
  options: ConfirmDialogOptions;
  resolve: (value: boolean) => void;
};

let globalConfirm: ((options?: ConfirmDialogOptions) => Promise<boolean>) | null = null;

export function getGlobalConfirm() {
  return globalConfirm;
}

export function ConfirmDialogProvider({ children }: PropsWithChildren) {
  const [pending, setPending] = useState<PendingConfirm | null>(null);
  const pendingRef = useRef<PendingConfirm | null>(null);

  const confirm = useCallback((options: ConfirmDialogOptions = {}) => {
    return new Promise<boolean>((resolve) => {
      const next: PendingConfirm = { options, resolve };
      pendingRef.current = next;
      setPending(next);
    });
  }, []);

  useEffect(() => {
    globalConfirm = confirm;

    return () => {
      if (globalConfirm === confirm) {
        globalConfirm = null;
      }
    };
  }, [confirm]);

  function close(result: boolean) {
    const current = pendingRef.current;
    pendingRef.current = null;
    setPending(null);
    current?.resolve(result);
  }

  const iconClass = pending?.options.icon === 'error'
    ? 'fas fa-circle-exclamation'
    : pending?.options.icon === 'info'
      ? 'fas fa-circle-info'
      : pending?.options.icon === 'question'
        ? 'fas fa-circle-question'
        : 'fas fa-triangle-exclamation';

  return (
    <ConfirmDialogContext.Provider value={{ confirm }}>
      {children}
      <Modal
        isOpen={pending !== null}
        onClose={() => close(false)}
        title={pending?.options.title ?? '¿Continuar?'}
        className="cf4-confirm-dialog"
        footer={(
          <>
            <Button variant="secondary" onClick={() => close(false)}>
              {pending?.options.cancelText ?? 'Cancelar'}
            </Button>
            <Button
              variant="primary"
              onClick={() => close(true)}
              style={pending?.options.confirmButtonColor ? { backgroundColor: pending.options.confirmButtonColor } : undefined}
            >
              {pending?.options.confirmText ?? 'Confirmar'}
            </Button>
          </>
        )}
      >
        {pending?.options.icon ? <p className="cf4-confirm-dialog__icon"><i className={iconClass} aria-hidden="true" /></p> : null}
        {pending?.options.text ? <p className="cf4-confirm-dialog__text">{pending.options.text}</p> : null}
      </Modal>
    </ConfirmDialogContext.Provider>
  );
}

export function useConfirmDialog() {
  const context = useContext(ConfirmDialogContext);
  if (!context) {
    throw new Error('useConfirmDialog must be used within ConfirmDialogProvider');
  }

  return context;
}
