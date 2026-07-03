import { Link } from '@inertiajs/react';

type FooterColumnProps = {
  links: [string, string][];
  title: string;
};

export function FooterColumn({ links, title }: FooterColumnProps) {
  return (
    <div className="footer-col">
      <h4>{title}</h4>
      <ul className="footer-links">
        {links.map(([label, href]) => (
          <li key={href}>
            <Link href={href}>{label}</Link>
          </li>
        ))}
      </ul>
    </div>
  );
}
