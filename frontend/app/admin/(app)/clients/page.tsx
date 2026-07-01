"use client";

import { useEffect, useState } from "react";
import { keepPreviousData, useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { isAxiosError } from "axios";
import { toast } from "sonner";
import { Search } from "lucide-react";

import {
  banClient,
  getClientHistory,
  getClients,
  unbanClient,
  type AdminClient,
} from "@/lib/api/admin/clients";
import { PageHeader } from "@/components/admin/page-header";
import { AdminCard } from "@/components/admin/admin-card";
import { ActionBar, ActionBtn } from "@/components/admin/action-btn";
import { useViewMode, ViewToggle } from "@/components/admin/view-toggle";
import { PaginationControls } from "@/components/admin/pagination-controls";
import { StatusBadge } from "@/components/admin/status-badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Skeleton } from "@/components/ui/skeleton";
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";

const ALL = "all";
const crc = new Intl.NumberFormat("es-CR", {
  style: "currency",
  currency: "CRC",
  maximumFractionDigits: 0,
});

function errMsg(e: unknown, fallback: string) {
  return (isAxiosError(e) && (e.response?.data?.message as string)) || fallback;
}

export default function ClientsPage() {
  const queryClient = useQueryClient();
  const [page, setPage] = useState(1);
  const [search, setSearch] = useState("");
  const [debounced, setDebounced] = useState("");
  const [status, setStatus] = useState(ALL);
  const [historyFor, setHistoryFor] = useState<AdminClient | null>(null);
  const [view, setView] = useViewMode("clients");

  const clientName = (c: AdminClient) =>
    [c.name, c.first_surname, c.second_surname].filter(Boolean).join(" ");

  useEffect(() => {
    const t = setTimeout(() => setDebounced(search), 350);
    return () => clearTimeout(t);
  }, [search]);

  const filters = { search: debounced, status: status === ALL ? "" : status };

  const { data, isLoading, isError } = useQuery({
    queryKey: ["admin-clients", page, filters],
    queryFn: () => getClients({ page, ...filters }),
    placeholderData: keepPreviousData,
  });

  const history = useQuery({
    queryKey: ["admin-client-history", historyFor?.user_id],
    queryFn: () => getClientHistory(historyFor!.user_id),
    enabled: !!historyFor,
  });

  const toggle = useMutation({
    mutationFn: (c: AdminClient) => (c.active ? banClient(c.user_id) : unbanClient(c.user_id)),
    onSuccess: (_d, c) => {
      toast.success(c.active ? "Cliente bloqueado" : "Cliente desbloqueado");
      queryClient.invalidateQueries({ queryKey: ["admin-clients"] });
    },
    onError: (e) => toast.error(errMsg(e, "No fue posible actualizar el cliente.")),
  });

  return (
    <>
      <PageHeader title="Clientes" description="Clientes registrados en la tienda." />

      <div className="mb-4 flex flex-wrap items-center gap-3">
        <div className="relative w-full max-w-xs">
          <Search className="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
          <Input
            placeholder="Buscar por nombre o correo…"
            className="pl-8"
            value={search}
            onChange={(e) => {
              setSearch(e.target.value);
              setPage(1);
            }}
          />
        </div>
        <Select value={status} onValueChange={(v) => { setStatus(v); setPage(1); }}>
          <SelectTrigger className="w-44" size="sm"><SelectValue placeholder="Estado" /></SelectTrigger>
          <SelectContent>
            <SelectItem value={ALL}>Todos</SelectItem>
            <SelectItem value="active">Activos</SelectItem>
            <SelectItem value="banned">Bloqueados</SelectItem>
          </SelectContent>
        </Select>
        <div className="ml-auto">
          <ViewToggle view={view} onChange={setView} />
        </div>
      </div>

      {isLoading ? (
        <Skeleton className="h-96" />
      ) : isError || !data ? (
        <Card>
          <CardContent className="py-8 text-center text-sm text-muted-foreground">
            No fue posible cargar los clientes.
          </CardContent>
        </Card>
      ) : data.clients.length === 0 ? (
        <Card>
          <CardContent className="py-8 text-center text-sm text-muted-foreground">Sin clientes.</CardContent>
        </Card>
      ) : (
        <>
          {view === "grid" ? (
            <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
              {data.clients.map((c) => (
                <AdminCard
                  key={c.user_id}
                  media={
                    <div className="flex h-12 w-12 shrink-0 items-center justify-center rounded-lg border bg-muted text-[#235347] dark:text-[#8EB69B]">
                      <i className="fas fa-user" aria-hidden />
                    </div>
                  }
                  title={clientName(c)}
                  subtitle={c.gmail}
                  badge={
                    <StatusBadge tone={c.active ? "success" : "danger"}>
                      {c.active ? "Activo" : "Bloqueado"}
                    </StatusBadge>
                  }
                  meta={
                    <div className="flex justify-between">
                      <span className="text-muted-foreground">Registro</span>
                      <span>{c.created_at ?? "—"}</span>
                    </div>
                  }
                  actions={
                    <ActionBar>
                      <ActionBtn icon="fa-clock-rotate-left" label="Historial" tone="view" onClick={() => setHistoryFor(c)} />
                      {c.active ? (
                        <ActionBtn icon="fa-ban" label="Bloquear" tone="delete" disabled={toggle.isPending} onClick={() => toggle.mutate(c)} />
                      ) : (
                        <ActionBtn icon="fa-shield-halved" label="Desbloquear" tone="activate" disabled={toggle.isPending} onClick={() => toggle.mutate(c)} />
                      )}
                    </ActionBar>
                  }
                />
              ))}
            </div>
          ) : (
            <Card>
              <CardContent className="p-0">
                <Table>
                  <TableHeader>
                    <TableRow>
                      <TableHead>Nombre</TableHead>
                      <TableHead>Correo</TableHead>
                      <TableHead>Registro</TableHead>
                      <TableHead>Estado</TableHead>
                      <TableHead className="w-32 text-right">Acciones</TableHead>
                    </TableRow>
                  </TableHeader>
                  <TableBody>
                    {data.clients.map((c) => (
                      <TableRow key={c.user_id}>
                        <TableCell className="font-medium">{clientName(c)}</TableCell>
                        <TableCell className="text-muted-foreground">{c.gmail}</TableCell>
                        <TableCell>{c.created_at ?? "—"}</TableCell>
                        <TableCell>
                          <StatusBadge tone={c.active ? "success" : "danger"}>
                            {c.active ? "Activo" : "Bloqueado"}
                          </StatusBadge>
                        </TableCell>
                        <TableCell>
                          <div className="flex justify-end">
                            <ActionBar>
                              <ActionBtn icon="fa-clock-rotate-left" label="Historial" tone="view" onClick={() => setHistoryFor(c)} />
                              {c.active ? (
                                <ActionBtn icon="fa-ban" label="Bloquear" tone="delete" disabled={toggle.isPending} onClick={() => toggle.mutate(c)} />
                              ) : (
                                <ActionBtn icon="fa-shield-halved" label="Desbloquear" tone="activate" disabled={toggle.isPending} onClick={() => toggle.mutate(c)} />
                              )}
                            </ActionBar>
                          </div>
                        </TableCell>
                      </TableRow>
                    ))}
                  </TableBody>
                </Table>
              </CardContent>
            </Card>
          )}
          <PaginationControls
            currentPage={data.pagination.currentPage}
            lastPage={data.pagination.lastPage}
            total={data.pagination.total}
            onPageChange={setPage}
          />
        </>
      )}

      {/* Historial de compras */}
      <Dialog open={!!historyFor} onOpenChange={(o) => !o && setHistoryFor(null)}>
        <DialogContent className="sm:max-w-[38rem]">
          <DialogHeader>
            <DialogTitle>{history.data?.displayName ?? "Historial de compras"}</DialogTitle>
            <DialogDescription>{history.data?.gmail ?? historyFor?.gmail}</DialogDescription>
          </DialogHeader>
          {history.isLoading ? (
            <Skeleton className="h-48" />
          ) : !history.data ? (
            <p className="py-6 text-center text-sm text-muted-foreground">Sin datos.</p>
          ) : history.data.orders.length === 0 ? (
            <p className="py-6 text-center text-sm text-muted-foreground">Sin compras registradas.</p>
          ) : (
            <div className="max-h-80 overflow-y-auto">
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>Factura</TableHead>
                    <TableHead>Fecha</TableHead>
                    <TableHead className="text-right">Total</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {history.data.orders.map((o) => (
                    <TableRow key={o.sale_id}>
                      <TableCell>{o.invoice_number}</TableCell>
                      <TableCell>{o.sale_date}</TableCell>
                      <TableCell className="text-right">{crc.format(Number(o.total))}</TableCell>
                    </TableRow>
                  ))}
                </TableBody>
              </Table>
            </div>
          )}
        </DialogContent>
      </Dialog>
    </>
  );
}
