import type { PropsWithChildren } from 'react';

export function InlineAlert({
  children,
  variant = 'danger',
}: PropsWithChildren<{ variant?: 'danger' | 'warning' | 'success' | 'info' }>) {
  return (
    <div className={`alert alert-${variant} mb-3`} role={variant === 'danger' || variant === 'warning' ? 'alert' : 'status'}>
      {children}
    </div>
  );
}

