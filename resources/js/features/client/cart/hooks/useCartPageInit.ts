import { useEffect } from 'react';

export function useCartPageInit() {
  useEffect(() => {
    let cancelled = false;

    void import('@/client/bundles/cart.js').then((module) => {
      if (!cancelled) {
        module.initClientCartPage();
      }
    });

    return () => {
      cancelled = true;
    };
  }, []);
}
