// @ts-nocheck
/**
 * Swiper carousel for product spotlight rows (catalog spotlight + home featured).
 * Markup: `[data-product-spotlight-carousel]` or legacy `[data-catalog-spotlight-carousel]`.
 */
const CAROUSEL_ROOT_SELECTOR =
    '[data-product-spotlight-carousel], [data-catalog-spotlight-carousel]';

const DEFAULT_MAX_SLIDES_PER_VIEW = 3;

/**
 * @param {HTMLElement} root
 * @param {import('swiper').default} Swiper
 * @param {{ Navigation: unknown; Autoplay: unknown; A11y: unknown }} swiperModules
 */
function initCarouselRoot(root, Swiper, { Navigation, Autoplay, A11y }) {
    const swiperEl = root.querySelector('.swiper');
    const prevBtn = root.querySelector('[data-spotlight-prev]');
    const nextBtn = root.querySelector('[data-spotlight-next]');
    if (!swiperEl || swiperEl.swiper) return;

    const wrapperEl = swiperEl.querySelector('.swiper-wrapper');
    let slides = swiperEl.querySelectorAll('.swiper-slide');
    if (!slides.length) return;

    const maxSlidesPerView =
        parseInt(root.getAttribute('data-max-slides-per-view'), 10) || DEFAULT_MAX_SLIDES_PER_VIEW;
    const minSlidesForLoop = maxSlidesPerView * 2;
    const isNarrowViewport = window.matchMedia('(max-width: 1023px)').matches;
    const enableLoop = slides.length > 1 && !isNarrowViewport;

    if (enableLoop && wrapperEl && slides.length > 1 && slides.length < minSlidesForLoop) {
        const originalSlides = Array.from(slides);
        while (wrapperEl.querySelectorAll('.swiper-slide').length < minSlidesForLoop) {
            originalSlides.forEach((slide) => {
                wrapperEl.appendChild(slide.cloneNode(true));
            });
        }
        slides = swiperEl.querySelectorAll('.swiper-slide');
    }

    let delay = parseInt(root.getAttribute('data-autoplay-delay'), 10);
    if (!Number.isFinite(delay) || delay <= 0) delay = 4000;
    const prevLabel =
        root.getAttribute('data-a11y-prev') || 'Producto destacado anterior';
    const nextLabel =
        root.getAttribute('data-a11y-next') || 'Siguiente producto destacado';

    try {
        new Swiper(swiperEl, {
            modules: [Navigation, Autoplay, A11y],
            slidesPerView: 1,
            spaceBetween: 18,
            centeredSlides: false,
            loop: enableLoop,
            loopAdditionalSlides: 0,
            speed: 600,
            grabCursor: true,
            watchOverflow: true,
            autoplay: {
                enabled: true,
                delay,
                disableOnInteraction: false,
                pauseOnMouseEnter: false,
            },
            navigation: {
                prevEl: prevBtn,
                nextEl: nextBtn,
                disabledClass: 'swiper-button-disabled',
            },
            a11y: {
                prevSlideMessage: prevLabel,
                nextSlideMessage: nextLabel,
                slideLabelMessage: '{{index}} de {{slidesLength}}',
            },
            breakpoints: {
                640: { slidesPerView: 2, spaceBetween: 16 },
                1024: { slidesPerView: 3, spaceBetween: 18 },
                1280: { slidesPerView: 4, spaceBetween: 20 },
                1680: { slidesPerView: 5, spaceBetween: 22 },
            },
        });
    } catch (err) {
        if (typeof console !== 'undefined' && console.error) {
            console.error('Product spotlight carousel failed to init:', err);
        }
    }
}

export async function initProductSpotlightCarousels() {
    const roots = document.querySelectorAll(CAROUSEL_ROOT_SELECTOR);
    if (!roots.length) return;

    const [{ default: Swiper }, { Navigation, Autoplay, A11y }] = await Promise.all([
        import('swiper'),
        import('swiper/modules'),
        import('swiper/css'),
        import('swiper/css/navigation'),
        import('swiper/css/a11y'),
    ]);

    roots.forEach((root) => {
        if (root instanceof HTMLElement) {
            initCarouselRoot(root, Swiper, { Navigation, Autoplay, A11y });
        }
    });
}
