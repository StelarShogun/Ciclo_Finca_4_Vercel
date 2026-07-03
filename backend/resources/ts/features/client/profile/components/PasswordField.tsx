export function PasswordField({
  autoComplete,
  error,
  fullWidth,
  id,
  label,
  minLength,
  onChange,
  onToggle,
  placeholder,
  strength,
  value,
  visible,
}: {
  id: string;
  label: string;
  value: string;
  error?: string;
  visible?: boolean;
  onToggle: () => void;
  onChange: (value: string) => void;
  placeholder?: string;
  minLength?: number;
  autoComplete?: string;
  fullWidth?: boolean;
  strength?: { width: string; color: string; label: string } | null;
}) {
  return (
    <div className={`form-group${fullWidth ? ' profile-field-full' : ''}`}>
      <label htmlFor={id}>{label}</label>
      <div className="profile-input-pass">
        <input
          type={visible ? 'text' : 'password'}
          id={id}
          name={id}
          className="form-control"
          value={value}
          placeholder={placeholder}
          minLength={minLength}
          autoComplete={autoComplete}
          onChange={(e) => onChange(e.target.value)}
        />
        <button type="button" className="profile-toggle-pass" onClick={onToggle} aria-label="Mostrar u ocultar contraseña">
          <i className={`fas ${visible ? 'fa-eye-slash' : 'fa-eye'}`} />
        </button>
      </div>
      {strength ? (
        <div id="passStrength" className="profile-strength">
          <div className="profile-strength-bar">
            <div
              className="profile-strength-fill"
              id="strengthFill"
              style={{ width: strength.width, background: strength.color }}
            />
          </div>
          <span id="strengthLabel" style={{ color: strength.color }}>
            {strength.label}
          </span>
        </div>
      ) : null}
      {error ? (
        <span className="profile-field-error">
          <i className="fas fa-exclamation-circle" /> {error}
        </span>
      ) : null}
    </div>
  );
}
