import { Link, router, usePage } from '@inertiajs/react';
import { useMemo, useState } from 'react';

import type { ClientListPagination } from '@/types/pagination';

type PaginationProps = {
  pagination: ClientListPagination;
  label?: string;
};

export function Pagination({ pagination, label = '' }: PaginationProps) {
  const inertiaPage = usePage();
  const uid = useMemo(() => `cpg-${Math.random().toString(16).slice(2)}`, []);
  const [target, setTarget] = useState(String(pagination.currentPage));

  if (pagination.lastPage <= 1) {
    return null;
  }

  function navigateToPage(nextPage: number) {
    const base = inertiaPage.url || window.location.pathname;
    const url = new URL(base, window.location.origin);
    url.searchParams.set('page', String(nextPage));

    router.visit(`${url.pathname}${url.search}`, {
      preserveScroll: true,
      preserveState: true,
    });
  }

  function commitGo() {
    const next = parseInt(target.trim(), 10);
    if (Number.isNaN(next)) {
      setTarget(String(pagination.currentPage));
      return;
    }

    const clamped = Math.max(1, Math.min(pagination.lastPage, next));
    setTarget(String(clamped));
    navigateToPage(clamped);
  }

  const ariaLabel = label ? `Paginación ${label}` : 'Paginación';

  return (
    <div className="pagination-wrapper">
      <div
        className="cf4-pagination-toolbar pagination is-compact catalog-pagination"
        data-last-page={pagination.lastPage}
        role="navigation"
        aria-label={ariaLabel}
      >
        <div className="results-info" aria-live="polite">
          {pagination.total === 0
            ? 'Mostrando 0 resultados'
            : `Mostrando ${pagination.from ?? 0}–${pagination.to ?? 0} de ${pagination.total} resultados`}
        </div>

        <div className="cf4-pagination-controls-row">
          <div className="admin-pagination-nav">
            {pagination.links.map((link, index) => {
              const isEllipsis = stripHtml(link.label) === '...';
              const isPrev = index === 0;
              const isNext = index === pagination.links.length - 1;

              if (isPrev || isNext) {
                const navLabel = isPrev ? 'Anterior' : 'Siguiente';
                return (
                  <PaginationLink
                    key={`${navLabel}-${index}`}
                    href={link.url}
                    ariaLabel={navLabel}
                    page={link.page ?? null}
                    variant="icon"
                    direction={isPrev ? 'prev' : 'next'}
                  />
                );
              }

              if (isEllipsis) {
                return (
                  <span className="button admin-pagination-ellipsis" aria-hidden="true" key={`ellipsis-${index}`}>
                    …
                  </span>
                );
              }

              if (link.active) {
                return (
                  <span className="button button-primary" aria-current="page" key={`active-${index}`}>
                    {stripHtml(link.label)}
                  </span>
                );
              }

              return (
                <PaginationLink
                  key={`page-${index}-${link.label}`}
                  href={link.url}
                  page={link.page ?? null}
                  label={stripHtml(link.label)}
                />
              );
            })}
          </div>

          <div className="cf4-pagination-jump">
            <label className="sr-only" htmlFor={`goToPageInput-${uid}`}>
              Ir a página
            </label>
            <input
              id={`goToPageInput-${uid}`}
              className="pagination-go-input"
              type="number"
              min={1}
              max={pagination.lastPage}
              step={1}
              inputMode="numeric"
              value={target}
              onChange={(e) => setTarget(e.target.value)}
              onKeyDown={(e) => {
                if (e.key === 'Enter') {
                  e.preventDefault();
                  commitGo();
                }
              }}
            />
            <button className="go-button pagination-go-button" type="button" onClick={commitGo}>
              Ir
            </button>
          </div>
        </div>
      </div>
    </div>
  );
}

function PaginationLink({
  ariaLabel,
  direction,
  href,
  label,
  page,
  variant = 'text',
}: {
  href: string | null;
  page: number | null;
  label?: string;
  ariaLabel?: string;
  variant?: 'text' | 'icon';
  direction?: 'prev' | 'next';
}) {
  if (!href) {
    return (
      <span className="button" aria-disabled="true" tabIndex={-1} aria-label={ariaLabel}>
        {variant === 'icon' ? <ChevronIcon direction={direction ?? 'next'} /> : label}
      </span>
    );
  }

  return (
    <Link
      href={href}
      preserveScroll
      className="button"
      data-page={page ?? undefined}
      aria-label={ariaLabel}
    >
      {variant === 'icon' ? <ChevronIcon direction={direction ?? 'next'} /> : label}
    </Link>
  );
}

function ChevronIcon({ direction }: { direction: 'prev' | 'next' }) {
  return direction === 'prev' ? (
    <svg width="16" height="16" viewBox="0 0 24 24" aria-hidden="true">
      <path
        d="M15 18l-6-6 6-6"
        fill="none"
        stroke="currentColor"
        strokeWidth="2"
        strokeLinecap="round"
        strokeLinejoin="round"
      />
    </svg>
  ) : (
    <svg width="16" height="16" viewBox="0 0 24 24" aria-hidden="true">
      <path
        d="M9 6l6 6-6 6"
        fill="none"
        stroke="currentColor"
        strokeWidth="2"
        strokeLinecap="round"
        strokeLinejoin="round"
      />
    </svg>
  );
}

function stripHtml(value: string) {
  return value.replace(/<[^>]*>/g, '').trim();
}
