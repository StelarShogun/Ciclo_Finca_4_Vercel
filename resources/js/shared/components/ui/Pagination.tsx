import { Link } from '@inertiajs/react';

type PaginationLink = {
  url: string | null;
  label: string;
  active: boolean;
};

type PaginationProps = {
  links?: PaginationLink[];
};

export function Pagination({ links = [] }: PaginationProps) {
  if (links.length === 0) {
    return null;
  }

  return (
    <nav className="pagination-wrapper" aria-label="Paginación">
      <ul className="pagination">
        {links.map((link, index) => (
          <li key={`${link.label}-${index}`} className={link.active ? 'active' : undefined}>
            {link.url ? (
              <Link href={link.url} dangerouslySetInnerHTML={{ __html: link.label }} />
            ) : (
              <span dangerouslySetInnerHTML={{ __html: link.label }} />
            )}
          </li>
        ))}
      </ul>
    </nav>
  );
}
