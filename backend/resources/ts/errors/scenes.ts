/**
 * Bootstraps GSAP scenes for [data-cf4-scene] inside .cf4-bike-scene.
 */

type SceneKey = 'wrong_route' | 'workshop' | 'order_pack';

type SceneModule = { init: (root: HTMLElement) => void };

const loaders: Record<SceneKey, () => Promise<SceneModule>> = {
    wrong_route: () => import('./scenes/wrong-route') as Promise<SceneModule>,
    workshop: () => import('./scenes/workshop'),
    order_pack: () => import('./scenes/order-pack'),
};

function bootScene(el: HTMLElement): void {
    const key = el.dataset.cf4Scene as SceneKey | undefined;
    if (!key || !(key in loaders)) {
        return;
    }

    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        el.classList.add('is-static');
        return;
    }

    requestAnimationFrame(() => {
        requestAnimationFrame(() => {
            loaders[key]()
                .then((mod) => {
                    mod.init(el);
                })
                .catch((err: unknown) => console.error('[cf4 scenes]', key, err));
        });
    });
}

document.querySelectorAll<HTMLElement>('[data-cf4-scene]').forEach(bootScene);
