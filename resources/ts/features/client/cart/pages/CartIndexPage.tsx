import { Head, Link, usePage } from '@inertiajs/react';
import { useMemo, useState } from 'react';

import { InertiaListPagination } from '@/shared/components/ui/InertiaListPagination';
import { CartEmptyState } from '@/features/client/cart/components/CartEmptyState';
import { CartItemRow } from '@/features/client/cart/components/CartItemRow';
import { CartSummary } from '@/features/client/cart/components/CartSummary';
import { useCartActions } from '@/features/client/cart/hooks/useCartActions';
import type { CartPageProps, CartPaymentMethod } from '@/features/client/cart/types';
import { normalizeCartItems } from '@/features/client/cart/types';
import { ClientLayout } from '@/shared/components/layout/ClientLayout';
import type { InertiaSharedProps } from '@/shared/types/models';

import '../../../../../css/client/clients-page.css';
import '../../../../../css/client/legal-pages.css';

function formatCurrency(amount: number): string {
  return `₡${amount.toLocaleString('es-CR', { maximumFractionDigits: 0 })}`;
}

function cartItemsSyncKey(items: CartPageProps['items']): string {
  return items.map((item) => `${item.productId}:${item.quantity}`).join('|');
}

function CartIndexContent({
  featuredProducts,
  items: initialItems,
  pagination,
  pickupPolicyLine,
  pickupPolicyNotice,
  stockAdjustedMessage,
}: CartPageProps) {
  const { csrfToken } = usePage<InertiaSharedProps>().props;
  const [items, setItems] = useState(() => normalizeCartItems(initialItems));
  const [paymentMethod, setPaymentMethod] = useState<CartPaymentMethod>('cash');

  const subtotal = useMemo(() => items.reduce((sum, item) => sum + item.unitPrice * item.quantity, 0), [items]);
  const subtotalFormatted = formatCurrency(subtotal);
  const totalQuantity = useMemo(() => items.reduce((sum, item) => sum + item.quantity, 0), [items]);
  const hasItems = items.length > 0;

  const cartActions = useCartActions({
    csrfToken,
    items,
    setItems,
    totalFormatted: subtotalFormatted,
  });

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
                      {totalQuantity} {totalQuantity === 1 ? 'artículo' : 'artículos'}
                    </span>
                  ) : null}
                </div>

                <div className="cart-toolbar-actions">
                  <Link href="/catalog" className="btn btn-ghost-cart">
                    <i className="fas fa-bicycle" aria-hidden="true" />
                    Seguir comprando
                  </Link>

                  {hasItems ? (
                    <button
                      type="button"
                      className="btn btn-outline-danger btn-sm"
                      disabled={cartActions.isClearing}
                      onClick={cartActions.clearItems}
                    >
                      <i className="fas fa-trash-alt" aria-hidden="true" />
                      {cartActions.isClearing ? 'Vaciando...' : 'Vaciar carrito'}
                    </button>
                  ) : null}
                </div>
              </div>

              {hasItems ? (
                <div className="cart-layout">
                  <div className="cart-items-panel">
                    <ul className="cart-items" aria-label="Productos en el carrito">
                      {items.map((item) => (
                        <CartItemRow
                          key={item.productId}
                          item={item}
                          isBusy={cartActions.busyItemId === item.productId}
                          onQuantityChange={cartActions.updateQuantity}
                          onRemove={cartActions.removeItem}
                        />
                      ))}
                    </ul>

                    {pagination.lastPage > 1 ? (
                      <div className="cart-pagination-wrap">
                        <InertiaListPagination pagination={pagination} label="carrito" />
                      </div>
                    ) : null}
                  </div>

                  <CartSummary
                    subtotalFormatted={subtotalFormatted}
                    totalFormatted={subtotalFormatted}
                    paymentMethod={paymentMethod}
                    pickupPolicyLine={pickupPolicyLine}
                    isCheckingOut={cartActions.isCheckingOut}
                    onCheckout={() => cartActions.checkout(paymentMethod)}
                    onPaymentMethodChange={setPaymentMethod}
                  />
                </div>
              ) : (
                <CartEmptyState featuredProducts={featuredProducts} />
              )}
            </div>
          </div>
        </section>
      </ClientLayout>
    </>
  );
}

export default function CartIndexPage(props: CartPageProps) {
  return <CartIndexContent key={cartItemsSyncKey(props.items)} {...props} />;
}
