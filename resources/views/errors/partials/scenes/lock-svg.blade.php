{{-- 403 · Candado CF4: composición completa — fondo con anillos y puntos, escudo concéntrico,
     candado grande con cuerpo en degradado de marca y grillete metálico pulido. --}}
<svg class="cf4-scene-svg" viewBox="0 0 320 320" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">
    <defs>
        <linearGradient id="lk-bg" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0%" stop-color="#f2faf5"/>
            <stop offset="60%" stop-color="#e3f1e8"/>
            <stop offset="100%" stop-color="#d2e7da"/>
        </linearGradient>
        <radialGradient id="lk-vignette" cx="50%" cy="44%" r="78%">
            <stop offset="58%" stop-color="#051f20" stop-opacity="0"/>
            <stop offset="100%" stop-color="#051f20" stop-opacity="0.08"/>
        </radialGradient>
        <radialGradient id="lk-shield" cx="50%" cy="42%" r="60%">
            <stop offset="0%" stop-color="#ffffff" stop-opacity="0.72"/>
            <stop offset="72%" stop-color="#ffffff" stop-opacity="0.34"/>
            <stop offset="100%" stop-color="#ffffff" stop-opacity="0"/>
        </radialGradient>
        <linearGradient id="lk-body" x1="18%" y1="0%" x2="82%" y2="100%">
            <stop offset="0%" stop-color="#375c50"/>
            <stop offset="45%" stop-color="#1d423a"/>
            <stop offset="100%" stop-color="#0a221e"/>
        </linearGradient>
        <linearGradient id="lk-shackle" x1="0" y1="0" x2="1" y2="0">
            <stop offset="0%" stop-color="#8fa1aa"/>
            <stop offset="30%" stop-color="#f4f7f8"/>
            <stop offset="62%" stop-color="#b6c4cb"/>
            <stop offset="100%" stop-color="#6f8893"/>
        </linearGradient>
        <linearGradient id="lk-edge" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0%" stop-color="#ffffff" stop-opacity="0.3"/>
            <stop offset="100%" stop-color="#ffffff" stop-opacity="0"/>
        </linearGradient>
        <pattern id="lk-dots" width="24" height="24" patternUnits="userSpaceOnUse">
            <circle cx="3" cy="3" r="1.5" fill="#235347" opacity="0.1"/>
        </pattern>
    </defs>

    {{-- fondo --}}
    <rect width="320" height="320" fill="url(#lk-bg)"/>
    <rect width="320" height="320" fill="url(#lk-dots)"/>

    {{-- anillos decorativos concéntricos detrás del candado --}}
    <g fill="none" stroke="#8eb69b">
        <circle cx="160" cy="160" r="142" stroke-width="1.5" stroke-opacity="0.22" stroke-dasharray="3 9" stroke-linecap="round"/>
        <circle cx="160" cy="160" r="124" stroke-width="1.5" stroke-opacity="0.34"/>
        <circle cx="160" cy="160" r="106" stroke-width="10" stroke-opacity="0.14"/>
    </g>
    <circle cx="160" cy="160" r="101" fill="url(#lk-shield)"/>

    {{-- destellos --}}
    <g fill="#235347">
        <path d="M58 74 60 80 66 82 60 84 58 90 56 84 50 82 56 80Z" opacity="0.3"/>
        <path d="M262 96 263.6 100.6 268.2 102.2 263.6 103.8 262 108.4 260.4 103.8 255.8 102.2 260.4 100.6Z" opacity="0.26"/>
        <path d="M246 240 247.4 244 251.4 245.4 247.4 246.8 246 250.8 244.6 246.8 240.6 245.4 244.6 244Z" opacity="0.22"/>
        <circle cx="70" cy="236" r="2.4" opacity="0.18"/>
        <circle cx="270" cy="170" r="2" opacity="0.16"/>
        <circle cx="48" cy="160" r="2" opacity="0.16"/>
    </g>

    <g id="lock" transform="translate(160, 170)">
        {{-- sombra suave en el piso --}}
        <ellipse cx="0" cy="98" rx="76" ry="11" fill="#051f20" opacity="0.16"/>
        <ellipse cx="0" cy="98" rx="50" ry="7" fill="#051f20" opacity="0.1"/>

        {{-- grillete metálico en U --}}
        <path
            d="M -46,-12 Q 0,-150 46,-12"
            fill="none"
            stroke="url(#lk-shackle)"
            stroke-width="19"
            stroke-linecap="round"
            stroke-linejoin="round"
        />
        <path
            d="M -46,-12 Q 0,-150 46,-12"
            fill="none"
            stroke="#263238"
            stroke-opacity="0.2"
            stroke-width="3"
            stroke-linecap="round"
        />
        {{-- brillo del grillete --}}
        <path
            d="M -40,-26 Q -14,-122 30,-100"
            fill="none"
            stroke="#ffffff"
            stroke-opacity="0.55"
            stroke-width="4"
            stroke-linecap="round"
        />

        {{-- cuerpo --}}
        <rect x="-74" y="-20" width="148" height="112" rx="24" fill="url(#lk-body)" stroke="#051f20" stroke-opacity="0.5" stroke-width="1.5"/>
        <rect x="-66" y="-13" width="132" height="26" rx="13" fill="url(#lk-edge)"/>

        {{-- remaches --}}
        <g fill="#daf1de" opacity="0.4">
            <circle cx="-58" cy="-4" r="3"/>
            <circle cx="58" cy="-4" r="3"/>
            <circle cx="-58" cy="76" r="3"/>
            <circle cx="58" cy="76" r="3"/>
        </g>

        {{-- bocallave --}}
        <circle cx="0" cy="30" r="17" fill="#8eb69b" opacity="0.16"/>
        <circle cx="0" cy="30" r="11.5" fill="#051f20"/>
        <rect x="-5" y="34" width="10" height="28" rx="4" fill="#051f20"/>
        <circle cx="-3.5" cy="26.5" r="3" fill="#8eb69b" opacity="0.5"/>
    </g>
</svg>
