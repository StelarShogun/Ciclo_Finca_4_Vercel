type ClientThemeToggleProps = {
  onToggle: () => void;
};

function SunGlyph() {
  return (
    <svg
      className="cf4-theme-glyph cf4-theme-glyph--sun"
      viewBox="0 0 52 28"
      xmlns="http://www.w3.org/2000/svg"
      aria-hidden="true"
      focusable="false"
      preserveAspectRatio="none"
    >
      <defs>
        <linearGradient id="cf4SunBg" x1="4" y1="2" x2="48" y2="26" gradientUnits="userSpaceOnUse">
          <stop offset="0%" stopColor="#fff7ed" />
          <stop offset="42%" stopColor="#fbbf24" />
          <stop offset="100%" stopColor="#ea580c" />
        </linearGradient>
        <radialGradient id="cf4SunCore" cx="26" cy="14" r="9" gradientUnits="userSpaceOnUse">
          <stop offset="0%" stopColor="#fffbeb" />
          <stop offset="55%" stopColor="#fde68a" />
          <stop offset="100%" stopColor="#f59e0b" />
        </radialGradient>
      </defs>
      <rect className="cf4-theme-sun__bg" width="52" height="28" rx="14" fill="url(#cf4SunBg)" />
      <g className="cf4-theme-sun__flames" fill="#ea580c">
        <path d="M26 1.2 27.1 7.2 26 5.8 24.9 7.2Z" />
        <path d="M26 1.2 27.1 7.2 26 5.8 24.9 7.2Z" transform="rotate(45 26 14)" />
        <path d="M26 1.2 27.1 7.2 26 5.8 24.9 7.2Z" transform="rotate(90 26 14)" />
        <path d="M26 1.2 27.1 7.2 26 5.8 24.9 7.2Z" transform="rotate(135 26 14)" />
        <path d="M26 1.2 27.1 7.2 26 5.8 24.9 7.2Z" transform="rotate(180 26 14)" />
        <path d="M26 1.2 27.1 7.2 26 5.8 24.9 7.2Z" transform="rotate(225 26 14)" />
        <path d="M26 1.2 27.1 7.2 26 5.8 24.9 7.2Z" transform="rotate(270 26 14)" />
        <path d="M26 1.2 27.1 7.2 26 5.8 24.9 7.2Z" transform="rotate(315 26 14)" />
      </g>
      <circle className="cf4-theme-sun__core" cx="26" cy="14" r="7.25" fill="url(#cf4SunCore)" />
      <circle className="cf4-theme-sun__highlight" cx="24.2" cy="12.1" r="2.1" fill="#fff" opacity="0.42" />
    </svg>
  );
}

function MoonGlyph() {
  return (
    <svg
      className="cf4-theme-glyph cf4-theme-glyph--moon"
      viewBox="0 0 52 28"
      xmlns="http://www.w3.org/2000/svg"
      aria-hidden="true"
      focusable="false"
      preserveAspectRatio="none"
    >
      <defs>
        <linearGradient id="cf4MoonBg" x1="6" y1="0" x2="46" y2="28" gradientUnits="userSpaceOnUse">
          <stop offset="0%" stopColor="#64748b" />
          <stop offset="38%" stopColor="#334155" />
          <stop offset="72%" stopColor="#1e293b" />
          <stop offset="100%" stopColor="#0f172a" />
        </linearGradient>
        <radialGradient id="cf4MoonBody" cx="26" cy="14" r="9" gradientUnits="userSpaceOnUse">
          <stop offset="0%" stopColor="#fffbeb" />
          <stop offset="55%" stopColor="#e7e5e4" />
          <stop offset="100%" stopColor="#d6d3d1" />
        </radialGradient>
        <radialGradient id="cf4MoonShade" cx="27" cy="14.5" r="7" gradientUnits="userSpaceOnUse">
          <stop offset="0%" stopColor="#78716c" stopOpacity="0.55" />
          <stop offset="100%" stopColor="#57534e" stopOpacity="0.15" />
        </radialGradient>
      </defs>
      <rect className="cf4-theme-moon__bg" width="52" height="28" rx="14" fill="url(#cf4MoonBg)" />
      <g className="cf4-theme-moon__scene">
        <g className="cf4-theme-moon__stars" fill="#f8fafc">
          <path
            className="cf4-theme-moon__star cf4-theme-moon__star--a"
            d="M10.5 8.2 10.9 9.2 11.9 9.6 10.9 10 10.5 11 10.1 10 9.1 9.6 10.1 9.2Z"
            opacity="0.9"
          />
          <path
            className="cf4-theme-moon__star cf4-theme-moon__star--b"
            d="M41.5 8.4 41.8 9.2 42.6 9.5 41.8 9.8 41.5 10.6 41.2 9.8 40.4 9.5 41.2 9.2Z"
            opacity="0.82"
          />
          <circle className="cf4-theme-moon__star cf4-theme-moon__star--c" cx="9.5" cy="18.8" r="0.45" opacity="0.65" />
          <circle className="cf4-theme-moon__star cf4-theme-moon__star--d" cx="42.5" cy="19.2" r="0.42" opacity="0.6" />
        </g>
        <path
          className="cf4-theme-moon__body"
          fill="url(#cf4MoonBody)"
          d="M32.8 14a8.5 8.5 0 1 1-13.6 0 7 7 0 1 0 13.6 0z"
        />
        <ellipse className="cf4-theme-moon__crater cf4-theme-moon__crater--lg" cx="27.2" cy="11.2" rx="1.75" ry="1.25" fill="url(#cf4MoonShade)" />
        <ellipse className="cf4-theme-moon__crater cf4-theme-moon__crater--md" cx="24.4" cy="14.6" rx="1.2" ry="0.9" fill="url(#cf4MoonShade)" />
        <ellipse className="cf4-theme-moon__crater cf4-theme-moon__crater--sm" cx="27.6" cy="16.1" rx="0.75" ry="0.55" fill="url(#cf4MoonShade)" />
        <ellipse className="cf4-theme-moon__crater cf4-theme-moon__crater--xs" cx="25.8" cy="12.8" rx="0.48" ry="0.36" fill="#78716c" opacity="0.35" />
        <path
          className="cf4-theme-moon__rim"
          fill="none"
          stroke="#fff"
          strokeOpacity="0.22"
          strokeWidth="0.6"
          d="M32.8 14a8.5 8.5 0 1 1-13.6 0 7 7 0 1 0 13.6 0z"
        />
      </g>
    </svg>
  );
}

export function ClientThemeToggle({ onToggle }: ClientThemeToggleProps) {
  return (
    <button
      type="button"
      className="theme-toggle-btn theme-toggle-btn--compact"
      aria-label="Cambiar tema"
      onClick={onToggle}
    >
      <span className="theme-toggle-btn__track" aria-hidden="true">
        <span className="theme-toggle-btn__icon theme-toggle-btn__icon--sun">
          <SunGlyph />
        </span>
        <span className="theme-toggle-btn__icon theme-toggle-btn__icon--moon">
          <MoonGlyph />
        </span>
      </span>
    </button>
  );
}
