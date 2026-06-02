import { useContext } from 'react';

import { ToastContext } from '@/Components/UI/ToastProvider';

export function useToast() {
  const ctx = useContext(ToastContext);
  if (!ctx) {
    throw new Error('useToast debe usarse dentro de ToastProvider');
  }
  return ctx;
}

