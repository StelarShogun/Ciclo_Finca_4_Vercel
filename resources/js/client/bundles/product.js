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

export function initClientProductPage() {
  initCatalogFavoriteClickDelegation();
    // Delegated: add to cart (quantity se ajusta solo en el carrito).
    document.addEventListener('click', function (e) {
        var favoriteBtn = e.target.closest('[data-product-favorite-btn]');
        if (favoriteBtn) {
            e.preventDefault();
            toggleFavoriteProduct(favoriteBtn);
            return;
        }

        var addBtn = e.target.closest('.add-to-cart-btn');
        if (addBtn) {
            if (addBtn.dataset.purchasable === '0' || parseInt(addBtn.dataset.productStock, 10) < 1) {
                fireSwal({ icon: 'warning', title: 'Producto agotado', text: 'Este producto no tiene unidades disponibles.' });
                return;
            }
            addToCart(addBtn.dataset.productId, 1, addBtn);
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
    var productQty      = 1;

    if (productQtyInput) {
        var maxQty = parseInt(productQtyInput.max, 10) || 999;

        document.getElementById('decrease-qty') && document.getElementById('decrease-qty').addEventListener('click', function () {
            if (productQty > 1) { productQty--; productQtyInput.value = productQty; }
        });

        document.getElementById('increase-qty') && document.getElementById('increase-qty').addEventListener('click', function () {
            if (productQty < maxQty) { productQty++; productQtyInput.value = productQty; }
        });

        productQtyInput.addEventListener('change', function () {
            var value = parseInt(this.value, 10);
            if (value < 1)       { this.value = 1;      productQty = 1; }
            else if (value > maxQty) { this.value = maxQty; productQty = maxQty; }
            else                 { productQty = value; }
        });

        // Add to cart using the quantity from the detail page selector.
        var detailAddBtn = document.querySelector('.product-detail-actions .add-to-cart-btn');
        if (detailAddBtn) {
            detailAddBtn.addEventListener('click', function () {
                addToCart(this.dataset.productId, productQty, this);
            });
        }
    }

    // ---- Product image carousel ----
    (function initProductCarousel() {
        var track = document.getElementById('carousel-track');
        if (!track) return;
        var slides  = track.querySelectorAll('.carousel-slide');
        var total   = slides.length;
        if (total <= 1) return;
        var prevBtn = document.getElementById('carousel-prev');
        var nextBtn = document.getElementById('carousel-next');
        var dots    = document.querySelectorAll('.carousel-dot');
        var current = 0;

        function goTo(index) {
            current = Math.max(0, Math.min(total - 1, index));
            track.style.transform = 'translateX(-' + (current * 100) + '%)';
            dots.forEach(function (d, i) { d.classList.toggle('active', i === current); });
            if (prevBtn) prevBtn.disabled = current === 0;
            if (nextBtn) nextBtn.disabled = current === total - 1;
        }

        if (prevBtn) prevBtn.addEventListener('click', function () { goTo(current - 1); });
        if (nextBtn) nextBtn.addEventListener('click', function () { goTo(current + 1); });
        dots.forEach(function (d, i) { d.addEventListener('click', function () { goTo(i); }); });

        // Swipe support (touch devices)
        var startX = null;
        track.addEventListener('touchstart', function (e) { startX = e.touches[0].clientX; }, { passive: true });
        track.addEventListener('touchend', function (e) {
            if (startX === null) return;
            var diff = startX - e.changedTouches[0].clientX;
            if (Math.abs(diff) > 40) goTo(diff > 0 ? current + 1 : current - 1);
            startX = null;
        }, { passive: true });

        // Keyboard arrow navigation
        document.addEventListener('keydown', function (e) {
            if (e.key === 'ArrowLeft')  goTo(current - 1);
            if (e.key === 'ArrowRight') goTo(current + 1);
        });

        goTo(0);
    })();
}
