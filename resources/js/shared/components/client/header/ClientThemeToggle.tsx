type ClientThemeToggleProps = {
  onToggle: () => void;
};

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
          <i className="fas fa-sun" />
        </span>
        <span className="theme-toggle-btn__icon theme-toggle-btn__icon--moon">
          <i className="fas fa-moon" />
        </span>
      </span>
    </button>
  );
}
