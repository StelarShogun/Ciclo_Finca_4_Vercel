"use client";

import { Suspense, useEffect, useState } from "react";
import Link from "next/link";
import { useRouter, useSearchParams } from "next/navigation";
import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import { z } from "zod";
import { useMutation, useQueryClient } from "@tanstack/react-query";
import { isAxiosError } from "axios";
import { toast } from "sonner";

import { clientLogin } from "@/lib/api/auth";
import {
  AuthBackLink,
  AuthBox,
  AuthDivider,
  AuthLogo,
  AuthSubmitButton,
  AuthSubtitle,
  AuthTitle,
  FieldError,
  FieldLabel,
  GoogleButton,
  PasswordToggle,
} from "@/components/auth/auth-shell";
import { Input } from "@/components/ui/input";

const schema = z.object({
  gmail: z.string().email("Correo inválido"),
  password: z.string().min(1, "La contraseña es obligatoria"),
});

type FormValues = z.infer<typeof schema>;

const API_URL = process.env.NEXT_PUBLIC_API_URL ?? "";

function LoginInner() {
  const router = useRouter();
  const params = useSearchParams();
  const queryClient = useQueryClient();
  const redirectTo = params.get("redirect") ?? "/account";
  const oauthError = params.get("oauth_error");
  const [showPass, setShowPass] = useState(false);

  // Mensaje de error si volvemos de un OAuth fallido.
  useEffect(() => {
    if (oauthError) toast.error(oauthError);
  }, [oauthError]);

  const { register, handleSubmit, setError, formState: { errors } } = useForm<FormValues>({
    resolver: zodResolver(schema),
  });

  const mutation = useMutation({
    mutationFn: clientLogin,
    onSuccess: (m) => {
      queryClient.setQueryData(["me"], m);
      toast.success(`Hola, ${m.user.name}`);
      router.push(redirectTo);
    },
    onError: (error) => {
      if (isAxiosError(error) && error.response?.status === 422) {
        const fieldErrors = error.response.data?.errors ?? {};
        for (const [field, messages] of Object.entries(fieldErrors)) {
          setError(field as keyof FormValues, {
            message: Array.isArray(messages) ? String(messages[0]) : String(messages),
          });
        }
        return;
      }
      toast.error(
        (isAxiosError(error) && (error.response?.data?.message as string)) ||
          "No fue posible iniciar sesión.",
      );
    },
  });

  return (
    <AuthBox>
      <AuthBackLink />
      <AuthLogo />
      <AuthTitle>Bienvenido de nuevo</AuthTitle>
      <AuthSubtitle>Ingresa a tu cuenta para continuar</AuthSubtitle>

      <form className="flex w-full flex-col gap-4" onSubmit={handleSubmit((v) => mutation.mutate(v))} noValidate>
        <div className="flex flex-col gap-1.5">
          <FieldLabel htmlFor="gmail" icon="fas fa-envelope">Correo Electrónico</FieldLabel>
          <Input
            id="gmail"
            type="email"
            maxLength={120}
            autoComplete="email"
            placeholder="ejemplo@correo.com"
            {...register("gmail")}
            aria-invalid={!!errors.gmail}
          />
          <FieldError>{errors.gmail?.message}</FieldError>
        </div>

        <div className="flex flex-col gap-1.5">
          <FieldLabel htmlFor="password" icon="fas fa-lock">Contraseña</FieldLabel>
          <div className="relative">
            <Input
              id="password"
              type={showPass ? "text" : "password"}
              maxLength={128}
              autoComplete="current-password"
              placeholder="Ingresa tu contraseña"
              className="pr-10"
              {...register("password")}
              aria-invalid={!!errors.password}
            />
            <PasswordToggle visible={showPass} onToggle={() => setShowPass((v) => !v)} />
          </div>
          <FieldError>{errors.password?.message}</FieldError>
          <div className="text-right">
            <Link href="/recovery" className="text-[0.85rem] font-medium text-cta hover:underline">
              ¿Olvidó su contraseña?
            </Link>
          </div>
        </div>

        <AuthSubmitButton icon="fas fa-sign-in-alt" pending={mutation.isPending} pendingText="Ingresando...">
          Iniciar Sesión
        </AuthSubmitButton>
      </form>

      <AuthDivider />
      <GoogleButton href={`${API_URL}/auth/google?from=spa`} />

      <div className="mt-5 w-full text-center">
        <p className="mb-2.5 text-sm text-muted-foreground">¿No tienes una cuenta?</p>
        <Link
          href="/register"
          className="inline-flex w-full items-center justify-center gap-2.5 rounded-[10px] border-[1.5px] border-[#dadce0] bg-card px-3.5 py-[11px] text-[0.95rem] font-semibold text-brand-medium transition hover:border-brand-medium hover:bg-accent dark:border-border dark:text-brand-light"
        >
          <i className="fas fa-user-plus" aria-hidden />
          <span>Crear cuenta</span>
        </Link>
      </div>
    </AuthBox>
  );
}

export default function ClientLoginPage() {
  return (
    <Suspense fallback={null}>
      <LoginInner />
    </Suspense>
  );
}
