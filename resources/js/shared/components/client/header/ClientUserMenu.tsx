import { Link } from '@inertiajs/react';

import { useFavoritesDrawer } from '@/features/client/favorites/context/FavoritesDrawerContext';
import type { InertiaSharedProps } from '@/shared/types/models';

type ClientUserMenuProps = {
  authClient: NonNullable<InertiaSharedProps['auth']['client']>;
  clientInitials: string;
  isOpen: boolean;
  onLogout: () => void;
  onToggle: () => void;
};

export function ClientUserMenu({ authClient, clientInitials, isOpen, onLogout, onToggle }: ClientUserMenuProps) {
  const { open: openFavoritesDrawer } = useFavoritesDrawer();

  return (
    <div className={`user-menu-wrap ${isOpen ? 'open' : ''}`}>
      <button
        className="user-menu-trigger"
        type="button"
        aria-expanded={isOpen}
        aria-haspopup="true"
        title="Mi cuenta"
        onClick={onToggle}
      >
        <span className="user-avatar-bubble">
          {authClient.avatarUrl ? (
            <img
              src={authClient.avatarUrl}
              alt=""
              width={32}
              height={32}
              style={{ width: 32, height: 32, borderRadius: '50%', objectFit: 'cover' }}
              referrerPolicy="no-referrer"
              loading="lazy"
              decoding="async"
            />
          ) : (
            clientInitials
          )}
        </span>
        <span className="user-trigger-name">{authClient.name}</span>
        <i className="fas fa-chevron-down user-trigger-caret" aria-hidden="true" />
      </button>
      <div className="user-dropdown-panel" aria-hidden={!isOpen} role="menu">
        <div className="user-dropdown-head">
          <p className="user-dropdown-fullname">
            {authClient.name} {authClient.first_surname}
          </p>
          <p className="user-dropdown-email">{authClient.gmail}</p>
        </div>
        <div className="user-dropdown-body">
          <Link className="user-dropdown-item" href="/profile" role="menuitem">
            <i className="fas fa-user" aria-hidden="true" />
            <span>Mi perfil</span>
          </Link>
          <button
            type="button"
            className="user-dropdown-item"
            role="menuitem"
            onClick={() => {
              if (isOpen) {
                onToggle();
              }
              openFavoritesDrawer();
            }}
          >
            <i className="fas fa-heart" aria-hidden="true" />
            <span>Mis favoritos</span>
          </button>
          <Link className="user-dropdown-item" href="/invoices" role="menuitem">
            <i className="fas fa-file-invoice" aria-hidden="true" />
            <span>Mis facturas</span>
          </Link>
          <Link className="user-dropdown-item" href="/notifications" role="menuitem">
            <i className="fas fa-bell" aria-hidden="true" />
            <span>Notificaciones</span>
          </Link>
        </div>
        <div className="user-dropdown-foot">
          <button type="button" className="user-dropdown-item" role="menuitem" onClick={onLogout}>
            <i className="fas fa-right-from-bracket" aria-hidden="true" />
            <span>Cerrar sesión</span>
          </button>
        </div>
      </div>
    </div>
  );
}
