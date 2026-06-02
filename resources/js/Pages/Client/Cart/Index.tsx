import { Head, Link } from '@inertiajs/react';

import { CatalogPagination } from '@/Components/Catalog/CatalogPagination';
import { ClientLayout } from '@/Layouts/ClientLayout';
import { useCartPageInit } from '@/hooks/useCartPageInit';
import type { CartPageProps } from '@/types/cart';

import '../../../../css/client/clients-page.css';
import '../../../../css/client/legal-pages.css';

export default function CartIndex({
  items,
  pagination,
  pickupPolicyLine,
  pickupPolicyNotice,
  stockAdjustedMessage,
  totalFormatted,
}: CartPageProps) {
  useCartPageInit();

  const hasItems = pagination.total > 0;

  return (
    <>
      <Head title="Carrito de Compras - Ciclo Finca 4" />
      <ClientLayout>
        <section className="cart-shell" aria-labelledby="cart-page-title">
          <header className="cart-hero">
            <div className="container cart-hero-inner">
              <p className="cart-hero-kicker">Ciclo Finca 4</p>
              <h1 id="cart-page-title" className="cart-hero-title">
                Tu carrito
              </h1>
              <p className="cart-hero-subtitle">Revisá cantidades, elegí cómo pagar y confirmá cuando estés listo.</p>
            </div>
          </header>

          <div className="container cart-body">
            <nav className="breadcrumb" aria-label="Migas de pan">
              <Link href="/">Inicio</Link>
              <span>/</span>
              <span>Carrito</span>
            </nav>

            {stockAdjustedMessage ? (
              <div className="cart-flash cart-flash--warning" role="alert">
                <i className="fas fa-triangle-exclamation" aria-hidden="true" />
                <span>{stockAdjustedMessage}</span>
              </div>
            ) : null}

            <aside className="cf4-pickup-policy cf4-pickup-policy--highlight" aria-label="Política de retiro en tienda">
              <div className="cf4-pickup-policy__head">
                <i className="fas fa-store" aria-hidden="true" />
                <strong>Retiro en tienda</strong>
              </div>
              <p className="cf4-pickup-policy__text">{pickupPolicyNotice}</p>
            </aside>

            <div className="cart-page-card">
              <div className="cart-toolbar">
                <div className="cart-toolbar-text">
                  <span className="cart-toolbar-label">Resumen rápido</span>
                  {hasItems ? (
                    <span className="cart-toolbar-count">
                      {pagination.total} {pagination.total === 1 ? 'artículo' : 'artículos'}
                    </span>
                  ) : null}
                </div>

                <div className="cart-toolbar-actions">
                  <Link href="/catalog" className="btn btn-ghost-cart">
                    <i className="fas fa-bicycle" aria-hidden="true" />
                    Seguir comprando
                  </Link>

                  {hasItems ? (
                    <button type="button" className="btn btn-outline-danger btn-sm" id="btn-clear-cart">
                      <i className="fas fa-trash-alt" aria-hidden="true" />
                      Vaciar carrito
                    </button>
                  ) : null}
                </div>
              </div>

              {hasItems ? (
                <div className="cart-layout">
                  <div className="cart-items-panel">
                    <div className="cart-items" role="list" aria-label="Productos en el carrito">
                      {items.map((item) => (
                        <article
                          key={item.product_id}
                          className="cart-item"
                          role="listitem"
                          data-product-id={item.product_id}
                        >
                          <Link href={item.product_url} className="cart-item-image" tabIndex={-1} aria-hidden="true">
                            {item.uses_placeholder_image ? (
                              <div
                                className="product-media-placeholder product-media-placeholder--cart"
                                role="img"
                                aria-label={`Sin imagen: ${item.name}`}
                              >
                                <i className={item.placeholder_icon_class} aria-hidden="true" />
                              </div>
                            ) : (
                              <img
                                src={item.image_url}
                                alt=""
                                data-fallback-src="/favicon.svg"
                                onError={(e) => {
                                  const img = e.currentTarget;
                                  img.src = img.dataset.fallbackSrc ?? '/favicon.svg';
                                }}
                              />
                            )}
                          </Link>

                          <div className="cart-item-main">
                            <h3 className="item-name">
                              <Link href={item.product_url}>{item.name}</Link>
                            </h3>
                            <div className="cart-item-meta">
                              <span className="item-price">
                                {item.priceFormatted}
                                <span className="item-price-unit">c/u</span>
                              </span>
                              <span className="item-stock-badge" title="Stock disponible en tienda">
                                <i className="fas fa-boxes-stacked" aria-hidden="true" />
                                {item.stock_available} disponibles
                              </span>
                            </div>
                          </div>

                          <div className="item-controls" aria-label="Cantidad">
                            <span className="item-controls-label" id={`qty-label-${item.product_id}`}>
                              Cantidad
                            </span>
                            <div className="quantity-controls cart-qty-controls">
                              <button
                                type="button"
                                className="quantity-btn"
                                data-action="decrease"
                                data-product-id={item.product_id}
                                aria-label="Disminuir cantidad"
                              >
                                <i className="fas fa-minus" aria-hidden="true" />
                              </button>
                              <input
                                type="number"
                                className="quantity-input"
                                defaultValue={item.quantity}
                                min={1}
                                max={item.stock_available}
                                data-product-id={item.product_id}
                                aria-labelledby={`qty-label-${item.product_id}`}
                              />
                              <button
                                type="button"
                                className="quantity-btn"
                                data-action="increase"
                                data-product-id={item.product_id}
                                aria-label="Aumentar cantidad"
                              >
                                <i className="fas fa-plus" aria-hidden="true" />
                              </button>
                            </div>
                          </div>

                          <div className="cart-item-right">
                            <div className="item-subtotal">
                              <span className="subtotal-label">Subtotal</span>
                              <span className="subtotal-amount">{item.subtotalFormatted}</span>
                            </div>
                            <button
                              type="button"
                              className="btn btn-icon-danger cart-remove-item"
                              data-product-id={item.product_id}
                              data-product-name={item.name}
                              title="Quitar del carrito"
                              aria-label={`Quitar ${item.name} del carrito`}
                            >
                              <i className="fas fa-trash-alt" aria-hidden="true" />
                            </button>
                          </div>
                        </article>
                      ))}
                    </div>

                    {pagination.lastPage > 1 ? (
                      <div className="cart-pagination-wrap">
                        <CatalogPagination pagination={pagination} />
                      </div>
                    ) : null}
                  </div>

                  <aside className="cart-summary" aria-labelledby="cart-summary-title">
                    <div className="summary-card">
                      <h2 id="cart-summary-title" className="summary-title">
                        Total del pedido
                      </h2>

                      <fieldset className="cart-payment-fieldset">
                        <legend className="cart-payment-legend" id="cart-payment-legend">
                          Forma de pago
                        </legend>
                        <p className="cart-payment-hint">Podés cambiarla luego; usamos esto para preparar tu pedido.</p>
                        <div className="cart-payment-options" role="radiogroup" aria-labelledby="cart-payment-legend">
                          {(['cash', 'sinpe', 'transfer'] as const).map((method, index) => (
                            <label key={method} className="cart-payment-option">
                              <input
                                type="radio"
                                name="checkout_payment_method"
                                value={method}
                                className="cart-payment-input"
                                defaultChecked={index === 0}
                              />
                              <span className="cart-payment-card">
                                <i
                                  className={
                                    method === 'cash'
                                      ? 'fas fa-money-bill-wave'
                                      : method === 'sinpe'
                                        ? 'fas fa-mobile-screen-button'
                                        : 'fas fa-building-columns'
                                  }
                                  aria-hidden="true"
                                />
                                <span className="cart-payment-label">
                                  {method === 'cash' ? 'Efectivo' : method === 'sinpe' ? 'SINPE Móvil' : 'Transferencia'}
                                </span>
                              </span>
                            </label>
                          ))}
                        </div>
                      </fieldset>

                      <div className="summary-details">
                        <div className="summary-row">
                          <span>Subtotal</span>
                          <span id="cart-subtotal">{totalFormatted}</span>
                        </div>
                        <div className="summary-row summary-row--muted">
                          <span>Impuestos</span>
                          <span id="cart-taxes">Incluidos / no aplican</span>
                        </div>
                        <div className="summary-row summary-total">
                          <span>Total estimado</span>
                          <span id="cart-total-amount">{totalFormatted}</span>
                        </div>
                      </div>

                      <div className="summary-actions">
                        <button type="button" className="btn btn-primary btn-block btn-lg" id="proceed-checkout">
                          <i className="fas fa-check" aria-hidden="true" />
                          Confirmar pedido
                        </button>
                        <p className="checkout-note">
                          <i className="fas fa-circle-info" aria-hidden="true" />
                          {pickupPolicyLine}
                        </p>
                      </div>
                    </div>
                  </aside>
                </div>
              ) : (
                <div className="cart-empty">
                  <div className="cart-empty-inner">
                    <div className="cart-empty-icon" aria-hidden="true">
                      <i className="fas fa-cart-shopping" />
                    </div>
                    <h2 className="cart-empty-title">Tu carrito está vacío</h2>
                    <p className="cart-empty-text">Explorá el catálogo y agregá productos para armar tu solicitud.</p>
                    <div className="cart-empty-actions">
                      <Link href="/catalog" className="btn btn-primary btn-lg">
                        <i className="fas fa-bicycle" aria-hidden="true" />
                        Ir al catálogo
                      </Link>
                      <Link href="/catalog#catalog-spotlight-heading" className="btn btn-ghost-cart btn-lg">
                        <i className="fas fa-star" aria-hidden="true" />
                        Ver destacados
                      </Link>
                    </div>
                    <p className="cart-empty-home-link">
                      <Link href="/" className="cart-empty-home-anchor">
                        Volver al inicio
                      </Link>
                    </p>
                  </div>
                </div>
              )}
            </div>
          </div>
        </section>
      </ClientLayout>
    </>
  );
}
