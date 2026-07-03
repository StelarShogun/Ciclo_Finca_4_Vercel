import { gsap } from 'gsap';

export function init(sceneRoot: HTMLElement): void {
    const q = gsap.utils.selector(sceneRoot);
    const pack = q('#package');

    if (!pack.length) {
        return;
    }

    gsap.to(pack, {
        y: -8,
        duration: 1.5,
        ease: 'sine.inOut',
        yoyo: true,
        repeat: -1,
    });

    window.addEventListener('pagehide', () => {
        gsap.killTweensOf(pack);
    });
}
