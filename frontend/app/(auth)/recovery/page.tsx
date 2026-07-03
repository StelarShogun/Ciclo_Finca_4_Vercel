"use client";

import { useState } from "react";
import Link from "next/link";
import { useRouter } from "next/navigation";
import { useMutation } from "@tanstack/react-query";
import { isAxiosError } from "axios";
import { toast } from "sonner";

import { recoveryReset, recoverySend, recoveryVerify } from "@/lib/api/auth";
import {
  AuthBackLink,
  AuthBox,
  AuthLogo,
  AuthSubmitButton,
  AuthSubtitle,
  AuthTitle,
  FieldLabel,
  PasswordToggle,
} from "@/components/auth/auth-shell";
import { OtpInput } from "@/components/auth/otp-input";
import { Input } from "@/components/ui/input";

function errMsg(e: unknown, fallback: string) {
  return (isAxiosError(e) && (e.response?.data?.message as string)) || fallback;
}

export default function RecoveryPage() {
  const router = useRouter();
  const [step, setStep] = useState<1 | 2 | 3>(1);
  const [gmail, setGmail] = useState("");
  const [code, setCode] = useState("");
  const [pwd, setPwd] = useState("");
  const [pwd2, setPwd2] = useState("");
  const [showPwd, setShowPwd] = useState(false);
  const [showPwd2, setShowPwd2] = useState(false);

  const send = useMutation({
    mutationFn: () => recoverySend(gmail.trim()),
    onSuccess: () => { toast.success("Te enviamos un código"); setStep(2); },
    onError: (e) => toast.error(errMsg(e, "No se pudo enviar el código.")),
  });
  const verify = useMutation({
    mutationFn: () => recoveryVerify(code),
    onSuccess: () => { toast.success("Código verificado"); setStep(3); },
    onError: (e) => toast.error(errMsg(e, "Código inválido o expirado.")),
  });
  const reset = useMutation({
    mutationFn: () => recoveryReset(pwd, pwd2),
    onSuccess: () => { toast.success("Contraseña actualizada"); router.push("/login"); },
    onError: (e) => toast.error(errMsg(e, "No se pudo actualizar la contraseña.")),
  });

  // Pantalla 2: verificación de código (fiel al VerifyCode viejo en modo recovery).
  if (step === 2) {
    return (
      <AuthBox className="max-w-[420px]">
        <div className="mb-3 text-center">
          <i className="fas fa-envelope-open-text text-5xl text-[#12B36A]" aria-hidden />
        </div>
        <AuthTitle>Verifica que eres tú</AuthTitle>
        <AuthSubtitle>
          Código de verificación ha sido enviado a tu correo
          <br />
          <strong>{gmail}</strong>
        </AuthSubtitle>

        <form
          className="flex w-full flex-col gap-4"
          onSubmit={(e) => { e.preventDefault(); if (code.length === 6) verify.mutate(); }}
          noValidate
        >
          <div>
            <p className="mb-1 text-center text-sm font-semibold">Código de verificación</p>
            <OtpInput value={code} onChange={setCode} disabled={verify.isPending} />
          </div>
          <AuthSubmitButton
            icon="fas fa-check-circle"
            disabled={code.length !== 6}
            pending={verify.isPending}
            pendingText="Verificando..."
          >
            Verificar Código
          </AuthSubmitButton>
        </form>

        <div className="mt-4 text-center">
          <button
            type="button"
            onClick={() => { setStep(1); setCode(""); }}
            className="text-sm text-muted-foreground hover:text-foreground"
          >
            <i className="fas fa-arrow-left" aria-hidden /> Volver a recuperación
          </button>
        </div>
      </AuthBox>
    );
  }

  // Pantalla 3: nueva contraseña (fiel a RecoveryReset).
  if (step === 3) {
    return (
      <AuthBox className="max-w-[460px]">
        <div className="mb-3 text-center">
          <i className="fas fa-key text-5xl text-[#12B36A]" aria-hidden />
        </div>
        <AuthTitle>Nueva contraseña</AuthTitle>
        <AuthSubtitle>Define tu nueva contraseña para continuar</AuthSubtitle>

        <form
          className="flex w-full flex-col gap-4"
          onSubmit={(e) => { e.preventDefault(); if (pwd.length >= 8 && pwd === pwd2) reset.mutate(); }}
          noValidate
        >
          <div className="flex flex-col gap-1.5">
            <FieldLabel htmlFor="np" icon="fas fa-lock">Nueva contraseña</FieldLabel>
            <div className="relative">
              <Input id="np" type={showPwd ? "text" : "password"} placeholder="Mínimo 8 caracteres" autoComplete="new-password" className="pr-10" value={pwd} onChange={(e) => setPwd(e.target.value)} />
              <PasswordToggle visible={showPwd} onToggle={() => setShowPwd((v) => !v)} />
            </div>
          </div>
          <div className="flex flex-col gap-1.5">
            <FieldLabel htmlFor="np2" icon="fas fa-lock">Confirmar contraseña</FieldLabel>
            <div className="relative">
              <Input id="np2" type={showPwd2 ? "text" : "password"} placeholder="Repite la contraseña" autoComplete="new-password" className="pr-10" value={pwd2} onChange={(e) => setPwd2(e.target.value)} />
              <PasswordToggle visible={showPwd2} onToggle={() => setShowPwd2((v) => !v)} />
            </div>
            {pwd2 && pwd !== pwd2 && (
              <p className="text-[0.82rem] font-medium text-destructive">Las contraseñas no coinciden</p>
            )}
          </div>
          <AuthSubmitButton
            icon="fas fa-check-circle"
            disabled={pwd.length < 8 || pwd !== pwd2}
            pending={reset.isPending}
            pendingText="Actualizando..."
          >
            Actualizar contraseña
          </AuthSubmitButton>
        </form>

        <p className="mt-4 text-center text-sm text-muted-foreground">
          <Link href="/login" className="font-semibold text-[#235347] hover:underline dark:text-[#8EB69B]">Volver a iniciar sesión</Link>
        </p>
      </AuthBox>
    );
  }

  // Pantalla 1: solicitar código (fiel a RecoveryRequest).
  return (
    <AuthBox className="max-w-[460px]">
      <AuthBackLink href="/login">Volver a iniciar sesión</AuthBackLink>
      <AuthLogo />
      <AuthTitle>Recuperar contraseña</AuthTitle>
      <AuthSubtitle>Ingresa tu correo y te enviaremos un código de recuperación</AuthSubtitle>

      <form
        className="flex w-full flex-col gap-4"
        onSubmit={(e) => { e.preventDefault(); if (gmail.trim()) send.mutate(); }}
        noValidate
      >
        <div className="flex flex-col gap-1.5">
          <FieldLabel htmlFor="gmail" icon="fas fa-envelope">Correo Electrónico</FieldLabel>
          <Input
            id="gmail"
            type="email"
            placeholder="ejemplo@correo.com"
            autoComplete="email"
            value={gmail}
            onChange={(e) => setGmail(e.target.value)}
          />
        </div>
        <AuthSubmitButton
          icon="fas fa-paper-plane"
          disabled={!gmail.trim()}
          pending={send.isPending}
          pendingText="Enviando..."
        >
          Enviar código
        </AuthSubmitButton>
      </form>
    </AuthBox>
  );
}
