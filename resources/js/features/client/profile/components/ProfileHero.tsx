import { Link, router } from '@inertiajs/react';
import { useRef, useState } from 'react';

import { useFavoritesDrawer } from '@/features/client/favorites/context/FavoritesDrawerContext';
import { profileInitials } from '@/features/client/profile/lib/profileFormUtils';
import { useToast } from '@/shared/hooks/useToast';

type ProfileHeroProps = {
  avatarUrl: string | null;
  heroName: string;
  gmail: string;
  name: string;
  firstSurname: string;
  isGoogleAccount: boolean;
};

export function ProfileHero({ avatarUrl, firstSurname, gmail, heroName, isGoogleAccount, name }: ProfileHeroProps) {
  const { open: openFavoritesDrawer } = useFavoritesDrawer();
  const { showToast } = useToast();
  const fileInputRef = useRef<HTMLInputElement>(null);
  const [isUploading, setIsUploading] = useState(false);
  const [avatarFailed, setAvatarFailed] = useState(false);
  const showAvatarImage = Boolean(avatarUrl) && !avatarFailed;

  function handleAvatarFile(file: File | undefined) {
    if (!file) {
      return;
    }

    setIsUploading(true);
    router.post(
      '/profile/avatar',
      { avatar: file },
      {
        forceFormData: true,
        preserveScroll: true,
        onError: (errors) => {
          const message = errors.avatar ?? 'No se pudo actualizar la foto.';
          showToast({ variant: 'error', title: 'No se pudo subir', message: String(message) });
        },
        onFinish: () => {
          setIsUploading(false);
          if (fileInputRef.current) {
            fileInputRef.current.value = '';
          }
        },
      },
    );
  }

  return (
    <div className="profile-hero">
      <div className="profile-avatar-wrap">
        <div className="profile-avatar">
          {showAvatarImage ? (
            <img
              src={avatarUrl ?? undefined}
              alt={`Foto de perfil de ${heroName}`}
              className="profile-avatar__img"
              referrerPolicy="no-referrer"
              onError={() => setAvatarFailed(true)}
            />
          ) : (
            <span id="avatarInitials">{profileInitials(name, firstSurname)}</span>
          )}
        </div>
        <button
          type="button"
          className="profile-avatar-upload"
          disabled={isUploading}
          aria-label="Cambiar foto de perfil"
          title="Cambiar foto de perfil"
          onClick={() => fileInputRef.current?.click()}
        >
          <i className={isUploading ? 'fas fa-spinner fa-spin' : 'fas fa-camera'} aria-hidden="true" />
        </button>
        <input
          ref={fileInputRef}
          type="file"
          accept="image/jpeg,image/png,image/webp"
          className="sr-only"
          aria-hidden="true"
          tabIndex={-1}
          onChange={(event) => handleAvatarFile(event.target.files?.[0])}
        />
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
              className="profile-quick-action profile-quick-action--favorites"
              onClick={openFavoritesDrawer}
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
