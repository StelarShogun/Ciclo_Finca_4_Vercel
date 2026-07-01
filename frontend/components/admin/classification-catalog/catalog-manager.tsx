"use client";

import { useState } from "react";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { isAxiosError } from "axios";
import { toast } from "sonner";
import { ChevronLeft, Pencil, Plus, RotateCcw, Trash2, X } from "lucide-react";

import {
  createDimension,
  createValue,
  deleteDimension,
  deleteValue,
  getCategoryAttributes,
  getDimensionValues,
  restoreDimension,
  restoreValue,
  updateDimension,
  updateValue,
  type CatalogAttribute,
} from "@/lib/api/admin/classification-catalog";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Skeleton } from "@/components/ui/skeleton";
import { StatusBadge } from "@/components/admin/status-badge";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";

function errMsg(e: unknown, fallback: string) {
  if (!isAxiosError(e)) return fallback;
  const d = e.response?.data as { message?: string; errors?: Record<string, string[]> } | undefined;
  if (d?.errors) return Object.values(d.errors)[0]?.[0] ?? fallback;
  return d?.message ?? fallback;
}

/** Fila editable genérica: muestra nombre, permite renombrar inline, borrar/restaurar. */
function EditableRow({
  label,
  trashed,
  onRename,
  onDelete,
  onRestore,
  extra,
  busy,
}: {
  label: string;
  trashed: boolean;
  onRename: (v: string) => void;
  onDelete: () => void;
  onRestore: () => void;
  extra?: React.ReactNode;
  busy: boolean;
}) {
  const [editing, setEditing] = useState(false);
  const [value, setValue] = useState(label);

  return (
    <div className="flex items-center justify-between gap-2 rounded-md border px-3 py-2">
      {editing ? (
        <form
          className="flex flex-1 items-center gap-2"
          onSubmit={(e) => {
            e.preventDefault();
            if (value.trim() && value.trim() !== label) onRename(value.trim());
            setEditing(false);
          }}
        >
          <Input autoFocus value={value} onChange={(e) => setValue(e.target.value)} className="h-8" />
          <Button type="submit" size="sm" disabled={busy}>Guardar</Button>
          <Button type="button" size="icon" variant="ghost" className="h-8 w-8" onClick={() => { setEditing(false); setValue(label); }}>
            <X className="h-4 w-4" />
          </Button>
        </form>
      ) : (
        <>
          <span className={`flex items-center gap-2 text-sm ${trashed ? "text-muted-foreground line-through" : ""}`}>
            {label}
            {trashed && <StatusBadge tone="danger">Eliminado</StatusBadge>}
            {extra}
          </span>
          <div className="flex items-center gap-1">
            {trashed ? (
              <Button size="icon" variant="ghost" className="h-8 w-8 text-emerald-600" title="Restaurar" disabled={busy} onClick={onRestore}>
                <RotateCcw className="h-4 w-4" />
              </Button>
            ) : (
              <>
                <Button size="icon" variant="ghost" className="h-8 w-8" title="Renombrar" onClick={() => { setValue(label); setEditing(true); }}>
                  <Pencil className="h-4 w-4" />
                </Button>
                <Button size="icon" variant="ghost" className="h-8 w-8 text-destructive" title="Eliminar" disabled={busy} onClick={onDelete}>
                  <Trash2 className="h-4 w-4" />
                </Button>
              </>
            )}
          </div>
        </>
      )}
    </div>
  );
}

export function CatalogManager({
  category,
  open,
  onClose,
}: {
  category: { category_id: number; name: string } | null;
  open: boolean;
  onClose: () => void;
}) {
  const queryClient = useQueryClient();
  const categoryId = category?.category_id ?? 0;
  const [dimension, setDimension] = useState<CatalogAttribute | null>(null);
  const [newName, setNewName] = useState("");

  const attrs = useQuery({
    queryKey: ["catalog-attributes", categoryId],
    queryFn: () => getCategoryAttributes(categoryId),
    enabled: open && !!category && dimension === null,
  });

  const values = useQuery({
    queryKey: ["catalog-values", dimension?.id],
    queryFn: () => getDimensionValues(dimension!.id),
    enabled: open && dimension !== null,
  });

  const invalidateAttrs = () => {
    queryClient.invalidateQueries({ queryKey: ["catalog-attributes", categoryId] });
    queryClient.invalidateQueries({ queryKey: ["admin-classification-catalog"] });
  };
  const invalidateValues = () =>
    queryClient.invalidateQueries({ queryKey: ["catalog-values", dimension?.id] });

  function mut<T extends unknown[]>(fn: (...a: T) => Promise<unknown>, ok: string, after: () => void) {
    return {
      mutationFn: (args: T) => fn(...args),
      onSuccess: () => { toast.success(ok); after(); },
      onError: (e: unknown) => toast.error(errMsg(e, "No fue posible completar la acción.")),
    };
  }

  const addDim = useMutation(mut(createDimension, "Atributo creado", () => { setNewName(""); invalidateAttrs(); }));
  const renameDim = useMutation(mut(updateDimension, "Atributo actualizado", invalidateAttrs));
  const delDim = useMutation(mut(deleteDimension, "Atributo eliminado", invalidateAttrs));
  const resDim = useMutation(mut(restoreDimension, "Atributo restaurado", invalidateAttrs));

  const addVal = useMutation(mut(createValue, "Valor creado", () => { setNewName(""); invalidateValues(); }));
  const renameVal = useMutation(mut(updateValue, "Valor actualizado", invalidateValues));
  const delVal = useMutation(mut(deleteValue, "Valor eliminado", invalidateValues));
  const resVal = useMutation(mut(restoreValue, "Valor restaurado", invalidateValues));

  function close() {
    setDimension(null);
    setNewName("");
    onClose();
  }

  const inValues = dimension !== null;
  const busy =
    addDim.isPending || renameDim.isPending || delDim.isPending || resDim.isPending ||
    addVal.isPending || renameVal.isPending || delVal.isPending || resVal.isPending;

  return (
    <Dialog open={open} onOpenChange={(o) => !o && close()}>
      <DialogContent className="sm:max-w-[38rem]">
        <DialogHeader>
          <DialogTitle className="flex items-center gap-2">
            {inValues && (
              <Button size="icon" variant="ghost" className="h-7 w-7" onClick={() => setDimension(null)}>
                <ChevronLeft className="h-4 w-4" />
              </Button>
            )}
            {inValues ? `Valores de "${dimension?.label}"` : `Atributos de "${category?.name}"`}
          </DialogTitle>
          <DialogDescription>
            {inValues
              ? "Un valor por opción (ej. Rojo, Azul). Se pueden restaurar."
              : "Atributos del tipo de producto (ej. Color, Talla)."}
          </DialogDescription>
        </DialogHeader>

        {/* Alta */}
        <form
          className="flex items-center gap-2"
          onSubmit={(e) => {
            e.preventDefault();
            const v = newName.trim();
            if (!v) return;
            if (inValues) addVal.mutate([dimension!.id, v]);
            else addDim.mutate([categoryId, v]);
          }}
        >
          <Input
            placeholder={inValues ? "Nuevo valor…" : "Nuevo atributo…"}
            value={newName}
            onChange={(e) => setNewName(e.target.value)}
            maxLength={255}
          />
          <Button type="submit" disabled={!newName.trim() || busy}>
            <Plus className="h-4 w-4" /> Agregar
          </Button>
        </form>

        {/* Lista */}
        <div className="max-h-80 space-y-2 overflow-y-auto">
          {!inValues ? (
            attrs.isLoading ? (
              <Skeleton className="h-32" />
            ) : !attrs.data || attrs.data.attributes.length === 0 ? (
              <p className="py-6 text-center text-sm text-muted-foreground">Sin atributos.</p>
            ) : (
              attrs.data.attributes.map((a) => (
                <EditableRow
                  key={a.id}
                  label={a.label}
                  trashed={a.trashed}
                  busy={busy}
                  onRename={(v) => renameDim.mutate([a.id, v])}
                  onDelete={() => delDim.mutate([a.id])}
                  onRestore={() => resDim.mutate([a.id])}
                  extra={
                    !a.trashed && (
                      <Button
                        size="sm"
                        variant="outline"
                        className="ml-2 h-6"
                        onClick={() => setDimension(a)}
                      >
                        {a.values_count} valores
                      </Button>
                    )
                  }
                />
              ))
            )
          ) : values.isLoading ? (
            <Skeleton className="h-32" />
          ) : !values.data || values.data.values.length === 0 ? (
            <p className="py-6 text-center text-sm text-muted-foreground">Sin valores.</p>
          ) : (
            values.data.values.map((v) => (
              <EditableRow
                key={v.id}
                label={v.value}
                trashed={v.trashed}
                busy={busy}
                onRename={(nv) => renameVal.mutate([v.id, nv])}
                onDelete={() => delVal.mutate([v.id])}
                onRestore={() => resVal.mutate([v.id])}
              />
            ))
          )}
        </div>
      </DialogContent>
    </Dialog>
  );
}
