"use client";

import { useEffect } from "react";
import Link from "next/link";
import { useRouter } from "next/navigation";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { isAxiosError } from "axios";
import { toast } from "sonner";
import { Bell, CheckCheck } from "lucide-react";

import { getNotifications, markAllNotificationsRead, markNotificationRead } from "@/lib/api/client/account";
import { useMe } from "@/lib/auth/use-me";
import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";
import { Skeleton } from "@/components/ui/skeleton";

export default function NotificationsPage() {
  const router = useRouter();
  const queryClient = useQueryClient();
  const me = useMe();

  useEffect(() => {
    const unauth = me.isError && isAxiosError(me.error) && me.error.response?.status === 401;
    if (unauth || (me.data && me.data.type !== "client")) {
      router.replace("/login?redirect=/notifications");
    }
  }, [me.isError, me.error, me.data, router]);

  const { data, isLoading } = useQuery({ queryKey: ["notifications"], queryFn: () => getNotifications() });
  const invalidate = () => queryClient.invalidateQueries({ queryKey: ["notifications"] });

  const markAll = useMutation({
    mutationFn: markAllNotificationsRead,
    onSuccess: () => { toast.success("Marcadas como leídas"); invalidate(); },
  });
  const markOne = useMutation({ mutationFn: (id: string) => markNotificationRead(id), onSuccess: invalidate });

  if (me.isLoading || isLoading) {
    return <div className="mx-auto max-w-2xl px-4 py-12"><Skeleton className="h-64" /></div>;
  }

  const items = data?.notifications ?? [];

  return (
    <div className="mx-auto max-w-2xl px-4 py-12">
      <div className="mb-6 flex items-center justify-between">
        <h1 className="flex items-center gap-2 text-2xl font-semibold tracking-tight">
          <Bell className="h-6 w-6 text-brand-medium" /> Notificaciones
        </h1>
        {items.length > 0 && (
          <Button variant="outline" size="sm" onClick={() => markAll.mutate()} disabled={markAll.isPending}>
            <CheckCheck className="h-4 w-4" /> Marcar todas
          </Button>
        )}
      </div>

      {items.length === 0 ? (
        <Card><CardContent className="py-12 text-center text-sm text-muted-foreground">No tenés notificaciones.</CardContent></Card>
      ) : (
        <div className="space-y-2">
          {items.map((n) => (
            <Card key={n.id}>
              <CardContent className="flex items-start justify-between gap-3 py-3">
                <div className="min-w-0">
                  <p className="text-sm">{n.message}</p>
                  <p className="text-xs text-muted-foreground">{n.createdAtLabel}</p>
                  {n.actionUrl && (
                    <Link href={n.actionUrl} className="text-xs font-medium text-brand-medium hover:underline">
                      {n.actionLabel}
                    </Link>
                  )}
                </div>
                <Button variant="ghost" size="sm" className="shrink-0 text-muted-foreground"
                  disabled={markOne.isPending} onClick={() => markOne.mutate(n.id)}>
                  Leída
                </Button>
              </CardContent>
            </Card>
          ))}
        </div>
      )}
    </div>
  );
}
