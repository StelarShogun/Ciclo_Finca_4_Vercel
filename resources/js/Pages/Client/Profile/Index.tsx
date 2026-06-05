import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';

import { ProfileHero } from '@/features/client/profile/components/ProfileHero';
import { ProfilePasswordCard } from '@/features/client/profile/components/ProfilePasswordCard';
import { ProfilePersonalDataCard } from '@/features/client/profile/components/ProfilePersonalDataCard';
import { fullName, passwordStrengthLevel } from '@/features/client/profile/lib/profileFormUtils';
import { useToast } from '@/shared/hooks/useToast';
import { confirm } from '@/shared/lib/confirm';
import { ClientLayout } from '@/shared/components/layout/ClientLayout';
import type { InertiaSharedProps } from '@/shared/types/models';
import type { ProfileClient, ProfileFlash } from '@/types/profile';

import '../../../../css/client/clients-users.css';

type ProfilePageProps = {
  profile: ProfileClient;
  isGoogleOnly: boolean;
  profileFlash: ProfileFlash;
};

type InertiaErrors = Record<string, string>;

export default function ProfileIndex({ profile, profileFlash }: ProfilePageProps) {
  const page = usePage<InertiaSharedProps & { errors?: InertiaErrors }>();
  const errors = page.props.errors ?? {};
  const { showToast } = useToast();

  const [providerOverride, setProviderOverride] = useState<ProfileClient['provider'] | null>(null);
  const provider = providerOverride ?? profile.provider;
  const [isEditing, setIsEditing] = useState(false);
  const [showPasswordFormOverride, setShowPasswordFormOverride] = useState<boolean | null>(null);
  const showPasswordForm = showPasswordFormOverride ?? profile.provider !== 'google';
  const [visiblePasswordFields, setVisiblePasswordFields] = useState<Record<string, boolean>>({});

  const profileForm = useForm({
    name: profile.name,
    first_surname: profile.first_surname,
    second_surname: profile.second_surname,
    gmail: profile.gmail,
  });

  const passwordForm = useForm({
    current_password: '',
    new_password: '',
    new_password_confirmation: '',
  });

  const heroName = fullName(profileForm.data);

  const strength = passwordStrengthLevel(passwordForm.data.new_password);
  const isGoogleAccount = provider === 'google';

  useEffect(() => {
    if (profileFlash.profileUpdated) {
      showToast({ variant: 'success', title: 'Listo', message: 'Cambios guardados correctamente.' });
    }
    if (profileFlash.passwordUpdated) {
      showToast({ variant: 'success', title: 'Listo', message: 'Contraseña actualizada correctamente.' });
    }
    if (profileFlash.passwordDefined) {
      showToast({
        variant: 'success',
        title: 'Listo',
        message: 'Contraseña definida. Ya puedes iniciar sesión con tu correo y contraseña.',
      });
    }
  }, [profileFlash.profileUpdated, profileFlash.passwordUpdated, profileFlash.passwordDefined, showToast]);

  function cancelEdit() {
    profileForm.reset();
    profileForm.clearErrors();
    setIsEditing(false);
  }

  async function saveProfile() {
    const confirmed = await confirm({
      title: '¿Guardar cambios?',
      text: 'Se actualizarán tus datos personales.',
      icon: 'question',
      confirmButtonText: 'Sí, guardar',
      cancelButtonText: 'Cancelar',
    });

    if (!confirmed.isConfirmed) {
      return;
    }

    profileForm.put('/profile', {
      preserveScroll: true,
      onSuccess: () => setIsEditing(false),
      onError: () => {
        showToast({ variant: 'error', title: 'No se pudo guardar', message: 'Revisa los campos marcados.' });
      },
    });
  }

  function submitPassword(e: React.FormEvent) {
    e.preventDefault();

    passwordForm.put('/profile/password', {
      preserveScroll: true,
      onSuccess: () => {
        passwordForm.reset();
        if (isGoogleAccount) {
          setProviderOverride('local');
          setShowPasswordFormOverride(true);
        }
      },
      onError: (validationErrors) => {
        const firstError = Object.values(validationErrors)[0];
        if (firstError) {
          showToast({ variant: 'error', title: 'No se pudo actualizar', message: String(firstError) });
        }
      },
    });
  }

  function togglePassVisibility(fieldId: string) {
    setVisiblePasswordFields((current) => ({ ...current, [fieldId]: !current[fieldId] }));
  }

  return (
    <>
      <Head title="Mi Perfil" />
      <ClientLayout>
        <div className="profile-shell">
          <div className="profile-header">
            <div className="container">
              <h1 className="profile-header-title">Mi Perfil</h1>
              <p className="profile-header-subtitle">Gestioná tu información personal y seguridad</p>
            </div>
          </div>

          <div className="container">
            <div className="profile-wrapper">
              <nav className="breadcrumb" aria-label="Migas de pan">
                <Link href="/">Inicio</Link>
                <span>/</span>
                <span>Mi Perfil</span>
              </nav>

              <ProfileHero
                heroName={heroName}
                gmail={profileForm.data.gmail}
                name={profileForm.data.name}
                firstSurname={profileForm.data.first_surname}
                isGoogleAccount={isGoogleAccount}
              />

              <div className="profile-grid">
                <ProfilePersonalDataCard
                  errors={errors}
                  form={profileForm}
                  isEditing={isEditing}
                  onCancel={cancelEdit}
                  onEdit={() => setIsEditing(true)}
                  onSave={saveProfile}
                />

                <ProfilePasswordCard
                  errors={errors}
                  form={passwordForm}
                  isGoogleAccount={isGoogleAccount}
                  onSubmit={submitPassword}
                  onTogglePassVisibility={togglePassVisibility}
                  setShowPasswordForm={setShowPasswordFormOverride}
                  showPasswordForm={showPasswordForm}
                  strength={strength}
                  visiblePasswordFields={visiblePasswordFields}
                />
              </div>
            </div>
          </div>
        </div>
      </ClientLayout>
    </>
  );
}
