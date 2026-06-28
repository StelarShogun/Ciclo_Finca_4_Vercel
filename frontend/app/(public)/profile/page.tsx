"use client";

import { useEffect, useState } from "react";
import { useRouter } from "next/navigation";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { isAxiosError } from "axios";
import { toast } from "sonner";

import {
  getProfile,
  updatePassword,
  updateProfile,
  type ClientProfile,
} from "@/lib/api/client/account";
import { useMe } from "@/lib/auth/use-me";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Skeleton } from "@/components/ui/skeleton";

function apiError(e: unknown, fallback: string): string {
  if (!isAxiosError(e)) return fallback;
  const d = e.response?.data as { message?: string; errors?: Record<string, string[]> } | undefined;
  if (d?.errors) return Object.values(d.errors)[0]?.[0] ?? fallback;
  return d?.message ?? fallback;
}

export default function ProfilePage() {
  const router = useRouter();
  const queryClient = useQueryClient();
  const me = useMe();

  useEffect(() => {
    const unauth = me.isError && isAxiosError(me.error) && me.error.response?.status === 401;
    if (unauth || (me.data && me.data.type !== "client")) {
      router.replace("/login?redirect=/profile");
    }
  }, [me.isError, me.error, me.data, router]);

  const { data, isLoading } = useQuery({ queryKey: ["profile"], queryFn: getProfile });

  const [form, setForm] = useState<ClientProfile | null>(null);
  const [pwd, setPwd] = useState({ current_password: "", new_password: "", new_password_confirmation: "" });

  // Sembrar el formulario una vez que carga el perfil (sin effect: clave por gmail).
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

  if (me.isLoading || isLoading || !effective) {
    return <div className="mx-auto max-w-2xl px-4 py-12"><Skeleton className="h-96" /></div>;
  }

  const set = (patch: Partial<ClientProfile>) => setForm({ ...effective, ...patch });

  return (
    <div className="mx-auto max-w-2xl space-y-6 px-4 py-12">
      <h1 className="text-2xl font-semibold tracking-tight">Mi perfil</h1>

      <Card>
        <CardHeader><CardTitle>Datos personales</CardTitle></CardHeader>
        <CardContent>
          <form className="grid gap-4 sm:grid-cols-2" onSubmit={(e) => { e.preventDefault(); saveProfile.mutate(); }}>
            <div className="space-y-1.5">
              <Label htmlFor="name">Nombre</Label>
              <Input id="name" value={effective.name} onChange={(e) => set({ name: e.target.value })} />
            </div>
            <div className="space-y-1.5">
              <Label htmlFor="fs">Primer apellido</Label>
              <Input id="fs" value={effective.first_surname} onChange={(e) => set({ first_surname: e.target.value })} />
            </div>
            <div className="space-y-1.5">
              <Label htmlFor="ss">Segundo apellido</Label>
              <Input id="ss" value={effective.second_surname} onChange={(e) => set({ second_surname: e.target.value })} />
            </div>
            <div className="space-y-1.5">
              <Label htmlFor="gmail">Correo</Label>
              <Input id="gmail" type="email" value={effective.gmail} onChange={(e) => set({ gmail: e.target.value })} />
            </div>
            <div className="sm:col-span-2">
              <Button type="submit" className="bg-[#235347] hover:bg-[#1a3f37]" disabled={saveProfile.isPending}>
                Guardar cambios
              </Button>
            </div>
          </form>
        </CardContent>
      </Card>

      <Card>
        <CardHeader><CardTitle>{data?.isGoogleOnly ? "Definir contraseña" : "Cambiar contraseña"}</CardTitle></CardHeader>
        <CardContent>
          <form className="space-y-4" onSubmit={(e) => { e.preventDefault(); savePassword.mutate(); }}>
            {!data?.isGoogleOnly && (
              <div className="space-y-1.5">
                <Label htmlFor="cp">Contraseña actual</Label>
                <Input id="cp" type="password" value={pwd.current_password} onChange={(e) => setPwd((s) => ({ ...s, current_password: e.target.value }))} />
              </div>
            )}
            <div className="space-y-1.5">
              <Label htmlFor="np">Nueva contraseña</Label>
              <Input id="np" type="password" value={pwd.new_password} onChange={(e) => setPwd((s) => ({ ...s, new_password: e.target.value }))} />
            </div>
            <div className="space-y-1.5">
              <Label htmlFor="npc">Confirmar nueva contraseña</Label>
              <Input id="npc" type="password" value={pwd.new_password_confirmation} onChange={(e) => setPwd((s) => ({ ...s, new_password_confirmation: e.target.value }))} />
            </div>
            <Button type="submit" variant="outline" disabled={savePassword.isPending || pwd.new_password.length < 8}>
              Actualizar contraseña
            </Button>
          </form>
        </CardContent>
      </Card>
    </div>
  );
}
