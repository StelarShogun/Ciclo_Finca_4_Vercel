"use client";

import { useState } from "react";
import Link from "next/link";
import { useMutation, useQueryClient } from "@tanstack/react-query";
import { isAxiosError } from "axios";
import { toast } from "sonner";
import { Eye, MoreHorizontal, Pencil, Power, Star, Trash2 } from "lucide-react";

import {
  activateProduct,
  deactivateProduct,
  forceDeleteProduct,
  toggleProductFeatured,
  type AdminProduct,
} from "@/lib/api/admin/products";
import { Button } from "@/components/ui/button";
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";
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
import { cn } from "@/lib/utils";
import { buttonVariants } from "@/components/ui/button";

export function ProductRowActions({ product }: { product: AdminProduct }) {
  const queryClient = useQueryClient();
  const [confirmOpen, setConfirmOpen] = useState(false);
  const id = product.product_id;
  const isActive = product.status === "active";

  const refresh = () =>
    queryClient.invalidateQueries({ queryKey: ["admin-products"] });

  function mutationFor(fn: () => Promise<unknown>, ok: string) {
    return {
      mutationFn: fn,
      onSuccess: () => {
        toast.success(ok);
        refresh();
      },
      onError: (e: unknown) =>
        toast.error(
          (isAxiosError(e) && (e.response?.data?.message as string)) ||
            "No fue posible completar la acción.",
        ),
    };
  }

  const toggleStatus = useMutation(
    mutationFor(
      () => (isActive ? deactivateProduct(id) : activateProduct(id)),
      isActive ? "Producto desactivado" : "Producto activado",
    ),
  );
  const toggleFeatured = useMutation(
    mutationFor(() => toggleProductFeatured(id), "Destacado actualizado"),
  );
  const remove = useMutation({
    ...mutationFor(() => forceDeleteProduct(id), "Producto eliminado"),
    onSuccess: () => {
      toast.success("Producto eliminado");
      setConfirmOpen(false);
      refresh();
    },
  });

  return (
    <>
      <DropdownMenu>
        <DropdownMenuTrigger asChild>
          <Button variant="ghost" size="icon" className="h-8 w-8">
            <MoreHorizontal className="h-4 w-4" />
            <span className="sr-only">Acciones</span>
          </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end" className="w-48">
          <DropdownMenuItem asChild>
            <Link href={`/admin/products/${id}`}>
              <Eye className="h-4 w-4" /> Ver detalle
            </Link>
          </DropdownMenuItem>
          <DropdownMenuItem asChild>
            <Link href={`/admin/products/${id}/edit`}>
              <Pencil className="h-4 w-4" /> Editar
            </Link>
          </DropdownMenuItem>
          <DropdownMenuItem
            onClick={() => toggleStatus.mutate()}
            disabled={toggleStatus.isPending}
          >
            <Power className="h-4 w-4" />
            {isActive ? "Desactivar" : "Activar"}
          </DropdownMenuItem>
          <DropdownMenuItem
            onClick={() => toggleFeatured.mutate()}
            disabled={toggleFeatured.isPending}
          >
            <Star className="h-4 w-4" />
            {product.is_featured ? "Quitar destacado" : "Destacar"}
          </DropdownMenuItem>
          <DropdownMenuSeparator />
          <DropdownMenuItem
            variant="destructive"
            onSelect={(e) => {
              e.preventDefault();
              setConfirmOpen(true);
            }}
          >
            <Trash2 className="h-4 w-4" /> Eliminar
          </DropdownMenuItem>
        </DropdownMenuContent>
      </DropdownMenu>

      <AlertDialog open={confirmOpen} onOpenChange={setConfirmOpen}>
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>Eliminar “{product.name}”</AlertDialogTitle>
            <AlertDialogDescription>
              Esto elimina el producto de forma permanente. No se puede deshacer.
            </AlertDialogDescription>
          </AlertDialogHeader>
          <AlertDialogFooter>
            <AlertDialogCancel>Cancelar</AlertDialogCancel>
            <AlertDialogAction
              className={cn(buttonVariants({ variant: "destructive" }))}
              onClick={(e) => {
                e.preventDefault();
                remove.mutate();
              }}
              disabled={remove.isPending}
            >
              Eliminar
            </AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>
    </>
  );
}
