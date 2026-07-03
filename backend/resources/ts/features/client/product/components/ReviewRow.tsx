import type { ProductReviewRow } from '@/features/client/product/types';
import { DECORATIVE_IMAGE_SRC } from '@/shared/lib/decorativeImage';

type ReviewRowProps = {
  review: ProductReviewRow;
};

export function ReviewRow({ review }: ReviewRowProps) {
  return (
    <article className={`product-review-item${review.mine ? ' product-review-item--mine' : ''}`}>
      {review.mine ? <div className="product-review-item__badge-mine">Tu reseña</div> : null}
      <div className="product-review-item__head">
        <strong className="product-review-item__author">{review.author}</strong>
        {review.publishedAt ? (
          <time className="product-review-item__date" dateTime={review.publishedAtIso ?? undefined}>
            {review.publishedAt}
          </time>
        ) : null}
      </div>
      <div className="product-review-item__stars-row">
        <span className="product-review-item__stars">
          <img alt={`${review.stars} de 5 estrellas`} className="sr-only" src={DECORATIVE_IMAGE_SRC} />
          {Array.from({ length: 5 }, (_, index) => (
            <i key={index} className={`${index < review.stars ? 'fas' : 'far'} fa-star`} aria-hidden="true" />
          ))}
        </span>
        {review.verified ? (
          <span className="product-review-item__verified" title="Reseña de un comprador con pedido completado">
            <i className="fas fa-check-circle" aria-hidden="true" />
            <span>Compra verificada</span>
          </span>
        ) : null}
      </div>
    </article>
  );
}
