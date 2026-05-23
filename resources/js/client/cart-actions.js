/**
 * Centralized cart page interactions (CF4 SweetAlert helpers + event delegation).
 */

import {
    cf4Confirm,
    cf4Toast,
    cf4Error,
    cf4Warning,
    escapeHtml,
} from './swal.js';
import {
    buildCf4CheckoutSuccessText,
    getCf4PaymentMethodShortLabel,
} from './checkout-copy.js';
import {
    addToCart,
    getCsrfToken,
    isClientStockShortMessage,
    updateCartCount,
} from './cart-shared.js';

function getCheckoutPaymentMethod() {
    const selected = document.querySelector('input[name="checkout_payment_method"]:checked');
    return selected ? selected.value : 'cash';
}

function formatCartTotal(amount) {
    return amount != null
        ? `₡${Number(amount).toLocaleString('es-CR')}`
        : '₡0';
}

function applyCartTotals(data) {
    const totalFormatted = formatCartTotal(data.cart_total);
    const subtotalEl = document.getElementById('cart-subtotal');
    const totalEl = document.getElementById('cart-total-amount');
    if (subtotalEl) subtotalEl.textContent = totalFormatted;
    if (totalEl) totalEl.textContent = totalFormatted;
    updateCartCount(data.cart_count || 0);
}

export function showCartEmptyState() {
    const card = document.querySelector('.cart-page-card');
    if (!card) return;

    const catalogLink = card.querySelector('a.btn-ghost-cart[href], a[href*="/catalog"]');
    const rawHref = (catalogLink && catalogLink.getAttribute('href')) || '/catalog';
    const catalogBase = rawHref.split('#')[0];
    const spotlightHref = `${catalogBase}#catalog-spotlight-heading`;
    const homeUrl = '/';

    card.innerHTML = `
        <div class="cart-toolbar">
            <div class="cart-toolbar-text">
                <span class="cart-toolbar-label">Resumen rápido</span>
            </div>
            <div class="cart-toolbar-actions">
                <a href="${escapeHtml(catalogBase)}" class="btn btn-ghost-cart">
                    <i class="fas fa-bicycle" aria-hidden="true"></i> Seguir comprando
                </a>
            </div>
        </div>
        <div class="cart-empty">
            <div class="cart-empty-inner">
                <div class="cart-empty-icon" aria-hidden="true"><i class="fas fa-cart-shopping"></i></div>
                <h2 class="cart-empty-title">Tu carrito está vacío</h2>
                <p class="cart-empty-text">Explorá el catálogo y agregá productos para armar tu solicitud.</p>
                <div class="cart-empty-actions">
                    <a href="${escapeHtml(catalogBase)}" class="btn btn-primary btn-lg">
                        <i class="fas fa-bicycle" aria-hidden="true"></i> Ir al catálogo
                    </a>
                    <a href="${escapeHtml(spotlightHref)}" class="btn btn-ghost-cart btn-lg">
                        <i class="fas fa-star" aria-hidden="true"></i> Ver destacados
                    </a>
                </div>
                <p class="cart-empty-home-link">
                    <a href="${escapeHtml(homeUrl)}" class="cart-empty-home-anchor">Volver al inicio</a>
                </p>
            </div>
        </div>`;
}

export function updateCartQuantity(productId, quantity) {
    return fetch('/cart/update', {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': getCsrfToken(),
            'X-Requested-With': 'XMLHttpRequest',
            Accept: 'application/json',
        },
        body: JSON.stringify({ product_id: productId, quantity }),
    })
        .then((res) => res.json().catch(() => ({})))
        .then(async (data) => {
            if (data.success) {
                applyCartTotals(data);

                const cartItem = document.querySelector(`.cart-item[data-product-id="${productId}"]`);
                if (cartItem) {
                    const lineSubtotalEl = cartItem.querySelector('.subtotal-amount');
                    if (lineSubtotalEl && data.line_subtotal != null) {
                        lineSubtotalEl.textContent = `₡${Number(data.line_subtotal).toLocaleString('es-CR')}`;
                    }
                    const qtyInput = cartItem.querySelector('.quantity-input');
                    if (qtyInput && data.quantity_applied != null) {
                        qtyInput.value = data.quantity_applied;
                    }
                    if (data.stock_clamped) {
                        await cf4Warning(
                            `La cantidad se ajustó al stock disponible (${data.quantity_applied} unidades).`,
                            'Stock disponible',
                        );
                    }
                }

                return;
            }

            const umsg = data.message || 'No se pudo actualizar el carrito';
            const uShort = isClientStockShortMessage(umsg);
            if (uShort) {
                await cf4Warning(umsg, 'Atención');
            } else {
                await cf4Error(umsg, 'Error');
            }
        })
        .catch(async () => {
            await cf4Error('Ocurrió un error al actualizar el carrito.', 'Error');
        });
}

function incrementInvoiceBadge() {
    const invoiceLink = document.getElementById('invoices-link');
    if (!invoiceLink) return;

    let badge = document.getElementById('invoice-count');
    const currentVal = badge ? (parseInt(badge.textContent, 10) || 0) : 0;
    const newVal = currentVal + 1;

    if (!badge) {
        badge = document.createElement('span');
        badge.id = 'invoice-count';
        badge.className = 'cf4-invoice-count';
        invoiceLink.appendChild(badge);
    }

    badge.textContent = String(newVal);
    badge.style.display = 'flex';
}

function initProductDetailQuantity() {
    const productQtyInput = document.getElementById('product-quantity');
    if (!productQtyInput) return;

    let productQty = parseInt(productQtyInput.value, 10) || 1;
    const maxQty = parseInt(productQtyInput.max, 10) || 999;

    document.getElementById('decrease-qty')?.addEventListener('click', () => {
        if (productQty > 1) {
            productQty -= 1;
            productQtyInput.value = String(productQty);
        }
    });

    document.getElementById('increase-qty')?.addEventListener('click', () => {
        if (productQty < maxQty) {
            productQty += 1;
            productQtyInput.value = String(productQty);
        }
    });

    productQtyInput.addEventListener('change', function onQtyChange() {
        const value = parseInt(this.value, 10);
        if (value < 1) {
            this.value = '1';
            productQty = 1;
        } else if (value > maxQty) {
            this.value = String(maxQty);
            productQty = maxQty;
        } else {
            productQty = value;
        }
    });

    const detailAddBtn = document.querySelector('.product-detail-actions .add-to-cart-btn');
    if (detailAddBtn) {
        detailAddBtn.addEventListener('click', function onAdd() {
            addToCart(this.dataset.productId, productQty, this);
        });
    }
}

/**
 * Bind cart interactions once per page (delegated clicks + quantity inputs).
 */
export function initCartInteractions() {
    if (window.__cf4CartInteractionsBound) {
        return;
    }

    window.__cf4CartInteractionsBound = true;

    document.addEventListener('click', async (event) => {
        const removeBtn = event.target.closest('.cart-remove-item');
        if (removeBtn) {
            event.preventDefault();

            const cartItem = removeBtn.closest('.cart-item');
            const itemId = removeBtn.dataset.productId;
            const itemName = removeBtn.dataset.productName || 'este producto';
            if (!cartItem || !itemId) return;

            const result = await cf4Confirm({
                title: '¿Eliminar producto?',
                text: `¿Deseas eliminar "${itemName}" del carrito?`,
                icon: 'warning',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar',
                danger: true,
            });

            if (!result.isConfirmed) return;

            removeBtn.disabled = true;

            try {
                const res = await fetch(`/cart/remove/${itemId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': getCsrfToken(),
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });
                const data = await res.json();

                if (!data.success) {
                    await cf4Error(data.message || 'No se pudo eliminar.', 'Error');
                    return;
                }

                cartItem.remove();
                await cf4Toast({
                    icon: 'success',
                    title: 'Producto eliminado del carrito',
                    timer: 2500,
                });
                applyCartTotals(data);

                if (document.querySelectorAll('.cart-item').length === 0) {
                    showCartEmptyState();
                }
            } catch {
                await cf4Error('No se pudo eliminar el producto.', 'Error');
            } finally {
                removeBtn.disabled = false;
            }

            return;
        }

        if (event.target.closest('#btn-clear-cart')) {
            event.preventDefault();

            const result = await cf4Confirm({
                title: '¿Vaciar carrito?',
                text: 'Se eliminarán todos los productos del carrito.',
                icon: 'warning',
                confirmButtonText: 'Sí, vaciar',
                cancelButtonText: 'Cancelar',
                danger: true,
            });

            if (!result.isConfirmed) return;

            const clearBtn = event.target.closest('#btn-clear-cart');
            if (clearBtn) clearBtn.disabled = true;

            try {
                const res = await fetch('/cart/clear', {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': getCsrfToken(),
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });
                const data = await res.json();

                if (data.success) {
                    updateCartCount(0);
                    showCartEmptyState();
                    await cf4Toast({
                        icon: 'success',
                        title: 'Carrito vaciado correctamente',
                        timer: 2500,
                    });
                } else {
                    await cf4Error(data.message || 'No se pudo vaciar el carrito.', 'Error');
                }
            } catch {
                await cf4Error('Ocurrió un error al vaciar el carrito.', 'Error');
            } finally {
                if (clearBtn) clearBtn.disabled = false;
            }

            return;
        }

        const qtyBtn = event.target.closest('.quantity-btn');
        if (qtyBtn) {
            event.preventDefault();

            const action = qtyBtn.dataset.action;
            const productId = qtyBtn.dataset.productId;
            const input = document.querySelector(`.quantity-input[data-product-id="${productId}"]`);
            if (!input) return;

            let quantity = parseInt(input.value, 10) || 1;
            const max = parseInt(input.max, 10) || 999;

            if (action === 'increase') {
                if (quantity >= max) {
                    await cf4Warning('La cantidad no puede exceder el stock disponible.', 'Stock disponible');
                    return;
                }
                quantity += 1;
            } else if (action === 'decrease') {
                if (quantity <= 1) return;
                quantity -= 1;
            }

            input.value = String(quantity);
            qtyBtn.disabled = true;
            try {
                await updateCartQuantity(productId, quantity);
            } finally {
                qtyBtn.disabled = false;
            }
        }
    });

    document.addEventListener('change', async (event) => {
        const input = event.target.closest('.quantity-input');
        if (!input || !input.dataset.productId) return;

        const productId = input.dataset.productId;
        let quantity = parseInt(input.value, 10);
        const max = parseInt(input.max, 10) || 999;

        if (quantity < 1) {
            input.value = '1';
            await updateCartQuantity(productId, 1);
        } else if (quantity > max) {
            input.value = String(max);
            await cf4Warning('La cantidad no puede exceder el stock disponible.', 'Stock disponible');
            await updateCartQuantity(productId, max);
        } else {
            await updateCartQuantity(productId, quantity);
        }
    });

    const proceedBtn = document.getElementById('proceed-checkout');
    if (proceedBtn && !proceedBtn.dataset.cf4CheckoutBound) {
        proceedBtn.dataset.cf4CheckoutBound = '1';

        proceedBtn.addEventListener('click', async () => {
            const chosenMethodPreview = getCheckoutPaymentMethod();
            const result = await cf4Confirm({
                title: `¿Confirmar pedido con pago por ${getCf4PaymentMethodShortLabel(chosenMethodPreview)}?`,
                text: 'Se enviará tu pedido para retiro en tienda.',
                icon: 'question',
                confirmButtonText: 'Sí, confirmar',
                cancelButtonText: 'Cancelar',
            });

            if (!result.isConfirmed) return;

            const originalHtml = proceedBtn.innerHTML;
            proceedBtn.disabled = true;
            proceedBtn.innerHTML = '<i class="fas fa-spinner fa-spin" aria-hidden="true"></i> Procesando...';

            try {
                const res = await fetch('/cart/checkout', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': getCsrfToken(),
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({
                        payment_method: getCheckoutPaymentMethod(),
                    }),
                });
                const data = await res.json();

                if (!data.success) {
                    const cmsg = data.message || 'No se pudo procesar el pedido';
                    const cshort = isClientStockShortMessage(cmsg);
                    if (cshort) {
                        await cf4Warning(cmsg, 'Atención');
                    } else {
                        await cf4Error(cmsg, 'Error');
                    }
                    return;
                }

                updateCartCount(0);
                showCartEmptyState();
                incrementInvoiceBadge();

                const paidWith = data.payment_method || getCheckoutPaymentMethod();
                await cf4Toast({
                    icon: 'success',
                    title: '¡Pedido confirmado!',
                    text: buildCf4CheckoutSuccessText(paidWith),
                    timer: 5000,
                });
            } catch (err) {
                console.error('Checkout error:', err);
                await cf4Error('Ocurrió un error al procesar el pedido.', 'Error');
            } finally {
                proceedBtn.disabled = false;
                proceedBtn.innerHTML = originalHtml;
            }
        });
    }

    initProductDetailQuantity();
}
