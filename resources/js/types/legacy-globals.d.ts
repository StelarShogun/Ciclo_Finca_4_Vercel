/** Minimal globals for legacy DOM scripts migrated from JS to TS. */
declare global {
  interface Window {
    Swal?: {
      fire: (...args: unknown[]) => Promise<unknown>;
      mixin?: (...args: unknown[]) => unknown;
    };
    Chart?: unknown;
    bootstrap?: unknown;
    grecaptcha?: {
      ready: (cb: () => void) => void;
      execute: (siteKey: string, options: { action: string }) => Promise<string>;
    };
    cf4ShowToast?: (payload: {
      variant: 'success' | 'error' | 'warning' | 'info';
      title: string;
      message?: string;
      durationMs?: number;
    }) => void;
    cf4AuthWelcomeToast?: (payload: unknown) => void;
    cf4SyncHeaderSearchSuggestionsPosition?: () => void;
    __cf4ClientPageJsLoaded?: boolean;
    axios?: unknown;
  }
}

export {};
