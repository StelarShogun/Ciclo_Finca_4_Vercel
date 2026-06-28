"use client";

import { useState } from "react";
import { useRouter } from "next/navigation";
import { useMutation, useQueryClient } from "@tanstack/react-query";
import { isAxiosError } from "axios";
import { toast } from "sonner";

import { resendCode, verifyCode } from "@/lib/api/auth";
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
    <div className="mx-auto flex max-w-md items-center justify-center px-4 py-16">
      <Card className="w-full border-t-4 border-t-[#235347]">
        <CardHeader>
          <CardTitle className="text-xl">Verificá tu correo</CardTitle>
          <CardDescription>Ingresá el código de 6 dígitos que te enviamos.</CardDescription>
        </CardHeader>
        <CardContent>
          <form
            className="flex flex-col gap-4"
            onSubmit={(e) => {
              e.preventDefault();
              if (code.length === 6) verify.mutate();
            }}
          >
            <div className="space-y-1.5">
              <Label htmlFor="code">Código</Label>
              <Input
                id="code"
                inputMode="numeric"
                maxLength={6}
                autoFocus
                value={code}
                onChange={(e) => setCode(e.target.value.replace(/\D/g, "").slice(0, 6))}
                className="text-center text-lg tracking-[0.4em]"
                placeholder="••••••"
              />
            </div>
            <Button type="submit" className="bg-[#235347] hover:bg-[#1a3f37]" disabled={code.length !== 6 || verify.isPending}>
              {verify.isPending ? "Verificando…" : "Verificar"}
            </Button>
          </form>
          <Button variant="ghost" className="mt-2 w-full text-sm text-muted-foreground" disabled={resend.isPending} onClick={() => resend.mutate()}>
            Reenviar código
          </Button>
        </CardContent>
      </Card>
    </div>
  );
}
