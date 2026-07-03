export function ProfileField({
  error,
  fullWidth,
  id,
  label,
  maxLength,
  minLength,
  onChange,
  placeholder,
  readOnly,
  required,
  type = 'text',
  value,
}: {
  id: string;
  label: string;
  value: string;
  readOnly: boolean;
  error?: string;
  onChange: (value: string) => void;
  type?: string;
  required?: boolean;
  minLength?: number;
  maxLength?: number;
  placeholder?: string;
  fullWidth?: boolean;
}) {
  return (
    <div className={`form-group${fullWidth ? ' profile-field-full' : ''}`}>
      <label htmlFor={id}>{label}</label>
      <input
        type={type}
        id={id}
        name={id}
        className="form-control"
        value={value}
        readOnly={readOnly}
        required={required}
        minLength={minLength}
        maxLength={maxLength}
        placeholder={placeholder}
        onChange={(e) => onChange(e.target.value)}
      />
      {error ? (
        <span className="profile-field-error">
          <i className="fas fa-exclamation-circle" /> {error}
        </span>
      ) : null}
    </div>
  );
}
