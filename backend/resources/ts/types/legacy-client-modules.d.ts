declare module '@/client/header-catalog-search' {
  export function initHeaderCatalogSearch(): void;
}

declare module '@/client/bundles/catalog' {
  export function initClientCatalogPage(): void;
}

declare module '@/client/clients-catalog-heartbeat' {
  export function startCatalogHeartbeat(): void;
}

declare module '@/client/swal' {
  export function cf4Confirm(options?: Record<string, unknown>): Promise<{ isConfirmed: boolean }>;
  export function cf4Error(message: string, title?: string): Promise<void>;
  export function cf4Toast(options?: Record<string, unknown>): Promise<void>;
  export function fireSwal(options?: Record<string, unknown>): Promise<unknown>;
}

declare module 'sweetalert2' {
  const Swal: {
    fire: (...args: unknown[]) => Promise<unknown>;
    mixin?: (...args: unknown[]) => unknown;
  };
  export default Swal;
}

