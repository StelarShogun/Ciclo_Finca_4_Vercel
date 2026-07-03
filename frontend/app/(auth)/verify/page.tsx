"use client";

import { useState } from "react";
import Link from "next/link";
import { useRouter } from "next/navigation";
import { useMutation, useQueryClient } from "@tanstack/react-query";
import { isAxiosError } from "axios";
import { toast } from "sonner";

import { resendCode, verifyCode } from "@/lib/api/auth";
import { AuthBox, AuthSubmitButton, AuthSubtitle, AuthTitle } from "@/components/auth/auth-shell";
import { OtpInput } from "@/components/auth/otp-input";

function errMsg(e: unknown, fallback: string) {
  return (isAxiosError(e) && (e.response?.data?.message as string)) || fallback;
}

export default function VerifyPage() {
  const router = useRouter();
  const queryClient = useQueryClient();
  const [code, setCode] = useState("");

  const verify = useMutation({
    mutationFn: () => verifyCode(code),
    onSuccess: (m) => {
      queryClient.setQueryData(["me"], m);
      toast.success("¡Cuenta verificada!");
      router.push("/account");
    },
    onError: (e) => toast.error(errMsg(e, "Código inválido o expirado.")),
  });

  const resend = useMutation({
    mutationFn: resendCode,
    onSuccess: () => toast.success("Código reenviado"),
    onError: (e) => toast.error(errMsg(e, "No se pudo reenviar el código.")),
  });

  return (
    <AuthBox className="max-w-[420px]">
      <div className="mb-3 text-center">
        <i className="fas fa-envelope-open-text text-5xl text-[#12B36A]" aria-hidden />
      </div>
      <AuthTitle>Verifica que eres tú</AuthTitle>
      <AuthSubtitle>Código de verificación ha sido enviado a tu correo</AuthSubtitle>

      <form
        className="flex w-full flex-col gap-4"
        onSubmit={(e) => {
          e.preventDefault();
          if (code.length === 6) verify.mutate();
        }}
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

      <div className="mt-6 text-center">
        <p className="mb-1.5 text-sm text-muted-foreground">¿No recibiste el código?</p>
        <button
          type="button"
          onClick={() => resend.mutate()}
          disabled={resend.isPending}
          className="text-sm font-semibold text-[#12B36A] hover:underline disabled:opacity-60"
        >
          {resend.isPending ? "Enviando..." : "Reenviar código"}
        </button>
      </div>

      <div className="mt-4 text-center">
        <Link href="/register" className="text-sm text-muted-foreground hover:text-foreground">
          <i className="fas fa-arrow-left" aria-hidden /> Volver al registro
        </Link>
      </div>
    </AuthBox>
  );
}
