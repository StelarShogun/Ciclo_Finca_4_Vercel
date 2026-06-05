import { useRef, useSyncExternalStore } from 'react';

/**
 * Server cart count from Inertia, with optional override from cf4:cart-count DOM events.
 */
export function useLiveCartCount(serverCartCount: number): number {
  const overrideRef = useRef<number | null>(null);
  const lastServerRef = useRef(serverCartCount);

  if (lastServerRef.current !== serverCartCount) {
    lastServerRef.current = serverCartCount;
    overrideRef.current = null;
  }

  return useSyncExternalStore(
    (notify) => {
      function onCartCount(event: Event) {
        const customEvent = event as CustomEvent<{ count?: number }>;
        if (typeof customEvent.detail?.count !== 'number') {
          return;
        }

        overrideRef.current = customEvent.detail.count;
        notify();
      }

      window.addEventListener('cf4:cart-count', onCartCount);

      return () => window.removeEventListener('cf4:cart-count', onCartCount);
    },
    () => overrideRef.current ?? serverCartCount,
    () => serverCartCount,
  );
}
