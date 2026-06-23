import { useEffect, useRef } from 'react';

import { usePage } from '@inertiajs/react';

import { useToast } from '@/shared/hooks/useToast';
import type { InertiaSharedProps } from '@/shared/types/models';

function firstName(displayName: string | null | undefined) {
  const trimmed = String(displayName ?? '').trim();
  if (!trimmed) return '';
  return trimmed.split(/\s+/)[0] ?? '';
}

export function useFlashToasts() {
  const page = usePage<InertiaSharedProps>();
  const { flash } = page.props;
  const { showToast } = useToast();

  const lastKeyRef = useRef<string>('');

  useEffect(() => {
    const modal = flash?.clientSuccessModal;
    const successText = flash?.success ?? flash?.status ?? null;
    const key = JSON.stringify({
      success: successText,
      error: flash?.error ?? null,
      modal: modal ?? null,
    });

    if (!key || key === lastKeyRef.current) {
      return;
    }

    lastKeyRef.current = key;

    if (flash?.error) {
      showToast({ variant: 'error', title: 'No se pudo completar', message: flash.error });
      return;
    }

    if (successText) {
      showToast({ variant: 'success', title: 'Listo', message: successText });
      return;
    }

    if (modal) {
      const kind = modal.kind ?? 'welcome';
      const icon = modal.authIcon ?? (kind === 'logout' ? 'signout' : 'user');
      const displayName = (modal as any).displayName as string | undefined;
      const name = firstName(displayName);

      if (kind === 'logout') {
        showToast({
          variant: 'info',
          title: modal.title ?? '¡Sesión cerrada!',
          message: modal.text ?? 'Has cerrado sesión correctamente.',
        });
        return;
      }

      showToast({
        variant: 'success',
        title: modal.title ?? (name ? `¡Hola, ${name}!` : '¡Bienvenido!'),
        message:
          modal.text ??
          (icon === 'google' ? 'Conectado con Google' : 'Inicio de sesión exitoso'),
      });
    }
  }, [flash?.clientSuccessModal, flash?.error, flash?.success, flash?.status, showToast]);
}

