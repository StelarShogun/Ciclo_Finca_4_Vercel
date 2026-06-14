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
        <radialGradient id="cf4MoonBody" cx="23.6" cy="11.4" r="11" gradientUnits="userSpaceOnUse">
          <stop offset="0%" stopColor="#fdfcfb" />
          <stop offset="48%" stopColor="#e7e5e4" />
          <stop offset="100%" stopColor="#c8c4c0" />
        </radialGradient>
        <radialGradient id="cf4MoonShade" cx="26" cy="14" r="7.5" gradientUnits="userSpaceOnUse">
          <stop offset="0%" stopColor="#78716c" stopOpacity="0.55" />
          <stop offset="100%" stopColor="#57534e" stopOpacity="0.15" />
        </radialGradient>
        <radialGradient id="cf4MoonHalo" cx="26" cy="14" r="10.5" gradientUnits="userSpaceOnUse">
          <stop offset="62%" stopColor="#f8fafc" stopOpacity="0.3" />
          <stop offset="80%" stopColor="#f8fafc" stopOpacity="0.12" />
          <stop offset="100%" stopColor="#f8fafc" stopOpacity="0" />
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
        <circle className="cf4-theme-moon__halo" cx="26" cy="14" r="10.5" fill="url(#cf4MoonHalo)" />
        <circle className="cf4-theme-moon__body" cx="26" cy="14" r="7.25" fill="url(#cf4MoonBody)" />
        <ellipse className="cf4-theme-moon__crater cf4-theme-moon__crater--lg" cx="28.3" cy="12.1" rx="1.6" ry="1.2" fill="url(#cf4MoonShade)" />
        <ellipse className="cf4-theme-moon__crater cf4-theme-moon__crater--md" cx="23.5" cy="15.3" rx="1.15" ry="0.85" fill="url(#cf4MoonShade)" />
        <ellipse className="cf4-theme-moon__crater cf4-theme-moon__crater--sm" cx="26.8" cy="17.1" rx="0.7" ry="0.52" fill="url(#cf4MoonShade)" />
        <ellipse className="cf4-theme-moon__crater cf4-theme-moon__crater--xs" cx="24.6" cy="11" rx="0.46" ry="0.36" fill="#78716c" opacity="0.35" />
        <path
          className="cf4-theme-moon__terminator"
          fill="#1e293b"
          opacity="0.16"
          d="M26 6.75 A7.25 7.25 0 0 1 26 21.25 A10 10 0 0 0 26 6.75 Z"
        />
        <circle
          className="cf4-theme-moon__rim"
          cx="26"
          cy="14"
          r="7.25"
          fill="none"
          stroke="#fff"
          strokeOpacity="0.22"
          strokeWidth="0.6"
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
