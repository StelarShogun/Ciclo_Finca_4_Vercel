import { Link } from '@inertiajs/react';

import type { FavoriteDrawerItem as FavoriteDrawerItemType } from '@/features/client/favorites/api';
import { DECORATIVE_IMAGE_SRC } from '@/shared/lib/decorativeImage';

type FavoriteDrawerItemProps = {
  item: FavoriteDrawerItemType;
  disabled?: boolean;
  onRemove: (productId: number) => void;
};

export function FavoriteDrawerItem({ disabled = false, item, onRemove }: FavoriteDrawerItemProps) {
  return (
    <article className="cf4-favorite-item" data-favorite-product-id={item.product_id}>
      {item.uses_placeholder_image ? (
        <span className="product-media-placeholder product-media-placeholder--favorite" role="img" aria-label={`Sin imagen: ${item.name}`}>
          <img alt={`Sin imagen: ${item.name}`} className="sr-only" src={DECORATIVE_IMAGE_SRC} />
          <i className={item.placeholder_icon_class} aria-hidden="true" />
        </span>
      ) : (
        <img src={item.image_url ?? ''} alt={item.name} loading="lazy" decoding="async" />
      )}

      <div className="cf4-favorite-meta">
        <div className="cf4-favorite-category">{item.category}</div>
        <Link className="cf4-favorite-name" href={item.url}>
          {item.name}
        </Link>
        <div className="cf4-favorite-price">{item.price_formatted}</div>
      </div>

      <button
        type="button"
        className="cf4-favorite-remove"
        aria-label="Quitar de favoritos"
        disabled={disabled}
        onClick={() => onRemove(item.product_id)}
      >
        <i className="fas fa-heart" />
      </button>
    </article>
  );
}
