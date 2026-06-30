"use client";

import Link from "next/link";
import { usePathname, useRouter } from "next/navigation";
import { useMutation, useQueryClient } from "@tanstack/react-query";
import { LogOut } from "lucide-react";

import { adminLogout } from "@/lib/api/auth";
import { useMe } from "@/lib/auth/use-me";
import { ADMIN_NAV } from "@/lib/admin/nav";
import { Avatar, AvatarFallback } from "@/components/ui/avatar";
import { Button } from "@/components/ui/button";
import {
  Sidebar,
  SidebarContent,
  SidebarFooter,
  SidebarGroup,
  SidebarGroupContent,
  SidebarHeader,
  SidebarMenu,
  SidebarMenuButton,
  SidebarMenuItem,
} from "@/components/ui/sidebar";

function isActive(pathname: string, href: string): boolean {
  if (href === "/admin") return pathname === "/admin";
  return pathname === href || pathname.startsWith(`${href}/`);
}

export function AdminSidebar() {
  const pathname = usePathname();
  const router = useRouter();
  const queryClient = useQueryClient();
  const { data } = useMe();

  const logout = useMutation({
    mutationFn: adminLogout,
    onSuccess: () => {
      queryClient.clear();
      router.replace("/admin/login");
    },
  });

  const initials = `${data?.user.name?.[0] ?? ""}${data?.user.first_surname?.[0] ?? ""}`.toUpperCase();

  return (
    <Sidebar>
      <SidebarHeader className="border-b border-sidebar-border px-4 py-3">
        <Link href="/admin" className="flex items-center gap-2.5">
          {/* eslint-disable-next-line @next/next/no-img-element */}
          <img src="/brand/logo-ciclo-finca-icon-64.webp" alt="" width={36} height={36} className="h-9 w-9 shrink-0 object-contain" />
          <span className="flex flex-col leading-tight">
            <span className="text-[10px] font-semibold uppercase tracking-widest text-sidebar-foreground/60">Admin</span>
            <span className="text-sm font-semibold text-sidebar-foreground">Ciclo Finca 4</span>
          </span>
        </Link>
      </SidebarHeader>

      <SidebarContent>
        <SidebarGroup>
          <SidebarGroupContent>
            <SidebarMenu>
              {ADMIN_NAV.map((item) => (
                <SidebarMenuItem key={item.href}>
                  <SidebarMenuButton asChild isActive={isActive(pathname, item.href)} tooltip={item.title}>
                    <Link href={item.href}>
                      <item.icon />
                      <span>{item.title}</span>
                    </Link>
                  </SidebarMenuButton>
                </SidebarMenuItem>
              ))}
            </SidebarMenu>
          </SidebarGroupContent>
        </SidebarGroup>
      </SidebarContent>

      <SidebarFooter className="border-t border-sidebar-border p-3">
        <div className="flex items-center gap-2">
          <Avatar className="h-8 w-8">
            <AvatarFallback className="bg-sidebar-primary text-xs text-sidebar-primary-foreground">{initials || "A"}</AvatarFallback>
          </Avatar>
          <div className="min-w-0 flex-1">
            <p className="truncate text-sm font-medium text-sidebar-foreground">{data?.user.name ?? "Administrador"}</p>
            <p className="truncate text-xs text-sidebar-foreground/60">{data?.user.gmail ?? ""}</p>
          </div>
          <Button
            variant="ghost"
            size="icon"
            className="h-8 w-8 text-sidebar-foreground/70 hover:bg-sidebar-accent hover:text-sidebar-foreground"
            title="Cerrar sesión"
            disabled={logout.isPending}
            onClick={() => logout.mutate()}
          >
            <LogOut className="h-4 w-4" />
          </Button>
        </div>
      </SidebarFooter>
    </Sidebar>
  );
}
