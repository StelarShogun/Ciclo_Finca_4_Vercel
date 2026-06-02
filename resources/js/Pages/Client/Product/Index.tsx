import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';

import { ProductStarsInline } from '@/Components/Product/ProductStarsInline';
import { ResponsivePicture } from '@/Components/Product/ResponsivePicture';
import { ClientLayout } from '@/Layouts/ClientLayout';
import { useProductPageInit } from '@/hooks/useProductPageInit';
import type { InertiaSharedProps } from '@/types/models';
import type { ProductDetailPageProps, ProductReviewRow } from '@/types/product';

import '../../../../css/client/clients-page.css';
import '../../../../css/client/product-badges.css';
import '../../../../css/client/product-detail.css';

export default function ProductIndex(props: ProductDetailPageProps) {
  const page = usePage<InertiaSharedProps>();
  const { auth } = page.props;
  const [activeTab, setActiveTab] = useState(props.tabs.defaultTab);

  useProductPageInit();

  useEffect(() => {
    if (auth.client) {
      window.catalogFavoriteConfig = { toggleUrl: props.favoriteConfig.toggleUrl };
    } else {
      window.catalogFavoriteConfig = { loginUrl: props.favoriteConfig.loginUrl };
    }
  }, [auth.client, props.favoriteConfig]);

  const productPath = `/product/${props.product.id}/${props.product.slug}`;

  return (
    <>
      <Head title={`${props.product.name} - Ciclo Finca 4`}>
        <link rel="canonical" href={props.seo.canonicalUrl} />
        <meta name="description" content={props.seo.description} />
        <meta name="robots" content={props.seo.robots} />
        <meta property="og:title" content={`${props.product.name} | Ciclo Finca 4`} />
        <meta property="og:description" content={props.seo.description} />
        <meta property="og:url" content={props.seo.canonicalUrl} />
        <meta property="og:type" content="product" />
        <meta property="og:image" content={props.seo.ogImage} />
        <meta name="twitter:card" content="summary_large_image" />
      </Head>
      <ClientLayout>
        <div className="product-detail-container product-detail-page">
          <div className="container">
            <nav className="breadcrumb product-detail-breadcrumb" aria-label="Ruta de navegación">
              <Link href="/">Inicio</Link>
              <span aria-hidden="true">/</span>
              <Link href="/catalog">Catálogo</Link>
              {props.taxonomy.parentCategory ? (
                <>
                  <span aria-hidden="true">/</span>
                  <Link href={props.taxonomy.parentCategory.url}>{props.taxonomy.parentCategory.name}</Link>
                </>
              ) : null}
              {props.taxonomy.subcategory ? (
                <>
                  <span aria-hidden="true">/</span>
                  <Link href={props.taxonomy.subcategory.url}>{props.taxonomy.subcategory.name}</Link>
                </>
              ) : null}
              <span aria-hidden="true">/</span>
              <span aria-current="page">{props.product.name}</span>
            </nav>

            <div className="product-detail-layout product-detail-hero">
              <ProductGallery product={props.product} />
              <ProductPurchasePanel
                authClient={auth.client}
                isNovelty={props.isNoveltyProduct}
                orderReservationHours={props.orderReservationHours}
                primaryBrand={props.primaryBrand}
                product={props.product}
                reviewAvg={props.reviews.averageStars ?? 0}
                reviewCount={props.reviews.totalCount}
                taxonomy={props.taxonomy}
                whatsappConsultUrl={props.whatsappConsultUrl}
              />
            </div>

            <ProductTabs
              activeTab={activeTab}
              authClient={auth.client}
              product={props.product}
              productPath={productPath}
              relatedProducts={props.relatedProducts}
              reviews={props.reviews}
              setActiveTab={setActiveTab}
              specs={props.specs}
              tabs={props.tabs}
            />
          </div>
        </div>
      </ClientLayout>
    </>
  );
}

function ProductGallery({ product }: { product: ProductDetailPageProps['product'] }) {
  const slideCount = product.carouselSlides.length;

  return (
    <div className="product-detail-image product-detail-hero__gallery">
      <div className="product-detail-gallery">
        <div
          className={`product-detail-media${product.showImagePlaceholder ? ' product-detail-media--placeholder' : ''}`}
        >
          {product.showImagePlaceholder ? (
            <div className="product-detail-image-placeholder" role="img" aria-label={product.name}>
              <i className={product.placeholderIconClass} aria-hidden="true" />
            </div>
          ) : (
            <div className="product-carousel" id="product-carousel" data-slide-count={slideCount}>
              <div className="carousel-viewport">
                <div className="carousel-track" id="carousel-track">
                  {product.carouselSlides.map((slide, index) => (
                    <div key={`${slide.fallback}-${index}`} className="carousel-slide">
                      <ResponsivePicture
                        alt={product.name}
                        desktopWebp={slide.desktopWebp}
                        mobileWebp={slide.mobileWebp}
                        fallback={slide.fallback}
                        loading={index === 0 ? 'eager' : 'lazy'}
                      />
                    </div>
                  ))}
                </div>
              </div>
              {slideCount > 1 ? (
                <>
                  <button className="carousel-btn carousel-btn--prev" id="carousel-prev" aria-label="Imagen anterior" disabled type="button">
                    <i className="fas fa-chevron-left" aria-hidden="true" />
                  </button>
                  <button className="carousel-btn carousel-btn--next" id="carousel-next" aria-label="Imagen siguiente" type="button">
                    <i className="fas fa-chevron-right" aria-hidden="true" />
                  </button>
                </>
              ) : null}
            </div>
          )}
        </div>

        {!product.showImagePlaceholder && slideCount > 0 ? (
          <div className="product-detail-thumbs" id="product-detail-thumbs" role="list" aria-label="Miniaturas del producto">
            {product.carouselSlides.map((slide, index) => (
              <button
                key={`thumb-${slide.fallback}`}
                type="button"
                className={`product-detail-thumb${index === 0 ? ' is-active' : ''}`}
                data-thumb-index={index}
                role="listitem"
                aria-label={`Ver imagen ${index + 1}`}
                aria-current={index === 0 ? 'true' : 'false'}
              >
                <ResponsivePicture alt="" fallback={slide.fallback} desktopWebp={slide.desktopWebp} mobileWebp={slide.mobileWebp} />
              </button>
            ))}
          </div>
        ) : null}
      </div>
    </div>
  );
}

function ProductPurchasePanel({
  authClient,
  isNovelty,
  orderReservationHours,
  primaryBrand,
  product,
  reviewAvg,
  reviewCount,
  taxonomy,
  whatsappConsultUrl,
}: {
  product: ProductDetailPageProps['product'];
  taxonomy: ProductDetailPageProps['taxonomy'];
  primaryBrand: ProductDetailPageProps['primaryBrand'];
  isNovelty: boolean;
  whatsappConsultUrl: string | null;
  orderReservationHours: number;
  reviewAvg: number;
  reviewCount: number;
  authClient: InertiaSharedProps['auth']['client'];
}) {
  const stockModifier =
    product.stockLabel === 'En stock'
      ? 'stock'
      : product.stockLabel === 'Últimas unidades'
        ? 'low-stock'
        : product.stockLabel === 'Agotado'
          ? 'out-stock'
          : 'unavailable';

  return (
    <div className="product-detail-info product-detail-hero__buy">
      <aside className="product-detail-purchase-panel" aria-label="Comprar producto">
        <div className="product-detail-badges" aria-label="Información rápida del producto">
          {taxonomy.parentCategory ? (
            <Link href={taxonomy.parentCategory.url} className="product-badge product-badge--category product-detail-badge product-detail-badge--category">
              <i className="fas fa-layer-group product-badge__icon" aria-hidden="true" />
              {taxonomy.parentCategory.name}
            </Link>
          ) : null}
          {taxonomy.subcategory ? (
            <Link href={taxonomy.subcategory.url} className="product-badge product-badge--subcategory product-detail-badge product-detail-badge--subcategory">
              <i className="fas fa-tag product-badge__icon" aria-hidden="true" />
              {taxonomy.subcategory.name}
            </Link>
          ) : null}
          {primaryBrand ? (
            <Link href={primaryBrand.catalogUrl} className="product-badge product-badge--brand product-detail-badge product-detail-badge--brand">
              <i className="fas fa-tag product-badge__icon" aria-hidden="true" />
              {primaryBrand.name}
            </Link>
          ) : null}
          <span className={`product-badge product-badge--${stockModifier} product-detail-badge product-detail-badge--stock`}>
            <i className="fas fa-check-circle product-badge__icon" aria-hidden="true" />
            {product.stockLabel}
          </span>
          {product.isFeatured ? (
            <span className="product-badge product-badge--featured product-detail-badge product-detail-badge--featured">
              <i className="fas fa-star product-badge__icon" aria-hidden="true" />
              Destacado
            </span>
          ) : null}
          {isNovelty ? (
            <span className="product-badge product-badge--new product-detail-badge product-detail-badge--novelty">
              <i className="fas fa-bolt product-badge__icon" aria-hidden="true" />
              Novedad
            </span>
          ) : null}
        </div>

        <h1 className="product-detail-name">{product.name}</h1>
        {product.sku ? <p className="product-detail-sku">SKU: {product.sku}</p> : null}

        <div className="product-detail-rating-summary">
          <ProductStarsInline avgStars={reviewAvg} reviewCount={reviewCount} variant="detail" emptyLabel="Aún no hay valoraciones" />
        </div>

        <div className="product-detail-price" data-unit-price={product.price}>
          <span className="product-detail-price__label">Precio</span>
          <span className="product-detail-price__amount">{product.priceFormatted}</span>
        </div>

        <ProductStockCard product={product} />

        {product.canBuy ? (
          <div className="product-detail-actions">
            <div className="product-detail-qty">
              <label className="product-detail-qty__label" htmlFor="product-quantity">
                Cantidad
              </label>
              <div className="product-detail-qty-stepper quantity-controls">
                <button type="button" className="quantity-btn product-detail-qty-stepper__btn" id="decrease-qty" aria-label="Disminuir cantidad">
                  <i className="fas fa-minus" aria-hidden="true" />
                </button>
                <input
                  type="number"
                  id="product-quantity"
                  className="quantity-input product-detail-qty-stepper__input"
                  defaultValue={1}
                  min={1}
                  max={product.stockCurrent}
                  inputMode="numeric"
                  aria-describedby="product-qty-max-hint product-qty-subtotal"
                />
                <button type="button" className="quantity-btn product-detail-qty-stepper__btn" id="increase-qty" aria-label="Aumentar cantidad">
                  <i className="fas fa-plus" aria-hidden="true" />
                </button>
              </div>
              <p className="product-detail-qty__hint" id="product-qty-max-hint">
                Máximo disponible: {product.stockCurrent.toLocaleString('es-CR')} unidades
              </p>
              <p className="product-detail-qty__subtotal" id="product-qty-subtotal" aria-live="polite">
                Subtotal: {product.priceFormatted}
              </p>
            </div>

            <div className="product-detail-actions__buttons">
              {authClient ? (
                <>
                  <button
                    type="button"
                    className="btn btn-primary btn-lg product-detail-actions__cart add-to-cart-btn"
                    data-purchasable="1"
                    data-product-id={product.id}
                    data-product-name={product.name}
                    data-product-price={product.price}
                    data-product-stock={product.stockCurrent}
                  >
                    <i className="fas fa-cart-plus" aria-hidden="true" />
                    Agregar al carrito
                  </button>
                  <button
                    type="button"
                    className={`product-detail-favorite product-favorite-btn${product.isFavorite ? ' is-active' : ''}`}
                    data-product-favorite-btn
                    data-product-id={product.id}
                    aria-pressed={product.isFavorite}
                    aria-label={product.isFavorite ? 'Quitar de favoritos' : 'Agregar a favoritos'}
                  >
                    <span className="product-detail-favorite__icon" aria-hidden="true">
                      <i className={`${product.isFavorite ? 'fas' : 'far'} fa-heart`} />
                    </span>
                    <span className="product-detail-favorite__label">{product.isFavorite ? 'En favoritos' : 'Agregar a favoritos'}</span>
                  </button>
                </>
              ) : (
                <button type="button" className="btn btn-primary btn-lg product-detail-actions__cart guest-add-btn" data-purchasable="1" data-product-stock={product.stockCurrent}>
                  <i className="fas fa-cart-plus" aria-hidden="true" />
                  Agregar al carrito
                </button>
              )}
              {whatsappConsultUrl ? (
                <a href={whatsappConsultUrl} className="btn btn-outline product-detail-actions__whatsapp" target="_blank" rel="noopener noreferrer">
                  <i className="fab fa-whatsapp" aria-hidden="true" />
                  Consultar por WhatsApp
                </a>
              ) : null}
            </div>
          </div>
        ) : null}

        <ul className="product-detail-trust" aria-label="Beneficios de compra">
          <li className="product-detail-trust__item">
            <span className="product-detail-trust__icon" aria-hidden="true"><i className="fas fa-store" /></span>
            <span className="product-detail-trust__text">Retiro en tienda</span>
          </li>
          <li className="product-detail-trust__item">
            <span className="product-detail-trust__icon" aria-hidden="true"><i className="fas fa-money-bill-wave" /></span>
            <span className="product-detail-trust__text">Pago al retirar</span>
          </li>
          <li className="product-detail-trust__item">
            <span className="product-detail-trust__icon" aria-hidden="true"><i className="fas fa-clock" /></span>
            <span className="product-detail-trust__text">Reserva por {orderReservationHours} horas</span>
          </li>
          <li className="product-detail-trust__item">
            <span className="product-detail-trust__icon" aria-hidden="true"><i className="fas fa-boxes" /></span>
            <span className="product-detail-trust__text">Stock actualizado</span>
          </li>
          {whatsappConsultUrl ? (
            <li className="product-detail-trust__item product-detail-trust__item--whatsapp">
              <span className="product-detail-trust__icon" aria-hidden="true"><i className="fas fa-comment-alt" /></span>
              <span className="product-detail-trust__text">Atención por WhatsApp</span>
            </li>
          ) : null}
        </ul>
      </aside>
    </div>
  );
}

function ProductStockCard({ product }: { product: ProductDetailPageProps['product'] }) {
  const purchasable = product.canBuy;

  return (
    <div
      className={`product-detail-stock-card product-detail-stock-card--${
        purchasable && !product.isLowStock ? 'available' : purchasable && product.isLowStock ? 'low' : 'unavailable'
      }`}
      role="status"
    >
      {purchasable && product.isLowStock ? (
        <>
          <span className="product-detail-stock-card__icon" aria-hidden="true"><i className="fas fa-exclamation-circle" /></span>
          <div className="product-detail-stock-card__text">
            <strong className="product-detail-stock-card__title">Últimas unidades</strong>
            <span className="product-detail-stock-card__subtitle">Solo quedan {product.stockCurrent.toLocaleString('es-CR')} disponibles</span>
          </div>
        </>
      ) : purchasable ? (
        <>
          <span className="product-detail-stock-card__icon" aria-hidden="true"><i className="fas fa-check-circle" /></span>
          <div className="product-detail-stock-card__text">
            <strong className="product-detail-stock-card__title">En stock</strong>
            <span className="product-detail-stock-card__subtitle">{product.stockCurrent.toLocaleString('es-CR')} unidades disponibles</span>
          </div>
        </>
      ) : (
        <>
          <span className="product-detail-stock-card__icon" aria-hidden="true"><i className="fas fa-times-circle" /></span>
          <div className="product-detail-stock-card__text">
            <strong className="product-detail-stock-card__title">{product.stockLabel}</strong>
            <span className="product-detail-stock-card__subtitle">
              {product.stockLabel === 'Agotado'
                ? 'Este producto no tiene unidades disponibles por ahora.'
                : 'No está disponible para compra en este momento.'}
            </span>
          </div>
        </>
      )}
    </div>
  );
}

function ProductTabs({
  activeTab,
  authClient,
  product,
  productPath,
  relatedProducts,
  reviews,
  setActiveTab,
  specs,
  tabs,
}: {
  activeTab: string;
  setActiveTab: (tab: string) => void;
  tabs: ProductDetailPageProps['tabs'];
  product: ProductDetailPageProps['product'];
  specs: ProductDetailPageProps['specs'];
  reviews: ProductDetailPageProps['reviews'];
  relatedProducts: ProductDetailPageProps['relatedProducts'];
  productPath: string;
  authClient: InertiaSharedProps['auth']['client'];
}) {
  return (
    <div className="product-detail-tabs" id="product-detail-tabs" data-default-tab={tabs.defaultTab}>
      <div className="product-detail-tabs__nav" role="tablist" aria-label="Información del producto">
        {(['description', 'specs', 'reviews', 'related'] as const).map((tab) => {
          if (tab === 'description' && !tabs.hasDescription) return null;
          if (tab === 'specs' && !tabs.hasSpecs) return null;
          if (tab === 'related' && !tabs.hasRelated) return null;

          const labels: Record<string, string> = {
            description: 'Descripción',
            specs: 'Especificaciones',
            reviews: 'Reseñas',
            related: 'Relacionados',
          };

          return (
            <button
              key={tab}
              type="button"
              role="tab"
              className={`product-detail-tabs__btn${activeTab === tab ? ' is-active' : ''}`}
              data-tab={tab}
              aria-selected={activeTab === tab}
              onClick={() => setActiveTab(tab)}
            >
              {labels[tab]}
              {tab === 'reviews' && reviews.totalCount > 0 ? (
                <span className="product-detail-tabs__count">{reviews.totalCount}</span>
              ) : null}
            </button>
          );
        })}
      </div>

      <div className="product-detail-tabs__panels">
        {tabs.hasDescription ? (
          <section
            id="product-tab-description"
            className="product-detail-tab-panel"
            data-panel="description"
            role="tabpanel"
            hidden={activeTab !== 'description'}
          >
            {product.description ? (
              <article className="product-detail-card product-detail-description-card">
                <h2 className="product-detail-card__title">Descripción del producto</h2>
                <div className="product-detail-description__body" style={{ whiteSpace: 'pre-wrap' }}>
                  {product.description}
                </div>
              </article>
            ) : (
              <article className="product-detail-card product-detail-description-card product-detail-description-card--empty">
                <h2 className="product-detail-card__title">Descripción del producto</h2>
                <p className="product-detail-description__empty">
                  Este producto aún no tiene una descripción detallada. Consultá con nuestro equipo o revisá las especificaciones técnicas.
                </p>
              </article>
            )}
          </section>
        ) : null}

        {tabs.hasSpecs ? (
          <section id="product-tab-specs" className="product-detail-tab-panel" data-panel="specs" role="tabpanel" hidden={activeTab !== 'specs'}>
            <article className="product-detail-card">
              <h2 className="product-detail-card__title">Características técnicas</h2>
              {specs.length > 0 ? (
                <div className="product-detail-specs__chips">
                  {specs.map((spec, index) => (
                    <span key={`${spec.value}-${index}`} className="product-detail-spec-chip">
                      {spec.dimensionLabel ? <span className="product-detail-spec-chip__label">{spec.dimensionLabel}</span> : null}
                      <span className="product-detail-spec-chip__value">{spec.value}</span>
                    </span>
                  ))}
                </div>
              ) : (
                <p className="product-detail-specs__empty">Pronto publicaremos las características técnicas de este producto.</p>
              )}
            </article>
          </section>
        ) : null}

        <section id="product-tab-reviews" className="product-detail-tab-panel product-detail-reviews" data-panel="reviews" role="tabpanel" hidden={activeTab !== 'reviews'}>
          <ProductReviewsSection authClient={authClient} productId={product.id} productPath={productPath} reviews={reviews} />
        </section>

        {tabs.hasRelated ? (
          <section id="product-tab-related" className="product-detail-tab-panel product-detail-related" data-panel="related" role="tabpanel" hidden={activeTab !== 'related'}>
            <h2 className="product-detail-card__title">Productos relacionados</h2>
            <div className="product-detail-related-scroll">
              <div className="products-grid products-grid--related">
                {relatedProducts.map((related) => (
                  <RelatedProductCard key={related.id} authClient={authClient} related={related} />
                ))}
              </div>
            </div>
          </section>
        ) : null}
      </div>
    </div>
  );
}

function ProductReviewsSection({
  authClient,
  productId,
  productPath,
  reviews,
}: {
  reviews: ProductDetailPageProps['reviews'];
  productPath: string;
  productId: number;
  authClient: InertiaSharedProps['auth']['client'];
}) {
  const reviewForm = useForm({ stars: reviews.clientReviewStars ? String(reviews.clientReviewStars) : '' });

  function submitReview(e: React.FormEvent) {
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

function ReviewRow({ review }: { review: ProductReviewRow }) {
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
        <div className="product-review-item__stars" role="img" aria-label={`${review.stars} de 5 estrellas`}>
          {Array.from({ length: 5 }, (_, index) => (
            <i key={index} className={`${index < review.stars ? 'fas' : 'far'} fa-star`} aria-hidden="true" />
          ))}
        </div>
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

function RelatedProductCard({
  authClient,
  related,
}: {
  related: ProductDetailPageProps['relatedProducts'][number];
  authClient: InertiaSharedProps['auth']['client'];
}) {
  const outOfStock = related.stockLabel === 'Agotado';

  return (
    <article className={`product-card product-card--related${outOfStock ? ' product-card--out-of-stock' : ''}`}>
      <div className="product-image product-image--related">
        {authClient ? (
          <button
            type="button"
            className={`product-favorite-btn${related.isFavorite ? ' is-active' : ''}`}
            data-product-favorite-btn
            data-product-id={related.id}
            aria-pressed={related.isFavorite}
            aria-label={related.isFavorite ? 'Quitar de favoritos' : 'Agregar a favoritos'}
          >
            <i className={`${related.isFavorite ? 'fas' : 'far'} fa-heart`} aria-hidden="true" />
          </button>
        ) : null}
        <Link className="product-image__link" href={related.url} aria-label={`Ver producto: ${related.name}`}>
          {related.image.usesPlaceholder ? (
            <div className="product-media-placeholder" role="img" aria-label={related.name}>
              <i className={related.image.placeholderIconClass} aria-hidden="true" />
            </div>
          ) : (
            <ResponsivePicture
              alt={related.name}
              desktopWebp={related.image.desktopWebp}
              mobileWebp={related.image.mobileWebp}
              fallback={related.image.fallback}
            />
          )}
        </Link>
      </div>
      <div className="product-info">
        <div className="product-card-meta-badges">
          <span className="product-category">{related.categoryName}</span>
          {related.brandName ? <span className="product-card-brand-badge">{related.brandName}</span> : null}
        </div>
        <h3 className="product-name">
          <Link href={related.url}>{related.name}</Link>
        </h3>
        <ProductStarsInline avgStars={related.reviews.avg} reviewCount={related.reviews.count} variant="related" />
        {related.sku ? <p className="product-card-sku">SKU: {related.sku}</p> : null}
        <p className="product-availability-text product-stock-badge">{related.stockLabel}</p>
        {related.canBuy ? (
          <p className="product-stock-qty">{related.stockCurrent.toLocaleString('es-CR')} unidades disponibles</p>
        ) : null}
        <div className="product-footer">
          <div className="product-price">{related.priceFormatted}</div>
          <div className="product-actions">
            <Link href={related.url} className="btn-product btn-ver-detalles">
              <i className="fas fa-arrow-right" aria-hidden="true" />
              Ver detalles
            </Link>
            {related.canBuy ? (
              authClient ? (
                <button
                  type="button"
                  className="btn-product btn-agregar add-to-cart-btn"
                  data-purchasable="1"
                  data-product-id={related.id}
                  data-product-name={related.name}
                  data-product-price={related.price}
                  data-product-stock={related.stockCurrent}
                >
                  <i className="fas fa-cart-plus" aria-hidden="true" />
                  Agregar
                </button>
              ) : (
                <button type="button" className="btn-product btn-agregar guest-add-btn" data-purchasable="1" data-product-stock={related.stockCurrent}>
                  <i className="fas fa-cart-plus" aria-hidden="true" />
                  Agregar
                </button>
              )
            ) : (
              <button type="button" className="btn-product btn-agotado" disabled>
                <i className="fas fa-ban" aria-hidden="true" />
                {related.stockLabel === 'Agotado' ? 'Agotado' : 'No disponible'}
              </button>
            )}
          </div>
        </div>
      </div>
    </article>
  );
}
