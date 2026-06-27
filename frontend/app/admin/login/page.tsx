"use client";

import { useRouter } from "next/navigation";
import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import { z } from "zod";
import { useMutation } from "@tanstack/react-query";
import { isAxiosError } from "axios";
import { toast } from "sonner";

import { adminLogin } from "@/lib/api/auth";
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

export default function AdminLoginPage() {
  const router = useRouter();
  const {
    register,
    handleSubmit,
    setError,
    formState: { errors },
  } = useForm<FormValues>({ resolver: zodResolver(schema) });

  const mutation = useMutation({
    mutationFn: adminLogin,
    onSuccess: (me) => {
      toast.success(`Bienvenido, ${me.user.name}`);
      router.push("/admin");
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
      const message =
        (isAxiosError(error) && (error.response?.data?.message as string)) ||
        "No fue posible iniciar sesión. Intentalo de nuevo.";
      toast.error(message);
    },
  });

  return (
    <main className="flex min-h-svh items-center justify-center bg-background p-4">
      <Card className="w-full max-w-sm">
        <CardHeader>
          <CardTitle className="text-xl">Panel administrativo</CardTitle>
          <CardDescription>Ingresá con tu cuenta de administrador.</CardDescription>
        </CardHeader>
        <CardContent>
          <form
            className="flex flex-col gap-4"
            onSubmit={handleSubmit((values) => mutation.mutate(values))}
            noValidate
          >
            <div className="flex flex-col gap-2">
              <Label htmlFor="gmail">Correo</Label>
              <Input
                id="gmail"
                type="email"
                autoComplete="username"
                {...register("gmail")}
                aria-invalid={!!errors.gmail}
              />
              {errors.gmail && (
                <p className="text-sm text-destructive">{errors.gmail.message}</p>
              )}
            </div>

            <div className="flex flex-col gap-2">
              <Label htmlFor="password">Contraseña</Label>
              <Input
                id="password"
                type="password"
                autoComplete="current-password"
                {...register("password")}
                aria-invalid={!!errors.password}
              />
              {errors.password && (
                <p className="text-sm text-destructive">{errors.password.message}</p>
              )}
            </div>

            <Button type="submit" className="w-full" disabled={mutation.isPending}>
              {mutation.isPending ? "Ingresando…" : "Ingresar"}
            </Button>
          </form>
        </CardContent>
      </Card>
    </main>
  );
}
