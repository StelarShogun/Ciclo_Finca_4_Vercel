"use client";

import { useEffect } from "react";
import Link from "next/link";
import { useRouter } from "next/navigation";
import { useMutation, useQueryClient } from "@tanstack/react-query";
import { isAxiosError } from "axios";
import { Heart, LogOut, Receipt, ShoppingCart } from "lucide-react";

import { clientLogout } from "@/lib/api/auth";
import { useMe } from "@/lib/auth/use-me";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Skeleton } from "@/components/ui/skeleton";

const LINKS = [
  { href: "/cart", label: "Mi carrito", icon: ShoppingCart },
  { href: "/favorites", label: "Favoritos", icon: Heart },
  { href: "/invoices", label: "Mis facturas", icon: Receipt },
];

export default function AccountPage() {
  const router = useRouter();
  const queryClient = useQueryClient();
  const { data, isLoading, isError, error } = useMe();

  useEffect(() => {
    const unauth = isError && isAxiosError(error) && error.response?.status === 401;
    if (unauth || (data && data.type !== "client")) {
      router.replace("/login?redirect=/account");
    }
  }, [isError, error, data, router]);

  const logout = useMutation({
    mutationFn: clientLogout,
    onSuccess: () => {
      queryClient.clear();
      router.replace("/catalog");
    },
  });

  if (isLoading) {
    return <div className="mx-auto max-w-3xl px-4 py-16"><Skeleton className="h-48" /></div>;
  }
  if (!data || data.type !== "client") return null;

  return (
    <div className="mx-auto max-w-3xl px-4 py-12">
      <Card>
        <CardHeader>
          <CardTitle>Hola, {data.user.name}</CardTitle>
          <p className="text-sm text-muted-foreground">{data.user.gmail}</p>
        </CardHeader>
        <CardContent className="space-y-4">
          <div className="grid gap-3 sm:grid-cols-3">
            {LINKS.map((l) => (
              <Button key={l.href} asChild variant="outline" className="h-auto justify-start gap-2 py-3">
                <Link href={l.href}>
                  <l.icon className="h-4 w-4" /> {l.label}
                </Link>
              </Button>
            ))}
          </div>
          <Button variant="ghost" className="text-destructive" disabled={logout.isPending} onClick={() => logout.mutate()}>
            <LogOut className="h-4 w-4" /> Cerrar sesión
          </Button>
        </CardContent>
      </Card>
    </div>
  );
}
