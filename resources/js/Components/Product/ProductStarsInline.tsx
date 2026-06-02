type ProductStarsInlineProps = {
  avgStars: number;
  reviewCount: number;
  variant?: 'detail' | 'related';
  emptyLabel?: string;
};

export function ProductStarsInline({
  avgStars,
  emptyLabel = 'Aún no hay valoraciones',
  reviewCount,
  variant = 'detail',
}: ProductStarsInlineProps) {
  const rounded = Math.round(avgStars);

  if (reviewCount < 1) {
    return <span className={`product-stars-inline product-stars-inline--${variant} is-empty`}>{emptyLabel}</span>;
  }

  return (
    <div className={`product-stars-inline product-stars-inline--${variant}`} aria-label={`${avgStars.toFixed(1)} de 5, ${reviewCount} reseñas`}>
      <div className="product-stars-inline__stars" aria-hidden="true">
        {Array.from({ length: 5 }, (_, index) => (
          <i key={index} className={`${index < rounded ? 'fas' : 'far'} fa-star`} />
        ))}
      </div>
      <span className="product-stars-inline__count">({reviewCount})</span>
    </div>
  );
}
