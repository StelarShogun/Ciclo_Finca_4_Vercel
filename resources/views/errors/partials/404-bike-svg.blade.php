<div class="cf4-bike404-scene" data-cf4-error-scene="wrong_route" aria-hidden="true">
    <svg
        class="cf4-bike404-svg"
        viewBox="0 0 640 420"
        xmlns="http://www.w3.org/2000/svg"
        role="img"
        aria-labelledby="cf4-bike404-title cf4-bike404-desc"
    >
        <title id="cf4-bike404-title">Bicicleta en ruta equivocada</title>
        <desc id="cf4-bike404-desc">
            Una bicicleta animada avanza por una ruta y se detiene al encontrar un camino equivocado.
        </desc>

        <defs>
            <filter id="cf4-bike-soft-shadow" x="-40%" y="-40%" width="180%" height="180%">
                <feDropShadow dx="0" dy="18" stdDeviation="14" flood-color="#0f172a" flood-opacity="0.18"/>
            </filter>

            <linearGradient id="cf4-bike-frame-gradient" x1="0" x2="1" y1="0" y2="1">
                <stop offset="0%" stop-color="#22c55e"/>
                <stop offset="100%" stop-color="#15803d"/>
            </linearGradient>
        </defs>

        <circle class="cf4-bike404-glow js-bike-glow" cx="320" cy="210" r="150"/>

        <g class="cf4-bike404-road js-bike-road">
            <path
                class="cf4-bike404-road-base"
                d="M80 326 C180 300, 260 350, 365 322 C440 302, 500 310, 575 330"
            />
            <path
                class="cf4-bike404-road-dash js-road-dash"
                d="M80 326 C180 300, 260 350, 365 322 C440 302, 500 310, 575 330"
            />
            <path
                class="cf4-bike404-road-break js-road-break"
                d="M458 314 L478 336 M482 312 L500 335"
            />
        </g>

        <g class="cf4-bike404-sign js-warning-sign">
            <line class="cf4-bike404-sign-post" x1="492" y1="302" x2="492" y2="230"/>
            <path class="cf4-bike404-sign-board" d="M492 198 L535 222 L492 246 L449 222 Z"/>
            <path class="cf4-bike404-sign-arrow" d="M477 222 H507 M497 212 L509 222 L497 232"/>
        </g>

        <g class="cf4-bike404-dust js-dust">
            <circle cx="192" cy="312" r="4"/>
            <circle cx="176" cy="322" r="3"/>
            <circle cx="160" cy="316" r="2.5"/>
            <circle cx="205" cy="328" r="2.5"/>
        </g>

        {{-- Story: solo movimiento GSAP (sin filter aquí: evita bugs de composición SVG+transform).
             Float: bobbing + sombra; filter en float para que la bici entera comparta el mismo stacking. --}}
        <g class="cf4-bike404-bike-story js-bike-story">
            <g class="cf4-bike404-bike-float js-bike-float" filter="url(#cf4-bike-soft-shadow)">
                {{--
                  Orden: sombra → rueda trasera → rueda delantera → cuadro → asiento → manubrio → pedalier.
                  Cada *.js-wheel-spin / .js-crank-spin sólo debe usar rotate(angle 0 0) en el atributo transform (coords locales del grupo ya trasladado).
                --}}
                <ellipse class="cf4-bike404-shadow js-bike-shadow" cx="318" cy="326" rx="170" ry="22"/>

                <g class="cf4-bike404-wheel-position cf4-bike404-wheel-back" transform="translate(210 286)">
                    <g class="cf4-bike404-wheel-spin js-wheel-spin" transform="rotate(0 0 0)">
                        <circle class="wheel-tire" r="58"/>
                        <circle class="wheel-inner" r="45"/>
                        <g class="wheel-spokes">
                            <line x1="0" y1="0" x2="0" y2="-52"/>
                            <line x1="0" y1="0" x2="0" y2="52"/>
                            <line x1="0" y1="0" x2="52" y2="0"/>
                            <line x1="0" y1="0" x2="-52" y2="0"/>
                            <line x1="0" y1="0" x2="37" y2="-37"/>
                            <line x1="0" y1="0" x2="-37" y2="37"/>
                            <line x1="0" y1="0" x2="-37" y2="-37"/>
                            <line x1="0" y1="0" x2="37" y2="37"/>
                        </g>
                        <circle class="wheel-hub" r="7"/>
                    </g>
                </g>

                <g class="cf4-bike404-wheel-position cf4-bike404-wheel-front" transform="translate(430 286)">
                    <g class="cf4-bike404-wheel-spin js-wheel-spin" transform="rotate(0 0 0)">
                        <circle class="wheel-tire" r="58"/>
                        <circle class="wheel-inner" r="45"/>
                        <g class="wheel-spokes">
                            <line x1="0" y1="0" x2="0" y2="-52"/>
                            <line x1="0" y1="0" x2="0" y2="52"/>
                            <line x1="0" y1="0" x2="52" y2="0"/>
                            <line x1="0" y1="0" x2="-52" y2="0"/>
                            <line x1="0" y1="0" x2="37" y2="-37"/>
                            <line x1="0" y1="0" x2="-37" y2="37"/>
                            <line x1="0" y1="0" x2="-37" y2="-37"/>
                            <line x1="0" y1="0" x2="37" y2="37"/>
                        </g>
                        <circle class="wheel-hub" r="7"/>
                    </g>
                </g>

                <g class="cf4-bike404-frame js-bike-frame">
                    <path class="frame-line frame-main" d="M210 286 L284 205 L336 286 Z"/>
                    <path class="frame-line" d="M284 205 L402 205 L336 286"/>
                    <path class="frame-line" d="M402 205 L430 286"/>
                    <path class="frame-line" d="M284 205 L264 170"/>
                    <path class="frame-line" d="M402 205 L430 164"/>
                    <path class="frame-line fork-line" d="M430 164 L430 286"/>
                </g>

                <g class="cf4-bike404-seat js-seat">
                    <path d="M235 158 C258 148, 288 150, 305 160 C295 170, 260 173, 235 166 Z"/>
                    <line x1="264" y1="170" x2="284" y2="205"/>
                </g>

                <g class="cf4-bike404-handlebar js-handlebar">
                    <line x1="430" y1="164" x2="462" y2="142"/>
                    <path d="M462 142 C488 132, 502 146, 486 160"/>
                </g>

                <g class="cf4-bike404-crank-position" transform="translate(336 286)">
                    <g class="cf4-bike404-crank-spin js-crank-spin" transform="rotate(0 0 0)">
                        <circle class="crank-center" r="11"/>
                        <line class="crank-arm" x1="0" y1="0" x2="0" y2="-34"/>
                        <line class="crank-arm" x1="0" y1="0" x2="0" y2="34"/>
                        <rect class="pedal" x="-18" y="-42" width="36" height="8" rx="4"/>
                        <rect class="pedal" x="-18" y="34" width="36" height="8" rx="4"/>
                    </g>
                </g>
            </g>
        </g>
    </svg>
</div>
