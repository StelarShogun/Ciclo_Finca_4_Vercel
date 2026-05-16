import { gsap } from 'gsap';

/**
 * Giro continuo sin CSS matrix: escribe `transform="rotate(angle 0 0)"` en el `<g>` hijo
 * (el padre ya hace `translate`; el origen local del buje es 0,0).
 */
function rotateSvgGroup(group, duration) {
    if (!group) {
        return null;
    }
    group.setAttribute('transform', 'rotate(0 0 0)');
    const state = { angle: 0 };
    return gsap.to(state, {
        angle: 360,
        duration,
        ease: 'none',
        repeat: -1,
        onUpdate: () => {
            group.setAttribute('transform', `rotate(${state.angle} 0 0)`);
        },
    });
}

/**
 * Microhistoria en `bike-story`, flotación en `bike-float`.
 * Ruedas y pedalier: solo atributo SVG `rotate` — nada de rotation/svgOrigin de GSAP en esos nodos.
 */
export function init(sceneRoot) {
    const scene = sceneRoot.querySelector('[data-cf4-error-scene="wrong_route"]');
    if (!scene) {
        return;
    }

    const bikeStory = scene.querySelector('.js-bike-story');
    const bikeFloat = scene.querySelector('.js-bike-float');
    const wheels = scene.querySelectorAll('.js-wheel-spin');
    const crank = scene.querySelector('.js-crank-spin');
    const roadDash = scene.querySelector('.js-road-dash');
    const roadBreak = scene.querySelector('.js-road-break');
    const sign = scene.querySelector('.js-warning-sign');
    const dust = scene.querySelectorAll('.js-dust circle');
    const shadow = scene.querySelector('.js-bike-shadow');
    const handlebar = scene.querySelector('.js-handlebar');

    if (!bikeStory || !bikeFloat || wheels.length < 1 || !crank) {
        return;
    }

    wheels.forEach((g) => g.setAttribute('transform', 'rotate(0 0 0)'));
    crank.setAttribute('transform', 'rotate(0 0 0)');

    gsap.set(bikeStory, { x: 0, y: 0, rotation: 0, transformOrigin: '50% 85%' });
    gsap.set(bikeFloat, { x: 0, y: 0, rotation: 0, transformOrigin: '50% 55%' });

    if (handlebar) {
        gsap.set(handlebar, { transformOrigin: '15% 75%' });
    }
    if (sign) {
        gsap.set(sign, { transformOrigin: '50% 100%', opacity: 0, scale: 0, y: 8 });
    }
    if (roadBreak) {
        gsap.set(roadBreak, { opacity: 0 });
    }
    if (dust.length) {
        gsap.set(dust, { opacity: 0, scale: 0, transformOrigin: '50% 50%' });
    }

    const ctx = gsap.context(() => {
        wheels.forEach((w) => rotateSvgGroup(w, 0.8));
        rotateSvgGroup(crank, 0.95);

        if (roadDash) {
            gsap.fromTo(
                roadDash,
                { strokeDashoffset: 0 },
                {
                    strokeDashoffset: -96,
                    duration: 1.1,
                    ease: 'none',
                    repeat: -1,
                },
            );
        }

        if (shadow) {
            gsap.to(shadow, {
                scaleX: 0.88,
                opacity: 0.55,
                duration: 0.9,
                yoyo: true,
                repeat: -1,
                ease: 'sine.inOut',
                transformOrigin: '50% 50%',
            });
        }

        gsap.to(bikeFloat, {
            y: -5,
            duration: 0.85,
            yoyo: true,
            repeat: -1,
            ease: 'sine.inOut',
        });

        const tl = gsap.timeline({
            repeat: -1,
            repeatDelay: 0.5,
            defaults: { ease: 'power2.inOut' },
        });

        tl.fromTo(bikeStory, { x: -22, rotation: 0 }, { x: 26, duration: 1.35 }, 0);

        if (sign) {
            tl.to(
                sign,
                {
                    opacity: 1,
                    scale: 1,
                    y: 0,
                    duration: 0.35,
                    ease: 'back.out(1.8)',
                },
                0.95,
            );
        }

        if (roadBreak) {
            tl.to(roadBreak, { opacity: 1, duration: 0.2 }, 1.1);
        }

        tl.to(
            bikeStory,
            {
                x: 35,
                rotation: -7,
                duration: 0.3,
                ease: 'power3.out',
            },
            1.45,
        );

        if (handlebar) {
            tl.to(
                handlebar,
                {
                    rotation: 8,
                    duration: 0.08,
                    yoyo: true,
                    repeat: 5,
                    ease: 'sine.inOut',
                },
                1.55,
            );
        }

        if (dust.length) {
            tl.to(
                dust,
                {
                    opacity: 0.85,
                    scale: 1,
                    x: (index) => -12 - index * 10,
                    y: (index) => -4 + index * 2,
                    stagger: 0.04,
                    duration: 0.24,
                    ease: 'power2.out',
                },
                1.52,
            ).to(
                dust,
                {
                    opacity: 0,
                    scale: 0.25,
                    duration: 0.35,
                    stagger: 0.03,
                    ease: 'power1.out',
                },
                1.85,
            );
        }

        tl.to(
            bikeStory,
            {
                rotation: 5,
                y: -10,
                duration: 0.18,
                ease: 'sine.inOut',
            },
            1.95,
        )
            .to(
                bikeStory,
                {
                    rotation: -4,
                    y: -2,
                    duration: 0.2,
                    ease: 'sine.inOut',
                },
                2.15,
            )
            .to(
                bikeStory,
                {
                    rotation: 2,
                    duration: 0.16,
                    ease: 'sine.inOut',
                },
                2.35,
            )
            .to(
                bikeStory,
                {
                    rotation: 0,
                    y: 0,
                    x: 0,
                    duration: 0.65,
                    ease: 'elastic.out(1, 0.45)',
                },
                2.55,
            );

        if (sign) {
            tl.to(
                sign,
                {
                    opacity: 0,
                    scale: 0.85,
                    y: 8,
                    duration: 0.28,
                    ease: 'power1.inOut',
                },
                3.35,
            );
        }
        if (roadBreak) {
            tl.to(roadBreak, { opacity: 0, duration: 0.25 }, 3.35);
        }
    }, scene);

    window.addEventListener('pagehide', () => ctx.revert(), { once: true });
}
