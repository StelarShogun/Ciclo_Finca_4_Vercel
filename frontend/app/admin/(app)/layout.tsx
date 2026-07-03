"use client";

import { useEffect } from "react";
import { useRouter } from "next/navigation";
import { isAxiosError } from "axios";

import { useMe } from "@/lib/auth/use-me";
import { AdminSidebar } from "@/components/admin/admin-sidebar";
import { AdminHeader } from "@/components/admin/admin-header";
import { SidebarInset, SidebarProvider } from "@/components/ui/sidebar";
import { Skeleton } from "@/components/ui/skeleton";

export default function AdminAppLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  const router = useRouter();
  const { data, isLoading, isError, error } = useMe();

  // Guard: sin sesión admin -> login. Un cliente logueado tampoco entra.
  useEffect(() => {
    const unauthenticated =
      isError && isAxiosError(error) && error.response?.status === 401;
    const notAdmin = data && data.type !== "admin";
    if (unauthenticated || notAdmin) {
      router.replace("/admin/login");
    }
  }, [isError, error, data, router]);

  if (isLoading) {
    return (
      <div className="flex min-h-svh items-center justify-center">
        <Skeleton className="h-8 w-40" />
      </div>
    );
  }

  if (!data || data.type !== "admin") {
    return null; // redirigiendo
  }

  return (
    <SidebarProvider>
      <AdminSidebar />
      <SidebarInset className="bg-[oklch(0.985_0.01_170)] dark:bg-background">
        <AdminHeader />
        <div className="flex-1 p-6">{children}</div>
      </SidebarInset>
    </SidebarProvider>
  );
}
