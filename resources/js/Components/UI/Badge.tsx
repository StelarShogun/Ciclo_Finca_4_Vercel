import type { PropsWithChildren } from 'react';

type BadgeProps = PropsWithChildren<{
  tone?: 'success' | 'warning' | 'danger' | 'neutral';
}>;

const toneClass = {
  success: 'product-badge--stock',
  warning: 'product-badge--low-stock',
  danger: 'product-badge--out-stock',
  neutral: 'product-badge--category',
};

export function Badge({ children, tone = 'neutral' }: BadgeProps) {
  return <span className={`product-badge ${toneClass[tone]}`}>{children}</span>;
}
