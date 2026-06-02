export type CardImagePayload = {
  fallback: string;
  desktopWebp?: string | null;
  mobileWebp?: string | null;
  usesPlaceholder: boolean;
  placeholderIconClass: string;
};

type ImageFallbackProps = {
  image: CardImagePayload;
  alt: string;
};

export function ImageFallback({ image, alt }: ImageFallbackProps) {
  if (image.usesPlaceholder) {
    return (
      <div className="product-media-placeholder product-media-placeholder--card" aria-label={alt}>
        <i className={image.placeholderIconClass} aria-hidden="true" />
      </div>
    );
  }

  return (
    <picture>
      {image.mobileWebp ? (
        <source type="image/webp" media="(max-width: 767px)" srcSet={image.mobileWebp} />
      ) : null}
      {image.desktopWebp ? <source type="image/webp" srcSet={image.desktopWebp} /> : null}
      <img src={image.fallback} alt={alt} loading="lazy" decoding="async" />
    </picture>
  );
}
