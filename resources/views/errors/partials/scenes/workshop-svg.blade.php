{{-- Taller 500/503 · bicicleta invertida en reparación, panel de herramientas y piso de taller.
     Los ids #gear y #wrench los anima resources/js/errors/scenes/workshop.ts — no renombrar. --}}
<svg class="cf4-scene-svg" viewBox="0 0 320 320" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">
    <defs>
        <linearGradient id="ws-bg" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0%" stop-color="#f5faf6"/>
            <stop offset="100%" stop-color="#dfeee6"/>
        </linearGradient>
        <linearGradient id="ws-floor" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0%" stop-color="#1a3d36"/>
            <stop offset="100%" stop-color="#0b1f1c"/>
        </linearGradient>
        <linearGradient id="ws-metal" x1="0" y1="0" x2="1" y2="1">
            <stop offset="0%" stop-color="#c5ddd0"/>
            <stop offset="100%" stop-color="#6d9a7d"/>
        </linearGradient>
        <linearGradient id="ws-frame" x1="0" y1="0" x2="1" y2="1">
            <stop offset="0%" stop-color="#357a63"/>
            <stop offset="100%" stop-color="#163832"/>
        </linearGradient>
        <linearGradient id="ws-tire" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0%" stop-color="#26302c"/>
            <stop offset="100%" stop-color="#0c1210"/>
        </linearGradient>
    </defs>

    {{-- pared --}}
    <rect width="320" height="252" fill="url(#ws-bg)"/>

    {{-- panel de herramientas (pegboard) --}}
    <g transform="translate(26, 32)">
        <rect width="112" height="88" rx="10" fill="#ffffff" opacity="0.55"/>
        <rect width="112" height="88" rx="10" fill="none" stroke="#8eb69b" stroke-opacity="0.55" stroke-width="1.5"/>
        <g fill="#235347" opacity="0.12">
            @for ($row = 0; $row < 4; $row++)
                @for ($col = 0; $col < 6; $col++)
                    <circle cx="{{ 13 + $col * 17.5 }}" cy="{{ 13 + $row * 21 }}" r="1.6"/>
                @endfor
            @endfor
        </g>
        <g fill="#235347" opacity="0.34">
            {{-- destornillador --}}
            <rect x="16" y="14" width="9" height="14" rx="3"/>
            <rect x="18.5" y="28" width="4" height="32" rx="1.5"/>
            {{-- martillo --}}
            <rect x="42" y="16" width="26" height="11" rx="2.5"/>
            <rect x="52.5" y="27" width="5" height="38" rx="2"/>
            {{-- llave fija --}}
            <circle cx="92" cy="20" r="7"/>
            <circle cx="92" cy="20" r="3" fill="#eaf4ee"/>
            <rect x="89.5" y="26" width="5" height="36" rx="2"/>
        </g>
    </g>

    {{-- piso --}}
    <rect y="252" width="320" height="68" fill="url(#ws-floor)"/>
    <line x1="0" y1="252" x2="320" y2="252" stroke="#8eb69b" stroke-opacity="0.4" stroke-width="1.5"/>
    <g stroke="#ffffff" stroke-opacity="0.06" stroke-width="1.5">
        <line x1="0" y1="278" x2="320" y2="274"/>
        <line x1="0" y1="302" x2="320" y2="300"/>
    </g>

    {{-- sombra de la bici --}}
    <ellipse cx="165" cy="254" rx="112" ry="9" fill="#000" opacity="0.2"/>

    {{-- bicicleta invertida (apoyada en sillín y manubrio) --}}
    <g>
        {{-- ruedas hacia arriba --}}
        @foreach ([95, 225] as $hubX)
            <g transform="translate({{ $hubX }}, 128)">
                <circle r="46" fill="none" stroke="url(#ws-tire)" stroke-width="11"/>
                <circle r="38" fill="none" stroke="#d8e6dd" stroke-width="3"/>
                <g stroke="#9fb8a9" stroke-width="1.8" stroke-linecap="round">
                    @for ($i = 0; $i < 8; $i++)
                        <line x1="0" y1="0" x2="0" y2="-36.5" transform="rotate({{ $i * 45 }})"/>
                    @endfor
                </g>
                <circle r="7" fill="#163832" stroke="#8eb69b" stroke-width="1.5"/>
            </g>
        @endforeach

        {{-- transmisión --}}
        <circle cx="95" cy="128" r="8" fill="none" stroke="#163832" stroke-width="2.5" opacity="0.8"/>
        <g stroke="#163832" stroke-width="2.5" stroke-dasharray="4 3" opacity="0.65">
            <line x1="151" y1="155" x2="96" y2="121"/>
            <line x1="151" y1="181" x2="96" y2="135"/>
        </g>

        {{-- cuadro (diamante invertido) --}}
        <g fill="none" stroke="url(#ws-frame)" stroke-width="7" stroke-linecap="round" stroke-linejoin="round">
            <path d="M95,128 L152,168 L104,212 Z"/>
            <path d="M104,212 L220,206 L152,168"/>
            <path d="M225,128 L220,206"/>
        </g>

        {{-- plato y bielas --}}
        <circle cx="152" cy="168" r="14" fill="none" stroke="#163832" stroke-width="3.5"/>
        <circle cx="152" cy="168" r="4.5" fill="#163832"/>
        <line x1="152" y1="168" x2="171" y2="152" stroke="#0b1f1c" stroke-width="4" stroke-linecap="round"/>
        <rect x="167" y="146" width="15" height="6.5" rx="2.5" fill="#235347"/>
        <line x1="152" y1="168" x2="133" y2="184" stroke="#0b1f1c" stroke-width="4" stroke-linecap="round" opacity="0.55"/>

        {{-- tija y sillín contra el piso --}}
        <line x1="104" y1="212" x2="100" y2="238" stroke="url(#ws-frame)" stroke-width="6" stroke-linecap="round"/>
        <rect x="77" y="240" width="47" height="11" rx="5.5" fill="#163832"/>

        {{-- potencia y manubrio contra el piso --}}
        <line x1="220" y1="206" x2="229" y2="224" stroke="url(#ws-frame)" stroke-width="6" stroke-linecap="round"/>
        <path d="M229,224 q15,3 17,24" fill="none" stroke="#163832" stroke-width="6.5" stroke-linecap="round"/>
        <circle cx="246" cy="248" r="4.5" fill="#0b1f1c"/>
    </g>

    {{-- engranaje de repuesto junto a la rueda delantera (lo gira workshop.ts) --}}
    <ellipse cx="282" cy="254" rx="30" ry="5" fill="#000" opacity="0.16"/>
    <g id="gear" transform="translate(282, 222)">
        <circle r="26" fill="#163832" stroke="#0b1f1c" stroke-width="2"/>
        <circle r="7.5" fill="#0b1f1c" stroke="#8eb69b" stroke-width="1.5"/>
        @for ($i = 0; $i < 10; $i++)
            <rect x="-3" y="-31.5" width="6" height="11" rx="1" fill="#235347" transform="rotate({{ $i * 36 }})"/>
        @endfor
    </g>

    {{-- llave apoyada junto a la rueda trasera (la mece workshop.ts) --}}
    <ellipse cx="42" cy="251" rx="20" ry="4" fill="#000" opacity="0.16"/>
    <g id="wrench" transform="translate(38, 236) rotate(-24)">
        <rect x="-3.5" y="-38" width="7" height="48" rx="2" fill="url(#ws-metal)" stroke="#163832" stroke-width="1"/>
        <circle cx="0" cy="-44" r="12" fill="#eaf4ee" stroke="#235347" stroke-width="1.8"/>
        <circle cx="0" cy="-44" r="5" fill="#163832"/>
    </g>
</svg>
