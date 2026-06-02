import type { PropsWithChildren } from 'react';

type FieldGroupProps = PropsWithChildren<{
  label: string;
  htmlFor?: string;
  hint?: string;
}>;

export function FieldGroup({ children, hint, htmlFor, label }: FieldGroupProps) {
  return (
    <div className="form-group">
      <label htmlFor={htmlFor}>{label}</label>
      {children}
      {hint ? <p className="form-hint">{hint}</p> : null}
    </div>
  );
}
