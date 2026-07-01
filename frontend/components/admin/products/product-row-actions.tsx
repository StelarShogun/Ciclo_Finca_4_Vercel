"use client";

import { useState } from "react";
import { useMutation, useQueryClient } from "@tanstack/react-query";
import { isAxiosError } from "axios";
import { toast } from "sonner";

import {
  activateProduct,
  deactivateProduct,
  forceDeleteProduct,
  type AdminProduct,
} from "@/lib/api/admin/products";
import { ActionBar, ActionBtn } from "@/components/admin/action-btn";
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

export function ProductRowActions({
  product,
  onEdit,
  onView,
}: {
  product: AdminProduct;
  onEdit?: (id: number) => void;
  onView?: (id: number) => void;
}) {
  const queryClient = useQueryClient();
  const [confirmOpen, setConfirmOpen] = useState(false);
  const id = product.product_id;
  const isActive = product.status === "active";

  const refresh = () => queryClient.invalidateQueries({ queryKey: ["admin-products"] });

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
      <ActionBar>
        <ActionBtn
          icon="fa-eye"
          label="Ver detalle"
          tone="view"
          onClick={() => (onView ? onView(id) : undefined)}
        />
        <ActionBtn
          icon="fa-pen-to-square"
          label="Editar"
          tone="edit"
          onClick={() => (onEdit ? onEdit(id) : undefined)}
        />
        {isActive ? (
          <ActionBtn
            icon="fa-ban"
            label="Desactivar"
            tone="delete"
            disabled={toggleStatus.isPending}
            onClick={() => toggleStatus.mutate()}
          />
        ) : (
          <ActionBtn
            icon="fa-circle-check"
            label="Reactivar"
            tone="activate"
            disabled={toggleStatus.isPending}
            onClick={() => toggleStatus.mutate()}
          />
        )}
        <ActionBtn
          icon="fa-trash"
          label="Eliminar"
          tone="delete"
          onClick={() => setConfirmOpen(true)}
        />
      </ActionBar>

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
