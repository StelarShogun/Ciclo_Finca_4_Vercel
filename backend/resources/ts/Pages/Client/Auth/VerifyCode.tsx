import { Head, router, usePage, Link } from '@inertiajs/react';
import { useRef, useState } from 'react';

import { ClientAuthLayout } from '@/shared/components/layout/ClientAuthLayout';
import { InlineAlert } from '@/shared/components/ui/InlineAlert';
import { firstError } from '@/shared/lib/inertiaErrors';

import type { InertiaSharedProps } from '@/shared/types/models';

type VerifyCodePageProps = {
  isRecoveryFlow: boolean;
  destinationEmail?: string | null;
  mailWarning?: string | null;
};

type InertiaErrors = Record<string, string[]>;

const OTP_SLOT_IDS = ['otp-slot-1', 'otp-slot-2', 'otp-slot-3', 'otp-slot-4', 'otp-slot-5', 'otp-slot-6'] as const;

export default function VerifyCode({ isRecoveryFlow, destinationEmail, mailWarning }: VerifyCodePageProps) {
  const page = usePage<InertiaSharedProps & { errors?: InertiaErrors }>();
  const errors = page.props.errors ?? {};

  const [digits, setDigits] = useState<string[]>(() => Array.from({ length: 6 }, () => ''));
  const [processing, setProcessing] = useState(false);
  const [resending, setResending] = useState(false);

  const inputsRef = useRef<Array<HTMLInputElement | null>>([]);

  const actionUrl = isRecoveryFlow ? '/recovery/verify' : '/verify';
  const showResend = !isRecoveryFlow;

  const verificationCode = digits.join('');
  const isComplete = verificationCode.length === 6 && !verificationCode.includes('');

  function setDigitAt(index: number, value: string) {
    const cleaned = value.replace(/\D/g, '').slice(-1);
    setDigits((prev) => {
      const next = [...prev];
      next[index] = cleaned;
      return next;
    });

    if (cleaned && index < 5) {
      inputsRef.current[index + 1]?.focus();
    }
  }

  function onInputChange(index: number, e: React.ChangeEvent<HTMLInputElement>) {
    setDigitAt(index, e.target.value);
  }

  function onSubmit(e: React.FormEvent) {
    e.preventDefault();
    if (processing) return;

    setProcessing(true);

    router.post(
      actionUrl,
      { verification_code: verificationCode },
      {
        onFinish: () => setProcessing(false),
      },
    );
  }

  function resend() {
    if (resending) return;
    setResending(true);

    router.post('/verify/resend', {}, { onFinish: () => setResending(false) });
  }

  return (
    <>
      <Head title={isRecoveryFlow ? 'Verificar Recuperación - Ciclo Finca 4' : 'Verificar Correo - Ciclo Finca 4'} />
      <ClientAuthLayout>
        <div className="login-page-center">
          <div className="login-form-box" style={{ maxWidth: 420, width: '100%' }}>
            <div className="text-center mb-3">
              <i className="fas fa-envelope-open-text" style={{ fontSize: '3rem', color: 'var(--color-success)' }} />
            </div>

            <h2 className="text-center mb-2" style={{ fontSize: '1.5rem', fontWeight: 700 }}>
              Verifica que eres tú
            </h2>

            <p className="text-center text-muted mb-4" style={{ fontSize: '0.95rem' }}>
              Código de verificación ha sido enviado a tu correo
              {destinationEmail ? (
                <>
                  <br />
                  <strong>{destinationEmail}</strong>
                </>
              ) : null}
            </p>

            {firstError(errors, 'verification_code') ? (
              <InlineAlert variant="danger">{firstError(errors, 'verification_code')}</InlineAlert>
            ) : null}

            {mailWarning ? (
              <InlineAlert variant="warning">
                <i className="fas fa-exclamation-triangle" /> {mailWarning}
              </InlineAlert>
            ) : null}

            <form id="formVerificar" onSubmit={onSubmit} noValidate>
              <div className="form-group mb-4">
                <label
                  className="login-field-label"
                  style={{ fontWeight: 600, justifyContent: 'center' }}
                  htmlFor="verification_code"
                >
                  Código de verificación
                </label>

                <fieldset className="otp-inputs" aria-label="Código de verificación">
                  {OTP_SLOT_IDS.map((slotId, idx) => (
                    <input
                      key={slotId}
                      type="text"
                      className="otp-box"
                      inputMode="numeric"
                      autoComplete={idx === 0 ? 'one-time-code' : 'off'}
                      maxLength={1}
                      value={digits[idx]}
                      aria-label={`Dígito ${idx + 1}`}
                      onChange={(e) => onInputChange(idx, e)}
                      ref={(el) => {
                        inputsRef.current[idx] = el;
                      }}
                    />
                  ))}
                  <span className="otp-success" aria-hidden="true" />
                </fieldset>

                <input type="hidden" name="verification_code" id="verification_code" value={verificationCode} />
              </div>

              <button type="submit" id="btnVerificar" className="btn btn-login-submit" style={{ marginTop: 0 }} disabled={processing || !isComplete}>
                <i className="fas fa-check-circle" />
                <span>Verificar Código</span>
                <span className="otp-loading" style={{ display: processing ? 'inline-flex' : 'none' }}>
                  Verificando...
                </span>
              </button>
            </form>

            {showResend ? (
              <div style={{ textAlign: 'center', marginTop: '1.5rem' }}>
                <p style={{ color: 'var(--text-secondary)', fontSize: '0.875rem', marginBottom: 6 }}>¿No recibiste el código?</p>
                <button
                  type="button"
                  onClick={resend}
                  style={{ background: 'none', border: 'none', color: 'var(--color-success)', fontWeight: 600, cursor: 'pointer', fontSize: '0.875rem' }}
                  disabled={resending}
                >
                  {resending ? 'Enviando...' : 'Reenviar código'}
                </button>
              </div>
            ) : null}

            <div style={{ textAlign: 'center', marginTop: '1rem' }}>
              <Link
                href={isRecoveryFlow ? '/recovery' : '/register'}
                style={{ fontSize: '0.875rem', color: 'var(--text-secondary)', textDecoration: 'none' }}
              >
                <i className="fas fa-arrow-left" /> {isRecoveryFlow ? 'Volver a recuperación' : 'Volver al registro'}
              </Link>
            </div>
          </div>
        </div>
      </ClientAuthLayout>
    </>
  );
}

