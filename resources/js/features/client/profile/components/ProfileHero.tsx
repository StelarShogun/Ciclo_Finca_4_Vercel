import { Link } from '@inertiajs/react';

import { profileInitials } from '@/features/client/profile/lib/profileFormUtils';

type ProfileHeroProps = {
  heroName: string;
  gmail: string;
  name: string;
  firstSurname: string;
  isGoogleAccount: boolean;
};

export function ProfileHero({ firstSurname, gmail, heroName, isGoogleAccount, name }: ProfileHeroProps) {
  return (
    <div className="profile-hero">
      <div className="profile-avatar">
        <span id="avatarInitials">{profileInitials(name, firstSurname)}</span>
      </div>

      <div className="profile-hero-info">
        <h1 id="heroName">{heroName}</h1>
        <p className="profile-email">{gmail}</p>

        <div className="profile-hero-meta">
          {isGoogleAccount ? (
            <span className="profile-badge profile-badge--google">
              <i className="fab fa-google" /> Cuenta de Google
            </span>
          ) : (
            <span className="profile-badge profile-badge--local">
              <i className="fas fa-envelope" /> Cuenta local
            </span>
          )}

          <nav className="profile-quick-actions" aria-label="Accesos rápidos">
            <button
              type="button"
              className="profile-quick-action profile-quick-action--favorites cf4-favorites-open-trigger"
            >
              <i className="fas fa-heart" aria-hidden="true" />
              <span>Mis favoritos</span>
            </button>

            <Link href="/notifications" className="profile-quick-action profile-quick-action--notifications">
              <i className="fas fa-bell" aria-hidden="true" />
              <span>Notificaciones</span>
            </Link>
          </nav>
        </div>
      </div>
    </div>
  );
}
