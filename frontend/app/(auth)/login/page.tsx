"use client";

import { Suspense, useEffect } from "react";
import Link from "next/link";
import { useRouter, useSearchParams } from "next/navigation";
import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import { z } from "zod";
import { useMutation, useQueryClient } from "@tanstack/react-query";
import { isAxiosError } from "axios";
import { toast } from "sonner";

import { clientLogin } from "@/lib/api/auth";
import { Button } from "@/components/ui/button";
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";

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
    <div className="mx-auto flex max-w-md items-center justify-center px-4 py-16">
      <Card className="w-full border-t-4 border-t-[#235347]">
        <CardHeader>
          <CardTitle className="text-xl">Iniciar sesión</CardTitle>
          <CardDescription>Ingresá a tu cuenta de Ciclo Finca.</CardDescription>
        </CardHeader>
        <CardContent>
          <form className="flex flex-col gap-4" onSubmit={handleSubmit((v) => mutation.mutate(v))} noValidate>
            <div className="flex flex-col gap-2">
              <Label htmlFor="gmail">Correo</Label>
              <Input id="gmail" type="email" autoComplete="username" {...register("gmail")} aria-invalid={!!errors.gmail} />
              {errors.gmail && <p className="text-sm text-destructive">{errors.gmail.message}</p>}
            </div>
            <div className="flex flex-col gap-2">
              <div className="flex items-center justify-between">
                <Label htmlFor="password">Contraseña</Label>
                <Link href="/recovery" className="text-xs text-[#235347] hover:underline dark:text-[#8EB69B]">¿Olvidaste tu contraseña?</Link>
              </div>
              <Input id="password" type="password" autoComplete="current-password" {...register("password")} aria-invalid={!!errors.password} />
              {errors.password && <p className="text-sm text-destructive">{errors.password.message}</p>}
            </div>
            <Button type="submit" className="w-full bg-[#235347] hover:bg-[#1a3f37]" disabled={mutation.isPending}>
              {mutation.isPending ? "Ingresando…" : "Ingresar"}
            </Button>
          </form>

          <div className="my-4 flex items-center gap-3 text-xs text-muted-foreground">
            <span className="h-px flex-1 bg-border" /> o <span className="h-px flex-1 bg-border" />
          </div>
          <Button asChild variant="outline" className="w-full">
            <a href={`${API_URL}/auth/google?from=spa`}>Continuar con Google</a>
          </Button>

          <p className="mt-4 text-center text-sm text-muted-foreground">
            ¿No tenés cuenta?{" "}
            <Link href="/register" className="font-medium text-[#235347] hover:underline">
              Registrate
            </Link>
          </p>
        </CardContent>
      </Card>
    </div>
  );
}

export default function ClientLoginPage() {
  return (
    <Suspense fallback={null}>
      <LoginInner />
    </Suspense>
  );
}
