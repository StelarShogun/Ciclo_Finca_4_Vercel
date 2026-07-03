"use client";

import { useEffect, useRef, useState } from "react";
import Link from "next/link";
import { useRouter } from "next/navigation";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { isAxiosError } from "axios";
import { toast } from "sonner";

import {
  getProfile,
  updateAvatar,
  updatePassword,
  updateProfile,
  type ClientProfile,
} from "@/lib/api/client/account";
import { useMe } from "@/lib/auth/use-me";
import { useFavoritesDrawer } from "@/components/storefront/favorites-drawer";
import { PasswordToggle } from "@/components/auth/auth-shell";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Skeleton } from "@/components/ui/skeleton";
import { cn } from "@/lib/utils";

function apiError(e: unknown, fallback: string): string {
  if (!isAxiosError(e)) return fallback;
  const d = e.response?.data as { message?: string; errors?: Record<string, string[]> } | undefined;
  if (d?.errors) return Object.values(d.errors)[0]?.[0] ?? fallback;
  return d?.message ?? fallback;
}

function profileInitials(name: string, firstSurname: string): string {
  return `${name.trim().charAt(0)}${firstSurname.trim().charAt(0)}`.toUpperCase() || "?";
}

export default function ProfilePage() {
  const router = useRouter();
  const queryClient = useQueryClient();
  const me = useMe();
  const favorites = useFavoritesDrawer();
  const fileInputRef = useRef<HTMLInputElement>(null);

  useEffect(() => {
    const unauth = me.isError && isAxiosError(me.error) && me.error.response?.status === 401;
    if (unauth || (me.data && me.data.type !== "client")) {
      router.replace("/login?redirect=/profile");
    }
  }, [me.isError, me.error, me.data, router]);

  const { data, isLoading } = useQuery({ queryKey: ["profile"], queryFn: getProfile });

  const [form, setForm] = useState<ClientProfile | null>(null);
  const [isEditing, setIsEditing] = useState(false);
  const [avatarFailed, setAvatarFailed] = useState(false);
  const [pwd, setPwd] = useState({ current_password: "", new_password: "", new_password_confirmation: "" });
  const [showPwd, setShowPwd] = useState({ current: false, next: false, confirm: false });

  const effective = form ?? data ?? null;

  const saveProfile = useMutation({
    mutationFn: () =>
      updateProfile({
        name: effective!.name,
        first_surname: effective!.first_surname,
        second_surname: effective!.second_surname || null,
        gmail: effective!.gmail,
      }),
    onSuccess: () => {
      toast.success("Perfil actualizado");
      setIsEditing(false);
      queryClient.invalidateQueries({ queryKey: ["profile"] });
      queryClient.invalidateQueries({ queryKey: ["me"] });
    },
    onError: (e) => toast.error(apiError(e, "No se pudo actualizar el perfil.")),
  });

  const savePassword = useMutation({
    mutationFn: () =>
      updatePassword({
        ...(data?.isGoogleOnly ? {} : { current_password: pwd.current_password }),
        new_password: pwd.new_password,
        new_password_confirmation: pwd.new_password_confirmation,
      }),
    onSuccess: () => {
      toast.success("Contraseña actualizada");
      setPwd({ current_password: "", new_password: "", new_password_confirmation: "" });
    },
    onError: (e) => toast.error(apiError(e, "No se pudo actualizar la contraseña.")),
  });

  const uploadAvatar = useMutation({
    mutationFn: updateAvatar,
    onSuccess: () => {
      toast.success("Foto de perfil actualizada");
      setAvatarFailed(false);
      queryClient.invalidateQueries({ queryKey: ["profile"] });
      queryClient.invalidateQueries({ queryKey: ["me"] });
    },
    onError: (e) => toast.error(apiError(e, "No se pudo actualizar la foto.")),
    onSettled: () => {
      if (fileInputRef.current) fileInputRef.current.value = "";
    },
  });

  if (me.isLoading || isLoading || !effective) {
    return <div className="mx-auto max-w-4xl px-4 py-12"><Skeleton className="h-96" /></div>;
  }

  const set = (patch: Partial<ClientProfile>) => setForm({ ...effective, ...patch });
  const heroName = `${data?.name ?? ""} ${data?.first_surname ?? ""}`.trim();
  const showAvatar = Boolean(data?.avatar_url) && !avatarFailed;
  const isGoogle = data?.provider === "google";

  const fieldClass = (readOnly: boolean) =>
    cn(!readOnly ? "" : "pointer-events-none bg-muted/60 text-muted-foreground");

  return (
    <div className="mx-auto max-w-4xl px-4 py-10">
      {/* Hero: avatar + nombre + accesos rápidos, fiel al ProfileHero viejo */}
      <div className="mb-6 flex flex-col items-center gap-5 rounded-2xl border bg-card p-6 sm:flex-row sm:items-center">
        <div className="relative">
          <div className="grid h-24 w-24 place-items-center overflow-hidden rounded-full border-2 border-[#235347] bg-accent text-3xl font-bold text-[#235347] dark:text-[#8EB69B]">
            {showAvatar ? (
              // eslint-disable-next-line @next/next/no-img-element
              <img
                src={data!.avatar_url!}
                alt={`Foto de perfil de ${heroName}`}
                className="h-full w-full object-cover"
                referrerPolicy="no-referrer"
                onError={() => setAvatarFailed(true)}
              />
            ) : (
              <span>{profileInitials(data?.name ?? "", data?.first_surname ?? "")}</span>
            )}
          </div>
          <button
            type="button"
            disabled={uploadAvatar.isPending}
            aria-label="Cambiar foto de perfil"
            title="Cambiar foto de perfil"
            onClick={() => fileInputRef.current?.click()}
            className="absolute -bottom-1 -right-1 grid h-9 w-9 place-items-center rounded-full bg-[#235347] text-sm text-white shadow transition hover:bg-[#256428] disabled:opacity-60"
          >
            <i className={uploadAvatar.isPending ? "fas fa-spinner fa-spin" : "fas fa-camera"} aria-hidden />
          </button>
          <input
            ref={fileInputRef}
            type="file"
            accept="image/jpeg,image/png,image/webp"
            className="sr-only"
            aria-hidden
            tabIndex={-1}
            onChange={(e) => {
              const file = e.target.files?.[0];
              if (file) uploadAvatar.mutate(file);
            }}
          />
        </div>

        <div className="flex-1 text-center sm:text-left">
          <h1 className="text-2xl font-bold tracking-tight">{heroName}</h1>
          <p className="text-sm text-muted-foreground">{data?.gmail}</p>
          <div className="mt-3 flex flex-wrap items-center justify-center gap-2 sm:justify-start">
            {isGoogle ? (
              <span className="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-xs font-medium">
                <i className="fab fa-google" aria-hidden /> Cuenta de Google
              </span>
            ) : (
              <span className="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-xs font-medium">
                <i className="fas fa-envelope" aria-hidden /> Cuenta local
              </span>
            )}
            <button
              type="button"
              onClick={favorites.open}
              className="inline-flex items-center gap-1.5 rounded-full bg-accent px-2.5 py-1 text-xs font-semibold text-[#235347] transition hover:bg-[#DAF1DE] dark:text-[#8EB69B]"
            >
              <i className="fas fa-heart" aria-hidden /> Mis favoritos
            </button>
            <Link
              href="/notifications"
              className="inline-flex items-center gap-1.5 rounded-full bg-accent px-2.5 py-1 text-xs font-semibold text-[#235347] transition hover:bg-[#DAF1DE] dark:text-[#8EB69B]"
            >
              <i className="fas fa-bell" aria-hidden /> Notificaciones
            </Link>
          </div>
        </div>
      </div>

      {/* Grid de tarjetas, fiel al profile-grid viejo */}
      <div className="grid gap-6 lg:grid-cols-2">
        {/* Datos personales */}
        <div className="rounded-2xl border bg-card p-5">
          <div className="mb-4 flex items-center justify-between">
            <h2 className="flex items-center gap-2 text-base font-bold">
              <i className="fas fa-user-circle text-[#235347] dark:text-[#8EB69B]" aria-hidden />
              Datos Personales
            </h2>
            {!isEditing && (
              <Button variant="outline" size="sm" onClick={() => setIsEditing(true)}>
                <i className="fas fa-pencil-alt" aria-hidden /> Editar
              </Button>
            )}
          </div>

          <form className="space-y-3.5" onSubmit={(e) => { e.preventDefault(); saveProfile.mutate(); }}>
            <div className="grid gap-3.5 sm:grid-cols-2">
              <div className="space-y-1.5">
                <Label htmlFor="name">Nombre *</Label>
                <Input id="name" value={effective.name} readOnly={!isEditing} className={fieldClass(!isEditing)} maxLength={60} placeholder="Tu nombre" onChange={(e) => set({ name: e.target.value })} />
              </div>
              <div className="space-y-1.5">
                <Label htmlFor="fs">Primer Apellido *</Label>
                <Input id="fs" value={effective.first_surname} readOnly={!isEditing} className={fieldClass(!isEditing)} maxLength={60} placeholder="Tu primer apellido" onChange={(e) => set({ first_surname: e.target.value })} />
              </div>
              <div className="space-y-1.5">
                <Label htmlFor="ss">Segundo Apellido</Label>
                <Input id="ss" value={effective.second_surname} readOnly={!isEditing} className={fieldClass(!isEditing)} maxLength={60} placeholder="Opcional" onChange={(e) => set({ second_surname: e.target.value })} />
              </div>
              <div className="space-y-1.5">
                <Label htmlFor="gmail">Correo Electrónico *</Label>
                <Input id="gmail" type="email" value={effective.gmail} readOnly={!isEditing} className={fieldClass(!isEditing)} placeholder="tu@correo.com" onChange={(e) => set({ gmail: e.target.value })} />
              </div>
            </div>

            {isEditing && (
              <div className="flex gap-2 pt-1">
                <Button type="submit" className="bg-[#235347] hover:bg-[#256428]" disabled={saveProfile.isPending}>
                  <i className="fas fa-save" aria-hidden /> Guardar Cambios
                </Button>
                <Button
                  type="button"
                  variant="secondary"
                  onClick={() => { setForm(null); setIsEditing(false); }}
                >
                  <i className="fas fa-times" aria-hidden /> Cancelar
                </Button>
              </div>
            )}
          </form>
        </div>

        {/* Contraseña */}
        <div className="rounded-2xl border bg-card p-5">
          <h2 className="mb-4 flex items-center gap-2 text-base font-bold">
            <i className="fas fa-lock text-[#235347] dark:text-[#8EB69B]" aria-hidden />
            {data?.isGoogleOnly ? "Definir contraseña" : "Cambiar contraseña"}
          </h2>

          <form className="space-y-3.5" onSubmit={(e) => { e.preventDefault(); savePassword.mutate(); }}>
            {!data?.isGoogleOnly && (
              <div className="space-y-1.5">
                <Label htmlFor="cp">Contraseña actual</Label>
                <div className="relative">
                  <Input id="cp" type={showPwd.current ? "text" : "password"} className="pr-10" autoComplete="current-password" value={pwd.current_password} onChange={(e) => setPwd((s) => ({ ...s, current_password: e.target.value }))} />
                  <PasswordToggle visible={showPwd.current} onToggle={() => setShowPwd((s) => ({ ...s, current: !s.current }))} />
                </div>
              </div>
            )}
            <div className="space-y-1.5">
              <Label htmlFor="np">Nueva contraseña</Label>
              <div className="relative">
                <Input id="np" type={showPwd.next ? "text" : "password"} className="pr-10" autoComplete="new-password" placeholder="Mínimo 8 caracteres" value={pwd.new_password} onChange={(e) => setPwd((s) => ({ ...s, new_password: e.target.value }))} />
                <PasswordToggle visible={showPwd.next} onToggle={() => setShowPwd((s) => ({ ...s, next: !s.next }))} />
              </div>
            </div>
            <div className="space-y-1.5">
              <Label htmlFor="npc">Confirmar nueva contraseña</Label>
              <div className="relative">
                <Input id="npc" type={showPwd.confirm ? "text" : "password"} className="pr-10" autoComplete="new-password" placeholder="Repite la contraseña" value={pwd.new_password_confirmation} onChange={(e) => setPwd((s) => ({ ...s, new_password_confirmation: e.target.value }))} />
                <PasswordToggle visible={showPwd.confirm} onToggle={() => setShowPwd((s) => ({ ...s, confirm: !s.confirm }))} />
              </div>
            </div>
            <Button
              type="submit"
              className="bg-[#235347] hover:bg-[#256428]"
              disabled={savePassword.isPending || pwd.new_password.length < 8 || pwd.new_password !== pwd.new_password_confirmation}
            >
              <i className="fas fa-key" aria-hidden /> Actualizar contraseña
            </Button>
          </form>
        </div>
      </div>
    </div>
  );
}
