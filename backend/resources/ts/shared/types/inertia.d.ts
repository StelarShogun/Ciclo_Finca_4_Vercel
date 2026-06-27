import type { InertiaSharedProps } from './models';

declare module '@inertiajs/core' {
  interface PageProps extends InertiaSharedProps {
    [key: string]: unknown;
  }
}
