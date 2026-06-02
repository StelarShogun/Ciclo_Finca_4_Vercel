import { Link, router, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';

import { ProductStarsInline } from '@/features/client/product/components/ProductStarsInline';
import { ReviewRow } from '@/features/client/product/components/ReviewRow';
import type { ProductDetailPageProps } from '@/features/client/product/types';
import type { InertiaSharedProps } from '@/shared/types/models';

type ProductReviewsSectionProps = {
  authClient: InertiaSharedProps['auth']['client'];
  productId: number;
  productPath: string;
  reviews: ProductDetailPageProps['reviews'];
};

export function ProductReviewsSection({ authClient, productId, productPath, reviews }: ProductReviewsSectionProps) {
  const reviewForm = useForm({ stars: reviews.clientReviewStars ? String(reviews.clientReviewStars) : '' });

  function submitReview(e: FormEvent) {
    e.preventDefault();
    reviewForm.post(`/products/${productId}/review`, { preserveScroll: true });
  }

  function visitWithReviews(query: Record<string, string>) {
    router.get(productPath, query, { preserveScroll: true, preserveState: true });
  }

  return (
    <div className="product-detail-reviews">
      <div className="product-detail-reviews-hero">
        <div className="product-detail-reviews-hero__score">
          <span className="product-detail-reviews-hero__average" aria-hidden="true">
            {(reviews.averageStars ?? 0).toFixed(1)}
          </span>
          <ProductStarsInline avgStars={reviews.averageStars ?? 0} reviewCount={reviews.totalCount} variant="detail" emptyLabel="Sin valoraciones" />
        </div>
      </div>

      {authClient && reviews.clientCanReview ? (
        <form id="product-review-form" className="product-detail-review-form" onSubmit={submitReview}>
          <label htmlFor="stars" className="product-detail-review-form__label">
            <strong>Escribir reseña</strong> (1 a 5 estrellas)
          </label>
          <div className="product-detail-review-form__row">
            <select
              id="stars"
              name="stars"
              className="form-control product-detail-review-form__select"
              value={reviewForm.data.stars}
              onChange={(e) => reviewForm.setData('stars', e.target.value)}
            >
              <option value="">Selecciona una calificación</option>
              {[1, 2, 3, 4, 5].map((star) => (
                <option key={star} value={star}>
                  {star} estrella{star > 1 ? 's' : ''}
                </option>
              ))}
            </select>
            <button type="submit" className="btn btn-primary" disabled={reviewForm.processing}>
              {reviews.clientReviewStars ? 'Actualizar reseña' : 'Publicar reseña'}
            </button>
          </div>
        </form>
      ) : null}

      {reviews.totalCount > 0 ? (
        <>
          <div className="product-reviews-toolbar">
            <div className="product-reviews-toolbar__body">
              <nav className="product-reviews-sort" aria-label="Ordenar reseñas">
                <div className="product-reviews-sort__chips">
                  {(['recent', 'stars_high', 'stars_low'] as const).map((sort) => (
                    <button
                      key={sort}
                      type="button"
                      className={`product-reviews-sort__chip${reviews.sort === sort ? ' is-active' : ''}`}
                      onClick={() => visitWithReviews({ reviews_sort: sort, review_filter: reviews.filter })}
                    >
                      {sort === 'recent' ? 'Más recientes' : sort === 'stars_high' ? 'Mayor calificación' : 'Menor calificación'}
                    </button>
                  ))}
                </div>
              </nav>
            </div>
          </div>

          {reviews.showMyHighlighted && reviews.myHighlighted ? (
            <div className="product-reviews-highlight" role="region" aria-label="Tu reseña">
              <ReviewRow review={reviews.myHighlighted} />
            </div>
          ) : null}

          <div className="product-reviews-list">
            {reviews.items.map((review) => (
              <ReviewRow key={review.id} review={review} />
            ))}
          </div>

          {reviews.pagination.lastPage > 1 ? (
            <nav className="product-reviews-pagination" aria-label="Paginación de reseñas">
              {reviews.pagination.links.map((link, index) =>
                link.url ? (
                  <Link
                    key={`${link.label}-${index}`}
                    href={link.url}
                    className={`button${link.active ? ' button-primary' : ''}`}
                    preserveScroll
                  >
                    {link.label.replace(/<[^>]*>/g, '').trim()}
                  </Link>
                ) : (
                  <span key={`ellipsis-${index}`} className="button admin-pagination-ellipsis" aria-hidden="true">
                    …
                  </span>
                ),
              )}
            </nav>
          ) : null}
        </>
      ) : null}
    </div>
  );
}
