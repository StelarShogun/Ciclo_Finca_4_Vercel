"use client";

import { useEffect, useState } from "react";
import { keepPreviousData, useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { isAxiosError } from "axios";
import { toast } from "sonner";
import { Plus, Search } from "lucide-react";

import {
  createBrand,
  deleteBrand,
  getBrands,
  updateBrand,
  type Brand,
} from "@/lib/api/admin/brands";
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

/** Mensaje legible para errores de la API de marcas (duplicado, validación, bloqueo). */
function brandError(e: unknown, fallback: string): string {
  if (!isAxiosError(e)) return fallback;
  const d = e.response?.data as
    | { message?: string; duplicate?: boolean; existing?: { name: string }; errors?: Record<string, string[]> }
    | undefined;
  if (d?.duplicate && d.existing) return `Ya existe la marca «${d.existing.name}».`;
  if (d?.message) return d.message;
  if (d?.errors) return Object.values(d.errors)[0]?.[0] ?? fallback;
  return fallback;
}

export default function BrandsPage() {
  const queryClient = useQueryClient();
  const [page, setPage] = useState(1);
  const [search, setSearch] = useState("");
  const [debounced, setDebounced] = useState("");

  const [editing, setEditing] = useState<Brand | null>(null); // null + dialogOpen => crear
  const [dialogOpen, setDialogOpen] = useState(false);
  const [name, setName] = useState("");
  const [deleting, setDeleting] = useState<Brand | null>(null);
  const [view, setView] = useViewMode("brands");

  useEffect(() => {
    const t = setTimeout(() => setDebounced(search), 350);
    return () => clearTimeout(t);
  }, [search]);

  const { data, isLoading, isError } = useQuery({
    queryKey: ["admin-brands", page, debounced],
    queryFn: () => getBrands({ page, name: debounced }),
    placeholderData: keepPreviousData,
  });

  const invalidate = () => queryClient.invalidateQueries({ queryKey: ["admin-brands"] });

  const save = useMutation({
    mutationFn: () =>
      editing ? updateBrand(editing.id, name.trim()) : createBrand(name.trim()),
    onSuccess: () => {
      toast.success(editing ? "Marca actualizada" : "Marca creada");
      setDialogOpen(false);
      invalidate();
    },
    onError: (e) => toast.error(brandError(e, "No se pudo guardar la marca.")),
  });

  const remove = useMutation({
    mutationFn: (b: Brand) => deleteBrand(b.id),
    onSuccess: () => {
      toast.success("Marca eliminada");
      setDeleting(null);
      invalidate();
    },
    onError: (e) => toast.error(brandError(e, "No se pudo eliminar la marca.")),
  });

  function openCreate() {
    setEditing(null);
    setName("");
    setDialogOpen(true);
  }

  function openEdit(b: Brand) {
    setEditing(b);
    setName(b.name);
    setDialogOpen(true);
  }

  return (
    <>
      <PageHeader
        title="Marcas"
        description="Marcas del catálogo de productos."
        actions={
          <Button onClick={openCreate}>
            <Plus className="h-4 w-4" />
            Nueva marca
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
            No fue posible cargar las marcas.
          </CardContent>
        </Card>
      ) : data.data.length === 0 ? (
        <Card>
          <CardContent className="py-8 text-center text-sm text-muted-foreground">Sin marcas.</CardContent>
        </Card>
      ) : (
        <>
          {view === "grid" ? (
            <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
              {data.data.map((b) => (
                <AdminCard
                  key={b.id}
                  media={
                    <div className="flex h-12 w-12 shrink-0 items-center justify-center rounded-lg border bg-muted text-[#235347] dark:text-[#8EB69B]">
                      <i className="fas fa-tag" aria-hidden />
                    </div>
                  }
                  title={b.name}
                  actions={
                    <ActionBar>
                      <ActionBtn icon="fa-pen-to-square" label="Editar" tone="edit" onClick={() => openEdit(b)} />
                      <ActionBtn icon="fa-trash" label="Eliminar" tone="delete" onClick={() => setDeleting(b)} />
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
                      <TableHead className="w-24 text-right">Acciones</TableHead>
                    </TableRow>
                  </TableHeader>
                  <TableBody>
                    {data.data.map((b) => (
                      <TableRow key={b.id}>
                        <TableCell className="font-medium">{b.name}</TableCell>
                        <TableCell>
                          <div className="flex justify-end">
                            <ActionBar>
                              <ActionBtn icon="fa-pen-to-square" label="Editar" tone="edit" onClick={() => openEdit(b)} />
                              <ActionBtn icon="fa-trash" label="Eliminar" tone="delete" onClick={() => setDeleting(b)} />
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
            currentPage={data.current_page}
            lastPage={data.last_page}
            total={data.total}
            onPageChange={setPage}
          />
        </>
      )}

      {/* Crear / editar */}
      <Dialog open={dialogOpen} onOpenChange={setDialogOpen}>
        <DialogContent className="sm:max-w-[38rem]">
          <form
            onSubmit={(e) => {
              e.preventDefault();
              if (name.trim()) save.mutate();
            }}
          >
            <DialogHeader>
              <DialogTitle className="flex items-center gap-2">
                <i className={`fas ${editing ? "fa-pen-to-square" : "fa-tag"} text-[#235347] dark:text-[#8EB69B]`} aria-hidden />
                {editing ? "Editar marca" : "Nueva marca"}
              </DialogTitle>
              <DialogDescription>El nombre debe ser único.</DialogDescription>
            </DialogHeader>
            <div className="space-y-1.5 py-4">
              <Label htmlFor="brand-name">Nombre</Label>
              <Input
                id="brand-name"
                autoFocus
                maxLength={100}
                value={name}
                onChange={(e) => setName(e.target.value)}
                placeholder="Ej. Trek"
              />
            </div>
            <DialogFooter>
              <Button type="button" variant="outline" onClick={() => setDialogOpen(false)}>
                Cancelar
              </Button>
              <Button type="submit" disabled={!name.trim() || save.isPending}>
                Guardar
              </Button>
            </DialogFooter>
          </form>
        </DialogContent>
      </Dialog>

      {/* Eliminar */}
      <AlertDialog open={!!deleting} onOpenChange={(o) => !o && setDeleting(null)}>
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>¿Eliminar «{deleting?.name}»?</AlertDialogTitle>
            <AlertDialogDescription>
              Esta acción no se puede deshacer. Solo se permite si la marca no tiene productos asociados.
            </AlertDialogDescription>
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
