import { useEffect } from 'react';

export function useProductPageInit() {
  useEffect(() => {
    let cancelled = false;

    void import('@/client/bundles/product.js').then((module) => {
      if (!cancelled) {
        module.initClientProductPage();
      }
    });

    return () => {
      cancelled = true;
    };
  }, []);
}
