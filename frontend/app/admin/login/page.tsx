"use client";

import { useState } from "react";
import { useRouter } from "next/navigation";
import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import { z } from "zod";
import { useMutation } from "@tanstack/react-query";
import { isAxiosError } from "axios";
import { toast } from "sonner";
import { Eye, EyeOff, Lock, LogIn, ShieldCheck } from "lucide-react";

import { adminLogin } from "@/lib/api/auth";
import { useRecaptchaSiteKey, useRecaptchaV2 } from "@/lib/recaptcha";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";

const schema = z.object({
  gmail: z.string().email("Correo inválido"),
  password: z.string().min(1, "La contraseña es obligatoria"),
});

type FormValues = z.infer<typeof schema>;

export default function AdminLoginPage() {
  const router = useRouter();
  const [showPwd, setShowPwd] = useState(false);
  const meta = useRecaptchaSiteKey();
  const siteKey = meta.data?.recaptchaSiteKey ?? null;
  const { widgetRef, token: recaptchaToken, reset: resetRecaptcha } = useRecaptchaV2(siteKey);

  const {
    register,
    handleSubmit,
    setError,
    formState: { errors },
  } = useForm<FormValues>({ resolver: zodResolver(schema) });

  const mutation = useMutation({
    mutationFn: (values: FormValues) =>
      adminLogin(siteKey ? { ...values, "g-recaptcha-response": recaptchaToken } : values),
    onSuccess: (me) => {
      toast.success(`Bienvenido, ${me.user.name}`);
      router.push("/admin");
    },
    onError: (error) => {
      resetRecaptcha();
      if (isAxiosError(error) && error.response?.status === 422) {
        const fieldErrors = error.response.data?.errors ?? {};
        for (const [field, messages] of Object.entries(fieldErrors)) {
          const msg = Array.isArray(messages) ? String(messages[0]) : String(messages);
          if (field === "gmail" || field === "password") setError(field, { message: msg });
          else toast.error(msg);
        }
        return;
      }
      const message =
        (isAxiosError(error) && (error.response?.data?.message as string)) ||
        "No fue posible iniciar sesión. Intentalo de nuevo.";
      toast.error(message);
    },
  });

  return (
    <main className="flex min-h-svh items-center justify-center bg-gradient-to-b from-brand-darkest via-brand-dark to-brand-medium-dark p-4">
      <div className="w-full max-w-md">
        <div className="mb-6 flex flex-col items-center text-center">
          {/* eslint-disable-next-line @next/next/no-img-element */}
          <img
            src="/brand/logo-ciclo-finca-icon-128.webp"
            alt="Ciclo Finca 4"
            className="mb-3 h-16 w-16 rounded-full border-2 border-brand-light/60 bg-brand-lightest object-contain p-1"
          />
          <h1 className="text-2xl font-bold text-white">Ciclo Finca 4</h1>
          <p className="text-sm text-brand-lightest/70">Panel administrativo</p>
        </div>

        <Card className="overflow-hidden border-0 shadow-2xl">
          <CardHeader className="space-y-2 pb-2">
            <h2 className="text-lg font-semibold">Iniciar sesión</h2>
            <div className="flex items-start gap-2 rounded-lg border border-brand-medium/25 bg-brand-lightest/40 px-3 py-2 text-xs text-brand-medium-dark dark:border-brand-light/25 dark:bg-brand-medium-dark/40 dark:text-brand-lightest">
              <Lock className="mt-0.5 h-3.5 w-3.5 shrink-0" aria-hidden />
              <span>
                <strong>Acceso restringido:</strong> este sistema está disponible únicamente para
                usuarios con rol de administrador.
              </span>
            </div>
          </CardHeader>
          <CardContent>
            <form
              className="flex flex-col gap-4"
              onSubmit={handleSubmit((values) => mutation.mutate(values))}
              noValidate
            >
              <div className="flex flex-col gap-1.5">
                <Label htmlFor="gmail">Correo electrónico</Label>
                <Input
                  id="gmail"
                  type="email"
                  placeholder="ejemplo@correo.com"
                  autoComplete="username"
                  {...register("gmail")}
                  aria-invalid={!!errors.gmail}
                />
                {errors.gmail && <p className="text-sm text-destructive">{errors.gmail.message}</p>}
              </div>

              <div className="flex flex-col gap-1.5">
                <Label htmlFor="password">Contraseña</Label>
                <div className="relative">
                  <Input
                    id="password"
                    type={showPwd ? "text" : "password"}
                    placeholder="Ingresa tu contraseña"
                    autoComplete="current-password"
                    className="pr-10"
                    {...register("password")}
                    aria-invalid={!!errors.password}
                  />
                  <button
                    type="button"
                    onClick={() => setShowPwd((v) => !v)}
                    aria-label={showPwd ? "Ocultar contraseña" : "Mostrar contraseña"}
                    className="absolute inset-y-0 right-0 flex w-10 items-center justify-center text-muted-foreground hover:text-foreground"
                  >
                    {showPwd ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
                  </button>
                </div>
                {errors.password && (
                  <p className="text-sm text-destructive">{errors.password.message}</p>
                )}
              </div>

              {siteKey && (
                <div className="flex justify-center">
                  {/* El iframe de reCAPTCHA mide 304px fijos; en pantallas muy angostas se escala. */}
                  <div ref={widgetRef} className="origin-center max-[359px]:scale-[0.85]" />
                </div>
              )}

              <Button
                type="submit"
                className="w-full bg-brand-medium hover:bg-brand-medium-dark"
                disabled={mutation.isPending || Boolean(siteKey && !recaptchaToken)}
              >
                <LogIn className="h-4 w-4" aria-hidden />
                {mutation.isPending ? "Ingresando…" : "Iniciar sesión"}
              </Button>
            </form>
          </CardContent>
        </Card>

        <p className="mt-4 flex items-center justify-center gap-1.5 text-center text-xs text-brand-lightest/60">
          <ShieldCheck className="h-3.5 w-3.5" aria-hidden />
          Conexión protegida · los accesos quedan registrados en auditoría
        </p>
      </div>
    </main>
  );
}
