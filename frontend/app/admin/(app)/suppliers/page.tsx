"use client";

import { useEffect, useState } from "react";
import { keepPreviousData, useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { isAxiosError } from "axios";
import { toast } from "sonner";
import { Plus, Search, Star } from "lucide-react";

import {
  createSupplier,
  deleteSupplier,
  getSuppliers,
  updateSupplier,
  type Supplier,
  type SupplierFormValues,
} from "@/lib/api/admin/suppliers";
import { PageHeader } from "@/components/admin/page-header";
import { AdminCard } from "@/components/admin/admin-card";
import { ActionBar, ActionBtn } from "@/components/admin/action-btn";
import { useViewMode, ViewToggle } from "@/components/admin/view-toggle";
import { PaginationControls } from "@/components/admin/pagination-controls";
import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
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
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
} from "@/components/ui/alert-dialog";

const EMPTY: SupplierFormValues = {
  name: "",
  primary_contact: "",
  phone: "",
  email: "",
  address: "",
  delivery_time: 1,
  rating: null,
};

function apiError(e: unknown, fallback: string): string {
  if (!isAxiosError(e)) return fallback;
  const d = e.response?.data as { message?: string; errors?: Record<string, string[]> } | undefined;
  if (d?.errors) return Object.values(d.errors)[0]?.[0] ?? fallback;
  return d?.message ?? fallback;
}

export default function SuppliersPage() {
  const queryClient = useQueryClient();
  const [page, setPage] = useState(1);
  const [search, setSearch] = useState("");
  const [debounced, setDebounced] = useState("");

  const [dialogOpen, setDialogOpen] = useState(false);
  const [editing, setEditing] = useState<Supplier | null>(null);
  const [form, setForm] = useState<SupplierFormValues>(EMPTY);
  const [deleting, setDeleting] = useState<Supplier | null>(null);
  const [view, setView] = useViewMode("suppliers");

  useEffect(() => {
    const t = setTimeout(() => setDebounced(search), 350);
    return () => clearTimeout(t);
  }, [search]);

  const { data, isLoading, isError } = useQuery({
    queryKey: ["admin-suppliers", page, debounced],
    queryFn: () => getSuppliers({ page, name: debounced }),
    placeholderData: keepPreviousData,
  });

  const invalidate = () => queryClient.invalidateQueries({ queryKey: ["admin-suppliers"] });

  const save = useMutation({
    mutationFn: () =>
      editing ? updateSupplier(editing.supplier_id, form) : createSupplier(form),
    onSuccess: () => {
      toast.success(editing ? "Proveedor actualizado" : "Proveedor creado");
      setDialogOpen(false);
      invalidate();
    },
    onError: (e) => toast.error(apiError(e, "No se pudo guardar el proveedor.")),
  });

  const remove = useMutation({
    mutationFn: (s: Supplier) => deleteSupplier(s.supplier_id),
    onSuccess: () => {
      toast.success("Proveedor eliminado");
      setDeleting(null);
      invalidate();
    },
    onError: (e) => toast.error(apiError(e, "No se pudo eliminar el proveedor.")),
  });

  function openCreate() {
    setEditing(null);
    setForm(EMPTY);
    setDialogOpen(true);
  }

  function openEdit(s: Supplier) {
    setEditing(s);
    setForm({
      name: s.name,
      primary_contact: s.primary_contact,
      phone: s.phone,
      email: s.email,
      address: s.address,
      delivery_time: s.delivery_time,
      rating: s.rating,
    });
    setDialogOpen(true);
  }

  const set = (patch: Partial<SupplierFormValues>) => setForm((f) => ({ ...f, ...patch }));
  const valid = form.name.trim() && form.primary_contact.trim() && form.phone.trim() && form.email.trim() && form.address.trim();

  return (
    <>
      <PageHeader
        title="Proveedores"
        description={data ? `Calificación promedio: ${data.averageRating.toFixed(1)} / 5` : "Proveedores del catálogo."}
        actions={
          <Button onClick={openCreate}>
            <Plus className="h-4 w-4" />
            Nuevo proveedor
          </Button>
        }
      />

      <div className="mb-4 flex flex-wrap items-center gap-3">
        <div className="relative w-full max-w-xs">
          <Search className="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
          <Input
            placeholder="Buscar por nombre…"
            className="pl-8"
            value={search}
            onChange={(e) => {
              setSearch(e.target.value);
              setPage(1);
            }}
          />
        </div>
        <div className="ml-auto">
          <ViewToggle view={view} onChange={setView} />
        </div>
      </div>

      {isLoading ? (
        <Skeleton className="h-96" />
      ) : isError || !data ? (
        <Card>
          <CardContent className="py-8 text-center text-sm text-muted-foreground">
            No fue posible cargar los proveedores.
          </CardContent>
        </Card>
      ) : data.suppliers.length === 0 ? (
        <Card>
          <CardContent className="py-8 text-center text-sm text-muted-foreground">Sin proveedores.</CardContent>
        </Card>
      ) : (
        <>
          {view === "grid" ? (
            <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
              {data.suppliers.map((s) => (
                <AdminCard
                  key={s.supplier_id}
                  media={
                    <div className="flex h-12 w-12 shrink-0 items-center justify-center rounded-lg border bg-muted text-[#235347] dark:text-[#8EB69B]">
                      <i className="fas fa-truck-field" aria-hidden />
                    </div>
                  }
                  title={s.name}
                  subtitle={s.primary_contact}
                  badge={
                    s.rating !== null ? (
                      <span className="inline-flex items-center gap-1 text-sm">
                        <Star className="h-3 w-3 fill-amber-400 text-amber-400" />
                        {s.rating.toFixed(1)}
                      </span>
                    ) : undefined
                  }
                  meta={
                    <>
                      <div className="flex justify-between">
                        <span className="text-muted-foreground">Teléfono</span>
                        <span>{s.phone}</span>
                      </div>
                      <div className="flex justify-between">
                        <span className="text-muted-foreground">Email</span>
                        <span className="truncate">{s.email}</span>
                      </div>
                      <div className="flex justify-between">
                        <span className="text-muted-foreground">Entrega</span>
                        <span>{s.delivery_time} d</span>
                      </div>
                    </>
                  }
                  actions={
                    <ActionBar>
                      <ActionBtn icon="fa-pen-to-square" label="Editar" tone="edit" onClick={() => openEdit(s)} />
                      <ActionBtn icon="fa-trash" label="Eliminar" tone="delete" onClick={() => setDeleting(s)} />
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
                      <TableHead>Contacto</TableHead>
                      <TableHead>Teléfono</TableHead>
                      <TableHead>Email</TableHead>
                      <TableHead className="text-center">Entrega</TableHead>
                      <TableHead className="text-center">Rating</TableHead>
                      <TableHead className="w-24 text-right">Acciones</TableHead>
                    </TableRow>
                  </TableHeader>
                  <TableBody>
                    {data.suppliers.map((s) => (
                      <TableRow key={s.supplier_id}>
                        <TableCell className="font-medium">{s.name}</TableCell>
                        <TableCell>{s.primary_contact}</TableCell>
                        <TableCell>{s.phone}</TableCell>
                        <TableCell className="text-muted-foreground">{s.email}</TableCell>
                        <TableCell className="text-center">{s.delivery_time} d</TableCell>
                        <TableCell className="text-center">
                          {s.rating !== null ? (
                            <span className="inline-flex items-center gap-1">
                              <Star className="h-3 w-3 fill-amber-400 text-amber-400" />
                              {s.rating.toFixed(1)}
                            </span>
                          ) : "—"}
                        </TableCell>
                        <TableCell>
                          <div className="flex justify-end">
                            <ActionBar>
                              <ActionBtn icon="fa-pen-to-square" label="Editar" tone="edit" onClick={() => openEdit(s)} />
                              <ActionBtn icon="fa-trash" label="Eliminar" tone="delete" onClick={() => setDeleting(s)} />
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

      {/* Crear / editar */}
      <Dialog open={dialogOpen} onOpenChange={setDialogOpen}>
        <DialogContent className="max-h-[90vh] sm:max-w-[56rem] overflow-y-auto">
          <form
            onSubmit={(e) => {
              e.preventDefault();
              if (valid) save.mutate();
            }}
          >
            <DialogHeader>
              <DialogTitle className="flex items-center gap-2">
                <i className={`fas ${editing ? "fa-pen-to-square" : "fa-truck-field"} text-[#235347] dark:text-[#8EB69B]`} aria-hidden />
                {editing ? "Editar proveedor" : "Nuevo proveedor"}
              </DialogTitle>
              <DialogDescription>Datos de contacto y entrega.</DialogDescription>
            </DialogHeader>
            <div className="grid gap-4 py-4 sm:grid-cols-2">
              <div className="space-y-1.5 sm:col-span-2">
                <Label htmlFor="s-name">Nombre</Label>
                <Input id="s-name" value={form.name} onChange={(e) => set({ name: e.target.value })} maxLength={100} />
              </div>
              <div className="space-y-1.5">
                <Label htmlFor="s-contact">Contacto</Label>
                <Input id="s-contact" value={form.primary_contact} onChange={(e) => set({ primary_contact: e.target.value })} maxLength={100} />
              </div>
              <div className="space-y-1.5">
                <Label htmlFor="s-phone">Teléfono</Label>
                <Input id="s-phone" value={form.phone} onChange={(e) => set({ phone: e.target.value })} maxLength={20} />
              </div>
              <div className="space-y-1.5 sm:col-span-2">
                <Label htmlFor="s-email">Email</Label>
                <Input id="s-email" type="email" value={form.email} onChange={(e) => set({ email: e.target.value })} maxLength={100} />
              </div>
              <div className="space-y-1.5 sm:col-span-2">
                <Label htmlFor="s-address">Dirección</Label>
                <Input id="s-address" value={form.address} onChange={(e) => set({ address: e.target.value })} maxLength={255} />
              </div>
              <div className="space-y-1.5">
                <Label htmlFor="s-delivery">Días de entrega</Label>
                <Input
                  id="s-delivery"
                  type="number"
                  min={1}
                  max={365}
                  value={form.delivery_time}
                  onChange={(e) => set({ delivery_time: Number(e.target.value) })}
                />
              </div>
              <div className="space-y-1.5">
                <Label htmlFor="s-rating">Calificación (0-5)</Label>
                <Input
                  id="s-rating"
                  type="number"
                  min={0}
                  max={5}
                  step={0.1}
                  value={form.rating ?? ""}
                  onChange={(e) => set({ rating: e.target.value === "" ? null : Number(e.target.value) })}
                />
              </div>
            </div>
            <DialogFooter>
              <Button type="button" variant="outline" onClick={() => setDialogOpen(false)}>Cancelar</Button>
              <Button type="submit" disabled={!valid || save.isPending}>Guardar</Button>
            </DialogFooter>
          </form>
        </DialogContent>
      </Dialog>

      {/* Eliminar */}
      <AlertDialog open={!!deleting} onOpenChange={(o) => !o && setDeleting(null)}>
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>¿Eliminar «{deleting?.name}»?</AlertDialogTitle>
            <AlertDialogDescription>Esta acción no se puede deshacer.</AlertDialogDescription>
          </AlertDialogHeader>
          <AlertDialogFooter>
            <AlertDialogCancel>Cancelar</AlertDialogCancel>
            <AlertDialogAction
              className="bg-destructive text-white hover:bg-destructive/90"
              disabled={remove.isPending}
              onClick={(e) => {
                e.preventDefault();
                if (deleting) remove.mutate(deleting);
              }}
            >
              Eliminar
            </AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>
    </>
  );
}
