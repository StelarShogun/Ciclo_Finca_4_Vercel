import { Head, router, usePage, Link } from '@inertiajs/react';
import { useState } from 'react';

import { ClientAuthLayout } from '@/shared/components/layout/ClientAuthLayout';
import { useRecaptchaV2 } from '@/shared/hooks/useRecaptchaV2';
import { InlineAlert } from '@/shared/components/ui/InlineAlert';
import { firstError } from '@/shared/lib/inertiaErrors';

import type { InertiaSharedProps } from '@/shared/types/models';

type LoginPageProps = {
  recaptchaSiteKey?: string | null;
  recoverySuccessModal?: string | null;
  sessionExpired?: boolean;
};

type InertiaErrors = Record<string, string[]>;

export default function Login({
  recaptchaSiteKey,
  recoverySuccessModal,
  sessionExpired,
}: LoginPageProps) {
  const page = usePage<InertiaSharedProps & { errors?: InertiaErrors }>();
  const errors = page.props.errors ?? {};

  const [gmail, setGmail] = useState('');
  const [password, setPassword] = useState('');
  const [isPassVisible, setIsPassVisible] = useState(false);
  const [processing, setProcessing] = useState(false);

  const { widgetRef, token, isRendered } = useRecaptchaV2(recaptchaSiteKey);

  const showSessionExpired = Boolean(sessionExpired);
  const showRecoverySuccess = Boolean(recoverySuccessModal);

  function submit(e: React.FormEvent) {
    e.preventDefault();
    if (processing) return;

    setProcessing(true);

    const payload: Record<string, unknown> = {
      gmail,
      password,
    };

    if (recaptchaSiteKey) {
      payload['g-recaptcha-response'] = token;
    }

    router.post('/login', payload as any, {
      onFinish: () => setProcessing(false),
    });
  }

  return (
    <>
      <Head title="Iniciar Sesión - Ciclo Finca 4" />
      <ClientAuthLayout>
        <div className="login-page-center">
          <div className="login-form-box">
            <a href="/"
              className="login-back-link"
            >
              <i className="fas fa-arrow-left" />
              <span>Regresar</span>
            </a>

            <div className="login-auth-logo">
              <img src="/assets/images/logo.png" alt="Ciclo Finca 4" />
            </div>

            {showSessionExpired ? (
              <InlineAlert variant="warning">
                <i className="fas fa-exclamation-triangle" /> La sesión expiró o el token no es válido. Intenta iniciar sesión de nuevo.
              </InlineAlert>
            ) : null}

            {showRecoverySuccess ? (
              <InlineAlert variant="success">
                <i className="fas fa-check-circle" /> {recoverySuccessModal}
              </InlineAlert>
            ) : null}

            <h2>Bienvenido de nuevo</h2>
            <p className="login-subtitle">Ingresa a tu cuenta para continuar</p>

            <form id="public-login-form" onSubmit={submit}>
              <div className="form-group">
                <label htmlFor="login-email" className="login-field-label">
                  <i className="fas fa-envelope login-field-icon" aria-hidden="true" />
                  Correo Electrónico
                </label>
                <input
                  type="email"
                  id="login-email"
                  name="gmail"
                  className="form-control"
                  required
                  maxLength={120}
                  autoComplete="email"
                  placeholder="ejemplo@correo.com"
                  value={gmail}
                  onChange={(e) => setGmail(e.target.value)}
                />
                {firstError(errors, 'gmail') ? <div className="field-msg error">{firstError(errors, 'gmail')}</div> : null}
              </div>

              <div className="form-group">
                <label htmlFor="login-password" className="login-field-label">
                  <i className="fas fa-lock login-field-icon" aria-hidden="true" />
                  Contraseña
                </label>
                <div className="login-pass-wrap">
                  <input
                    type={isPassVisible ? 'text' : 'password'}
                    id="login-password"
                    name="password"
                    className="form-control"
                    required
                    maxLength={128}
                    autoComplete="current-password"
                    placeholder="Ingresa tu contraseña"
                    value={password}
                    onChange={(e) => setPassword(e.target.value)}
                  />
                  <button
                    type="button"
                    id="toggle-password"
                    className="login-pass-toggle"
                    onClick={() => setIsPassVisible((v) => !v)}
                    aria-label="Mostrar/ocultar contraseña"
                  >
                    <i className={`fas ${isPassVisible ? 'fa-eye-slash' : 'fa-eye'}`} />
                  </button>
                </div>
                {errors.password?.[0] ? <div className="field-msg error">{errors.password[0]}</div> : null}
                <div className="text-right mt-1" style={{ textAlign: 'right' }}>
                  <a
                    href="/recovery"
                    className="login-field-label"
                    style={{ fontSize: '0.85rem', color: 'var(--color-success)' }}
                  >
                    ¿Olvidó su contraseña?
                  </a>
                </div>
              </div>

              {recaptchaSiteKey ? (
                <div className="form-group recaptcha-wrap">
                  <div ref={widgetRef} />
                </div>
              ) : null}

              <button
                type="submit"
                className="btn btn-primary btn-login-submit"
                id="login-submit-btn"
                disabled={processing || Boolean(recaptchaSiteKey && !isRendered)}
              >
                <i className="fas fa-sign-in-alt" />
                <span>Iniciar Sesión</span>
              </button>
            </form>

            {errors['g-recaptcha-response']?.[0] ? (
              <p className="field-msg error" style={{ marginTop: 10 }}>
                {errors['g-recaptcha-response'][0]}
              </p>
            ) : null}

            <div className="login-divider">
              <span>o</span>
            </div>

            <div className="oauth-buttons">
              <a href="/auth/google" className="oauth-btn google-btn">
                <span className="google-g-icon" aria-hidden="true">G</span>
                <span className="google-text">
                  Continuar con
                  <span className="google-brand" aria-hidden="true">
                    <span className="brand-letter brand-g">G</span>
                    <span className="brand-letter brand-o">o</span>
                    <span className="brand-letter brand-o2">o</span>
                    <span className="brand-letter brand-g2">g</span>
                    <span className="brand-letter brand-l">l</span>
                    <span className="brand-letter brand-e">e</span>
                  </span>
                </span>
              </a>

              <p className="cf4-oauth-legal-note">
                Si creas tu cuenta con Google, aceptas los
                {' '}
                <a href="/legal/terminos" target="_blank" rel="noopener noreferrer">Términos y condiciones</a>
                {' '}
                y la
                {' '}
                <a href="/legal/privacidad" target="_blank" rel="noopener noreferrer">Política de privacidad</a>.
              </p>
            </div>

            <div className="login-footer">
              <p className="login-footer-text">¿No tienes una cuenta?</p>
              <Link href="/register" className="login-register-btn">
                <i className="fas fa-user-plus" aria-hidden="true" />
                <span>Crear cuenta</span>
              </Link>
            </div>
          </div>
        </div>
      </ClientAuthLayout>
    </>
  );
}

