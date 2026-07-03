"use client";

import { useMemo, useState } from "react";
import Link from "next/link";
import { useQuery } from "@tanstack/react-query";

import {
  getExportsConfig,
  type ExportDef,
  type ExportFilterDef,
  type FilterOption,
} from "@/lib/api/admin/reports";
import { ReportHeader } from "@/components/admin/reports/report-header";
import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Skeleton } from "@/components/ui/skeleton";
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
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";

const CHIP_META: Record<string, { label: string; icon?: string }> = {
  pdf: { label: "PDF", icon: "fa-file-pdf" },
  excel: { label: "Excel", icon: "fa-file-excel" },
  csv: { label: "CSV", icon: "fa-file-csv" },
  bundle: { label: "ZIP", icon: "fa-file-zipper" },
  json: { label: "JSON", icon: "fa-file-code" },
  xml: { label: "XML", icon: "fa-file-code" },
};

const PDF_GROUP = ["dashboard", "inventory", "productSales", "sales"];
const REGISTRY_GROUP = [
  "registry.suppliers",
  "registry.brands",
  "registry.supplierOrders",
  "registry.users",
  "registry.clientOrders",
];

function buildUrl(
  def: ExportDef,
  format: string,
  scope: string,
  values: Record<string, string>,
): string {
  const params = new URLSearchParams();
  if (def.formatMode === "query") params.set("format", format);
  if (scope === "all") {
    params.set("scope", "all");
  } else {
    for (const field of def.filters ?? []) {
      const v = values[field.name];
      if (v !== undefined && v !== null && String(v).trim() !== "") params.set(field.name, String(v));
    }
  }
  for (const [k, v] of Object.entries(def.staticParams ?? {})) {
    if (v !== undefined && v !== null && String(v) !== "") params.set(k, String(v));
  }
  const base = def.baseUrls[format];
  if (!base) throw new Error(`Missing base URL for ${def.id} ${format}`);
  const url = new URL(base, window.location.origin);
  for (const [k, v] of new URLSearchParams(url.search)) {
    if (!params.has(k)) params.set(k, v);
  }
  url.search = params.toString();
  return url.toString();
}

export default function ExportsReport() {
  const { data, isLoading, isError } = useQuery({
    queryKey: ["admin-report-exports-config"],
    queryFn: getExportsConfig,
  });

  const exportsMap = useMemo(() => data?.exports ?? {}, [data]);
  const [active, setActive] = useState<{ exportId: string; format: string } | null>(null);
  const [scope, setScope] = useState<"all" | "filtered">("all");
  const [values, setValues] = useState<Record<string, string>>({});

  const activeDef = active ? exportsMap[active.exportId] : null;

  function openModal(exportId: string, format: string) {
    const def = exportsMap[exportId];
    if (!def) return;
    const init: Record<string, string> = {};
    for (const field of def.filters ?? []) {
      init[field.name] = def.initialValues?.[field.name] ?? field.default ?? "";
    }
    for (const field of def.filters ?? []) {
      if (field.autofills && field.autofillData) {
        const autofill = field.autofillData[init[field.name]] ?? {};
        for (const target of field.autofills) init[target] = autofill[target] ?? init[target] ?? "";
      }
    }
    setValues(init);
    setScope("all");
    setActive({ exportId, format });
  }

  function changeField(field: ExportFilterDef, value: string) {
    setValues((prev) => {
      const next = { ...prev, [field.name]: value };
      if (field.cascades) next[field.cascades] = "";
      if (field.autofills && field.autofillData) {
        const autofill = field.autofillData[value] ?? {};
        for (const target of field.autofills) next[target] = autofill[target] ?? "";
      }
      return next;
    });
  }

  function childOptions(def: ExportDef, childName: string): FilterOption[] | null {
    const parent = (def.filters ?? []).find((f) => f.cascades === childName && f.cascadeOptions);
    if (!parent?.cascadeOptions) return null;
    const parentVal = values[parent.name] ?? "";
    return parent.cascadeOptions[parentVal] ?? [{ value: "", label: "Todas" }];
  }

  function submit() {
    if (!activeDef || !active) return;
    const url = buildUrl(activeDef, active.format, scope, values);
    window.open(url, "_blank", "noopener,noreferrer");
    setActive(null);
  }

  const pdfCards = PDF_GROUP.map((id) => exportsMap[id]).filter(Boolean);
  const registryCards = REGISTRY_GROUP.map((id) => exportsMap[id]).filter(Boolean);

  function renderChips(def: ExportDef) {
    const formats = Object.keys(def.baseUrls);
    return (
      <span className="flex flex-wrap justify-end gap-1.5">
        {formats.map((fmt, i) => {
          const meta = CHIP_META[fmt] ?? { label: fmt.toUpperCase() };
          return (
            <Button
              key={fmt}
              size="sm"
              variant={i === 0 ? "default" : "outline"}
              className="h-7 gap-1.5 px-2.5 text-xs"
              onClick={() => openModal(def.id, fmt)}
            >
              {meta.icon ? <i className={`fas ${meta.icon}`} aria-hidden /> : null}
              {meta.label}
            </Button>
          );
        })}
      </span>
    );
  }

  function renderList(defs: ExportDef[]) {
    return (
      <ul className="divide-y">
        {defs.map((def) => (
          <li key={def.id} className="flex flex-wrap items-center justify-between gap-2 py-2.5">
            <span className="text-sm font-medium">{def.title}</span>
            {renderChips(def)}
          </li>
        ))}
      </ul>
    );
  }

  return (
    <>
      <ReportHeader
        title="Exportación de datos"
        icon="fa-file-arrow-down"
        description={
          <>
            Descarga reportes y listados administrativos en PDF, Excel o XML desde un solo lugar.
            Puedes exportar información del dashboard, inventario, ventas, productos más vendidos y
            registros administrativos.
          </>
        }
      />

      {isLoading ? (
        <Skeleton className="h-96" />
      ) : isError || !data ? (
        <Card>
          <CardContent className="py-8 text-center text-sm text-muted-foreground">
            No fue posible cargar la configuración de exportaciones.
          </CardContent>
        </Card>
      ) : (
        <>
          <div className="grid gap-4 lg:grid-cols-2">
            <Card>
              <CardContent className="p-5">
                <h2 className="mb-2 text-base font-semibold">Reportes en PDF y Excel</h2>
                {renderList(pdfCards)}
              </CardContent>
            </Card>
            <Card>
              <CardContent className="p-5">
                <h2 className="mb-1 text-base font-semibold">Listados administrativos</h2>
                <p className="mb-2 text-xs text-muted-foreground">
                  Proveedores, marcas, pedidos a proveedores, usuarios y encargos. Excel o PDF; en
                  pedidos y encargos valen los mismos filtros que en sus pantallas.
                </p>
                {renderList(registryCards)}
              </CardContent>
            </Card>
          </div>

          <p className="mt-4 text-sm text-muted-foreground">
            Para importar productos use el botón <strong>Importar</strong> en{" "}
            <Link href="/admin/inventory" className="underline underline-offset-2">Inventario</Link>.
          </p>
        </>
      )}

      {/* Modal de exportación: alcance + filtros dinámicos */}
      <Dialog open={!!active} onOpenChange={(o) => !o && setActive(null)}>
        <DialogContent className="max-h-[90vh] sm:max-w-[38rem] overflow-y-auto">
          <DialogHeader>
            <DialogTitle className="flex items-center gap-2">
              <i className="fas fa-file-arrow-down text-[#235347] dark:text-[#8EB69B]" aria-hidden />
              {activeDef?.title ?? "Exportar"}
            </DialogTitle>
            {activeDef?.subtitle ? <DialogDescription>{activeDef.subtitle}</DialogDescription> : null}
          </DialogHeader>

          <fieldset className="flex gap-6" aria-label="Alcance de la exportación">
            <label className="flex items-center gap-2 text-sm">
              <input type="radio" name="scope" className="accent-[#235347] dark:accent-[#8EB69B]" checked={scope === "all"} onChange={() => setScope("all")} />
              Todo
            </label>
            <label className="flex items-center gap-2 text-sm">
              <input type="radio" name="scope" className="accent-[#235347] dark:accent-[#8EB69B]" checked={scope === "filtered"} onChange={() => setScope("filtered")} />
              Con filtros
            </label>
          </fieldset>

          {scope === "filtered" && activeDef ? (
            <div className="grid gap-3 sm:grid-cols-2">
              {(activeDef.filters ?? []).map((field) => {
                const opts =
                  field.type === "select"
                    ? childOptions(activeDef, field.name) ?? field.options ?? []
                    : null;
                return (
                  <div key={field.name} className="space-y-1.5">
                    <Label htmlFor={`export-field-${field.name}`}>{field.label}</Label>
                    {field.help ? (
                      <p className="text-xs text-muted-foreground">{field.help}</p>
                    ) : null}
                    {field.type === "select" ? (
                      <Select
                        value={values[field.name] || "__all__"}
                        onValueChange={(v) => changeField(field, v === "__all__" ? "" : v)}
                      >
                        <SelectTrigger id={`export-field-${field.name}`} className="w-full" size="sm">
                          <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                          {(opts ?? []).map((o) => (
                            <SelectItem key={o.value || "__all__"} value={o.value || "__all__"}>
                              {o.label}
                            </SelectItem>
                          ))}
                        </SelectContent>
                      </Select>
                    ) : (
                      <Input
                        id={`export-field-${field.name}`}
                        type={field.type ?? "text"}
                        placeholder={field.placeholder}
                        readOnly={field.readonly}
                        value={values[field.name] ?? ""}
                        onChange={(e) => changeField(field, e.target.value)}
                      />
                    )}
                  </div>
                );
              })}
            </div>
          ) : null}

          <DialogFooter>
            <Button type="button" variant="outline" onClick={() => setActive(null)}>Cancelar</Button>
            <Button type="button" onClick={submit}>Exportar</Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </>
  );
}
