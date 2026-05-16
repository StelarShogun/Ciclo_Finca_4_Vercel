<div class="cf4-bike404-scene" data-cf4-error-scene="wrong_route" aria-hidden="true">
    <svg
        class="cf4-bike404-svg"
        viewBox="0 28 640 348"
        xmlns="http://www.w3.org/2000/svg"
    >
        <defs>
            <linearGradient id="cf4-bike-frame-gradient" x1="0" x2="1" y1="0" y2="1">
                <stop offset="0%" stop-color="#22c55e"/>
                <stop offset="100%" stop-color="#15803d"/>
            </linearGradient>

            <radialGradient id="cf4-bike404-sun-halo" cx="50%" cy="50%" r="50%">
                <stop offset="0%" stop-color="#fde68a" stop-opacity="0.45"/>
                <stop offset="70%" stop-color="#fde68a" stop-opacity="0"/>
            </radialGradient>

            <linearGradient id="cf4-bike404-seat-surface" x1="0" y1="0" x2="0" y2="1">
                <stop offset="0%" stop-color="#166534"/>
                <stop offset="42%" stop-color="#134e4a"/>
                <stop offset="100%" stop-color="#052e16"/>
            </linearGradient>
        </defs>

        {{-- Capas: cielo → sol → nubes → árboles → carretera → skid → obstáculo → cartel → bici → polvo --}}
        <rect class="cf4-bike404-sky" x="0" y="28" width="640" height="348"/>

        <g class="cf4-bike404-sun js-sun" aria-hidden="true">
            <circle class="cf4-bike404-sun-halo" cx="568" cy="78" r="48" fill="url(#cf4-bike404-sun-halo)"/>
            <circle class="cf4-bike404-sun-core" cx="568" cy="78" r="20"/>
            <g class="cf4-bike404-sun-rays" stroke="currentColor" stroke-linecap="round">
                <line x1="568" y1="50" x2="568" y2="44"/>
                <line x1="592" y1="62" x2="598" y2="56"/>
                <line x1="596" y1="78" x2="606" y2="78"/>
                <line x1="592" y1="94" x2="598" y2="100"/>
                <line x1="568" y1="106" x2="568" y2="112"/>
                <line x1="544" y1="94" x2="538" y2="100"/>
                <line x1="540" y1="78" x2="530" y2="78"/>
                <line x1="544" y1="62" x2="538" y2="56"/>
            </g>
        </g>

        <g class="cf4-bike404-clouds js-clouds" aria-hidden="true">
            <g class="cf4-bike404-cloud cf4-bike404-cloud--1" transform="translate(0 8)">
                <path d="M88 122c-16 0-28 12-28 26 0 14 13 26 30 26h146c17 0 30-11 30-24 0-11-10-21-23-23 2-23-19-40-43-37-17 18-43 21-61 12-13 11-34 13-51 10z"/>
            </g>
            <g class="cf4-bike404-cloud cf4-bike404-cloud--2">
                <path d="M348 92c-12 0-22 10-22 21 0 12 11 21 26 21h118c15 0 26-10 26-21 0-10-10-17-21-17 8-26-31-43-53-29-17-8-43-3-53 17-7-8-26-13-43-9z"/>
            </g>
            <g class="cf4-bike404-cloud cf4-bike404-cloud--3" transform="translate(-8 18)">
                <path d="M210 154c-10 0-18 9-18 19 0 11 11 21 26 21h92c13 0 22-10 22-20 0-9-7-16-16-17 10-26-42-43-61-29-21-17-61 13-61 26z"/>
            </g>
        </g>

        <g class="cf4-bike404-trees" aria-hidden="true">
            <g class="cf4-bike404-tree">
                <line x1="92" y1="306" x2="92" y2="262"/>
                <circle cx="92" cy="242" r="26"/>
            </g>
            <g class="cf4-bike404-tree cf4-bike404-tree--sm">
                <line x1="148" y1="298" x2="148" y2="268"/>
                <circle cx="148" cy="254" r="18"/>
            </g>
            <g class="cf4-bike404-tree">
                <line x1="236" y1="312" x2="236" y2="258"/>
                <path d="M236 228 L264 278 L208 278 Z"/>
            </g>
            <g class="cf4-bike404-tree cf4-bike404-tree--sm">
                <line x1="392" y1="302" x2="392" y2="270"/>
                <circle cx="392" cy="248" r="20"/>
            </g>
            <g class="cf4-bike404-tree">
                <line x1="458" y1="306" x2="458" y2="260"/>
                <circle cx="458" cy="238" r="24"/>
            </g>
        </g>

        <g class="cf4-bike404-road js-bike-road">
            <path
                class="cf4-bike404-road-base"
                d="M72 324 C172 298, 252 348, 357 320 C432 300, 492 308, 568 328"
            />
            <path
                class="cf4-bike404-road-dash js-road-dash"
                d="M72 324 C172 298, 252 348, 357 320 C432 300, 492 308, 568 328"
            />
            {{-- Mini tope vía: base gris + cuerpo geométrico + franja (opacity vía GSAP) --}}
            <g class="cf4-bike404-road-break js-road-break">
                <g transform="translate(582 322) rotate(-5)">
                    <rect class="cf4-bike404-curb-pad" x="-26" y="1" width="52" height="5" rx="1.5"/>
                    <rect class="cf4-bike404-curb-body" x="-18" y="-7" width="36" height="7" rx="1.5"/>
                    <rect class="cf4-bike404-curb-cap" x="-18" y="-9" width="36" height="3" rx="1"/>
                    <rect class="cf4-bike404-curb-mark" x="-12" y="-8.5" width="2" height="2" rx="0.3"/>
                    <rect class="cf4-bike404-curb-mark" x="-4" y="-8.5" width="2" height="2" rx="0.3"/>
                    <rect class="cf4-bike404-curb-mark" x="4" y="-8.5" width="2" height="2" rx="0.3"/>
                    <rect class="cf4-bike404-curb-mark" x="12" y="-8.5" width="2" height="2" rx="0.3"/>
                </g>
            </g>
        </g>

        <g class="cf4-bike404-skid js-skid" aria-hidden="true">
            <path d="M167 328 C202 338, 237 338, 280 328"/>
            <path d="M180 342 C210 350, 244 348, 270 340"/>
        </g>

        <g class="cf4-bike404-obstacle js-road-obstacle" aria-hidden="true">
            <g transform="translate(408 312) rotate(-6)">
                <rect class="cf4-bike404-barrier" x="-20" y="-6" width="40" height="11" rx="4"/>
                <line class="cf4-bike404-barrier-stripe" x1="-12" y1="-2" x2="-4" y2="4"/>
                <line class="cf4-bike404-barrier-stripe" x1="-2" y1="-2" x2="6" y2="4"/>
                <line class="cf4-bike404-barrier-stripe" x1="8" y1="-2" x2="16" y2="4"/>
            </g>
        </g>

        <g class="cf4-bike404-sign-anchor" transform="translate(38 -18)">
            <g class="cf4-bike404-sign js-warning-sign">
                <line class="cf4-bike404-sign-post" x1="546" y1="322" x2="546" y2="256"/>
                <circle class="cf4-bike404-sign-cap" cx="546" cy="250" r="4.5"/>

                {{-- Señal compacta tipo carretera: marco metalizado + cara reflectante + prohibido --}}
                <g class="cf4-bike404-sign-board-wrap" transform="translate(546 224)">
                    <rect class="cf4-bike404-sign-panel-outer" x="-36" y="-26" width="72" height="52" rx="7"/>
                    <rect class="cf4-bike404-sign-panel-inner" x="-31" y="-21" width="62" height="42" rx="5"/>
                    <rect class="cf4-bike404-sign-panel-highlight" x="-22" y="-18" width="44" height="9" rx="2.5"/>
                    <circle class="cf4-bike404-sign-bolt" cx="-23" cy="-16" r="2.4"/>
                    <circle class="cf4-bike404-sign-bolt" cx="23" cy="-16" r="2.4"/>
                    <circle class="cf4-bike404-sign-bolt" cx="-23" cy="14" r="2.4"/>
                    <circle class="cf4-bike404-sign-bolt" cx="23" cy="14" r="2.4"/>
                    <circle class="cf4-bike404-sign-no-entry" cx="0" cy="3" r="11"/>
                    <line class="cf4-bike404-sign-slash" x1="-8" y1="-4" x2="9" y2="13"/>
                </g>
            </g>
        </g>

        {{-- Sin filter en bike-float: evita desalineación subpixel del pedalier al frenar/bobbing --}}
        <g class="cf4-bike404-bike-story js-bike-story">
            <g class="cf4-bike404-bike-float js-bike-float">
                <g class="cf4-bike404-bike-float-bob js-bike-float-bob" transform="translate(0 0)">
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

                {{-- Refuerzo en pedalier: tapa huecos entre tubos y animación GSAP --}}
                <circle class="cf4-bike404-frame-weld" cx="336" cy="286" r="10"/>

                <g class="cf4-bike404-frame js-bike-frame">
                    <path class="frame-line frame-main" d="M210 286 L284 205 L336 286 Z"/>
                    <path class="frame-line" d="M284 205 L402 205 L336 286"/>
                    <path class="frame-line" d="M402 205 L430 286"/>
                    <path class="frame-line" d="M284 205 L281 199"/>
                    <path class="frame-line" d="M402 205 L430 164"/>
                    <path class="frame-line fork-line" d="M430 164 L430 286"/>
                </g>

                <g class="cf4-bike404-seat js-seat">
                    {{-- Sillín lateral: cola ancha atrás (x menor), nariz fina adelante, perfiles casi planos arriba --}}
                    <path
                        class="cf4-bike404-seat-rail"
                        d="M276 186 L278 186 L280 199 M284 186 L282 186 L281 199"
                    />
                    <path
                        class="cf4-bike404-seat-shell"
                        d="M262 181 L260 174 L264 167 L272 162 L282 160 L291 162 L296 167 L297 173 L294 179 L286 184 L276 187 L268 186 Z"
                    />
                    <path
                        class="cf4-bike404-seat-top"
                        d="M264 167 L272 163 L284 161 L292 163 L295 168 L290 174 L280 177 L270 175 Z"
                    />
                    <path
                        class="cf4-bike404-seat-highlight"
                        d="M272 164 L284 162 L290 166 L282 171 Z"
                    />
                    <path class="cf4-bike404-seat-crease" d="M274 165 L286 163"/>
                </g>

                <g class="cf4-bike404-handlebar js-handlebar">
                    <line x1="430" y1="164" x2="462" y2="142"/>
                    <path d="M462 142 C488 132, 502 146, 486 160"/>
                </g>

                {{-- BB fijo sólido (sin anillo blanco); solo brazos/pedales en js-crank-spin --}}
                <g class="cf4-bike404-crank-position js-crank-position" transform="translate(336 286)">
                    <circle class="bb-shell-fixed" cx="0" cy="0" r="6.5"/>
                    <g class="cf4-bike404-crank-spin js-crank-spin" transform="rotate(0 0 0)">
                        <line class="crank-arm crank-arm-front" x1="0" y1="0" x2="14" y2="-14"/>
                        <line class="crank-arm crank-arm-back" x1="0" y1="0" x2="-14" y2="14"/>
                        <g class="pedal-group pedal-front" transform="translate(14 -14)">
                            <rect class="pedal" x="-7" y="-2.5" width="14" height="5" rx="2"/>
                        </g>
                        <g class="pedal-group pedal-back" transform="translate(-14 14)">
                            <rect class="pedal" x="-7" y="-2.5" width="14" height="5" rx="2"/>
                        </g>
                    </g>
                </g>
                </g>
            </g>
        </g>

        <g class="cf4-bike404-dust js-dust">
            <circle cx="192" cy="312" r="4"/>
            <circle cx="176" cy="322" r="3"/>
            <circle cx="160" cy="316" r="2.5"/>
            <circle cx="205" cy="328" r="2.5"/>
        </g>
    </svg>
</div>
