import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { useEffect, useMemo, useState } from 'react';

import { useToast } from '@/shared/hooks/useToast';
import { confirm } from '@/shared/lib/confirm';
import { ClientLayout } from '@/shared/components/layout/ClientLayout';
import type { InertiaSharedProps } from '@/types/models';
import type { ProfileClient, ProfileFlash } from '@/types/profile';

import '../../../../css/client/clients-users.css';

type ProfilePageProps = {
  profile: ProfileClient;
  isGoogleOnly: boolean;
  profileFlash: ProfileFlash;
};

type InertiaErrors = Record<string, string>;

function profileInitials(name: string, firstSurname: string) {
  return `${name.charAt(0)}${firstSurname.charAt(0)}`.toUpperCase();
}

function fullName(parts: { name: string; first_surname: string; second_surname: string }) {
  return [parts.name, parts.first_surname, parts.second_surname].filter(Boolean).join(' ');
}

function passwordStrengthLevel(value: string) {
  if (!value) {
    return null;
  }

  let score = 0;
  if (value.length >= 8) score += 1;
  if (/[A-Z]/.test(value)) score += 1;
  if (/[0-9]/.test(value)) score += 1;
  if (/[^A-Za-z0-9]/.test(value)) score += 1;

  const levels = [
    { width: '25%', color: '#d32f2f', label: 'Débil' },
    { width: '50%', color: '#f57c00', label: 'Regular' },
    { width: '75%', color: '#fbc02d', label: 'Buena' },
    { width: '100%', color: '#235347', label: 'Fuerte' },
  ];

  return levels[Math.max(score - 1, 0)];
}

export default function ProfileIndex({ profile, profileFlash }: ProfilePageProps) {
  const page = usePage<InertiaSharedProps & { errors?: InertiaErrors }>();
  const errors = page.props.errors ?? {};
  const { showToast } = useToast();

  const [provider, setProvider] = useState(profile.provider);
  const [isEditing, setIsEditing] = useState(false);
  const [showPasswordForm, setShowPasswordForm] = useState(provider !== 'google');
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

  const heroName = useMemo(
    () => fullName(profileForm.data),
    [profileForm.data.name, profileForm.data.first_surname, profileForm.data.second_surname],
  );

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
          setProvider('local');
          setShowPasswordForm(true);
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

              <div className="profile-hero">
                <div className="profile-avatar">
                  <span id="avatarInitials">
                    {profileInitials(profileForm.data.name, profileForm.data.first_surname)}
                  </span>
                </div>

                <div className="profile-hero-info">
                  <h1 id="heroName">{heroName}</h1>
                  <p className="profile-email">{profileForm.data.gmail}</p>

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

                      <Link
                        href="/notifications"
                        className="profile-quick-action profile-quick-action--notifications"
                      >
                        <i className="fas fa-bell" aria-hidden="true" />
                        <span>Notificaciones</span>
                      </Link>
                    </nav>
                  </div>
                </div>
              </div>

              <div className="profile-grid">
                <div className="profile-card">
                  <div className="profile-card-header">
                    <h2>
                      <i className="fas fa-user-circle" style={{ color: 'var(--color-primary)' }} />
                      Datos Personales
                    </h2>

                    {!isEditing ? (
                      <button
                        type="button"
                        id="btnEditarPerfil"
                        className="btn btn-sm btn-outline-primary"
                        onClick={() => setIsEditing(true)}
                      >
                        <i className="fas fa-pencil-alt" />
                        <span>Editar</span>
                      </button>
                    ) : null}
                  </div>

                  <form id="formPerfil" onSubmit={(e) => e.preventDefault()}>
                    <div className="profile-fields">
                      <ProfileField
                        id="name"
                        label="Nombre *"
                        value={profileForm.data.name}
                        readOnly={!isEditing}
                        error={errors.name ?? profileForm.errors.name}
                        onChange={(value) => profileForm.setData('name', value)}
                        required
                        minLength={2}
                        maxLength={60}
                        placeholder="Tu nombre"
                      />
                      <ProfileField
                        id="first_surname"
                        label="Primer Apellido *"
                        value={profileForm.data.first_surname}
                        readOnly={!isEditing}
                        error={errors.first_surname ?? profileForm.errors.first_surname}
                        onChange={(value) => profileForm.setData('first_surname', value)}
                        required
                        minLength={2}
                        maxLength={60}
                        placeholder="Tu primer apellido"
                      />
                      <ProfileField
                        id="second_surname"
                        label="Segundo Apellido"
                        value={profileForm.data.second_surname}
                        readOnly={!isEditing}
                        error={errors.second_surname ?? profileForm.errors.second_surname}
                        onChange={(value) => profileForm.setData('second_surname', value)}
                        maxLength={60}
                        placeholder="Opcional"
                      />
                      <ProfileField
                        id="gmail"
                        label="Correo Electrónico *"
                        type="email"
                        value={profileForm.data.gmail}
                        readOnly={!isEditing}
                        error={errors.gmail ?? profileForm.errors.gmail}
                        onChange={(value) => profileForm.setData('gmail', value)}
                        required
                        fullWidth
                        placeholder="tu@correo.com"
                      />
                    </div>

                    {isEditing ? (
                      <div id="accionesEdicion" className="profile-form-actions">
                        <button
                          type="button"
                          className="btn btn-primary"
                          disabled={profileForm.processing}
                          onClick={() => void saveProfile()}
                        >
                          <i className="fas fa-save" /> Guardar Cambios
                        </button>
                        <button type="button" className="btn btn-secondary" onClick={cancelEdit}>
                          <i className="fas fa-times" /> Cancelar
                        </button>
                      </div>
                    ) : null}
                  </form>
                </div>

                <div className="profile-card" id="card-password">
                  <div className="profile-card-header">
                    <h2>
                      <i className="fas fa-lock" style={{ color: 'var(--color-primary)' }} />
                      <span id="passwordCardTitle">
                        {isGoogleAccount ? 'Definir Contraseña' : 'Cambiar Contraseña'}
                      </span>
                    </h2>
                  </div>

                  {isGoogleAccount && !showPasswordForm ? (
                    <div id="googlePassCta" className="profile-google-cta">
                      <div className="profile-google-icon">
                        <i className="fab fa-google" />
                      </div>
                      <p>
                        Actualmente inicias sesión con Google.
                        <br />
                        Puedes agregar una contraseña para usar también correo y contraseña.
                      </p>
                      <button type="button" className="btn btn-primary btn-block" onClick={() => setShowPasswordForm(true)}>
                        <i className="fas fa-key" /> Definir contraseña
                      </button>
                    </div>
                  ) : null}

                  <form
                    id="formPassword"
                    onSubmit={submitPassword}
                    className={isGoogleAccount && !showPasswordForm ? 'hidden' : ''}
                  >
                    <div className="profile-fields">
                      {!isGoogleAccount ? (
                        <PasswordField
                          id="current_password"
                          label="Contraseña Actual"
                          value={passwordForm.data.current_password}
                          error={errors.current_password ?? passwordForm.errors.current_password}
                          visible={visiblePasswordFields.current_password}
                          onToggle={() => togglePassVisibility('current_password')}
                          onChange={(value) => passwordForm.setData('current_password', value)}
                          fullWidth
                          autoComplete="current-password"
                          placeholder="Tu contraseña actual"
                        />
                      ) : null}

                      <PasswordField
                        id="new_password"
                        label="Nueva Contraseña"
                        value={passwordForm.data.new_password}
                        error={errors.new_password ?? passwordForm.errors.new_password}
                        visible={visiblePasswordFields.new_password}
                        onToggle={() => togglePassVisibility('new_password')}
                        onChange={(value) => passwordForm.setData('new_password', value)}
                        autoComplete="new-password"
                        placeholder="Mínimo 8 caracteres"
                        minLength={8}
                        strength={strength}
                      />

                      <PasswordField
                        id="new_password_confirmation"
                        label="Confirmar Contraseña"
                        value={passwordForm.data.new_password_confirmation}
                        error={
                          errors.new_password_confirmation ?? passwordForm.errors.new_password_confirmation
                        }
                        visible={visiblePasswordFields.new_password_confirmation}
                        onToggle={() => togglePassVisibility('new_password_confirmation')}
                        onChange={(value) => passwordForm.setData('new_password_confirmation', value)}
                        autoComplete="new-password"
                        placeholder="Repite la contraseña"
                        minLength={8}
                      />
                    </div>

                    <div className="profile-form-actions">
                      <button type="submit" className="btn btn-primary" id="btnSavePassword" disabled={passwordForm.processing}>
                        <i className="fas fa-save" />
                        {isGoogleAccount ? ' Guardar Contraseña' : ' Actualizar Contraseña'}
                      </button>

                      {isGoogleAccount ? (
                        <button
                          type="button"
                          className="btn btn-secondary"
                          onClick={() => {
                            passwordForm.reset();
                            setShowPasswordForm(false);
                          }}
                        >
                          <i className="fas fa-times" /> Cancelar
                        </button>
                      ) : null}
                    </div>
                  </form>
                </div>
              </div>
            </div>
          </div>
        </div>
      </ClientLayout>
    </>
  );
}

function ProfileField({
  error,
  fullWidth,
  id,
  label,
  maxLength,
  minLength,
  onChange,
  placeholder,
  readOnly,
  required,
  type = 'text',
  value,
}: {
  id: string;
  label: string;
  value: string;
  readOnly: boolean;
  error?: string;
  onChange: (value: string) => void;
  type?: string;
  required?: boolean;
  minLength?: number;
  maxLength?: number;
  placeholder?: string;
  fullWidth?: boolean;
}) {
  return (
    <div className={`form-group${fullWidth ? ' profile-field-full' : ''}`}>
      <label htmlFor={id}>{label}</label>
      <input
        type={type}
        id={id}
        name={id}
        className="form-control"
        value={value}
        readOnly={readOnly}
        required={required}
        minLength={minLength}
        maxLength={maxLength}
        placeholder={placeholder}
        onChange={(e) => onChange(e.target.value)}
      />
      {error ? (
        <span className="profile-field-error">
          <i className="fas fa-exclamation-circle" /> {error}
        </span>
      ) : null}
    </div>
  );
}

function PasswordField({
  autoComplete,
  error,
  fullWidth,
  id,
  label,
  minLength,
  onChange,
  onToggle,
  placeholder,
  strength,
  value,
  visible,
}: {
  id: string;
  label: string;
  value: string;
  error?: string;
  visible?: boolean;
  onToggle: () => void;
  onChange: (value: string) => void;
  placeholder?: string;
  minLength?: number;
  autoComplete?: string;
  fullWidth?: boolean;
  strength?: { width: string; color: string; label: string } | null;
}) {
  return (
    <div className={`form-group${fullWidth ? ' profile-field-full' : ''}`}>
      <label htmlFor={id}>{label}</label>
      <div className="profile-input-pass">
        <input
          type={visible ? 'text' : 'password'}
          id={id}
          name={id}
          className="form-control"
          value={value}
          placeholder={placeholder}
          minLength={minLength}
          autoComplete={autoComplete}
          onChange={(e) => onChange(e.target.value)}
        />
        <button type="button" className="profile-toggle-pass" onClick={onToggle} aria-label="Mostrar u ocultar contraseña">
          <i className={`fas ${visible ? 'fa-eye-slash' : 'fa-eye'}`} />
        </button>
      </div>
      {strength ? (
        <div id="passStrength" className="profile-strength">
          <div className="profile-strength-bar">
            <div
              className="profile-strength-fill"
              id="strengthFill"
              style={{ width: strength.width, background: strength.color }}
            />
          </div>
          <span id="strengthLabel" style={{ color: strength.color }}>
            {strength.label}
          </span>
        </div>
      ) : null}
      {error ? (
        <span className="profile-field-error">
          <i className="fas fa-exclamation-circle" /> {error}
        </span>
      ) : null}
    </div>
  );
}
