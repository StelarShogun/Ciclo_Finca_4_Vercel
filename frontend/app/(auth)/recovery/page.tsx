"use client";

import { useState } from "react";
import Link from "next/link";
import { useRouter } from "next/navigation";
import { useMutation } from "@tanstack/react-query";
import { isAxiosError } from "axios";
import { toast } from "sonner";

import { recoveryReset, recoverySend, recoveryVerify } from "@/lib/api/auth";
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

  return (
    <Card className="w-full max-w-sm border-t-4 border-t-[#235347]">
      <CardHeader>
        <CardTitle className="text-xl">Recuperar contraseña</CardTitle>
        <CardDescription>
          {step === 1 && "Ingresá tu correo y te enviaremos un código."}
          {step === 2 && "Ingresá el código de 6 dígitos que te enviamos."}
          {step === 3 && "Definí tu nueva contraseña."}
        </CardDescription>
      </CardHeader>
      <CardContent>
        {step === 1 && (
          <form className="flex flex-col gap-4" onSubmit={(e) => { e.preventDefault(); if (gmail.trim()) send.mutate(); }}>
            <div className="space-y-1.5">
              <Label htmlFor="gmail">Correo</Label>
              <Input id="gmail" type="email" value={gmail} onChange={(e) => setGmail(e.target.value)} />
            </div>
            <Button type="submit" className="bg-[#235347] hover:bg-[#1a3f37]" disabled={send.isPending}>Enviar código</Button>
          </form>
        )}
        {step === 2 && (
          <form className="flex flex-col gap-4" onSubmit={(e) => { e.preventDefault(); if (code.length === 6) verify.mutate(); }}>
            <div className="space-y-1.5">
              <Label htmlFor="code">Código</Label>
              <Input id="code" inputMode="numeric" maxLength={6} value={code} onChange={(e) => setCode(e.target.value.replace(/\D/g, "").slice(0, 6))} className="text-center text-lg tracking-[0.4em]" placeholder="••••••" />
            </div>
            <Button type="submit" className="bg-[#235347] hover:bg-[#1a3f37]" disabled={code.length !== 6 || verify.isPending}>Verificar</Button>
          </form>
        )}
        {step === 3 && (
          <form className="flex flex-col gap-4" onSubmit={(e) => { e.preventDefault(); if (pwd.length >= 8 && pwd === pwd2) reset.mutate(); }}>
            <div className="space-y-1.5">
              <Label htmlFor="np">Nueva contraseña</Label>
              <Input id="np" type="password" value={pwd} onChange={(e) => setPwd(e.target.value)} />
            </div>
            <div className="space-y-1.5">
              <Label htmlFor="np2">Confirmar contraseña</Label>
              <Input id="np2" type="password" value={pwd2} onChange={(e) => setPwd2(e.target.value)} />
            </div>
            <Button type="submit" className="bg-[#235347] hover:bg-[#1a3f37]" disabled={pwd.length < 8 || pwd !== pwd2 || reset.isPending}>Actualizar contraseña</Button>
          </form>
        )}
        <p className="mt-4 text-center text-sm text-muted-foreground">
          <Link href="/login" className="font-medium text-[#235347] hover:underline">Volver a iniciar sesión</Link>
        </p>
      </CardContent>
    </Card>
  );
}
