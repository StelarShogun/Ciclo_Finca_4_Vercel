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
<<<<<<< Updated upstream
<<<<<<< Updated upstream
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
=======
                d="M438 322 L462 348 M470 312 L498 340 M448 334 L474 358"
            />
        </g>

        <g class="cf4-bike404-skid js-skid" aria-hidden="true">
            <path d="M175 332 C210 342, 245 342, 288 332"/>
            <path d="M188 346 C218 354, 252 352, 278 344"/>
        </g>

        <g class="cf4-bike404-obstacle js-road-obstacle" aria-hidden="true">
            <path class="cf4-bike404-crack" d="M455 318 L472 332 L462 344 L485 352"/>
            <rect class="cf4-bike404-barrier" x="466" y="286" width="68" height="14" rx="7" transform="rotate(-10 500 293)"/>
            <line class="cf4-bike404-barrier-stripe" x1="478" y1="287" x2="494" y2="300"/>
            <line class="cf4-bike404-barrier-stripe" x1="508" y1="287" x2="524" y2="300"/>
        </g>

        <g class="cf4-bike404-sign js-warning-sign">
            <line class="cf4-bike404-sign-post" x1="488" y1="308" x2="488" y2="208"/>
            <path class="cf4-bike404-sign-board" d="M488 168 L548 238 L488 308 L428 238 Z"/>
            <path class="cf4-bike404-sign-arrow" d="M462 238 H514 M496 218 L520 238 L496 258"/>
            <line class="cf4-bike404-sign-block" x1="440" y1="200" x2="536" y2="276"/>
        </g>

        <g class="cf4-bike404-dust js-dust">
            <circle cx="192" cy="312" r="4"/>
            <circle cx="176" cy="322" r="3"/>
            <circle cx="160" cy="316" r="2.5"/>
            <circle cx="205" cy="328" r="2.5"/>
        </g>

=======
                d="M438 322 L462 348 M470 312 L498 340 M448 334 L474 358"
            />
        </g>

        <g class="cf4-bike404-skid js-skid" aria-hidden="true">
            <path d="M175 332 C210 342, 245 342, 288 332"/>
            <path d="M188 346 C218 354, 252 352, 278 344"/>
        </g>

        <g class="cf4-bike404-obstacle js-road-obstacle" aria-hidden="true">
            <path class="cf4-bike404-crack" d="M455 318 L472 332 L462 344 L485 352"/>
            <rect class="cf4-bike404-barrier" x="466" y="286" width="68" height="14" rx="7" transform="rotate(-10 500 293)"/>
            <line class="cf4-bike404-barrier-stripe" x1="478" y1="287" x2="494" y2="300"/>
            <line class="cf4-bike404-barrier-stripe" x1="508" y1="287" x2="524" y2="300"/>
        </g>

        <g class="cf4-bike404-sign js-warning-sign">
            <line class="cf4-bike404-sign-post" x1="488" y1="308" x2="488" y2="208"/>
            <path class="cf4-bike404-sign-board" d="M488 168 L548 238 L488 308 L428 238 Z"/>
            <path class="cf4-bike404-sign-arrow" d="M462 238 H514 M496 218 L520 238 L496 258"/>
            <line class="cf4-bike404-sign-block" x1="440" y1="200" x2="536" y2="276"/>
        </g>

        <g class="cf4-bike404-dust js-dust">
            <circle cx="192" cy="312" r="4"/>
            <circle cx="176" cy="322" r="3"/>
            <circle cx="160" cy="316" r="2.5"/>
            <circle cx="205" cy="328" r="2.5"/>
        </g>

>>>>>>> Stashed changes
        {{-- Story: movimiento GSAP. Float: sombra + filtro drop-shadow en el conjunto. --}}
        <g class="cf4-bike404-bike-story js-bike-story">
            <g class="cf4-bike404-bike-float js-bike-float" filter="url(#cf4-bike-soft-shadow)">
                {{--
                  Orden: sombra → rueda trasera → rueda delantera → cuadro → asiento → manubrio → pedalier.
                  Solo js-wheel-spin / js-crank-spin usan rotate(angle 0 0) vía GSAP sobre el atributo transform.
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
                        <circle class="crank-center" r="10"/>
                        <line class="crank-arm" x1="0" y1="0" x2="24" y2="-24"/>
                        <line class="crank-arm crank-arm-back" x1="0" y1="0" x2="-24" y2="24"/>
                        <rect class="pedal" x="18" y="-32" width="28" height="7" rx="3"/>
                        <rect class="pedal pedal-back" x="-46" y="25" width="28" height="7" rx="3"/>
                    </g>
                </g>
            </g>
        </g>
<<<<<<< Updated upstream
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
    </svg>
</div>
