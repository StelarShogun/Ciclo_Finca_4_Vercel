import { useEffect, useState } from 'react';

import { Modal } from '@/shared/components/ui/Modal';

type ViewProductModalProps = {
  productId: number | null;
  onClose: () => void;
};

type ProductData = {
  name: string;
  sku?: string;
  description?: string | null;
  sale_price?: number | string;
  purchase_price?: number | string;
  stock_current?: number;
  stock_minimum?: number;
  status?: string;
  category?: { name?: string; parent?: { name?: string } | null } | null;
  media_main?: string;
  media_gallery?: string[];
  uses_placeholder_image?: boolean;
  variants?: Array<{ product_id: number; name: string; sale_price: string; stock_current: number; sku?: string }>;
};

const currency = new Intl.NumberFormat('es-CR', { style: 'currency', currency: 'CRC', maximumFractionDigits: 0 });

export function ViewProductModal({ onClose, productId }: ViewProductModalProps) {
  const [data, setData] = useState<ProductData | null>(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');

  useEffect(() => {
    if (productId === null) {
      setData(null);
      setError('');
      return;
    }
    let active = true;
    setLoading(true);
    setError('');
    fetch(`/products/${productId}`, {
      headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      credentials: 'same-origin',
    })
      .then((response) => response.json())
      .then((payload) => {
        if (!active) return;
        if (payload?.success) {
          setData(payload.data);
        } else {
          setError(payload?.message ?? 'No se pudo cargar el producto.');
        }
      })
      .catch(() => active && setError('Error de conexión al cargar el producto.'))
      .finally(() => active && setLoading(false));
    return () => {
      active = false;
    };
  }, [productId]);

  return (
    <Modal isOpen={productId !== null} onClose={onClose} title="Detalle del producto" className="cf4-modal cf4-modal--wide">
      {loading ? <p className="text-muted">Cargando…</p> : null}
      {error ? <p className="field-error">{error}</p> : null}
      {data ? (
        <div className="view-product-body">
          {!data.uses_placeholder_image && data.media_main ? (
            <img src={data.media_main} alt={data.name} className="view-product-image" width={160} height={160} />
          ) : null}
          <h3>{data.name}</h3>
          {data.sku ? <p className="text-muted">SKU: {data.sku}</p> : null}
          {data.description ? <p>{data.description}</p> : null}
          <dl className="supplier-detail">
            <div><dt>Categoría</dt><dd>{data.category?.parent?.name ? `${data.category.parent.name} → ` : ''}{data.category?.name ?? '—'}</dd></div>
            <div><dt>Precio venta</dt><dd>{currency.format(Number(data.sale_price ?? 0))}</dd></div>
            <div><dt>Precio compra</dt><dd>{currency.format(Number(data.purchase_price ?? 0))}</dd></div>
            <div><dt>Stock</dt><dd>{data.stock_current ?? 0} (mín. {data.stock_minimum ?? 0})</dd></div>
            <div><dt>Estado</dt><dd>{data.status ?? '—'}</dd></div>
          </dl>

          {data.media_gallery && data.media_gallery.length > 0 ? (
            <div className="view-product-gallery">
              {data.media_gallery.map((url) => (
                <img key={url} src={url} alt="" width={72} height={72} />
              ))}
            </div>
          ) : null}

          {data.variants && data.variants.length > 0 ? (
            <div className="view-product-variants">
              <h4>Variantes</h4>
              <ul>
                {data.variants.map((variant) => (
                  <li key={variant.product_id}>
                    {variant.name} — {currency.format(Number(variant.sale_price))} · stock {variant.stock_current}
                  </li>
                ))}
              </ul>
            </div>
          ) : null}
        </div>
      ) : null}
    </Modal>
  );
}
