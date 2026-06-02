declare module '@/client/header-catalog-search.js' {
  export function initHeaderCatalogSearch(): void;
}

declare module '@/client/bundles/catalog.js' {
  export function initClientCatalogPage(): void;
}

declare module '@/client/clients-catalog-heartbeat.js' {
  export function startCatalogHeartbeat(): void;
}

declare module '@/client/clients-header-auth.js' {
  export function initClientHeaderAuth(): void;
}

declare module '@/client/bundles/product.js' {
  export function initClientProductPage(): void;
}

declare module '@/client/bundles/cart.js' {
  export function initClientCartPage(): void;
}

declare module '@/client/swal' {
  export function cf4Confirm(options?: Record<string, unknown>): Promise<{ isConfirmed: boolean }>;
  export function cf4Error(message: string, title?: string): Promise<void>;
  export function cf4Toast(options?: Record<string, unknown>): Promise<void>;
}

declare module '@/client/auth-welcome-toast.js' {
  export {};
}

declare module '@/client/recovery-success-modal.js' {
  export {};
}

declare module 'sweetalert2' {
  const Swal: any;
  export default Swal;
}

interface Window {
  cf4ShowToast?: (payload: {
    variant: 'success' | 'error' | 'warning' | 'info';
    title: string;
    message?: string;
    durationMs?: number;
  }) => void;

  cf4AuthWelcomeToast?: (opts: any) => Promise<void> | void;

  catalogFavoriteConfig?: {
    toggleUrl?: string;
    loginUrl?: string;
  };
}
