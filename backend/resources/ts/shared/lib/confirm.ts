import { getGlobalConfirm, type ConfirmDialogOptions } from '@/shared/components/ui/ConfirmDialogProvider';

function mapConfirmOptions(options: Record<string, unknown> = {}): ConfirmDialogOptions {
  return {
    title: typeof options.title === 'string' ? options.title : undefined,
    text: typeof options.text === 'string' ? options.text : undefined,
    confirmText: typeof options.confirmButtonText === 'string' ? options.confirmButtonText : undefined,
    cancelText: typeof options.cancelButtonText === 'string' ? options.cancelButtonText : undefined,
    icon: typeof options.icon === 'string'
      ? (options.icon as ConfirmDialogOptions['icon'])
      : undefined,
    confirmButtonColor: options.danger === true ? '#b42318' : undefined,
  };
}

export async function confirm(options?: Record<string, unknown>) {
  const globalConfirm = getGlobalConfirm();
  if (globalConfirm) {
    const isConfirmed = await globalConfirm(mapConfirmOptions(options ?? {}));

    return { isConfirmed };
  }

  const { cf4Confirm } = await import('@/client/swal');
  return await cf4Confirm(options ?? {});
}
