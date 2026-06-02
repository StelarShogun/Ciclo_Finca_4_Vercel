/** @deprecated Temporary re-export — canonical module: `@/shared/types/inertia.d.ts`. */
import type { InertiaSharedProps } from './models';

declare module '@inertiajs/core' {
  interface PageProps extends InertiaSharedProps {
    [key: string]: unknown;
  }
}
