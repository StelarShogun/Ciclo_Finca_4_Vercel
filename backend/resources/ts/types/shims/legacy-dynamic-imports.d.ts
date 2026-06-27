declare module '@/client/bundles/catalog' {
  export function initClientCatalogPage(): void;
}

declare module '@/client/clients-catalog-heartbeat' {
  export function startCatalogHeartbeat(): void;
}

declare module '@/client/header-catalog-search' {
  export function initHeaderCatalogSearch(): void;
}

declare module '@/errors/scenes/wrong-route' {
  export function init(sceneRoot: HTMLElement): void;
}

declare module '@/client/swal' {
  export function cf4Confirm(options?: Record<string, unknown>): Promise<{ isConfirmed: boolean }>;
}

export {};
