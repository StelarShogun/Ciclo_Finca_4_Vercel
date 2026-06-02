import { Link, router } from '@inertiajs/react';
import { useState } from 'react';

import { ImageFallback } from '@/shared/components/ui/ImageFallback';
import { useToast } from '@/shared/hooks/useToast';
import { addToCart } from '@/features/client/cart/api';
import { toggleFavorite } from '@/features/client/favorites/api';
import type { CatalogProduct } from '@/types/catalog';

type CatalogProductCardProps = {
  product: CatalogProduct;
  csrfToken: string;
  isAuthenticated: boolean;
};

export function CatalogProductCard({ csrfToken, isAuthenticated, product }: CatalogProductCardProps) {
  const [isFavorite, setIsFavorite] = useState(product.isFavorite);
  const [isBusy, setIsBusy] = useState(false);
  const { showToast } = useToast();

  async function handleAddToCart() {
    if (!product.canBuy) {
      showToast({
        variant: 'warning',
        title: 'No disponible',
        message: 'Este producto no está disponible para compra.',
      });
      return;
    }

    if (!isAuthenticated) {
      router.visit('/login');
      return;
    }

    setIsBusy(true);

    try {
      const result = await addToCart(product.id, 1, csrfToken);
      if (!result.success) {
        showToast({
          variant: 'error',
          title: 'No se pudo agregar',
          message: result.message ?? 'No se pudo agregar el producto.',
        });
        return;
      }

      showToast({
        variant: 'success',
        title: 'Producto agregado',
        message: result.message ?? `${product.name} se agregó al carrito.`,
      });
      window.dispatchEvent(new CustomEvent('cf4:cart-count', { detail: { count: result.cartCount } }));
    } catch {
      showToast({
        variant: 'error',
        title: 'Error',
        message: 'No se pudo agregar el producto. Inténtalo de nuevo.',
      });
    } finally {
      setIsBusy(false);
    }
  }

  async function handleFavorite() {
    if (!isAuthenticated) {
      router.visit('/login');
      return;
    }

    setIsBusy(true);

    try {
      const result = await toggleFavorite(product.id, csrfToken);
      if (!result.success) {
        showToast({
          variant: 'error',
          title: 'No se pudo actualizar',
          message: result.message ?? 'No se pudo actualizar el favorito.',
        });
        return;
      }

      setIsFavorite(result.isFavorite);
      showToast({
        variant: 'success',
        title: result.isFavorite ? 'Agregado a favoritos' : 'Quitado de favoritos',
        message: result.message ?? product.name,
      });
    } catch {
      showToast({
        variant: 'error',
        title: 'Error',
        message: 'No se pudo actualizar el favorito.',
      });
    } finally {
      setIsBusy(false);
    }
  }

  return (
    <article className={`product-card product-card--catalog-cf128 ${product.stockLabel === 'Agotado' ? 'product-card--out-of-stock' : ''}`}>
      <div className="product-image product-image--catalog-cf128">
        <button
          type="button"
          className={`product-favorite-btn ${isFavorite ? 'is-active' : ''}`}
          aria-pressed={isFavorite}
          aria-label={isFavorite ? 'Quitar de favoritos' : 'Agregar a favoritos'}
          disabled={isBusy}
          onClick={handleFavorite}
        >
          <i className={isFavorite ? 'fas fa-heart' : 'far fa-heart'} aria-hidden="true" />
        </button>

        <div className="product-image__frame">
          <Link className="product-image__link" href={product.url} aria-label={`Ver producto: ${product.name}`}>
            <ImageFallback image={product.image} alt={product.name} />
          </Link>
          <div className="product-image__hover-overlay" aria-hidden="true">
            <span className="product-image__hover-price">{product.priceFormatted}</span>
          </div>
        </div>

        {product.isFeatured ? <span className="catalog-spotlight-badge spotlight-badge">Destacado</span> : null}
        {!product.isFeatured && product.isNew ? <span className="catalog-spotlight-badge spotlight-badge">Nuevo</span> : null}
      </div>

      <div className="product-info product-info--catalog-cf128">
        <div className="product-category">{product.category?.name ?? 'Sin categoría'}</div>
        <h3 className="product-name">{product.name}</h3>
        {product.brands.length > 0 ? <p className="product-card-sku">Marca: {product.brands.map((brand) => brand.name).join(', ')}</p> : null}
        {product.sku ? <p className="product-card-sku">SKU: {product.sku}</p> : null}

        <p
          className={[
            'product-availability-text',
            'product-stock-badge',
            product.stockLabel === 'En stock' ? 'is-available' : '',
            product.stockLabel === 'Últimas unidades' ? 'is-low' : '',
            product.stockLabel === 'Agotado' ? 'is-out' : '',
            product.stockLabel === 'No disponible' ? 'is-na' : '',
          ]
            .filter(Boolean)
            .join(' ')}
        >
          {product.stockLabel}
        </p>

        {product.description ? <p className="product-description">{product.description}</p> : null}

        <div className="product-footer">
          <div className="product-price">{product.priceFormatted}</div>
          <div className="product-actions">
            <Link href={product.url} className="btn-product btn-ver-detalles">
              Ver producto
            </Link>
            <button
              type="button"
              className={`btn-product ${product.canBuy ? 'btn-agregar' : 'btn-agotado'}`}
              disabled={!product.canBuy || isBusy}
              onClick={handleAddToCart}
            >
              <i className={product.canBuy ? 'fas fa-cart-plus' : 'fas fa-ban'} aria-hidden="true" />
              {product.canBuy ? 'Agregar' : product.stockLabel}
            </button>
          </div>
        </div>

        {null}
      </div>
    </article>
  );
}
