import { createContext, useCallback, useEffect, useMemo, useRef, useState } from 'react';
import type { PropsWithChildren } from 'react';

import { ToastContainer } from '@/shared/components/ui/ToastContainer';
import type { ToastItem, ToastVariant } from '@/shared/components/ui/ToastContainer';

export type ShowToastInput = {
  variant: ToastVariant;
  title: string;
  message?: string;
  durationMs?: number;
};

export type ToastContextValue = {
  showToast: (toast: ShowToastInput) => string;
  removeToast: (id: string) => void;
};

export const ToastContext = createContext<ToastContextValue | null>(null);

function makeId() {
  try {
    return crypto.randomUUID();
  } catch {
    return `t-${Date.now()}-${Math.random().toString(16).slice(2)}`;
  }
}

export function ToastProvider({ children }: PropsWithChildren) {
  const [toasts, setToasts] = useState<ToastItem[]>([]);
  const timersRef = useRef<Map<string, number>>(new Map());

  const removeToast = useCallback((id: string) => {
    const timer = timersRef.current.get(id);
    if (timer) {
      window.clearTimeout(timer);
      timersRef.current.delete(id);
    }

    setToasts((current) => current.filter((toast) => toast.id !== id));
  }, []);

  const showToast = useCallback((input: ShowToastInput) => {
    const id = makeId();
    const toast: ToastItem = {
      id,
      variant: input.variant,
      title: input.title,
      message: input.message,
    };

    setToasts((current) => [...current, toast]);

    const duration = typeof input.durationMs === 'number' ? input.durationMs : 4500;
    const timer = window.setTimeout(() => removeToast(id), duration);
    timersRef.current.set(id, timer);

    return id;
  }, [removeToast]);

  const value = useMemo(() => ({ showToast, removeToast }), [showToast, removeToast]);

  useEffect(() => {
    // Bridge for legacy JS bundles (cart, favorites) to avoid SweetAlert for simple toasts.
    // Kept intentionally small: accepts the same payload as showToast().
    window.cf4ShowToast = (payload: ShowToastInput) => {
      showToast(payload);
    };

    return () => {
      if (window.cf4ShowToast) {
        delete window.cf4ShowToast;
      }
    };
  }, [showToast]);

  return (
    <ToastContext.Provider value={value}>
      {children}
      <ToastContainer toasts={toasts} onRemove={removeToast} />
    </ToastContext.Provider>
  );
}

