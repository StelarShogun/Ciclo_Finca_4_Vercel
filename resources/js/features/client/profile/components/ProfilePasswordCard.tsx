import type { FormEvent } from 'react';
import type { InertiaFormProps } from '@inertiajs/react';

import { PasswordField } from '@/features/client/profile/components/PasswordField';

type PasswordFormData = {
  current_password: string;
  new_password: string;
  new_password_confirmation: string;
};

type ProfilePasswordCardProps = {
  errors: Record<string, string>;
  form: InertiaFormProps<PasswordFormData>;
  isGoogleAccount: boolean;
  onSubmit: (event: FormEvent) => void;
  onTogglePassVisibility: (fieldId: string) => void;
  setShowPasswordForm: (show: boolean) => void;
  showPasswordForm: boolean;
  strength: { width: string; color: string; label: string } | null;
  visiblePasswordFields: Record<string, boolean>;
};

export function ProfilePasswordCard({
  errors,
  form,
  isGoogleAccount,
  onSubmit,
  onTogglePassVisibility,
  setShowPasswordForm,
  showPasswordForm,
  strength,
  visiblePasswordFields,
}: ProfilePasswordCardProps) {
  return (
    <div className="profile-card" id="card-password">
      <div className="profile-card-header">
        <h2>
          <i className="fas fa-lock" style={{ color: 'var(--color-primary)' }} />
          <span id="passwordCardTitle">{isGoogleAccount ? 'Definir Contraseña' : 'Cambiar Contraseña'}</span>
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
        onSubmit={onSubmit}
        className={isGoogleAccount && !showPasswordForm ? 'hidden' : ''}
      >
        <div className="profile-fields">
          {!isGoogleAccount ? (
            <PasswordField
              id="current_password"
              label="Contraseña Actual"
              value={form.data.current_password}
              error={errors.current_password ?? form.errors.current_password}
              visible={visiblePasswordFields.current_password}
              onToggle={() => onTogglePassVisibility('current_password')}
              onChange={(value) => form.setData('current_password', value)}
              fullWidth
              autoComplete="current-password"
              placeholder="Tu contraseña actual"
            />
          ) : null}

          <PasswordField
            id="new_password"
            label="Nueva Contraseña"
            value={form.data.new_password}
            error={errors.new_password ?? form.errors.new_password}
            visible={visiblePasswordFields.new_password}
            onToggle={() => onTogglePassVisibility('new_password')}
            onChange={(value) => form.setData('new_password', value)}
            autoComplete="new-password"
            placeholder="Mínimo 8 caracteres"
            minLength={8}
            strength={strength}
          />

          <PasswordField
            id="new_password_confirmation"
            label="Confirmar Contraseña"
            value={form.data.new_password_confirmation}
            error={errors.new_password_confirmation ?? form.errors.new_password_confirmation}
            visible={visiblePasswordFields.new_password_confirmation}
            onToggle={() => onTogglePassVisibility('new_password_confirmation')}
            onChange={(value) => form.setData('new_password_confirmation', value)}
            autoComplete="new-password"
            placeholder="Repite la contraseña"
            minLength={8}
          />
        </div>

        <div className="profile-form-actions">
          <button type="submit" className="btn btn-primary" id="btnSavePassword" disabled={form.processing}>
            <i className="fas fa-save" />
            {isGoogleAccount ? ' Guardar Contraseña' : ' Actualizar Contraseña'}
          </button>

          {isGoogleAccount ? (
            <button
              type="button"
              className="btn btn-secondary"
              onClick={() => {
                form.reset();
                setShowPasswordForm(false);
              }}
            >
              <i className="fas fa-times" /> Cancelar
            </button>
          ) : null}
        </div>
      </form>
    </div>
  );
}
