"use client";

import { useState } from "react";
import Link from "next/link";
import { useRouter } from "next/navigation";
import { Controller, useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import { z } from "zod";
import { useMutation } from "@tanstack/react-query";
import { isAxiosError } from "axios";
import { toast } from "sonner";

import { clientRegister } from "@/lib/api/auth";
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
  const [showPass, setShowPass] = useState(false);
  const [showPass2, setShowPass2] = useState(false);

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

  const required = <span className="text-destructive">*</span>;

  return (
    <AuthBox className="max-w-[480px]">
      <AuthBackLink href="/login">Volver a iniciar sesión</AuthBackLink>
      <AuthLogo />
      <AuthTitle>Crear Cuenta</AuthTitle>
      <AuthSubtitle>Regístrate para comprar en Ciclo Finca 4</AuthSubtitle>

      <form className="flex w-full flex-col gap-3.5" onSubmit={handleSubmit((v) => mutation.mutate(v))} noValidate>
        <div className="flex flex-col gap-1.5">
          <FieldLabel htmlFor="name" icon="fas fa-user">Nombre {required}</FieldLabel>
          <Input id="name" placeholder="Ej: Juan" {...register("name")} aria-invalid={!!errors.name} />
          <FieldError>{errors.name?.message}</FieldError>
        </div>

        <div className="grid gap-3.5 sm:grid-cols-2">
          <div className="flex flex-col gap-1.5">
            <FieldLabel htmlFor="fs" icon="fas fa-user">Primer apellido {required}</FieldLabel>
            <Input id="fs" placeholder="Ej: Pérez" {...register("first_surname")} aria-invalid={!!errors.first_surname} />
            <FieldError>{errors.first_surname?.message}</FieldError>
          </div>
          <div className="flex flex-col gap-1.5">
            <FieldLabel htmlFor="ss" icon="fas fa-user">Segundo apellido</FieldLabel>
            <Input id="ss" placeholder="Ej: García (opcional)" {...register("second_surname")} />
          </div>
        </div>

        <div className="flex flex-col gap-1.5">
          <FieldLabel htmlFor="gmail" icon="fas fa-envelope">Correo Electrónico {required}</FieldLabel>
          <Input id="gmail" type="email" placeholder="ejemplo@gmail.com" autoComplete="email" {...register("gmail")} aria-invalid={!!errors.gmail} />
          <FieldError>{errors.gmail?.message}</FieldError>
        </div>

        <div className="flex flex-col gap-1.5">
          <FieldLabel htmlFor="pw" icon="fas fa-lock">Contraseña {required}</FieldLabel>
          <div className="relative">
            <Input id="pw" type={showPass ? "text" : "password"} placeholder="Mínimo 8 caracteres" autoComplete="new-password" className="pr-10" {...register("password")} aria-invalid={!!errors.password} />
            <PasswordToggle visible={showPass} onToggle={() => setShowPass((v) => !v)} />
          </div>
          <FieldError>{errors.password?.message}</FieldError>
        </div>

        <div className="flex flex-col gap-1.5">
          <FieldLabel htmlFor="pwc" icon="fas fa-lock">Confirmar contraseña {required}</FieldLabel>
          <div className="relative">
            <Input id="pwc" type={showPass2 ? "text" : "password"} placeholder="Repite la contraseña" autoComplete="new-password" className="pr-10" {...register("password_confirmation")} aria-invalid={!!errors.password_confirmation} />
            <PasswordToggle visible={showPass2} onToggle={() => setShowPass2((v) => !v)} />
          </div>
          <FieldError>{errors.password_confirmation?.message}</FieldError>
        </div>

        <div>
          <label className="flex items-start gap-2 text-sm leading-relaxed">
            <Controller
              control={control}
              name="accept_terms"
              render={({ field }) => (
                <Checkbox className="mt-0.5" checked={field.value} onCheckedChange={(c) => field.onChange(c === true)} />
              )}
            />
            <span>
              Al registrarme acepto los{" "}
              <a href="/legal/terminos" target="_blank" rel="noopener noreferrer" className="font-medium text-[#235347] underline underline-offset-2 dark:text-[#8EB69B]">Términos y condiciones</a>{" "}
              y la{" "}
              <a href="/legal/privacidad" target="_blank" rel="noopener noreferrer" className="font-medium text-[#235347] underline underline-offset-2 dark:text-[#8EB69B]">Política de privacidad</a>.
            </span>
          </label>
          <FieldError>{errors.accept_terms?.message}</FieldError>
        </div>

        <AuthSubmitButton icon="fas fa-user-plus" pending={mutation.isPending} pendingText="Registrando...">
          Crear Cuenta
        </AuthSubmitButton>
      </form>

      <AuthDivider />
      <GoogleButton href={`${API_URL}/auth/google?from=spa`} />

      <p className="mt-5 text-center text-sm text-muted-foreground">
        ¿Ya tienes cuenta?{" "}
        <Link href="/login" className="font-semibold text-[#235347] hover:underline dark:text-[#8EB69B]">Iniciar sesión</Link>
      </p>
    </AuthBox>
  );
}
