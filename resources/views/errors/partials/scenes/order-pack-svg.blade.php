{{-- Paquete · tonos neutros + acento verde CF4, sin cinta naranja chillona. --}}
<svg class="cf4-scene-svg" viewBox="0 0 320 320" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">
    <defs>
        <linearGradient id="op-bg" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0%" stop-color="#f4faf6"/>
            <stop offset="100%" stop-color="#e3efe6"/>
        </linearGradient>
        <linearGradient id="op-box" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0%" stop-color="#f5f0ea"/>
            <stop offset="55%" stop-color="#e8dfd4"/>
            <stop offset="100%" stop-color="#d4c9bc"/>
        </linearGradient>
        <linearGradient id="op-tape" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0%" stop-color="#8eb69b"/>
            <stop offset="100%" stop-color="#235347"/>
        </linearGradient>
        <filter id="op-soft" x="-25%" y="-25%" width="150%" height="150%">
            <feGaussianBlur in="SourceAlpha" stdDeviation="2.5" result="b"/>
            <feOffset in="b" dy="3" result="o"/>
            <feMerge>
                <feMergeNode in="o"/>
                <feMergeNode in="SourceGraphic"/>
            </feMerge>
        </filter>
    </defs>
    <rect width="320" height="320" fill="url(#op-bg)"/>
    <ellipse cx="160" cy="258" rx="96" ry="14" fill="#163832" opacity="0.06"/>
    <g transform="translate(160, 168)">
        <g id="package" filter="url(#op-soft)">
            <rect x="-48" y="-46" width="96" height="56" rx="5" fill="url(#op-box)" stroke="#8d8578" stroke-width="1.8"/>
            <path d="M-48,-46 L0,-72 L48,-46" fill="#ebe4dc" stroke="#8d8578" stroke-width="1.8" stroke-linejoin="round"/>
            <path d="M0,-46 L0,10" fill="none" stroke="#a69f94" stroke-width="1.2" opacity="0.45"/>
            <rect x="-3" y="-46" width="6" height="56" rx="1" fill="url(#op-tape)" opacity="0.9"/>
            <path d="M-48,-6 L48,-6" stroke="#235347" stroke-width="7" stroke-linecap="round" opacity="0.35"/>
            <rect x="-34" y="-36" width="68" height="18" rx="3" fill="#faf8f6" stroke="#c4bbb0" stroke-width="1"/>
            <path d="M-26,-30 L26,-30" stroke="#163832" stroke-opacity="0.08" stroke-width="1"/>
            <path d="M-26,-24 L10,-24" stroke="#163832" stroke-opacity="0.06" stroke-width="1"/>
            <text x="0" y="-22" text-anchor="middle" font-size="8" fill="#235347" font-family="system-ui,sans-serif" font-weight="700" letter-spacing="0.2em" opacity="0.55">CF4</text>
        </g>
    </g>
</svg>
