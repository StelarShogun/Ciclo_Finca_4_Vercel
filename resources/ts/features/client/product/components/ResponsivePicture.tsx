type ResponsivePictureProps = {
  alt: string;
  desktopWebp?: string | null;
  mobileWebp?: string | null;
  fallback: string;
  loading?: 'eager' | 'lazy';
};

export function ResponsivePicture({
  alt,
  desktopWebp,
  fallback,
  loading = 'lazy',
  mobileWebp,
}: ResponsivePictureProps) {
  return (
    <picture>
      {desktopWebp ? <source media="(min-width: 768px)" srcSet={desktopWebp} type="image/webp" /> : null}
      {mobileWebp ? <source srcSet={mobileWebp} type="image/webp" /> : null}
      <img src={fallback} alt={alt} loading={loading} decoding="async" />
    </picture>
  );
}
