import type { ButtonHTMLAttributes, PropsWithChildren } from 'react';

type ButtonVariant = 'primary' | 'secondary' | 'ghost' | 'danger';

type ButtonProps = PropsWithChildren<
  ButtonHTMLAttributes<HTMLButtonElement> & {
    variant?: ButtonVariant;
  }
>;

const variantClass: Record<ButtonVariant, string> = {
  primary: 'btn btn-primary',
  secondary: 'btn btn-secondary',
  ghost: 'btn btn-ghost-cart',
  danger: 'btn btn-outline-danger',
};

export function Button({ children, className = '', type = 'button', variant = 'primary', ...props }: ButtonProps) {
  return (
    <button type={type} className={`${variantClass[variant]} ${className}`.trim()} {...props}>
      {children}
    </button>
  );
}
