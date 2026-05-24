import { fireSwal } from '../swal.js';
import {
    buildCf4CheckoutSuccessText,
    getCf4PaymentMethodShortLabel,
} from '../checkout-copy.js';
import {
    addToCart,
    getCsrfToken,
    isClientStockShortMessage,
    updateCartCount,
} from '../cart-shared.js';
import '../../shared/ajax-pagination.js';

window.__cf4ClientPageJsLoaded = true;

import { initCatalogFavoriteClickDelegation } from '../catalog-product-favorites.js';

function formatColones(amount) {
    return '₡' + Number(amount).toLocaleString('es-CR', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    });
}

/**
 * Sync quantity stepper UI: value bounds, disabled +/- buttons, max hint, subtotal.
 */
export function syncProductQtyUi() {
    const productQtyInput = document.getElementById('product-quantity');
    const decreaseBtn = document.getElementById('decrease-qty');
    const increaseBtn = document.getElementById('increase-qty');
    const maxHint = document.getElementById('product-qty-max-hint');
    const subtotalEl = document.getElementById('product-qty-subtotal');
    const priceBlock = document.querySelector('.product-detail-price[data-unit-price]');

    if (!productQtyInput) {
        return null;
    }

    const minQty = parseInt(productQtyInput.min, 10) || 1;
    const maxQty = parseInt(productQtyInput.max, 10) || 999;
    const unitPrice = priceBlock
        ? parseInt(priceBlock.getAttribute('data-unit-price'), 10) || 0
        : 0;

    let value = parseInt(productQtyInput.value, 10);
    if (Number.isNaN(value) || value < minQty) {
        value = minQty;
    } else if (value > maxQty) {
        value = maxQty;
    }
    productQtyInput.value = String(value);

    if (decreaseBtn) {
        const atMin = value <= minQty;
        decreaseBtn.disabled = atMin;
        decreaseBtn.setAttribute('aria-disabled', atMin ? 'true' : 'false');
    }

    if (increaseBtn) {
        const atMax = value >= maxQty;
        increaseBtn.disabled = atMax;
        increaseBtn.setAttribute('aria-disabled', atMax ? 'true' : 'false');
    }

    if (maxHint) {
        maxHint.textContent = 'Máximo disponible: ' + maxQty.toLocaleString('es-CR') + ' unidades';
    }

    if (subtotalEl && unitPrice > 0) {
        subtotalEl.textContent = 'Subtotal: ' + formatColones(unitPrice * value);
    }

    return value;
}

function initProductDetailTabs() {
    const root = document.getElementById('product-detail-tabs');
    if (!root) {
        return;
    }

    const buttons = root.querySelectorAll('.product-detail-tabs__btn[data-tab]');
    const panels = root.querySelectorAll('.product-detail-tab-panel[data-panel]');
    if (!buttons.length || !panels.length) {
        return;
    }

    const hashTab = window.location.hash.replace('#', '');
    const defaultTab = hashTab && root.querySelector('[data-tab="' + hashTab + '"]')
        ? hashTab
        : (root.getAttribute('data-default-tab') || 'description');

    function activate(tabId) {
        buttons.forEach(function (btn) {
            const isActive = btn.getAttribute('data-tab') === tabId;
            btn.classList.toggle('is-active', isActive);
            btn.setAttribute('aria-selected', isActive ? 'true' : 'false');
        });
        panels.forEach(function (panel) {
            const isActive = panel.getAttribute('data-panel') === tabId;
            panel.hidden = !isActive;
            panel.classList.toggle('is-active', isActive);
        });
    }

    buttons.forEach(function (btn) {
        btn.addEventListener('click', function () {
            activate(btn.getAttribute('data-tab'));
        });
    });

    if (hashTab === 'product-detail-tabs' || hashTab === 'reviews') {
        activate('reviews');
    } else if (hashTab.startsWith('product-tab-')) {
        activate(hashTab.replace('product-tab-', ''));
    } else if (root.querySelector('[data-tab="' + hashTab + '"]')) {
        activate(hashTab);
    } else {
        activate(defaultTab);
    }
}

export function initClientProductPage() {
    initCatalogFavoriteClickDelegation();
    initProductDetailTabs();

    document.addEventListener('click', function (e) {
        var addBtn = e.target.closest('.add-to-cart-btn');
        if (addBtn) {
            if (addBtn.dataset.purchasable === '0' || parseInt(addBtn.dataset.productStock, 10) < 1) {
                fireSwal({ icon: 'warning', title: 'Producto agotado', text: 'Este producto no tiene unidades disponibles.' });
                return;
            }
            var qtyFromDetail = addBtn.closest('.product-detail-actions');
            var qty = qtyFromDetail ? syncProductQtyUi() : 1;
            addToCart(addBtn.dataset.productId, qty || 1, addBtn);
            return;
        }

        var guestBtn = e.target.closest('.guest-add-btn');
        if (guestBtn) {
            if (guestBtn.dataset.purchasable === '0' || parseInt(guestBtn.dataset.productStock, 10) < 1) {
                fireSwal({ icon: 'warning', title: 'Producto agotado', text: 'Este producto no tiene unidades disponibles.' });
                return;
            }
            window.location.href = '/login';
            return;
        }

        // Close modal on backdrop click.
        if (e.target.classList.contains('modal') && e.target.classList.contains('active')) {
            e.target.classList.remove('active');
        }
    });


    // Quantity controls on product detail page.
    var productQtyInput = document.getElementById('product-quantity');

    if (productQtyInput) {
        var productQty = syncProductQtyUi() || 1;

        document.getElementById('decrease-qty')?.addEventListener('click', function () {
            var current = syncProductQtyUi() || 1;
            if (current > 1) {
                productQtyInput.value = String(current - 1);
                productQty = syncProductQtyUi();
            }
        });

        document.getElementById('increase-qty')?.addEventListener('click', function () {
            var maxQty = parseInt(productQtyInput.max, 10) || 999;
            var current = syncProductQtyUi() || 1;
            if (current < maxQty) {
                productQtyInput.value = String(current + 1);
                productQty = syncProductQtyUi();
            }
        });

        productQtyInput.addEventListener('change', function () {
            productQty = syncProductQtyUi();
        });

        productQtyInput.addEventListener('input', function () {
            productQty = syncProductQtyUi();
        });
    }

    // ---- Product image carousel + thumbnails ----
    (function initProductCarousel() {
        var track = document.getElementById('carousel-track');
        if (!track) return;
        var slides = track.querySelectorAll('.carousel-slide');
        var total = slides.length;
        if (total < 1) return;

        var prevBtn = document.getElementById('carousel-prev');
        var nextBtn = document.getElementById('carousel-next');
        var thumbs = document.querySelectorAll('.product-detail-thumb[data-thumb-index]');
        var current = 0;

        function goTo(index) {
            current = Math.max(0, Math.min(total - 1, index));
            track.style.transform = 'translateX(-' + (current * 100) + '%)';
            thumbs.forEach(function (thumb, i) {
                var active = i === current;
                thumb.classList.toggle('is-active', active);
                thumb.setAttribute('aria-current', active ? 'true' : 'false');
            });
            if (prevBtn) prevBtn.disabled = current === 0;
            if (nextBtn) nextBtn.disabled = current === total - 1;
        }

        if (prevBtn) prevBtn.addEventListener('click', function () { goTo(current - 1); });
        if (nextBtn) nextBtn.addEventListener('click', function () { goTo(current + 1); });
        thumbs.forEach(function (thumb) {
            thumb.addEventListener('click', function () {
                goTo(parseInt(thumb.getAttribute('data-thumb-index'), 10) || 0);
            });
        });

        if (total > 1) {
            var startX = null;
            track.addEventListener('touchstart', function (e) { startX = e.touches[0].clientX; }, { passive: true });
            track.addEventListener('touchend', function (e) {
                if (startX === null) return;
                var diff = startX - e.changedTouches[0].clientX;
                if (Math.abs(diff) > 40) goTo(diff > 0 ? current + 1 : current - 1);
                startX = null;
            }, { passive: true });

            document.addEventListener('keydown', function (e) {
                if (!document.getElementById('product-carousel')) return;
                if (e.key === 'ArrowLeft') goTo(current - 1);
                if (e.key === 'ArrowRight') goTo(current + 1);
            });
        }

        goTo(0);
    })();
}
