import { Link, router, usePage } from '@inertiajs/react';
import type { PropsWithChildren } from 'react';
import { useEffect, useRef, useState } from 'react';

import { AnimatedThemeToggle } from '@/shared/components/ui/AnimatedThemeToggle';
import { toggleTheme } from '@/shared/theme-toggle';
import type { InertiaSharedProps } from '@/shared/types/models';

import '../../../../css/admin/shell-base.css';
import '../../../../css/admin/components/page-header.css';
import '../../../../css/admin/components/admin-table.css';
import '../../../../css/admin/dashboard/dashboard.css';
import '../../../../css/admin/components/kpi-card.css';
import '../../../../css/admin/components/modal.css';

type AdminLayoutProps = PropsWithChildren<{
  /** Conservado por compatibilidad; el título lo provee cada página vía PageHeader. */
  title?: string;
}>;

type NavItem = {
  href: string;
  label: string;
  icon: string;
  /** Prefijos de ruta que marcan este item como activo. */
  match: string[];
};

const NAV_ITEMS: NavItem[] = [
  { href: '/dashboard', label: 'Dashboard', icon: 'fa-chart-line', match: ['/dashboard'] },
  { href: '/sales', label: 'Ventas', icon: 'fa-cash-register', match: ['/sales'] },
  { href: '/orders', label: 'Encargos', icon: 'fa-shopping-cart', match: ['/orders'] },
  { href: '/supplier-orders', label: 'Pedidos proveedores', icon: 'fa-clipboard-list', match: ['/supplier-orders'] },
  { href: '/inventory', label: 'Inventario', icon: 'fa-box', match: ['/inventory', '/categories'] },
  { href: '/product-classifications', label: 'Características por producto', icon: 'fa-layer-group', match: ['/product-classifications'] },
  { href: '/classifications/catalog', label: 'Opciones por tipo', icon: 'fa-th-list', match: ['/classifications'] },
  { href: '/brands', label: 'Marcas', icon: 'fa-tags', match: ['/brands'] },
  { href: '/suppliers', label: 'Proveedores', icon: 'fa-truck', match: ['/suppliers'] },
  { href: '/clientes', label: 'Usuarios', icon: 'fa-users', match: ['/clientes'] },
  { href: '/reports', label: 'Reportes', icon: 'fa-file-alt', match: ['/reports'] },
];

function isActive(currentPath: string, match: string[]): boolean {
  return match.some((prefix) => currentPath === prefix || currentPath.startsWith(`${prefix}/`));
}

export function AdminLayout({ children }: AdminLayoutProps) {
  const { auth } = usePage<InertiaSharedProps>().props;
  const currentPath = new URL(usePage().url, window.location.origin).pathname;
  const admin = auth.admin;

  const displayName = admin ? `${admin.name} ${admin.first_surname ?? ''}`.trim() : 'Administrador';
  const initials = admin
    ? `${(admin.name?.[0] ?? 'A')}${(admin.first_surname?.[0] ?? admin.name?.[0] ?? 'A')}`.toUpperCase()
    : 'A';

  const [accountOpen, setAccountOpen] = useState(false);
  const accountRef = useRef<HTMLDivElement>(null);

  const [collapsed, setCollapsed] = useState<boolean>(() => {
    if (typeof window === 'undefined') {
      return false;
    }
    return window.localStorage.getItem('admin-sidebar-collapsed') === '1';
  });

  useEffect(() => {
    window.localStorage.setItem('admin-sidebar-collapsed', collapsed ? '1' : '0');
  }, [collapsed]);

  const [theme, setTheme] = useState<'light' | 'dark'>(() => {
    if (typeof document === 'undefined') {
      return 'light';
    }
    return document.documentElement.dataset.theme === 'dark' ? 'dark' : 'light';
  });

  function onToggleTheme() {
    toggleTheme();
    setTheme((prev) => (prev === 'dark' ? 'light' : 'dark'));
  }

  // Tooltip flotante global: se posiciona en <body> con position:fixed, así no lo
  // recorta ningún contenedor con overflow y siempre queda por encima de todo.
  useEffect(() => {
    const tip = document.createElement('div');
    tip.className = 'cf4-tooltip';
    tip.setAttribute('role', 'tooltip');
    document.body.appendChild(tip);
    let current: HTMLElement | null = null;

    function place() {
      if (!current) {
        return;
      }
      const rect = current.getBoundingClientRect();
      const tipRect = tip.getBoundingClientRect();
      const gap = 8;
      let top = rect.top - tipRect.height - gap;
      const below = top < 4;
      tip.classList.toggle('is-below', below);
      if (below) {
        top = rect.bottom + gap;
      }
      let left = rect.left + rect.width / 2 - tipRect.width / 2;
      left = Math.max(6, Math.min(left, window.innerWidth - tipRect.width - 6));
      tip.style.top = `${Math.round(top)}px`;
      tip.style.left = `${Math.round(left)}px`;
    }

    function show(target: HTMLElement) {
      const text = target.getAttribute('data-tooltip');
      if (!text) {
        return;
      }
      current = target;
      tip.textContent = text;
      tip.style.left = '-9999px';
      tip.style.top = '-9999px';
      tip.classList.add('is-visible');
      requestAnimationFrame(place);
    }

    function hide() {
      current = null;
      tip.classList.remove('is-visible');
    }

    function onOver(event: Event) {
      const target = (event.target as HTMLElement | null)?.closest('[data-tooltip]') as HTMLElement | null;
      if (target) {
        show(target);
      }
    }
    function onOut(event: Event) {
      const related = (event as MouseEvent).relatedTarget as Node | null;
      if (current && (!related || !current.contains(related))) {
        hide();
      }
    }

    document.addEventListener('mouseover', onOver);
    document.addEventListener('mouseout', onOut);
    document.addEventListener('focusin', onOver);
    document.addEventListener('focusout', hide);
    window.addEventListener('scroll', hide, true);
    return () => {
      document.removeEventListener('mouseover', onOver);
      document.removeEventListener('mouseout', onOut);
      document.removeEventListener('focusin', onOver);
      document.removeEventListener('focusout', hide);
      window.removeEventListener('scroll', hide, true);
      tip.remove();
    };
  }, []);

  useEffect(() => {
    if (!accountOpen) {
      return;
    }
    function onDocClick(event: MouseEvent) {
      if (accountRef.current && !accountRef.current.contains(event.target as Node)) {
        setAccountOpen(false);
      }
    }
    document.addEventListener('mousedown', onDocClick);
    return () => document.removeEventListener('mousedown', onDocClick);
  }, [accountOpen]);

  function logout() {
    router.post('/admin/logout');
  }

  return (
    <div className={`admin-layout${collapsed ? ' sidebar-collapsed' : ''}`}>
      <aside className={`admin-sidebar ${collapsed ? 'collapsed' : 'expanded'}`}>
        <div className="sidebar-header">
          <Link href="/dashboard" className="sidebar-header-brand" title="Ir al panel de administración">
            <div className="sidebar-header-text">
              <span className="sidebar-header-admin">Admin</span>
              <span className="sidebar-header-title">Ciclo Finca 4</span>
            </div>
          </Link>
          <button
            type="button"
            className="sidebar-toggle"
            aria-label={collapsed ? 'Expandir menú' : 'Contraer menú'}
            aria-pressed={collapsed}
            title={collapsed ? 'Expandir menú' : 'Contraer menú'}
            onClick={() => setCollapsed((value) => !value)}
          >
            <i className={`fas ${collapsed ? 'fa-chevron-right' : 'fa-chevron-left'}`} aria-hidden="true" />
          </button>
        </div>

        <nav className="sidebar-nav" aria-label="Navegación admin">
          <ul>
            {NAV_ITEMS.map((item) => (
              <li key={item.href} className={isActive(currentPath, item.match) ? 'active' : ''}>
                <Link href={item.href} title={collapsed ? item.label : undefined}>
                  <i className={`fas ${item.icon}`} aria-hidden="true" />
                  <span className="sidebar-label">{item.label}</span>
                </Link>
              </li>
            ))}
          </ul>
        </nav>

        <div className="sidebar-footer">
          <div className="sidebar-account-menu" ref={accountRef}>
            <button
              type="button"
              className="sidebar-account-trigger"
              aria-expanded={accountOpen}
              aria-haspopup="true"
              title="Opciones de cuenta"
              onClick={() => setAccountOpen((open) => !open)}
            >
              <span className="sidebar-account-avatar" aria-hidden="true">{initials}</span>
              <span className="sidebar-account-meta">
                <span className="sidebar-account-name">{displayName}</span>
                {admin ? <span className="sidebar-account-email">{admin.gmail}</span> : null}
              </span>
              <i className="fas fa-chevron-up sidebar-account-chevron" aria-hidden="true" />
            </button>

            {accountOpen ? (
              <div className="sidebar-account-panel" role="menu" aria-label="Opciones de cuenta">
                <a
                  href="/admin/visit-store"
                  className="sidebar-account-row sidebar-account-row--link"
                  role="menuitem"
                  title="Abrir la página principal del sitio web (mantiene la sesión de administrador)"
                >
                  <i className="fas fa-globe" aria-hidden="true" />
                  <span>Ir a sitio web</span>
                </a>
                <div className="sidebar-account-row sidebar-account-row--theme" role="group" aria-label="Tema de la interfaz">
                  <span className="sidebar-account-row__label">
                    <i className={`fas ${theme === 'dark' ? 'fa-moon' : 'fa-sun'}`} aria-hidden="true" />
                    {theme === 'dark' ? 'Modo oscuro' : 'Modo claro'}
                  </span>
                  <AnimatedThemeToggle isDark={theme === 'dark'} onToggle={onToggleTheme} />
                </div>
                <button
                  type="button"
                  className="sidebar-account-row sidebar-account-row--logout"
                  role="menuitem"
                  onClick={logout}
                >
                  <i className="fas fa-sign-out-alt" aria-hidden="true" />
                  <span>Cerrar sesión</span>
                </button>
              </div>
            ) : null}
          </div>
        </div>
      </aside>

      <main className="admin-main admin-main--content">
        <div className="admin-content-wrapper">{children}</div>
      </main>
    </div>
  );
}
