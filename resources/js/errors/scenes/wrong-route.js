import { gsap } from 'gsap';

export function init(sceneRoot) {
    const q = gsap.utils.selector(sceneRoot);
    const bike = q('#bike-group');
    if (!bike.length) return;

    const notes = [q('#note-1'), q('#note-2'), q('#note-3')].flat().filter(Boolean);
    const wheels = [q('#back-wheel'), q('#front-wheel')].flat().filter(Boolean);
    const dust = q('#dust');

    function resetAll() {
        gsap.set(bike, {
            x: -1024, rotation: 0, scaleY: 1,
            transformOrigin: '50% 100%',
        });
        if (wheels.length) {
            gsap.set(wheels, { rotation: 0, transformOrigin: '50% 50%' });
        }
        if (dust.length) {
            gsap.set(dust, { autoAlpha: 0, scale: 0, transformOrigin: '50% 50%' });
        }
        if (notes.length) {
            gsap.set(notes, { autoAlpha: 0, y: 10 });
        }
    }

    resetAll();

    const tl = gsap.timeline({
        repeat: -1,
        repeatDelay: 1.0,
        onRepeat: resetAll,
        defaults: { ease: 'power2.out' },
    });

    tl.to(bike, {
        x: 0, duration: 1.5, ease: 'power1.inOut',
    }, 0);

    if (wheels.length) {
        tl.to(wheels, {
            rotation: 720, duration: 1.5, ease: 'none',
        }, 0);
    }

    if (notes.length >= 3) {
        tl.to(notes[0], {
            autoAlpha: 1, y: 0, duration: 0.3, ease: 'back.out(2)',
        }, 1.0)
            .to(notes[1], {
                autoAlpha: 1, y: 0, duration: 0.3, ease: 'back.out(2)',
            }, 1.15)
            .to(notes[2], {
                autoAlpha: 1, y: 0, duration: 0.3, ease: 'back.out(2)',
            }, 1.3);
    }

    tl.to(bike, {
        x: 30, duration: 0.42, ease: 'power3.out',
    }, 1.6);

    if (wheels.length) {
        tl.to(wheels, {
            rotation: '+=65', duration: 0.42, ease: 'power3.out',
        }, 1.6);
    }

    tl.to(bike, {
        rotation: -7, duration: 0.28, ease: 'power2.out',
    }, 1.68);

    if (dust.length) {
        tl.to(dust, {
            autoAlpha: 1, scale: 1.3, duration: 0.28, ease: 'power1.out',
        }, 1.80)
            .to(dust, {
                autoAlpha: 0, scale: 0.9, duration: 0.45,
            }, 2.35);
    }

    tl.to(bike, {
        rotation: 6, duration: 0.22, ease: 'sine.inOut',
        yoyo: true, repeat: 5,
    }, 2.1);

    if (notes.length) {
        tl.to(notes, {
            autoAlpha: 0, y: -22, duration: 0.6, stagger: 0.1,
        }, 3.0);
    }

    tl.to(bike, {
        scaleY: 0.97, rotation: 2, transformOrigin: '50% 100%',
        duration: 0.85, ease: 'sine.inOut',
        yoyo: true, repeat: 1,
    }, 3.5);

    if (notes.length) {
        tl.to(notes, { autoAlpha: 0, duration: 0.4 }, 5.0);
    }

    window.addEventListener('pagehide', () => tl.kill());
}
