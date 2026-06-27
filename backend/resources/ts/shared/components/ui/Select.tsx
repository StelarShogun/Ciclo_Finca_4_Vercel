import type { SelectHTMLAttributes } from 'react';

type SelectProps = SelectHTMLAttributes<HTMLSelectElement> & {
  invalid?: boolean;
};

export function Select({ className = '', invalid = false, ...props }: SelectProps) {
  return (
    <select
      className={`form-control ${invalid ? 'is-invalid' : ''} ${className}`.trim()}
      aria-invalid={invalid || undefined}
      {...props}
    />
  );
}
