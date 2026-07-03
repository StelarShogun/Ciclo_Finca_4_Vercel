import type { PropsWithChildren } from 'react';

type HomeSectionProps = PropsWithChildren<{
  className: string;
  title: string;
  subtitle?: string;
  headingId?: string;
  ariaLabel?: string;
}>;

export function HomeSection({
  children,
  className,
  title,
  subtitle,
  headingId,
  ariaLabel,
}: HomeSectionProps) {
  return (
    <section className={className} aria-labelledby={headingId} aria-label={ariaLabel}>
      <div className="container">
        <div className="section-header">
          <h2 className="section-title" id={headingId}>
            {title}
          </h2>
          {subtitle ? <p className="section-subtitle">{subtitle}</p> : null}
        </div>
        {children}
      </div>
    </section>
  );
}
