import { Head, router, usePage, Link } from '@inertiajs/react';
import { useState } from 'react';

import { ClientAuthLayout } from '@/shared/components/layout/ClientAuthLayout';
import { InlineAlert } from '@/shared/components/ui/InlineAlert';
import { firstError } from '@/lib/inertiaErrors';

import type { InertiaSharedProps } from '@/types/models';

type RecoveryRequestPageProps = {
  unregisteredRecoveryEmail?: boolean;
};

type InertiaErrors = Record<string, string[]>;

export default function RecoveryRequest({ unregisteredRecoveryEmail }: RecoveryRequestPageProps) {
  const page = usePage<InertiaSharedProps & { errors?: InertiaErrors }>();
  const errors = page.props.errors ?? {};

  const [gmail, setGmail] = useState('');
  const [processing, setProcessing] = useState(false);

  function submit(e: React.FormEvent) {
    e.preventDefault();
    if (processing) return;
    setProcessing(true);

    router.post('/recovery', { gmail }, { onFinish: () => setProcessing(false) });
  }

  return (
    <>
      <Head title="Recuperar Contraseña - Ciclo Finca 4" />
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

            <h2 className="text-center mb-2">Recuperar Contraseña</h2>
            <p className="login-subtitle text-center mb-4">Ingresa tu correo y te enviaremos un código de verificación</p>

            {unregisteredRecoveryEmail ? (
              <InlineAlert variant="danger">
                <div className="mb-1">Correo no está registrado.</div>
                <Link href="/register" className="alert-link">Ir a registrarse</Link>
              </InlineAlert>
            ) : null}

            {firstError(errors, 'gmail') ? (
              <InlineAlert variant="danger">{firstError(errors, 'gmail')}</InlineAlert>
            ) : null}

            <form id="formRecovery" onSubmit={submit} noValidate>
              <div className="form-group mb-4">
                <label htmlFor="recovery-email" className="login-field-label">
                  <i className="fas fa-envelope login-field-icon" aria-hidden="true" />
                  Correo Electrónico
                </label>
                <input
                  type="email"
                  id="recovery-email"
                  name="gmail"
                  className="form-control"
                  required
                  value={gmail}
                  placeholder="ejemplo@gmail.com"
                  onChange={(e) => setGmail(e.target.value)}
                />
              </div>

              <button type="submit" id="btnRecovery" className="btn btn-primary btn-block btn-lg mt-2" disabled={processing}>
                <i className="fas fa-paper-plane" />
                <span>{processing ? 'Enviando...' : 'Enviar código'}</span>
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

