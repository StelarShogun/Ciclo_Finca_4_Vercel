<svg id="scene" viewBox="0 0 1024 1024" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">

    {{-- ══════════════════════════════════════════════════════════════
         Ilustración 1024×1024 en viewBox cuadrado = mismo aspecto que
         el PNG: sin letterboxing ni slice; cubre todo el contenedor.

         Ruedas (coords en espacio fuente): trasera ~(242, 732), delantera ~(710, 732)
    ══════════════════════════════════════════════════════════════ --}}

    <g id="bike-group">

        <image
            href="{{ asset('images/errors/404-bike-illustration-orig.png') }}"
            x="0" y="0" width="1024" height="1024"
            preserveAspectRatio="xMidYMid meet"
        />

        {{-- Rueda trasera --}}
        <g id="back-wheel" transform="translate(242, 731.5)">
            <circle r="145"
                    fill="none"
                    stroke="#d4a83a"
                    stroke-width="6"
                    stroke-dasharray="16 22"
                    opacity="0.42"/>
            <circle r="14" fill="#2c2c2c" opacity="0.48"/>
            <circle r="6"  fill="#a0a0a0" opacity="0.48"/>
        </g>

        {{-- Rueda delantera --}}
        <g id="front-wheel" transform="translate(709.5, 731.5)">
            <circle r="145"
                    fill="none"
                    stroke="#d4a83a"
                    stroke-width="6"
                    stroke-dasharray="16 22"
                    opacity="0.42"/>
            <circle r="14" fill="#2c2c2c" opacity="0.48"/>
            <circle r="6"  fill="#a0a0a0" opacity="0.48"/>
        </g>

        <g id="music-notes">
            <text id="note-1"
                  x="560" y="238"
                  font-size="38" font-family="Georgia, 'Times New Roman', serif"
                  fill="#2d4a73">♪</text>
            <text id="note-2"
                  x="602" y="210"
                  font-size="32" font-family="Georgia, 'Times New Roman', serif"
                  fill="#3a5d94">♫</text>
            <text id="note-3"
                  x="636" y="228"
                  font-size="35" font-family="Georgia, 'Times New Roman', serif"
                  fill="#4a6fa5">♩</text>
        </g>

        <g id="dust" transform="translate(124, 902)">
            <ellipse cx="0"   cy="0"   rx="32" ry="14" fill="#c4a35a" opacity="0.72"/>
            <ellipse cx="46"  cy="-10" rx="20" ry="10" fill="#b8924a" opacity="0.56"/>
            <ellipse cx="-26" cy="-6"  rx="16" ry="9"  fill="#c4a35a" opacity="0.50"/>
        </g>

    </g>

</svg>
