import { gsap } from 'gsap';

export function init(sceneRoot: HTMLElement): void {
    const q = gsap.utils.selector(sceneRoot);
    const gear = q('#gear');
    const wrench = q('#wrench');

    if (gear.length) {
        gsap.to(gear, {
            rotation: 360,
            transformOrigin: '50% 50%',
            duration: 4.5,
            ease: 'none',
            repeat: -1,
        });
    }
    if (wrench.length) {
        gsap.to(wrench, {
            rotation: 12,
            transformOrigin: '50% 60%',
            duration: 1,
            ease: 'sine.inOut',
            yoyo: true,
            repeat: -1,
        });
    }

    window.addEventListener('pagehide', () => {
        gsap.killTweensOf([gear, wrench].flat());
    });
}
