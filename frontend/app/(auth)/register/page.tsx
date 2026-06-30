"use client";

import Link from "next/link";
import { useRouter } from "next/navigation";
import { Controller, useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import { z } from "zod";
import { useMutation } from "@tanstack/react-query";
import { isAxiosError } from "axios";
import { toast } from "sonner";

import { clientRegister } from "@/lib/api/auth";
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
import { Checkbox } from "@/components/ui/checkbox";

const schema = z
  .object({
    name: z.string().min(2, "Mínimo 2 caracteres"),
    first_surname: z.string().min(2, "Mínimo 2 caracteres"),
    second_surname: z.string().optional(),
    gmail: z.string().regex(/^[^@]+@gmail\.com$/i, "Debe ser un correo @gmail.com"),
    password: z.string().min(8, "Mínimo 8 caracteres"),
    password_confirmation: z.string(),
    accept_terms: z.boolean().refine((v) => v, "Debés aceptar los términos"),
  })
  .refine((d) => d.password === d.password_confirmation, {
    message: "Las contraseñas no coinciden",
    path: ["password_confirmation"],
  });

type FormValues = z.infer<typeof schema>;

const API_URL = process.env.NEXT_PUBLIC_API_URL ?? "";

export default function RegisterPage() {
  const router = useRouter();
  const { register, handleSubmit, control, setError, formState: { errors } } = useForm<FormValues>({
    resolver: zodResolver(schema),
    defaultValues: { accept_terms: false },
  });

  const mutation = useMutation({
    mutationFn: clientRegister,
    onSuccess: (res) => {
      if (res.mail_warning) toast.warning(res.mail_warning);
      toast.success("Te enviamos un código a tu correo");
      router.push("/verify");
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
      toast.error((isAxiosError(error) && (error.response?.data?.message as string)) || "No fue posible registrarte.");
    },
  });

  return (
    <div className="mx-auto flex max-w-md items-center justify-center px-4 py-12">
      <Card className="w-full border-t-4 border-t-[#235347]">
        <CardHeader>
          <CardTitle className="text-xl">Crear cuenta</CardTitle>
          <CardDescription>Registrate con tu correo de Gmail.</CardDescription>
        </CardHeader>
        <CardContent>
          <form className="grid gap-4 sm:grid-cols-2" onSubmit={handleSubmit((v) => mutation.mutate(v))} noValidate>
            <div className="space-y-1.5">
              <Label htmlFor="name">Nombre</Label>
              <Input id="name" {...register("name")} aria-invalid={!!errors.name} />
              {errors.name && <p className="text-xs text-destructive">{errors.name.message}</p>}
            </div>
            <div className="space-y-1.5">
              <Label htmlFor="fs">Primer apellido</Label>
              <Input id="fs" {...register("first_surname")} aria-invalid={!!errors.first_surname} />
              {errors.first_surname && <p className="text-xs text-destructive">{errors.first_surname.message}</p>}
            </div>
            <div className="space-y-1.5 sm:col-span-2">
              <Label htmlFor="ss">Segundo apellido (opcional)</Label>
              <Input id="ss" {...register("second_surname")} />
            </div>
            <div className="space-y-1.5 sm:col-span-2">
              <Label htmlFor="gmail">Correo</Label>
              <Input id="gmail" type="email" {...register("gmail")} aria-invalid={!!errors.gmail} />
              {errors.gmail && <p className="text-xs text-destructive">{errors.gmail.message}</p>}
            </div>
            <div className="space-y-1.5">
              <Label htmlFor="pw">Contraseña</Label>
              <Input id="pw" type="password" {...register("password")} aria-invalid={!!errors.password} />
              {errors.password && <p className="text-xs text-destructive">{errors.password.message}</p>}
            </div>
            <div className="space-y-1.5">
              <Label htmlFor="pwc">Confirmar</Label>
              <Input id="pwc" type="password" {...register("password_confirmation")} aria-invalid={!!errors.password_confirmation} />
              {errors.password_confirmation && <p className="text-xs text-destructive">{errors.password_confirmation.message}</p>}
            </div>
            <div className="sm:col-span-2">
              <label className="flex items-start gap-2 text-sm">
                <Controller
                  control={control}
                  name="accept_terms"
                  render={({ field }) => (
                    <Checkbox
                      checked={field.value}
                      onCheckedChange={(c) => field.onChange(c === true)}
                    />
                  )}
                />
                <span>Acepto los términos y condiciones.</span>
              </label>
              {errors.accept_terms && <p className="text-xs text-destructive">{errors.accept_terms.message}</p>}
            </div>
            <Button type="submit" className="bg-[#235347] hover:bg-[#1a3f37] sm:col-span-2" disabled={mutation.isPending}>
              {mutation.isPending ? "Creando…" : "Crear cuenta"}
            </Button>
          </form>

          <div className="my-4 flex items-center gap-3 text-xs text-muted-foreground">
            <span className="h-px flex-1 bg-border" /> o <span className="h-px flex-1 bg-border" />
          </div>
          <Button asChild variant="outline" className="w-full">
            <a href={`${API_URL}/auth/google?from=spa`}>Continuar con Google</a>
          </Button>
          <p className="mt-4 text-center text-sm text-muted-foreground">
            ¿Ya tenés cuenta?{" "}
            <Link href="/login" className="font-medium text-[#235347] hover:underline">Ingresá</Link>
          </p>
        </CardContent>
      </Card>
    </div>
  );
}
