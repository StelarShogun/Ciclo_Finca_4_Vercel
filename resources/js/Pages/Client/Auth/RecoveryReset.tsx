import { Head, router, usePage, Link } from '@inertiajs/react';
import { useState } from 'react';

import { ClientAuthLayout } from '@/shared/components/layout/ClientAuthLayout';
import { InlineAlert } from '@/shared/components/ui/InlineAlert';
import { firstError } from '@/lib/inertiaErrors';

import type { InertiaSharedProps } from '@/types/models';

type RecoveryResetPageProps = {
  gmail: string;
};

type InertiaErrors = Record<string, string[]>;

export default function RecoveryReset({ gmail }: RecoveryResetPageProps) {
  const page = usePage<InertiaSharedProps & { errors?: InertiaErrors }>();
  const errors = page.props.errors ?? {};

  const [newPassword, setNewPassword] = useState('');
  const [newPasswordConfirmation, setNewPasswordConfirmation] = useState('');
  const [isPassVisible, setIsPassVisible] = useState(false);
  const [isConfirmVisible, setIsConfirmVisible] = useState(false);
  const [processing, setProcessing] = useState(false);

  function submit(e: React.FormEvent) {
    e.preventDefault();
    if (processing) return;
    setProcessing(true);

    router.post('/recovery/reset', {
      new_password: newPassword,
      new_password_confirmation: newPasswordConfirmation,
    }, { onFinish: () => setProcessing(false) });
  }

  return (
    <>
      <Head title="Nueva Contraseña - Ciclo Finca 4" />
      <ClientAuthLayout>
        <div className="login-page-center">
          <div className="login-form-box" style={{ maxWidth: 480 }}>
            <a href="/login" className="login-back-link">
              <i className="fas fa-arrow-left" />
              <span>Volver al inicio de sesión</span>
            </a>

            <div className="login-auth-logo">
              <img src="/assets/images/logo.png" alt="Ciclo Finca 4" />
            </div>

            <h2 className="text-center mb-2">Nueva Contraseña</h2>
            <p className="login-subtitle text-center mb-4">
              Define una nueva contraseña para <strong>{gmail}</strong>
            </p>

            {firstError(errors, 'new_password') ? (
              <InlineAlert variant="danger">{firstError(errors, 'new_password')}</InlineAlert>
            ) : null}

            {firstError(errors, 'new_password_confirmation') ? (
              <InlineAlert variant="danger">{firstError(errors, 'new_password_confirmation')}</InlineAlert>
            ) : null}

            <form id="formRecoveryReset" onSubmit={submit} noValidate>
              <div className="form-group mb-3">
                <label htmlFor="reset-password" className="login-field-label">
                  <i className="fas fa-lock login-field-icon" aria-hidden="true" />
                  Nueva Contraseña
                </label>
                <div className="login-pass-wrap">
                  <input
                    type={isPassVisible ? 'text' : 'password'}
                    id="reset-password"
                    name="new_password"
                    className="form-control"
                    required
                    minLength={8}
                    placeholder="Mínimo 8 caracteres"
                    value={newPassword}
                    onChange={(e) => setNewPassword(e.target.value)}
                  />
                  <button type="button" id="toggle-reset-password" className="login-pass-toggle" onClick={() => setIsPassVisible((v) => !v)}>
                    <i className={`fas ${isPassVisible ? 'fa-eye-slash' : 'fa-eye'}`} />
                  </button>
                </div>
              </div>

              <div className="form-group mb-4">
                <label htmlFor="reset-password-confirm" className="login-field-label">
                  <i className="fas fa-lock login-field-icon" aria-hidden="true" />
                  Confirmar Nueva Contraseña
                </label>
                <div className="login-pass-wrap">
                  <input
                    type={isConfirmVisible ? 'text' : 'password'}
                    id="reset-password-confirm"
                    name="new_password_confirmation"
                    className="form-control"
                    required
                    minLength={8}
                    placeholder="Repite la contraseña"
                    value={newPasswordConfirmation}
                    onChange={(e) => setNewPasswordConfirmation(e.target.value)}
                  />
                  <button type="button" id="toggle-reset-confirm" className="login-pass-toggle" onClick={() => setIsConfirmVisible((v) => !v)}>
                    <i className={`fas ${isConfirmVisible ? 'fa-eye-slash' : 'fa-eye'}`} />
                  </button>
                </div>
              </div>

              <button
                type="submit"
                id="btnRecoveryReset"
                className="btn btn-primary btn-block btn-lg mt-2"
                disabled={processing}
              >
                <i className="fas fa-key" />
                <span>{processing ? 'Actualizando...' : 'Actualizar Contraseña'}</span>
              </button>
            </form>

            <div className="login-footer text-center mt-3">
              <p>¿Ya tienes cuenta? <Link href="/login">Iniciar sesión</Link></p>
            </div>
          </div>
        </div>
      </ClientAuthLayout>
    </>
  );
}

