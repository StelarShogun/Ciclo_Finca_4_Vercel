"use client";

import { useState } from "react";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { isAxiosError } from "axios";
import { toast } from "sonner";
import { FolderPlus, Plus } from "lucide-react";

import {
  createParentCategory,
  createSubcategory,
  getCategories,
  type ParentOption,
} from "@/lib/api/admin/categories";
import { PageHeader } from "@/components/admin/page-header";
import { StatusBadge } from "@/components/admin/status-badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Skeleton } from "@/components/ui/skeleton";
import { Textarea } from "@/components/ui/textarea";
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
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";

function apiError(e: unknown, fallback: string): string {
  if (!isAxiosError(e)) return fallback;
  const d = e.response?.data as { message?: string; errors?: Record<string, string[]> } | undefined;
  if (d?.errors) return Object.values(d.errors)[0]?.[0] ?? fallback;
  return d?.message ?? fallback;
}

export default function CategoriesPage() {
  const queryClient = useQueryClient();
  const { data, isLoading, isError } = useQuery({
    queryKey: ["admin-categories"],
    queryFn: getCategories,
  });

  const [parentOpen, setParentOpen] = useState(false);
  const [subOpen, setSubOpen] = useState(false);
  const [pName, setPName] = useState("");
  const [pDesc, setPDesc] = useState("");
  const [sName, setSName] = useState("");
  const [sDesc, setSDesc] = useState("");
  const [sParent, setSParent] = useState<string>("");

  const invalidate = () => queryClient.invalidateQueries({ queryKey: ["admin-categories"] });

  const addParent = useMutation({
    mutationFn: () => createParentCategory(pName.trim(), pDesc.trim()),
    onSuccess: () => {
      toast.success("Categoría creada");
      setParentOpen(false);
      setPName("");
      setPDesc("");
      invalidate();
    },
    onError: (e) => toast.error(apiError(e, "No se pudo crear la categoría.")),
  });

  const addSub = useMutation({
    mutationFn: () => createSubcategory(sName.trim(), Number(sParent), sDesc.trim()),
    onSuccess: () => {
      toast.success("Subcategoría creada");
      setSubOpen(false);
      setSName("");
      setSDesc("");
      setSParent("");
      invalidate();
    },
    onError: (e) => toast.error(apiError(e, "No se pudo crear la subcategoría.")),
  });

  return (
    <>
      <PageHeader
        title="Categorías"
        description="Jerarquía de categorías y subcategorías del catálogo."
        actions={
          <div className="flex gap-2">
            <Button variant="outline" onClick={() => setParentOpen(true)}>
              <FolderPlus className="h-4 w-4" />
              Nueva categoría
            </Button>
            <Button onClick={() => setSubOpen(true)} disabled={(data?.parents.length ?? 0) === 0}>
              <Plus className="h-4 w-4" />
              Nueva subcategoría
            </Button>
          </div>
        }
      />

      {isLoading ? (
        <Skeleton className="h-96" />
      ) : isError || !data ? (
        <Card>
          <CardContent className="py-8 text-center text-sm text-muted-foreground">
            No fue posible cargar las categorías.
          </CardContent>
        </Card>
      ) : (
        <Card>
          <CardContent className="p-0">
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Nombre</TableHead>
                  <TableHead>Tipo</TableHead>
                  <TableHead>Categoría padre</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {data.hierarchy.length === 0 ? (
                  <TableRow>
                    <TableCell colSpan={3} className="py-8 text-center text-sm text-muted-foreground">
                      Sin categorías.
                    </TableCell>
                  </TableRow>
                ) : (
                  data.hierarchy.map((row) => (
                    <TableRow key={row.category_id}>
                      <TableCell className={row.is_parent ? "font-medium" : "pl-8"}>
                        {row.name}
                      </TableCell>
                      <TableCell>
                        <StatusBadge tone={row.is_parent ? "success" : "neutral"}>
                          {row.is_parent ? "Categoría" : "Subcategoría"}
                        </StatusBadge>
                      </TableCell>
                      <TableCell className="text-muted-foreground">
                        {row.parent_name ?? "—"}
                      </TableCell>
                    </TableRow>
                  ))
                )}
              </TableBody>
            </Table>
          </CardContent>
        </Card>
      )}

      {/* Nueva categoría padre */}
      <Dialog open={parentOpen} onOpenChange={setParentOpen}>
        <DialogContent>
          <form
            onSubmit={(e) => {
              e.preventDefault();
              if (pName.trim()) addParent.mutate();
            }}
          >
            <DialogHeader>
              <DialogTitle>Nueva categoría</DialogTitle>
              <DialogDescription>Categoría raíz del catálogo.</DialogDescription>
            </DialogHeader>
            <div className="space-y-4 py-4">
              <div className="space-y-1.5">
                <Label htmlFor="p-name">Nombre</Label>
                <Input id="p-name" autoFocus maxLength={255} value={pName} onChange={(e) => setPName(e.target.value)} />
              </div>
              <div className="space-y-1.5">
                <Label htmlFor="p-desc">Descripción (opcional)</Label>
                <Textarea id="p-desc" value={pDesc} onChange={(e) => setPDesc(e.target.value)} />
              </div>
            </div>
            <DialogFooter>
              <Button type="button" variant="outline" onClick={() => setParentOpen(false)}>Cancelar</Button>
              <Button type="submit" disabled={!pName.trim() || addParent.isPending}>Guardar</Button>
            </DialogFooter>
          </form>
        </DialogContent>
      </Dialog>

      {/* Nueva subcategoría */}
      <Dialog open={subOpen} onOpenChange={setSubOpen}>
        <DialogContent>
          <form
            onSubmit={(e) => {
              e.preventDefault();
              if (sName.trim() && sParent) addSub.mutate();
            }}
          >
            <DialogHeader>
              <DialogTitle>Nueva subcategoría</DialogTitle>
              <DialogDescription>Cuelga de una categoría existente.</DialogDescription>
            </DialogHeader>
            <div className="space-y-4 py-4">
              <div className="space-y-1.5">
                <Label htmlFor="s-parent">Categoría padre</Label>
                <Select value={sParent} onValueChange={setSParent}>
                  <SelectTrigger id="s-parent">
                    <SelectValue placeholder="Seleccioná una categoría" />
                  </SelectTrigger>
                  <SelectContent>
                    {(data?.parents ?? []).map((p: ParentOption) => (
                      <SelectItem key={p.category_id} value={String(p.category_id)}>
                        {p.name}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </div>
              <div className="space-y-1.5">
                <Label htmlFor="s-name">Nombre</Label>
                <Input id="s-name" maxLength={255} value={sName} onChange={(e) => setSName(e.target.value)} />
              </div>
              <div className="space-y-1.5">
                <Label htmlFor="s-desc">Descripción (opcional)</Label>
                <Textarea id="s-desc" value={sDesc} onChange={(e) => setSDesc(e.target.value)} />
              </div>
            </div>
            <DialogFooter>
              <Button type="button" variant="outline" onClick={() => setSubOpen(false)}>Cancelar</Button>
              <Button type="submit" disabled={!sName.trim() || !sParent || addSub.isPending}>Guardar</Button>
            </DialogFooter>
          </form>
        </DialogContent>
      </Dialog>
    </>
  );
}
