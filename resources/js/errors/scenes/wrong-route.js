import { gsap } from 'gsap';

/**
 * Giro continuo: solo atributo `transform="rotate(angle 0 0)"` en el `<g>` hijo.
 * Devuelve el tween para poder bajar `timeScale` en la frenada.
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
            const a = ((state.angle % 360) + 360) % 360;
            group.setAttribute('transform', `rotate(${a.toFixed(2)} 0 0)`);
        },
    });
}

/**
 * Microhistoria: anticipación + frenado con peso + señal/obstáculo/grietas + skid/polvo.
 * Ruedas, bielas y rayas del camino desaceleran con timeScale durante la parada.
 *
 * DEBUG jerarquía pedalier: si es true, el crank queda en rotate(45) sin tween de giro.
 * Debe ser false en producción.
 */
const DEBUG_CF4_FREEZE_CRANK_AT_45 = false;

export function init(sceneRoot) {
    const scene = sceneRoot.querySelector('[data-cf4-error-scene="wrong_route"]');
    if (!scene) {
        return;
    }

    const bikeStory = scene.querySelector('.js-bike-story');
    const bikeFloat = scene.querySelector('.js-bike-float');
    const bikeFloatBob = scene.querySelector('.js-bike-float-bob');
    const wheels = scene.querySelectorAll('.js-wheel-spin');
    const crank = scene.querySelector('.js-crank-spin');
    const roadDash = scene.querySelector('.js-road-dash');
    const roadBreak = scene.querySelector('.js-road-break');
    const obstacle = scene.querySelector('.js-road-obstacle');
    const skid = scene.querySelector('.js-skid');
    const sign = scene.querySelector('.js-warning-sign');
    const dust = scene.querySelectorAll('.js-dust circle');
    const shadow = scene.querySelector('.js-bike-shadow');
    const handlebar = scene.querySelector('.js-handlebar');
    const clouds = scene.querySelector('.js-clouds');
    const sun = scene.querySelector('.js-sun');

    const reduceMotion =
        typeof window !== 'undefined' &&
        typeof window.matchMedia === 'function' &&
        window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    if (!bikeStory || !bikeFloat || !bikeFloatBob || wheels.length < 1 || !crank) {
        return;
    }

    wheels.forEach((g) => g.setAttribute('transform', 'rotate(0 0 0)'));
    crank.setAttribute('transform', 'rotate(0 0 0)');

    /*
     * Origen estable en coords SVG (no % sobre fill-box): con transform-box:fill-box el bbox
     * del grupo cambia al girar los radios → el pivote % “brecha” y el pedalier puede separarse del cuadro.
     */
    gsap.set(bikeStory, { x: 0, y: 0, rotation: 0, svgOrigin: '210 286' });
    /* Sin transform CSS en bike-float: el salto vertical va en js-bike-float-bob con translate SVG nativo (compatible con rotate() del pedalier). */
    gsap.set(bikeFloat, { clearProps: 'transform' });

    if (handlebar) {
        gsap.set(handlebar, { rotation: 0, svgOrigin: '430 164' });
    }
    if (sign) {
        gsap.set(sign, { transformOrigin: '50% 100%', opacity: 0, scale: 0, y: 10 });
    }
    if (roadBreak) {
        gsap.set(roadBreak, { opacity: 0 });
    }
    if (obstacle) {
        gsap.set(obstacle, { opacity: 0, scale: 0.88, transformOrigin: '50% 50%' });
    }
    if (skid) {
        gsap.set(skid, { opacity: 0 });
    }
    if (dust.length) {
        gsap.set(dust, { opacity: 0, scale: 0, transformOrigin: '50% 50%' });
    }

    const wheelTweens = [];
    let crankTween = null;
    let roadDashTween = null;

    const slowRotors = () => {
        wheelTweens.forEach((t) => {
            if (t) {
                t.timeScale(0.26);
            }
        });
        if (crankTween) {
            crankTween.timeScale(0.07);
        }
        if (roadDashTween) {
            roadDashTween.timeScale(0.2);
        }
    };

    const fastRotors = () => {
        wheelTweens.forEach((t) => {
            if (t) {
                t.timeScale(1);
            }
        });
        if (crankTween) {
            crankTween.timeScale(1);
        }
        if (roadDashTween) {
            roadDashTween.timeScale(1);
        }
    };

    const ctx = gsap.context(() => {
        wheels.forEach((w) => {
            const tw = rotateSvgGroup(w, 0.8);
            if (tw) {
                wheelTweens.push(tw);
            }
        });
        if (DEBUG_CF4_FREEZE_CRANK_AT_45) {
            crank.setAttribute('transform', 'rotate(45 0 0)');
        } else {
            crankTween = rotateSvgGroup(crank, 0.95);
        }

        if (roadDash) {
            roadDashTween = gsap.fromTo(
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

        if (!reduceMotion && bikeFloatBob) {
            bikeFloatBob.setAttribute('transform', 'translate(0 0)');
            const bob = { dy: 0 };
            gsap.to(bob, {
                dy: -5,
                duration: 0.85,
                yoyo: true,
                repeat: -1,
                ease: 'sine.inOut',
                onUpdate: () => {
                    bikeFloatBob.setAttribute('transform', `translate(0 ${bob.dy})`);
                },
            });
        }

        if (!reduceMotion && clouds) {
            gsap.to(clouds, {
                x: 14,
                duration: 26,
                ease: 'sine.inOut',
                yoyo: true,
                repeat: -1,
            });
        }

        if (!reduceMotion && sun) {
            gsap.to(sun, {
                scale: 1.04,
                transformOrigin: '50% 50%',
                duration: 5,
                ease: 'sine.inOut',
                yoyo: true,
                repeat: -1,
            });
        }

        const tl = gsap.timeline({
            repeat: -1,
            repeatDelay: 0.55,
            defaults: { ease: 'power2.inOut' },
        });

        /* Cartel compacto en x≈546; pivotes svgOrigin evitan drift del pedalier al rotar ruedas */
        tl.fromTo(
            bikeStory,
            { x: -20, y: 0, rotation: 0 },
            { x: 26, duration: 1.12, ease: 'power1.inOut' },
            0,
        );

        if (sign) {
            tl.to(
                sign,
                {
                    opacity: 1,
                    scale: 1,
                    y: 0,
                    duration: 0.32,
                    ease: 'back.out(1.75)',
                },
                0.76,
            );
        }

        if (roadBreak) {
            tl.to(roadBreak, { opacity: 1, duration: 0.22 }, 0.8);
        }

        if (obstacle) {
            tl.to(
                obstacle,
                {
                    opacity: 1,
                    scale: 1,
                    duration: 0.28,
                    ease: 'back.out(1.5)',
                },
                0.82,
            );
        }

        tl.to(
            bikeStory,
            {
                x: 27,
                rotation: 2.4,
                duration: 0.15,
                ease: 'power1.out',
            },
            1.02,
        );

        tl.to(
            bikeStory,
            {
                x: 29,
                y: 5,
                rotation: -8.8,
                duration: 0.26,
                ease: 'power3.out',
            },
            1.18,
        );

        tl.call(slowRotors, null, 1.18);

        if (skid) {
            tl.to(skid, { opacity: 1, duration: 0.2 }, 1.2);
        }

        if (handlebar) {
            tl.to(
                handlebar,
                {
                    rotation: 9,
                    duration: 0.07,
                    yoyo: true,
                    repeat: 6,
                    ease: 'sine.inOut',
                },
                1.22,
            );
        }

        if (dust.length) {
            tl.to(
                dust,
                {
                    opacity: 0.95,
                    scale: 1.05,
                    x: (index) => -14 - index * 11,
                    y: (index) => -5 + index * 2,
                    stagger: 0.035,
                    duration: 0.26,
                    ease: 'power2.out',
                },
                1.2,
            ).to(
                dust,
                {
                    opacity: 0,
                    scale: 0.2,
                    duration: 0.38,
                    stagger: 0.03,
                    ease: 'power1.out',
                },
                1.62,
            );
        }

        tl.to(
            bikeStory,
            {
                y: -7,
                rotation: 6,
                duration: 0.2,
                ease: 'sine.out',
            },
            1.46,
        )
            .to(
                bikeStory,
                {
                    y: 2,
                    rotation: -3.2,
                    duration: 0.18,
                    ease: 'sine.inOut',
                },
                1.66,
            )
            .to(
                bikeStory,
                {
                    x: 0,
                    y: 0,
                    rotation: 0,
                    duration: 0.72,
                    ease: 'elastic.out(1, 0.42)',
                },
                1.84,
            );

        tl.call(fastRotors, null, 2.88);

        if (skid) {
            tl.to(skid, { opacity: 0, duration: 0.38 }, 2.75);
        }

        if (sign) {
            tl.to(
                sign,
                {
                    opacity: 0,
                    scale: 0.86,
                    y: 12,
                    duration: 0.3,
                    ease: 'power2.in',
                },
                3.05,
            );
        }
        if (obstacle) {
            tl.to(
                obstacle,
                {
                    opacity: 0,
                    scale: 0.88,
                    duration: 0.28,
                    ease: 'power2.in',
                },
                3.08,
            );
        }
        if (roadBreak) {
            tl.to(roadBreak, { opacity: 0, duration: 0.28 }, 3.08);
        }
    }, scene);

    window.addEventListener('pagehide', () => ctx.revert(), { once: true });
}
