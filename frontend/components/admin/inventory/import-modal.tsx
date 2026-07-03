"use client";

import { useEffect, useRef, useState } from "react";
import { useMutation, useQueryClient } from "@tanstack/react-query";
import { isAxiosError } from "axios";
import { toast } from "sonner";
import { CheckCircle2, FileUp, Loader2, XCircle } from "lucide-react";

import { getImportProgress, importCatalog, type ImportProgress } from "@/lib/api/admin/inventory";
import { Button } from "@/components/ui/button";
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from "@/components/ui/dialog";

function errMsg(e: unknown, fallback: string) {
  if (!isAxiosError(e)) return fallback;
  const d = e.response?.data as { message?: string; errors?: Record<string, string[]> } | undefined;
  if (d?.errors) return Object.values(d.errors)[0]?.[0] ?? fallback;
  return d?.message ?? fallback;
}

/** Importa el catálogo (ZIP/JSON/XML/CSV): encola el job y hace polling del progreso. */
export function ImportModal({ open, onClose }: { open: boolean; onClose: () => void }) {
  const queryClient = useQueryClient();
  const inputRef = useRef<HTMLInputElement>(null);
  const [file, setFile] = useState<File | null>(null);
  const [importId, setImportId] = useState<string | null>(null);
  const [progress, setProgress] = useState<ImportProgress | null>(null);

  const start = useMutation({
    mutationFn: () => importCatalog(file as File),
    onSuccess: (res) => setImportId(res.importId),
    onError: (e) => toast.error(errMsg(e, "No fue posible iniciar la importación.")),
  });

  // Polling del progreso mientras haya un importId activo.
  useEffect(() => {
    if (!importId) return;
    let cancelled = false;
    const tick = async () => {
      try {
        const p = await getImportProgress(importId);
        if (cancelled) return;
        setProgress(p);
        if (p.status === "done") {
          toast.success("Importación completada");
          queryClient.invalidateQueries({ queryKey: ["admin-inventory"] });
          queryClient.invalidateQueries({ queryKey: ["admin-products"] });
        } else if (p.status !== "failed") {
          timer = setTimeout(tick, 1500);
        }
      } catch {
        if (!cancelled) timer = setTimeout(tick, 2000);
      }
    };
    let timer = setTimeout(tick, 800);
    return () => { cancelled = true; clearTimeout(timer); };
  }, [importId, queryClient]);

  function reset() {
    setFile(null); setImportId(null); setProgress(null);
    if (inputRef.current) inputRef.current.value = "";
  }
  function close() { reset(); onClose(); }

  const running = !!importId && progress?.status !== "done" && progress?.status !== "failed";
  const pct = progress?.total ? Math.round(((progress.processed ?? 0) / progress.total) * 100) : null;

  return (
    <Dialog open={open} onOpenChange={(o) => !o && close()}>
      <DialogContent className="sm:max-w-[38rem]">
        <DialogHeader>
          <DialogTitle className="flex items-center gap-2"><i className="fas fa-file-import text-brand-medium dark:text-brand-light" aria-hidden /> Importar catálogo</DialogTitle>
          <DialogDescription>Archivo ZIP, JSON, XML o CSV con productos.</DialogDescription>
        </DialogHeader>

        {!importId ? (
          <div className="space-y-4">
            <div
              onClick={() => inputRef.current?.click()}
              className="flex cursor-pointer flex-col items-center gap-2 rounded-lg border-2 border-dashed p-6 text-center hover:border-brand-medium/60"
            >
              <FileUp className="h-8 w-8 text-muted-foreground" />
              <p className="text-sm text-muted-foreground">{file ? file.name : "Hacé clic para elegir el archivo"}</p>
              <input ref={inputRef} type="file" accept=".zip,.json,.xml,.csv,.txt" className="hidden" onChange={(e) => setFile(e.target.files?.[0] ?? null)} />
            </div>
          </div>
        ) : (
          <div className="flex flex-col items-center gap-3 py-6 text-center">
            {progress?.status === "done" ? (
              <><CheckCircle2 className="h-10 w-10 text-brand-medium" /><p className="font-medium">Importación completada</p></>
            ) : progress?.status === "failed" ? (
              <><XCircle className="h-10 w-10 text-destructive" /><p className="font-medium">La importación falló</p></>
            ) : (
              <><Loader2 className="h-10 w-10 animate-spin text-brand-medium" /><p className="font-medium">Procesando…</p></>
            )}
            {progress?.message && <p className="text-sm text-muted-foreground">{progress.message}</p>}
            {pct !== null && running && (
              <div className="w-full">
                <div className="h-2 overflow-hidden rounded bg-muted"><div className="h-full bg-cta transition-all" style={{ width: `${pct}%` }} /></div>
                <p className="mt-1 text-xs text-muted-foreground">{progress?.processed ?? 0} / {progress?.total} ({pct}%)</p>
              </div>
            )}
            {(progress?.created != null || progress?.updated != null) && progress?.status === "done" && (
              <p className="text-sm text-muted-foreground">Creados: {progress?.created ?? 0} · Actualizados: {progress?.updated ?? 0}</p>
            )}
          </div>
        )}

        <DialogFooter>
          {!importId ? (
            <>
              <Button variant="outline" onClick={close}>Cancelar</Button>
              <Button className="bg-brand-medium hover:bg-brand-medium-dark" disabled={!file || start.isPending} onClick={() => start.mutate()}>
                {start.isPending ? "Subiendo…" : "Importar"}
              </Button>
            </>
          ) : (
            <Button variant="outline" onClick={close}>{running ? "Cerrar (sigue en segundo plano)" : "Cerrar"}</Button>
          )}
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
