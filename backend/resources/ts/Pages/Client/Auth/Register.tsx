import { Head, router, usePage, Link } from '@inertiajs/react';
import { useState } from 'react';

import { ClientAuthLayout } from '@/shared/components/layout/ClientAuthLayout';
import { Checkbox } from '@/shared/components/ui/Checkbox';
import { InlineAlert } from '@/shared/components/ui/InlineAlert';
import { firstError } from '@/shared/lib/inertiaErrors';

import type { InertiaSharedProps } from '@/shared/types/models';

type RegisterPageProps = {
  recaptchaSiteKey?: string | null;
};

type InertiaErrors = Record<string, string[]>;

export default function Register({ recaptchaSiteKey }: RegisterPageProps) {
  const page = usePage<InertiaSharedProps & { errors?: InertiaErrors }>();
  const errors = page.props.errors ?? {};

  const [name, setName] = useState('');
  const [firstSurname, setFirstSurname] = useState('');
  const [secondSurname, setSecondSurname] = useState('');
  const [gmail, setGmail] = useState('');
  const [password, setPassword] = useState('');
  const [passwordConfirmation, setPasswordConfirmation] = useState('');
  const [acceptTerms, setAcceptTerms] = useState(false);

  const [isPassVisible, setIsPassVisible] = useState(false);
  const [isPassConfirmVisible, setIsPassConfirmVisible] = useState(false);
  const [processing, setProcessing] = useState(false);

  function submit(e: React.FormEvent) {
    e.preventDefault();
    if (processing) return;
    setProcessing(true);

    const payload: Record<string, unknown> = {
      name,
      first_surname: firstSurname,
      second_surname: secondSurname || null,
      gmail,
      password,
      password_confirmation: passwordConfirmation,
      accept_terms: acceptTerms ? 1 : 0,
    };

    router.post('/register', payload as any, {
      onFinish: () => setProcessing(false),
    });
  }

  return (
    <>
      <Head title="Registrar Cliente - Ciclo Finca 4" />
      <ClientAuthLayout>
        <div className="login-page-center">
          <div className="login-form-box" style={{ maxWidth: 480 }}>
            <h2 className="text-center mb-4">Crear Cuenta</h2>

            {recaptchaSiteKey ? (
              <InlineAlert variant="info">
                Este formulario ya no requiere reCAPTCHA; puedes registrarte normalmente.
              </InlineAlert>
            ) : null}

            <form id="formRegistroCliente" onSubmit={submit} noValidate>
              <div className="form-group mb-3">
                <label htmlFor="name">Nombre <span className="text-danger">*</span></label>
                <input
                  type="text"
                  id="name"
                  name="name"
                  className="form-control"
                  value={name}
                  placeholder="Ej: Juan"
                  maxLength={50}
                  autoComplete="given-name"
                  onChange={(e) => setName(e.target.value)}
                />
                {firstError(errors, 'name') ? <div className="field-msg error">{firstError(errors, 'name')}</div> : null}
              </div>

              <div className="surnames-row" style={{ display: 'flex', gap: 16, marginBottom: '1rem' }}>
                <div className="form-group" style={{ flex: 1, minWidth: 0, marginBottom: 0 }}>
                  <label htmlFor="first_surname">Apellido <span className="text-danger">*</span></label>
                  <input
                    type="text"
                    id="first_surname"
                    name="first_surname"
                    className="form-control"
                    value={firstSurname}
                    placeholder="Ej: Pérez"
                    maxLength={50}
                    autoComplete="family-name"
                    onChange={(e) => setFirstSurname(e.target.value)}
                  />
                  {firstError(errors, 'first_surname') ? <div className="field-msg error">{firstError(errors, 'first_surname')}</div> : null}
                </div>

                <div className="form-group" style={{ flex: 1, minWidth: 0, marginBottom: 0 }}>
                  <label htmlFor="second_surname">Segundo Apellido</label>
                  <input
                    type="text"
                    id="second_surname"
                    name="second_surname"
                    className="form-control"
                    value={secondSurname}
                    placeholder="Ej: García (opcional)"
                    maxLength={50}
                    autoComplete="additional-name"
                    onChange={(e) => setSecondSurname(e.target.value)}
                  />
                  {firstError(errors, 'second_surname') ? <div className="field-msg error">{firstError(errors, 'second_surname')}</div> : null}
                </div>
              </div>

              <div className="form-group mb-3">
                <label htmlFor="gmail">Correo Electrónico <span className="text-danger">*</span></label>
                <input
                  type="email"
                  id="gmail"
                  name="gmail"
                  className="form-control"
                  value={gmail}
                  placeholder="ejemplo@gmail.com"
                  maxLength={120}
                  autoComplete="email"
                  onChange={(e) => setGmail(e.target.value)}
                />
                {firstError(errors, 'gmail') ? <div className="field-msg error">{firstError(errors, 'gmail')}</div> : null}
              </div>

              <div className="form-group mb-3">
                <label htmlFor="password">Contraseña <span className="text-danger">*</span></label>
                <div style={{ position: 'relative' }}>
                  <input
                    type={isPassVisible ? 'text' : 'password'}
                    id="password"
                    name="password"
                    className="form-control"
                    minLength={8}
                    maxLength={128}
                    autoComplete="new-password"
                    value={password}
                    placeholder="Mínimo 8 caracteres"
                    onChange={(e) => setPassword(e.target.value)}
                  />
                  <button
                    type="button"
                    onClick={() => setIsPassVisible((v) => !v)}
                    style={{
                      position: 'absolute',
                      top: '50%',
                      right: 8,
                      transform: 'translateY(-50%)',
                      background: 'none',
                      border: 'none',
                      cursor: 'pointer',
                    }}
                    aria-label="Mostrar/ocultar contraseña"
                  >
                    <i className={`fas ${isPassVisible ? 'fa-eye-slash' : 'fa-eye'}`} />
                  </button>
                </div>
                {firstError(errors, 'password') ? <div className="field-msg error">{firstError(errors, 'password')}</div> : null}
              </div>

              <div className="form-group mb-4">
                <label htmlFor="password_confirmation">Verificar Contraseña <span className="text-danger">*</span></label>
                <div style={{ position: 'relative' }}>
                  <input
                    type={isPassConfirmVisible ? 'text' : 'password'}
                    id="password_confirmation"
                    name="password_confirmation"
                    className="form-control"
                    minLength={8}
                    maxLength={128}
                    autoComplete="new-password"
                    value={passwordConfirmation}
                    placeholder="Repite la contraseña"
                    onChange={(e) => setPasswordConfirmation(e.target.value)}
                  />
                  <button
                    type="button"
                    onClick={() => setIsPassConfirmVisible((v) => !v)}
                    style={{
                      position: 'absolute',
                      top: '50%',
                      right: 8,
                      transform: 'translateY(-50%)',
                      background: 'none',
                      border: 'none',
                      cursor: 'pointer',
                    }}
                    aria-label="Mostrar/ocultar confirmar contraseña"
                  >
                    <i className={`fas ${isPassConfirmVisible ? 'fa-eye-slash' : 'fa-eye'}`} />
                  </button>
                </div>
                {firstError(errors, 'password_confirmation') ? <div className="field-msg error">{firstError(errors, 'password_confirmation')}</div> : null}
              </div>

              <div className="cf4-legal-consent">
                <Checkbox
                  name="accept_terms"
                  id="accept_terms"
                  checked={acceptTerms}
                  onChange={(e) => setAcceptTerms(e.target.checked)}
                  value="1"
                  labelClassName="cf4-legal-consent-label"
                  label={(
                    <>
                      Al registrarme acepto los
                      {' '}
                      <a href="/legal/terminos" target="_blank" rel="noopener noreferrer">Términos y condiciones</a>
                      {' '}
                      y la
                      {' '}
                      <a href="/legal/privacidad" target="_blank" rel="noopener noreferrer">Política de privacidad</a>.
                    </>
                  )}
                />
                {firstError(errors, 'accept_terms') ? <div className="field-msg error">{firstError(errors, 'accept_terms')}</div> : null}
              </div>

              <button
                type="submit"
                id="btnRegistrar"
                className="btn btn-primary btn-block btn-lg mt-2"
                disabled={processing}
              >
                <i className="fas fa-user-plus" />
                <span>{processing ? 'Registrando...' : 'Crear Cuenta'}</span>
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

