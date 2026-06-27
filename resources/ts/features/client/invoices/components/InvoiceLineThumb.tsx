import { DECORATIVE_IMAGE_SRC } from '@/shared/lib/decorativeImage';
import type { InvoiceDetailItem } from '@/types/invoices';

type InvoiceLineThumbProps = {
  item: InvoiceDetailItem;
};

export function InvoiceLineThumb({ item }: InvoiceLineThumbProps) {
  return (
    <span className="cf4-product-thumb">
      {item.image.usesPlaceholder ? (
        <span className="product-media-placeholder product-media-placeholder--thumb-invoice" role="img" aria-label={`Sin imagen: ${item.name}`}>
          <img alt={`Sin imagen: ${item.name}`} className="sr-only" src={DECORATIVE_IMAGE_SRC} />
          <i className={item.image.placeholderIconClass} aria-hidden="true" />
        </span>
      ) : (
        <picture>
          {item.image.mobileWebp ? <source type="image/webp" srcSet={item.image.mobileWebp} /> : null}
          <img src={item.image.fallback} alt={item.name} loading="lazy" decoding="async" />
        </picture>
      )}
    </span>
  );
}
