import { useEffect, useState } from 'react';

import { useToast } from '@/shared/hooks/useToast';
import { useConfirmDialog } from '@/shared/components/ui/ConfirmDialogProvider';

type Variant = { product_id: number; name: string; sale_price: string; stock_current: number; sku?: string };
type SearchResult = { product_id: number; name: string; sku?: string };

type VariantsManagerProps = {
  baseProductId: number;
  csrfToken: string;
};

export function VariantsManager({ baseProductId, csrfToken }: VariantsManagerProps) {
  const { showToast } = useToast();
  const { confirm } = useConfirmDialog();
  const [variants, setVariants] = useState<Variant[]>([]);
  const [query, setQuery] = useState('');
  const [results, setResults] = useState<SearchResult[]>([]);

  function loadVariants() {
    fetch(`/products/${baseProductId}`, { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
      .then((r) => r.json())
      .then((payload) => {
        if (payload?.success) {
          setVariants(payload.data.variants ?? []);
        }
      })
      .catch(() => undefined);
  }

  useEffect(loadVariants, [baseProductId]);

  useEffect(() => {
    const term = query.trim();
    if (term.length < 2) {
      setResults([]);
      return;
    }
    let active = true;
    const handle = window.setTimeout(() => {
      fetch(`/admin/products/search?q=${encodeURIComponent(term)}`, {
        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      })
        .then((r) => r.json())
        .then((payload) => {
          if (!active) return;
          setResults((payload?.products ?? []).filter((p: SearchResult) => p.product_id !== baseProductId));
        })
        .catch(() => active && setResults([]));
    }, 250);
    return () => {
      active = false;
      window.clearTimeout(handle);
    };
  }, [query, baseProductId]);

  async function addVariant(variantProductId: number) {
    try {
      const response = await fetch(`/products/${baseProductId}/variants`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          Accept: 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': csrfToken,
        },
        body: JSON.stringify({ variant_product_id: variantProductId }),
      });
      const data = await response.json().catch(() => ({}));
      if (!response.ok || !data.success) {
        showToast({ variant: 'error', title: 'No se pudo agregar', message: data.message ?? 'Error al agregar la variante.' });
        return;
      }
      showToast({ variant: 'success', title: 'Variante agregada' });
      setQuery('');
      setResults([]);
      loadVariants();
    } catch {
      showToast({ variant: 'error', title: 'Error', message: 'Error de conexión.' });
    }
  }

  async function removeVariant(variant: Variant) {
    const ok = await confirm({
      title: '¿Quitar variante?',
      text: `Se quitará "${variant.name}" como variante (no elimina el producto).`,
      icon: 'warning',
      confirmText: 'Sí, quitar',
      cancelText: 'Cancelar',
    });
    if (!ok) {
      return;
    }
    try {
      const response = await fetch(`/products/${baseProductId}/variants/${variant.product_id}`, {
        method: 'DELETE',
        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrfToken },
      });
      const data = await response.json().catch(() => ({}));
      if (!response.ok || !data.success) {
        showToast({ variant: 'error', title: 'No se pudo quitar', message: data.message ?? 'Error al quitar la variante.' });
        return;
      }
      showToast({ variant: 'success', title: 'Variante quitada' });
      loadVariants();
    } catch {
      showToast({ variant: 'error', title: 'Error', message: 'Error de conexión.' });
    }
  }

  return (
    <div className="variants-manager">
      <h4>Variantes</h4>
      <div className="variant-search">
        <input
          type="text"
          value={query}
          onChange={(event) => setQuery(event.target.value)}
          placeholder="Buscar producto para agregar como variante (nombre o SKU)…"
        />
        {results.length > 0 ? (
          <ul className="variant-search-results">
            {results.map((result) => (
              <li key={result.product_id}>
                <button type="button" onClick={() => addVariant(result.product_id)}>
                  {result.name} {result.sku ? `· ${result.sku}` : ''}
                </button>
              </li>
            ))}
          </ul>
        ) : null}
      </div>

      {variants.length === 0 ? (
        <p className="text-muted">Sin variantes asociadas.</p>
      ) : (
        <ul className="variants-list">
          {variants.map((variant) => (
            <li key={variant.product_id}>
              <span>
                {variant.name} · stock {variant.stock_current}
              </span>
              <button type="button" className="btn btn-danger-soft" onClick={() => removeVariant(variant)}>
                Quitar
              </button>
            </li>
          ))}
        </ul>
      )}
    </div>
  );
}
