import { useEffect } from 'react';

/**
 * Binds legacy catalog behaviors (rail, flyouts, filters, spotlight Swiper, heartbeat).
 * Safe to call on each Catalog mount: header search guards with data-cf4-search-init;
 * category UI re-queries DOM on each init.
 */
export function useCatalogPageInit() {
  useEffect(() => {
    let cancelled = false;

    void Promise.all([
      import('@/client/bundles/catalog.js'),
      import('@/client/clients-catalog-heartbeat.js'),
    ]).then(([catalogModule, heartbeatModule]) => {
      if (cancelled) {
        return;
      }

      catalogModule.initClientCatalogPage();
      heartbeatModule.startCatalogHeartbeat();
    });

    return () => {
      cancelled = true;
    };
  }, []);
}
