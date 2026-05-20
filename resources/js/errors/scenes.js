/**
 * Bootstraps GSAP scenes for [data-cf4-scene] inside .cf4-bike-scene.
 * Timelines start after layout paint (double rAF) per UX plan.
 */

const loaders = {
    wrong_route: () => import('./scenes/wrong-route.js'),
    workshop: () => import('./scenes/workshop.js'),
    order_pack: () => import('./scenes/order-pack.js'),
};

function bootScene(el) {
    const key = el.dataset.cf4Scene;
    if (!key || !loaders[key]) return;

    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        el.classList.add('is-static');
        return;
    }

    requestAnimationFrame(() => {
        requestAnimationFrame(() => {
            loaders[key]()
                .then((mod) => {
                    if (typeof mod.init === 'function') {
                        mod.init(el);
                    }
                })
                .catch((err) => console.error('[cf4 scenes]', key, err));
        });
    });
}

document.querySelectorAll('[data-cf4-scene]').forEach(bootScene);
