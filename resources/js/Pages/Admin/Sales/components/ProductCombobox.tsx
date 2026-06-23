import { useEffect, useRef, useState } from 'react';

import type { SaleProductOption } from '../types';

const colones = new Intl.NumberFormat('es-CR', { maximumFractionDigits: 0 });

function formatColones(amount: number): string {
  return `₡${colones.format(Math.round(amount || 0))}`;
}

type Props = {
  /** Display label shown in the input once a product is picked. */
  selectedLabel: string;
  invalid?: boolean;
  onSelect: (product: SaleProductOption) => void;
  onClear: () => void;
};

export function ProductCombobox({ selectedLabel, invalid, onSelect, onClear }: Props) {
  const [term, setTerm] = useState('');
  const [open, setOpen] = useState(false);
  const [results, setResults] = useState<SaleProductOption[]>([]);
  const [loading, setLoading] = useState(false);
  const [picked, setPicked] = useState(false);
  const seqRef = useRef(0);
  const wrapRef = useRef<HTMLDivElement>(null);

  useEffect(() => {
    function onDocClick(event: MouseEvent) {
      if (wrapRef.current && !wrapRef.current.contains(event.target as Node)) {
        setOpen(false);
      }
    }
    document.addEventListener('mousedown', onDocClick);
    return () => document.removeEventListener('mousedown', onDocClick);
  }, []);

  async function runSearch(q: string) {
    const seq = ++seqRef.current;
    setLoading(true);
    try {
      const params = new URLSearchParams({ q, context: 'sale' });
      const res = await fetch(`/admin/products/search?${params.toString()}`, {
        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      });
      const data = await res.json().catch(() => ({}));
      if (seq !== seqRef.current) {
        return;
      }
      setResults(Array.isArray(data.products) ? data.products : Array.isArray(data) ? data : []);
    } catch {
      if (seq === seqRef.current) {
        setResults([]);
      }
    } finally {
      if (seq === seqRef.current) {
        setLoading(false);
      }
    }
  }

  function handleInput(value: string) {
    setTerm(value);
    setPicked(false);
    setOpen(true);
    void runSearch(value.trim());
  }

  function handlePick(product: SaleProductOption) {
    onSelect(product);
    const stock = product.stock != null ? ` · Stock: ${product.stock}` : '';
    setTerm(`${product.name} — ${formatColones(Number(product.unit_price) || 0)}${stock}`);
    setPicked(true);
    setOpen(false);
  }

  const displayValue = picked ? term : term || selectedLabel;

  return (
    <div
      ref={wrapRef}
      className={`product-combobox sale-product-combobox${invalid ? ' error' : ''}`}
    >
      <input
        type="text"
        className="product-combobox-input sale-product-search"
        placeholder="Buscar por nombre o SKU…"
        autoComplete="off"
        role="combobox"
        aria-expanded={open}
        value={displayValue}
        onFocus={() => {
          setOpen(true);
          if (!picked) {
            void runSearch(term.trim());
          }
        }}
        onChange={(e) => {
          if (picked) {
            onClear();
          }
          handleInput(e.target.value);
        }}
      />
      <i className="fas fa-chevron-down product-combobox-chevron" aria-hidden="true" />
      {open ? (
        <div className="product-combobox-dropdown sale-product-dropdown" role="listbox">
          {loading ? (
            <div className="product-combobox-empty">Buscando…</div>
          ) : results.length === 0 ? (
            <div className="product-combobox-empty">Sin resultados.</div>
          ) : (
            results.map((p) => (
              <button
                type="button"
                key={p.product_id}
                className="product-combobox-option"
                role="option"
                aria-selected="false"
                onClick={() => handlePick(p)}
              >
                <div className="product-combobox-option-name">{p.name}</div>
                <div className="product-combobox-option-meta">
                  <code>{p.sku}</code>
                  <span>{formatColones(Number(p.unit_price) || 0)}</span>
                  {p.stock != null ? <span>Stock: {p.stock}</span> : null}
                </div>
              </button>
            ))
          )}
        </div>
      ) : null}
    </div>
  );
}
