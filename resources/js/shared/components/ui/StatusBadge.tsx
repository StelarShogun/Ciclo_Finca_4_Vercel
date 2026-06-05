import type { PropsWithChildren, ReactNode } from 'react';

export type StatusTone = 'pending' | 'ready' | 'cancelled' | 'completed' | 'default';

type StatusBadgeProps = PropsWithChildren<{
  tone?: StatusTone;
  className?: string;
  icon?: ReactNode;
  variant?: 'badge' | 'pill';
  pillClass?: string;
}>;

const toneClass: Record<StatusTone, string> = {
  pending: 'cf4-invoice-status-pending',
  ready: 'cf4-invoice-status-ready',
  cancelled: 'cf4-invoice-status-cancelled',
  completed: 'cf4-invoice-status-completed',
  default: 'cf4-invoice-status-default',
};

export function StatusBadge({
  children,
  className = '',
  icon,
  pillClass = '',
  tone = 'default',
  variant = 'badge',
}: StatusBadgeProps) {
  if (variant === 'pill') {
    return (
      <span className={`cf4-invoice-pill ${pillClass} ${className}`.trim()}>
        {icon}
        {children}
      </span>
    );
  }

  return (
    <span className={`cf4-invoice-status-badge ${toneClass[tone]} ${className}`.trim()}>
      {children}
    </span>
  );
}
