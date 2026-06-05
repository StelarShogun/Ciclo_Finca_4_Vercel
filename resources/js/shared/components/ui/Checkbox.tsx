import type { InputHTMLAttributes, ReactNode } from 'react';

type CheckboxProps = Omit<InputHTMLAttributes<HTMLInputElement>, 'type'> & {
  label?: ReactNode;
  labelClassName?: string;
};

export function Checkbox({ className = '', id, label, labelClassName = '', ...props }: CheckboxProps) {
  const input = <input type="checkbox" className={className} id={id} {...props} />;

  if (!label) {
    return input;
  }

  return (
    <label className={labelClassName} htmlFor={id}>
      {input}
      <span>{label}</span>
    </label>
  );
}
