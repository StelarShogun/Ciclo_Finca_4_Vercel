import { cf4Confirm } from '@/client/swal';

export async function confirm(options?: Record<string, unknown>) {
  return await cf4Confirm(options ?? {});
}

