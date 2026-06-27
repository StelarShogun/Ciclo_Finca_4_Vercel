"use client";

import { useEffect } from "react";
import { useRouter } from "next/navigation";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { isAxiosError } from "axios";

import { me, adminLogout } from "@/lib/api/auth";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Skeleton } from "@/components/ui/skeleton";

export default function AdminHomePage() {
  const router = useRouter();
  const queryClient = useQueryClient();

  const { data, isLoading, isError, error } = useQuery({
    queryKey: ["me"],
    queryFn: me,
  });

  // 401 → no hay sesión admin, al login.
  useEffect(() => {
    if (isError && isAxiosError(error) && error.response?.status === 401) {
      router.replace("/admin/login");
    }
  }, [isError, error, router]);

  const logout = useMutation({
    mutationFn: adminLogout,
    onSuccess: () => {
      queryClient.clear();
      router.replace("/admin/login");
    },
  });

  return (
    <main className="mx-auto flex min-h-svh max-w-3xl flex-col gap-6 p-8">
      <Card>
        <CardHeader>
          <CardTitle>Dashboard (placeholder)</CardTitle>
        </CardHeader>
        <CardContent className="flex flex-col gap-4">
          {isLoading ? (
            <Skeleton className="h-6 w-48" />
          ) : data ? (
            <div className="flex flex-col gap-1">
              <p className="text-sm text-muted-foreground">Sesión activa ({data.type})</p>
              <p className="text-lg font-medium">{data.user.name} — {data.user.gmail}</p>
            </div>
          ) : (
            <p className="text-sm text-muted-foreground">Sin sesión.</p>
          )}

          <div>
            <Button
              variant="outline"
              onClick={() => logout.mutate()}
              disabled={logout.isPending}
            >
              Cerrar sesión
            </Button>
          </div>
        </CardContent>
      </Card>
    </main>
  );
}
