"use client";

import { useEffect, useState } from "react";
import { keepPreviousData, useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { isAxiosError } from "axios";
import { toast } from "sonner";
import { Pencil, Plus, Search, Trash2 } from "lucide-react";

import {
  createBrand,
  deleteBrand,
  getBrands,
  updateBrand,
  type Brand,
} from "@/lib/api/admin/brands";
import { PageHeader } from "@/components/admin/page-header";
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
      </div>

      {isLoading ? (
        <Skeleton className="h-96" />
      ) : isError || !data ? (
        <Card>
          <CardContent className="py-8 text-center text-sm text-muted-foreground">
            No fue posible cargar las marcas.
          </CardContent>
        </Card>
      ) : (
        <>
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
                  {data.data.length === 0 ? (
                    <TableRow>
                      <TableCell colSpan={2} className="py-8 text-center text-sm text-muted-foreground">
                        Sin marcas.
                      </TableCell>
                    </TableRow>
                  ) : (
                    data.data.map((b) => (
                      <TableRow key={b.id}>
                        <TableCell className="font-medium">{b.name}</TableCell>
                        <TableCell className="text-right">
                          <Button size="icon" variant="ghost" className="h-8 w-8" title="Editar" onClick={() => openEdit(b)}>
                            <Pencil className="h-4 w-4" />
                          </Button>
                          <Button size="icon" variant="ghost" className="h-8 w-8 text-destructive" title="Eliminar" onClick={() => setDeleting(b)}>
                            <Trash2 className="h-4 w-4" />
                          </Button>
                        </TableCell>
                      </TableRow>
                    ))
                  )}
                </TableBody>
              </Table>
            </CardContent>
          </Card>
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
        <DialogContent>
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
