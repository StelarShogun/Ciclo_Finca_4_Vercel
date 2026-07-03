"use client";

import { useMemo, useState } from "react";
import Link from "next/link";
import { useRouter } from "next/navigation";
import { useMutation } from "@tanstack/react-query";
import { isAxiosError } from "axios";
import { toast } from "sonner";
import { AlertTriangle, ArrowLeft, Check, CheckCheck, FileCode2, RotateCcw, Search, X, XCircle } from "lucide-react";

import {
  analyzeXmlDeviation,
  applyXmlDeviation,
  type XmlDeviationAnalysis,
} from "@/lib/api/admin/xml-deviation";
import { PageHeader } from "@/components/admin/page-header";
import { FileUpload } from "@/components/admin/file-upload";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";
import { Checkbox } from "@/components/ui/checkbox";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table";

const money = new Intl.NumberFormat("es-CR", { minimumFractionDigits: 2 });
const crc = (n: number) => `₡${money.format(Number(n) || 0)}`;

function errMsg(e: unknown, fallback: string) {
  return (isAxiosError(e) && (e.response?.data?.message as string)) || fallback;
}

export default function XmlDeviationPage() {
  const router = useRouter();
  const [file, setFile] = useState<File | null>(null);
  const [threshold, setThreshold] = useState("10");
  const [analysisId, setAnalysisId] = useState<string | null>(null);
  const [analysis, setAnalysis] = useState<XmlDeviationAnalysis | null>(null);
  const [checked, setChecked] = useState<Set<number>>(new Set());
  const [salePrices, setSalePrices] = useState<Record<number, string>>({});
  const [reason, setReason] = useState("");

  const analyze = useMutation({
    mutationFn: () => analyzeXmlDeviation(file!, Number(threshold)),
    onSuccess: ({ analysisId, analysis }) => {
      setAnalysisId(analysisId);
      setAnalysis(analysis);
      // Preselección fiel al viejo: con desvío marcados y sugerencias precargadas.
      const s = new Set<number>();
      const prices: Record<number, string> = {};
      for (const it of analysis.items) {
        if (!it.found || it.product_id == null) continue;
        if (it.has_deviation) s.add(it.product_id);
        if (it.suggested_sale_price !== null) prices[it.product_id] = String(it.suggested_sale_price);
      }
      setChecked(s);
      setSalePrices(prices);
    },
    onError: (e) => toast.error(errMsg(e, "No fue posible analizar el XML.")),
  });

  const apply = useMutation({
    mutationFn: () => {
      const prices: Record<number, string> = {};
      for (const [id, val] of Object.entries(salePrices)) {
        if (val !== "" && checked.has(Number(id))) prices[Number(id)] = val;
      }
      return applyXmlDeviation({ analysisId: analysisId!, updates: [...checked], salePrices: prices, reason });
    },
    onSuccess: (res) => {
      toast.success(res.message);
      router.push("/admin/supplier-orders");
    },
    onError: (e) => toast.error(errMsg(e, "No fue posible aplicar los cambios.")),
  });

  const stats = useMemo(() => {
    const items = analysis?.items ?? [];
    return {
      total: items.length,
      deviation: items.filter((i) => i.found && i.has_deviation).length,
      notFound: items.filter((i) => !i.found).length,
      priceUp: items.filter((i) => i.found && i.suggested_sale_price !== null).length,
    };
  }, [analysis]);

  const selectable = (analysis?.items ?? []).filter((i) => i.found && i.product_id != null);

  function toggle(id: number) {
    setChecked((prev) => {
      const next = new Set(prev);
      if (next.has(id)) next.delete(id);
      else next.add(id);
      return next;
    });
  }

  // ── Paso 1: subir XML ──────────────────────────────────────────────
  if (!analysis) {
    return (
      <div className="space-y-6">
        <PageHeader
          kicker="Proveedores"
          title="Importar XML de proveedor"
          description="Compara los precios del XML contra el precio de compra actual antes de aplicar cambios."
          icon="fa-file-code"
          actions={
            <Button asChild variant="secondary" size="sm">
              <Link href="/admin/supplier-orders"><ArrowLeft className="h-4 w-4" /> Volver a pedidos</Link>
            </Button>
          }
        />

        <Card className="mx-auto w-full max-w-2xl">
          <CardContent className="space-y-5 p-5 sm:p-6">
            <form
              className="space-y-5"
              onSubmit={(e) => { e.preventDefault(); if (file) analyze.mutate(); }}
              noValidate
            >
              <FileUpload
                label="Archivo XML del proveedor"
                hint="Tamaño máximo: 5 MB. Solo archivos .xml."
                accept=".xml,text/xml,application/xml"
                onChange={(files) => setFile(files[0] ?? null)}
              />

              <div className="space-y-1.5">
                <Label htmlFor="threshold">Umbral de desvío (%)</Label>
                <Input
                  id="threshold"
                  type="number"
                  min={0}
                  max={100}
                  step={0.5}
                  value={threshold}
                  onChange={(e) => setThreshold(e.target.value)}
                  className="max-w-[160px]"
                />
                <p className="text-xs text-muted-foreground">
                  Variación mínima para marcar un producto como desvío. Ej: <strong>10</strong> resalta
                  productos con cambio de precio ≥ 10%.
                </p>
              </div>

              <div className="flex flex-wrap gap-2">
                <Button type="submit" disabled={!file || analyze.isPending}>
                  <Search className="h-4 w-4" />
                  {analyze.isPending ? "Procesando…" : "Analizar XML"}
                </Button>
                <Button asChild variant="ghost">
                  <Link href="/admin/supplier-orders">Cancelar</Link>
                </Button>
              </div>
            </form>
          </CardContent>
        </Card>
      </div>
    );
  }

  // ── Paso 2: revisión ───────────────────────────────────────────────
  return (
    <div className="space-y-5">
      <PageHeader
        kicker="Proveedores"
        title="Revisión de precios XML"
        description="Selecciona los productos a actualizar y ajusta el precio de venta cuando corresponda."
        icon="fa-file-code"
        actions={
          <div className="flex flex-wrap gap-2">
            <Button variant="secondary" size="sm" onClick={() => { setAnalysis(null); setAnalysisId(null); setFile(null); }}>
              <RotateCcw className="h-4 w-4" /> Cargar otro XML
            </Button>
            <Button asChild variant="ghost" size="sm">
              <Link href="/admin/supplier-orders"><ArrowLeft className="h-4 w-4" /> Volver a pedidos</Link>
            </Button>
          </div>
        }
      />

      <div className="flex flex-wrap items-center gap-x-5 gap-y-1.5 text-sm text-muted-foreground">
        <span className="inline-flex items-center gap-1.5"><FileCode2 className="h-4 w-4" /> <strong className="text-foreground">{analysis.file_name}</strong></span>
        <span>Umbral: <strong className="text-foreground">{analysis.threshold_percentage.toFixed(1)}%</strong></span>
        <span>Total: <strong className="text-foreground">{stats.total}</strong></span>
        <span>Con desvío: <strong className="text-amber-600 dark:text-amber-400">{stats.deviation}</strong></span>
        {stats.priceUp > 0 && <span>Con alza en compra: <strong className="text-amber-600 dark:text-amber-400">{stats.priceUp}</strong></span>}
        {stats.notFound > 0 && <span>No encontrados: <strong className="text-destructive">{stats.notFound}</strong></span>}
      </div>

      <div className="flex flex-wrap gap-2">
        <Button variant="secondary" size="sm" onClick={() => setChecked(new Set(selectable.filter((i) => i.has_deviation).map((i) => i.product_id!)))}>
          <AlertTriangle className="h-4 w-4" /> Seleccionar con desvío
        </Button>
        <Button variant="secondary" size="sm" onClick={() => setChecked(new Set(selectable.map((i) => i.product_id!)))}>
          <CheckCheck className="h-4 w-4" /> Seleccionar todos
        </Button>
        <Button variant="ghost" size="sm" onClick={() => setChecked(new Set())}>
          <X className="h-4 w-4" /> Deseleccionar todos
        </Button>
      </div>

      <Card>
        <CardContent className="overflow-x-auto p-0">
          <Table className="min-w-[900px]">
            <TableHeader>
              <TableRow>
                <TableHead className="w-10" />
                <TableHead>Producto</TableHead>
                <TableHead>Código</TableHead>
                <TableHead>Cant.</TableHead>
                <TableHead>P. compra actual</TableHead>
                <TableHead>P. compra XML</TableHead>
                <TableHead>Diferencia</TableHead>
                <TableHead>% Desvío</TableHead>
                <TableHead className="min-w-[220px]">Precio de venta</TableHead>
                <TableHead>Estado</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {analysis.items.map((item, idx) => {
                const pid = item.product_id;
                const diff = item.difference_amount;
                const diffClass = diff > 0 ? "text-destructive" : diff < 0 ? "text-emerald-600 dark:text-emerald-400" : "";
                const sign = diff > 0 ? "+" : diff < 0 ? "−" : "";
                const hasSuggestion = item.found && item.suggested_sale_price !== null;
                return (
                  <TableRow key={pid ?? `nf-${idx}`} className={!item.found ? "opacity-60" : item.has_deviation ? "bg-amber-500/5" : ""}>
                    <TableCell>
                      {item.found && pid != null ? (
                        <Checkbox checked={checked.has(pid)} onCheckedChange={() => toggle(pid)} aria-label={`Actualizar ${item.name}`} />
                      ) : (
                        <span title="Producto no encontrado en el sistema">—</span>
                      )}
                    </TableCell>
                    <TableCell className="max-w-[240px] truncate font-medium">{item.name || "(sin nombre)"}</TableCell>
                    <TableCell><code className="text-xs">{item.sku || "—"}</code></TableCell>
                    <TableCell>{item.quantity}</TableCell>
                    <TableCell>{item.found ? crc(item.current_price) : "—"}</TableCell>
                    <TableCell>{crc(item.xml_price)}</TableCell>
                    <TableCell className={diffClass}>{item.found ? `${sign}${crc(Math.abs(diff))}` : "—"}</TableCell>
                    <TableCell className={diffClass}>{item.found ? `${sign}${Math.abs(item.difference_percentage).toFixed(2)}%` : "—"}</TableCell>
                    <TableCell>
                      {hasSuggestion && pid != null ? (
                        <div className="space-y-1">
                          <div className="flex flex-wrap items-center gap-1.5 text-xs text-muted-foreground">
                            <span>{crc(item.current_sale_price)}</span>
                            <span aria-hidden>→</span>
                            <span className="font-semibold text-amber-600 dark:text-amber-400">{crc(item.suggested_sale_price ?? 0)}</span>
                            <span>({item.current_margin_pct.toFixed(1)}% margen)</span>
                          </div>
                          <div className="flex items-center gap-1.5">
                            <div className="relative flex-1">
                              <span className="absolute left-2.5 top-1/2 -translate-y-1/2 text-sm text-muted-foreground">₡</span>
                              <Input
                                type="number"
                                min={item.xml_price}
                                step={1}
                                value={salePrices[pid] ?? ""}
                                onChange={(e) => setSalePrices((prev) => ({ ...prev, [pid]: e.target.value }))}
                                aria-label={`Nuevo precio de venta para ${item.name}`}
                                className="h-8 pl-6"
                              />
                            </div>
                            <button
                              type="button"
                              title="Limpiar — no modificará el precio de venta"
                              onClick={() => setSalePrices((prev) => ({ ...prev, [pid]: "" }))}
                              className="text-muted-foreground hover:text-foreground"
                            >
                              <XCircle className="h-4 w-4" />
                            </button>
                          </div>
                          <p className="text-[11px] text-muted-foreground">Vacío = precio de venta sin cambios</p>
                        </div>
                      ) : item.found ? (
                        <span className="text-xs text-muted-foreground">Sin cambio sugerido</span>
                      ) : (
                        "—"
                      )}
                    </TableCell>
                    <TableCell>
                      {!item.found ? (
                        <Badge variant="destructive"><XCircle className="h-3 w-3" /> No encontrado</Badge>
                      ) : item.has_deviation ? (
                        <Badge className="bg-amber-500/15 text-amber-700 dark:text-amber-400"><AlertTriangle className="h-3 w-3" /> Desvío</Badge>
                      ) : (
                        <Badge className="bg-emerald-500/15 text-emerald-700 dark:text-emerald-400"><Check className="h-3 w-3" /> Sin desvío</Badge>
                      )}
                    </TableCell>
                  </TableRow>
                );
              })}
            </TableBody>
          </Table>
        </CardContent>
      </Card>

      <div className="space-y-1.5">
        <Label htmlFor="reason">Motivo / nota del ajuste <span className="font-normal text-muted-foreground">(opcional)</span></Label>
        <Textarea
          id="reason"
          maxLength={500}
          value={reason}
          onChange={(e) => setReason(e.target.value)}
          placeholder="Ej: Ajuste por alza generalizada de precios del proveedor XYZ."
          className="max-w-2xl"
        />
      </div>

      <div className="flex flex-wrap items-center gap-3">
        <Button onClick={() => apply.mutate()} disabled={apply.isPending}>
          <Check className="h-4 w-4" />
          {apply.isPending ? "Aplicando…" : "Aplicar cambios seleccionados"}
          <span className="rounded-full bg-white/20 px-2 text-xs">{checked.size}</span>
        </Button>
        <Button variant="secondary" onClick={() => { setAnalysis(null); setAnalysisId(null); setFile(null); }}>
          <X className="h-4 w-4" /> Cancelar
        </Button>
        <span className="text-sm text-muted-foreground">{checked.size} producto(s) seleccionado(s)</span>
      </div>
    </div>
  );
}
