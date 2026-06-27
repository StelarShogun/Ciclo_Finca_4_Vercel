import type { InertiaFormProps } from '@inertiajs/react';

import { ProfileField } from '@/features/client/profile/components/ProfileField';

type ProfileFormData = {
  name: string;
  first_surname: string;
  second_surname: string;
  gmail: string;
};

type ProfilePersonalDataCardProps = {
  errors: Record<string, string>;
  form: InertiaFormProps<ProfileFormData>;
  isEditing: boolean;
  onCancel: () => void;
  onEdit: () => void;
  onSave: () => void;
};

export function ProfilePersonalDataCard({
  errors,
  form,
  isEditing,
  onCancel,
  onEdit,
  onSave,
}: ProfilePersonalDataCardProps) {
  return (
    <div className="profile-card">
      <div className="profile-card-header">
        <h2>
          <i className="fas fa-user-circle" style={{ color: 'var(--color-primary)' }} />
          Datos Personales
        </h2>

        {!isEditing ? (
          <button type="button" id="btnEditarPerfil" className="btn btn-sm btn-outline-primary" onClick={onEdit}>
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
            value={form.data.name}
            readOnly={!isEditing}
            error={errors.name ?? form.errors.name}
            onChange={(value) => form.setData('name', value)}
            required
            minLength={2}
            maxLength={60}
            placeholder="Tu nombre"
          />
          <ProfileField
            id="first_surname"
            label="Primer Apellido *"
            value={form.data.first_surname}
            readOnly={!isEditing}
            error={errors.first_surname ?? form.errors.first_surname}
            onChange={(value) => form.setData('first_surname', value)}
            required
            minLength={2}
            maxLength={60}
            placeholder="Tu primer apellido"
          />
          <ProfileField
            id="second_surname"
            label="Segundo Apellido"
            value={form.data.second_surname}
            readOnly={!isEditing}
            error={errors.second_surname ?? form.errors.second_surname}
            onChange={(value) => form.setData('second_surname', value)}
            maxLength={60}
            placeholder="Opcional"
          />
          <ProfileField
            id="gmail"
            label="Correo Electrónico *"
            type="email"
            value={form.data.gmail}
            readOnly={!isEditing}
            error={errors.gmail ?? form.errors.gmail}
            onChange={(value) => form.setData('gmail', value)}
            required
            fullWidth
            placeholder="tu@correo.com"
          />
        </div>

        {isEditing ? (
          <div id="accionesEdicion" className="profile-form-actions">
            <button type="button" className="btn btn-primary" disabled={form.processing} onClick={() => void onSave()}>
              <i className="fas fa-save" /> Guardar Cambios
            </button>
            <button type="button" className="btn btn-secondary" onClick={onCancel}>
              <i className="fas fa-times" /> Cancelar
            </button>
          </div>
        ) : null}
      </form>
    </div>
  );
}
