import { addToCart, fireSwal } from './cart-shared.js';

document.addEventListener('DOMContentLoaded', function () {
    document.addEventListener('click', function (e) {
        const addBtn = e.target.closest('.add-to-cart-btn');
        if (!addBtn) return;

        if (addBtn.dataset.purchasable === '0' || parseInt(addBtn.dataset.productStock, 10) < 1) {
            fireSwal({
                icon: 'warning',
                title: 'Producto agotado',
                text: 'Este producto no tiene unidades disponibles.',
            });
            return;
        }

        addToCart(addBtn.dataset.productId, 1, addBtn);
    });

    (function initCategoriesCarousel() {
        const wrap = document.querySelector('[data-categories-carousel]');
        if (!wrap) return;
        const track = wrap.querySelector('[data-carousel-track]');
        const prev = wrap.querySelector('[data-carousel-prev]');
        const next = wrap.querySelector('[data-carousel-next]');
        if (!track || !prev || !next) return;

        function getStep() {
            const first = track.querySelector('.category-slide');
            if (!first) return Math.max(120, track.clientWidth * 0.85);
            let gap = parseInt(getComputedStyle(track).gap, 10);
            if (isNaN(gap)) gap = 18;
            return first.getBoundingClientRect().width + gap;
        }

        function updateButtons() {
            const maxScroll = track.scrollWidth - track.clientWidth - 2;
            prev.disabled = track.scrollLeft <= 2;
            next.disabled = track.scrollLeft >= maxScroll;
        }

        prev.addEventListener('click', function () {
            track.scrollBy({ left: -getStep(), behavior: 'smooth' });
        });
        next.addEventListener('click', function () {
            track.scrollBy({ left: getStep(), behavior: 'smooth' });
        });
        track.addEventListener('scroll', function () {
            window.requestAnimationFrame(updateButtons);
        });
        window.addEventListener('resize', function () {
            updateButtons();
        });
        updateButtons();
    })();

    (function initHomeRevealSections() {
        if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

        const sections = document.querySelectorAll(
            '.home-trust-strip, .featured-section, .categories-section, .benefits-section, .how-it-works-section, .testimonials-section, .final-cta-section',
        );
        if (!sections.length) return;

        sections.forEach((section) => section.classList.add('home-reveal'));

        if (!('IntersectionObserver' in window)) {
            sections.forEach((section) => section.classList.add('is-visible'));
            return;
        }

        const observer = new IntersectionObserver((entries, obs) => {
            entries.forEach((entry) => {
                if (!entry.isIntersecting) return;
                entry.target.classList.add('is-visible');
                obs.unobserve(entry.target);
            });
        }, {
            rootMargin: '0px 0px -8% 0px',
            threshold: 0.14,
        });

        sections.forEach((section) => observer.observe(section));
    })();
});
