{{-- Taller 500/503 · misma línea CF4: limpio, sin marrón pesado. --}}
<svg class="cf4-scene-svg" viewBox="0 0 320 320" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">
    <defs>
        <linearGradient id="ws-bg" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0%" stop-color="#f6faf7"/>
            <stop offset="100%" stop-color="#dfece4"/>
        </linearGradient>
        <linearGradient id="ws-floor" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0%" stop-color="#1a3d36"/>
            <stop offset="100%" stop-color="#0b1f1c"/>
        </linearGradient>
        <linearGradient id="ws-metal" x1="0" y1="0" x2="1" y2="1">
            <stop offset="0%" stop-color="#c5ddd0"/>
            <stop offset="100%" stop-color="#6d9a7d"/>
        </linearGradient>
        <linearGradient id="ws-tire" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0%" stop-color="#1a1a1a"/>
            <stop offset="100%" stop-color="#0a0a0a"/>
        </linearGradient>
    </defs>
    <rect width="320" height="320" fill="url(#ws-bg)"/>
    <path d="M0,232 L320,232 L320,320 L0,320 Z" fill="url(#ws-floor)"/>
    <path d="M0,232 L320,232 L300,320 L20,320 Z" fill="#000" opacity="0.12"/>
    <ellipse cx="160" cy="232" rx="100" ry="12" fill="#8eb69b" opacity="0.15"/>
    <g transform="translate(160, 200)">
        <ellipse cx="0" cy="16" rx="68" ry="9" fill="#000" opacity="0.14"/>
        <rect x="-86" y="-118" width="172" height="6" rx="2" fill="#163832"/>
        <rect x="-5" y="-112" width="10" height="108" rx="2" fill="url(#ws-metal)" stroke="#163832" stroke-width="1" opacity="0.95"/>
        <rect x="-72" y="-105" width="7" height="92" rx="2" fill="url(#ws-metal)" stroke="#163832" stroke-width="1" transform="rotate(-5)"/>
        <rect x="65" y="-102" width="7" height="90" rx="2" fill="url(#ws-metal)" stroke="#163832" stroke-width="1" transform="rotate(4)"/>
        <circle cx="-44" cy="10" r="24" fill="url(#ws-tire)"/>
        <circle cx="-44" cy="10" r="14" fill="#2d4a3e"/>
        <circle cx="48" cy="10" r="24" fill="url(#ws-tire)"/>
        <circle cx="48" cy="10" r="14" fill="#2d4a3e"/>
        <path d="M-44,10 L-6,-72 L48,10 L10,10 Z" fill="none" stroke="#235347" stroke-width="4" stroke-linejoin="round"/>
        <path d="M-6,-72 L10,-98 L30,-66" fill="none" stroke="#235347" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round"/>
        <rect x="-18" y="-108" width="36" height="12" rx="3" fill="#163832" opacity="0.85"/>
    </g>
    <g id="gear" transform="translate(248, 88)">
        <circle r="34" fill="#163832" stroke="#0b1f1c" stroke-width="2"/>
        <circle r="9" fill="#0b1f1c" stroke="#8eb69b" stroke-width="1.5"/>
        @for ($i = 0; $i < 10; $i++)
            @php $a = $i * 36; @endphp
            <rect x="-3" y="-40" width="6" height="13" rx="1" fill="#235347" transform="rotate({{ $a }})"/>
        @endfor
    </g>
    <g id="wrench" transform="translate(72, 98) rotate(-30)">
        <rect x="-4" y="-42" width="8" height="52" rx="2" fill="url(#ws-metal)" stroke="#163832" stroke-width="1"/>
        <circle cx="0" cy="-50" r="14" fill="#eaf4ee" stroke="#235347" stroke-width="1.8"/>
        <circle cx="0" cy="-50" r="6" fill="#163832"/>
    </g>
</svg>
