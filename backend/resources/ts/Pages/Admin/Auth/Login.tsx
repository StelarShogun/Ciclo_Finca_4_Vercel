import { Head, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import type { FormEvent } from 'react';

import { useRecaptchaV2 } from '@/shared/hooks/useRecaptchaV2';
import type { InertiaSharedProps } from '@/shared/types/models';

import '../../../../css/admin/shell-base.css';
import '../../../../css/admin/login/login.css';

type LoginPageProps = { recaptchaSiteKey?: string | null };
type InertiaErrors = Record<string, string[]>;

export default function Login({ recaptchaSiteKey }: LoginPageProps) {
  const page = usePage<InertiaSharedProps & { errors?: InertiaErrors }>();
  const errors = page.props.errors ?? {};

  const [gmail, setGmail] = useState('');
  const [password, setPassword] = useState('');
  const [isPassVisible, setIsPassVisible] = useState(false);
  const [processing, setProcessing] = useState(false);

  const { widgetRef, token, isRendered } = useRecaptchaV2(recaptchaSiteKey);

  function submit(e: FormEvent) {
    e.preventDefault();
    if (processing) return;
    setProcessing(true);

    const payload: Record<string, unknown> = { gmail, password };
    if (recaptchaSiteKey) {
      payload['g-recaptcha-response'] = token;
    }

    router.post('/admin/login', payload as Record<string, string>, { onFinish: () => setProcessing(false) });
  }

  return (
    <>
      <Head title="Iniciar Sesión - Ciclo Finca 4" />

      <div className="auth-container">
        <div className="auth-form active">
          <div className="formulario-header">
            <h1>Iniciar Sesión - Administradores</h1>
            <p>Solo los administradores pueden acceder al sistema</p>
            <div className="alert alert-info">
              <i className="fas fa-lock" aria-hidden="true" />
              <strong>Acceso Restringido:</strong> Este sistema está disponible únicamente para usuarios con rol de administrador.
            </div>
          </div>

          <div className="formulario-card">
            <form className="formulario-body" onSubmit={submit}>
              <div className="form-group">
                <label htmlFor="loginEmail">Correo Electrónico *</label>
                <input
                  type="email"
                  id="loginEmail"
                  name="gmail"
                  required
                  placeholder="ejemplo@correo.com"
                  value={gmail}
                  onChange={(e) => setGmail(e.target.value)}
                />
                {errors.gmail?.[0] ? <div className="field-msg error">{errors.gmail[0]}</div> : null}
              </div>

              <div className="form-group">
                <label htmlFor="loginPassword">Contraseña *</label>
                <div className="password-container">
                  <input
                    type={isPassVisible ? 'text' : 'password'}
                    id="loginPassword"
                    name="password"
                    required
                    placeholder="Ingresa tu contraseña"
                    value={password}
                    onChange={(e) => setPassword(e.target.value)}
                  />
                  <i
                    className={`fas ${isPassVisible ? 'fa-eye-slash' : 'fa-eye'}`}
                    id="togglePassword"
                    role="button"
                    aria-label="Mostrar/ocultar contraseña"
                    onClick={() => setIsPassVisible((v) => !v)}
                  />
                </div>
                {errors.password?.[0] ? <div className="field-msg error">{errors.password[0]}</div> : null}
              </div>

              {recaptchaSiteKey ? (
                <div className="form-group">
                  <div ref={widgetRef} className="g-recaptcha" />
                  {errors['g-recaptcha-response']?.[0] ? <div className="field-msg error">{errors['g-recaptcha-response'][0]}</div> : null}
                </div>
              ) : null}

              <div className="form-actions">
                <button
                  type="submit"
                  className="btn btn-primary full-width"
                  disabled={processing || Boolean(recaptchaSiteKey && !isRendered)}
                >
                  <i className="fas fa-sign-in-alt" aria-hidden="true" />
                  <span>{processing ? 'Ingresando…' : 'Iniciar Sesión'}</span>
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </>
  );
}
