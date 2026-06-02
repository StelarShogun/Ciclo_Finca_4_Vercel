import { Link } from '@inertiajs/react';

import { CartQuantitySelector } from '@/features/client/cart/components/CartQuantitySelector';
import { DECORATIVE_IMAGE_SRC } from '@/shared/lib/decorativeImage';
import type { CartItem } from '@/features/client/cart/types';

type CartItemRowProps = {
  item: CartItem;
  isBusy?: boolean;
  onQuantityChange: (item: CartItem, quantity: number) => void;
  onRemove: (item: CartItem) => void;
};

export function CartItemRow({ isBusy = false, item, onQuantityChange, onRemove }: CartItemRowProps) {
  const productUrl = item.productUrl ?? `/catalog?search=${encodeURIComponent(item.name)}`;

  return (
    <li className="cart-item" data-product-id={item.productId}>
      <Link href={productUrl} className="cart-item-image" tabIndex={-1} aria-hidden="true">
        {item.image.usesPlaceholder ? (
          <span className="product-media-placeholder product-media-placeholder--cart">
            <img alt={`Sin imagen: ${item.name}`} className="sr-only" src={DECORATIVE_IMAGE_SRC} />
            <i className={item.image.placeholderIconClass} aria-hidden="true" />
          </span>
        ) : (
          <picture>
            {item.image.mobileWebp ? (
              <source type="image/webp" media="(max-width: 767px)" srcSet={item.image.mobileWebp} />
            ) : null}
            {item.image.desktopWebp ? <source type="image/webp" srcSet={item.image.desktopWebp} /> : null}
            <img
              src={item.image.fallback}
              alt=""
              data-fallback-src="/favicon.svg"
              onError={(event) => {
                const img = event.currentTarget;
                img.src = img.dataset.fallbackSrc ?? '/favicon.svg';
              }}
            />
          </picture>
        )}
      </Link>

      <div className="cart-item-main">
        <h3 className="item-name">
          <Link href={productUrl}>{item.name}</Link>
        </h3>
        <div className="cart-item-meta">
          <span className="item-price">
            {item.unitPriceFormatted}
            <span className="item-price-unit">c/u</span>
          </span>
          <span className="item-stock-badge" title="Stock disponible en tienda">
            <i className="fas fa-boxes-stacked" aria-hidden="true" />
            {item.stockCurrent} disponibles
          </span>
        </div>
      </div>

      <CartQuantitySelector item={item} disabled={isBusy} onChange={(quantity) => onQuantityChange(item, quantity)} />

      <div className="cart-item-right">
        <div className="item-subtotal">
          <span className="subtotal-label">Subtotal</span>
          <span className="subtotal-amount">{item.subtotalFormatted}</span>
        </div>
        <button
          type="button"
          className="btn btn-icon-danger cart-remove-item"
          title="Quitar del carrito"
          aria-label={`Quitar ${item.name} del carrito`}
          disabled={isBusy}
          onClick={() => onRemove(item)}
        >
          <i className="fas fa-trash-alt" aria-hidden="true" />
        </button>
      </div>
    </li>
  );
}
