"use client";

import { useEffect, useState } from "react";
import { keepPreviousData, useQuery } from "@tanstack/react-query";
import { ArrowLeft, Search } from "lucide-react";

import { getInventoryMovements, type MovementProduct } from "@/lib/api/admin/reports";
import { getMovements } from "@/lib/api/admin/inventory";
import { ReportHeader } from "@/components/admin/reports/report-header";
import { ActionBar, ActionBtn } from "@/components/admin/action-btn";
import { PaginationControls } from "@/components/admin/pagination-controls";
import { StatusBadge, type StatusTone } from "@/components/admin/status-badge";
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

const nf = new Intl.NumberFormat("es-CR");

const TYPE_OPTIONS = [
  { value: "entrada", label: "Entrada" },
  { value: "salida", label: "Salida" },
  { value: "ajuste", label: "Ajuste" },
  { value: "devolucion", label: "Devolución" },
  { value: "cancelado", label: "Cancelado" },
];

const ORIGIN_OPTIONS = [
  { value: "sale_admin", label: "Venta (admin)" },
  { value: "sale_web", label: "Venta web" },
  { value: "return", label: "Devolución de venta" },
  { value: "cancellation", label: "Cancelación de encargo" },
  { value: "provider", label: "Entrada de proveedor" },
  { value: "manual_adjustment", label: "Ajuste manual" },
];

function toTone(badge: string): StatusTone {
  if (badge === "success" || badge === "warning" || badge === "danger" || badge === "info") return badge;
  return "neutral";
}

function FilterButtons({
  value,
  options,
  onChange,
}: {
  value: string;
  options: { value: string; label: string }[];
  onChange: (v: string) => void;
}) {
  return (
    <div className="flex flex-wrap gap-1.5">
      {[{ value: "", label: "Todos" }, ...options].map((o) => (
        <Button
          key={o.value || "__all__"}
          size="sm"
          variant={value === o.value ? "default" : "outline"}
          className="h-7 px-2.5 text-xs"
          onClick={() => onChange(o.value)}
        >
          {o.label}
        </Button>
      ))}
    </div>
  );
}

function ProductMovements({ product, onBack }: { product: MovementProduct; onBack: () => void }) {
  const [type, setType] = useState("");
  const [origin, setOrigin] = useState("");
  const [dateFrom, setDateFrom] = useState("");
  const [dateTo, setDateTo] = useState("");
  const [page, setPage] = useState(1);

  const { data, isLoading } = useQuery({
    queryKey: ["report-movements-detail", product.product_id, type, origin, dateFrom, dateTo, page],
    queryFn: () =>
      getMovements(product.product_id, { page, type, origin, date_from: dateFrom, date_to: dateTo }),
    placeholderData: keepPreviousData,
  });

  const movements = data?.data ?? [];
  const summary = data?.summary ?? { total_entradas: 0, total_salidas: 0 };
  const meta = data?.meta;

  function clearFilters() {
    setType("");
    setOrigin("");
    setDateFrom("");
    setDateTo("");
    setPage(1);
  }

  return (
    <>
      <ReportHeader
        title={product.name}
        icon="fa-clock-rotate-left"
        description={
          <>
            SKU {product.sku} · Historial de entradas, salidas y devoluciones · Stock actual:{" "}
            <strong>{nf.format(product.stock_current)}</strong> unid.
          </>
        }
        actions={
          <Button
            variant="outline"
            className="border-white/25 bg-white/10 text-white hover:bg-white/20 hover:text-white"
            onClick={onBack}
          >
            <ArrowLeft className="h-4 w-4" /> Volver al listado
          </Button>
        }
      />

      <div className="grid gap-4 lg:grid-cols-[17rem_1fr]">
        <Card className="h-fit">
          <CardContent className="space-y-4 p-4">
            <div>
              <p className="mb-1.5 text-sm font-semibold">Tipo</p>
              <FilterButtons value={type} options={TYPE_OPTIONS} onChange={(v) => { setType(v); setPage(1); }} />
            </div>
            <div>
              <p className="mb-1.5 text-sm font-semibold">Origen</p>
              <FilterButtons value={origin} options={ORIGIN_OPTIONS} onChange={(v) => { setOrigin(v); setPage(1); }} />
            </div>
            <div>
              <p className="mb-1.5 text-sm font-semibold">Rango de fechas</p>
              <div className="space-y-2">
                <div className="space-y-1">
                  <Label htmlFor="mov-from">Desde</Label>
                  <Input id="mov-from" type="date" value={dateFrom} onChange={(e) => { setDateFrom(e.target.value); setPage(1); }} />
                </div>
                <div className="space-y-1">
                  <Label htmlFor="mov-to">Hasta</Label>
                  <Input id="mov-to" type="date" value={dateTo} onChange={(e) => { setDateTo(e.target.value); setPage(1); }} />
                </div>
              </div>
            </div>
            <Button variant="outline" size="sm" className="w-full" onClick={clearFilters}>
              Limpiar filtros
            </Button>
          </CardContent>
        </Card>

        <div className="space-y-4">
          <div className="grid gap-3 sm:grid-cols-3">
            <Card>
              <CardContent className="p-4">
                <p className="text-sm font-medium text-muted-foreground">Movimientos</p>
                <p className="text-2xl font-bold">{nf.format(meta?.total ?? 0)}</p>
              </CardContent>
            </Card>
            <Card className="border-[#0E9558]/40">
              <CardContent className="p-4">
                <p className="text-sm font-medium text-muted-foreground">Unidades entradas</p>
                <p className="text-2xl font-bold text-[#0E9558] dark:text-[#2ED27E]">
                  {nf.format(summary.total_entradas)}
                </p>
              </CardContent>
            </Card>
            <Card className="border-[#d32f2f]/40">
              <CardContent className="p-4">
                <p className="text-sm font-medium text-muted-foreground">Unidades salidas</p>
                <p className="text-2xl font-bold text-[#d32f2f] dark:text-[#F87171]">
                  {nf.format(summary.total_salidas)}
                </p>
              </CardContent>
            </Card>
          </div>

          {isLoading && !data ? (
            <Skeleton className="h-72" />
          ) : (
            <>
              <Card>
                <CardContent className="p-0">
                  <Table>
                    <TableHeader>
                      <TableRow>
                        <TableHead>Fecha y hora</TableHead>
                        <TableHead>Tipo</TableHead>
                        <TableHead>Origen</TableHead>
                        <TableHead className="text-right">Cantidad</TableHead>
                        <TableHead className="text-right">Stock antes</TableHead>
                        <TableHead className="text-right">Stock después</TableHead>
                        <TableHead>Administrador</TableHead>
                        <TableHead>Motivo</TableHead>
                      </TableRow>
                    </TableHeader>
                    <TableBody>
                      {movements.length === 0 ? (
                        <TableRow>
                          <TableCell colSpan={8} className="py-8 text-center text-sm text-muted-foreground">
                            No hay movimientos para los filtros seleccionados.
                          </TableCell>
                        </TableRow>
                      ) : movements.map((m) => (
                        <TableRow key={m.id}>
                          <TableCell>{m.created_at_human}</TableCell>
                          <TableCell>
                            <StatusBadge tone={toTone(m.type_badge)}>{m.type_label}</StatusBadge>
                          </TableCell>
                          <TableCell>{m.origin_label ?? "—"}</TableCell>
                          <TableCell className="text-right">{nf.format(m.quantity)}</TableCell>
                          <TableCell className="text-right">{nf.format(m.stock_before)}</TableCell>
                          <TableCell className="text-right">{nf.format(m.stock_after)}</TableCell>
                          <TableCell>{m.admin?.name ?? <span className="text-muted-foreground">Automático</span>}</TableCell>
                          <TableCell className="text-muted-foreground">{m.reason ?? "—"}</TableCell>
                        </TableRow>
                      ))}
                    </TableBody>
                  </Table>
                </CardContent>
              </Card>
              {meta && (
                <PaginationControls
                  currentPage={meta.current_page}
                  lastPage={meta.last_page}
                  total={meta.total}
                  perPage={meta.per_page}
                  onPageChange={setPage}
                />
              )}
            </>
          )}
        </div>
      </div>
    </>
  );
}

export default function MovementsReport() {
  const [search, setSearch] = useState("");
  const [debounced, setDebounced] = useState("");
  const [page, setPage] = useState(1);
  const [selected, setSelected] = useState<MovementProduct | null>(null);

  useEffect(() => {
    const t = setTimeout(() => setDebounced(search), 350);
    return () => clearTimeout(t);
  }, [search]);

  const { data, isLoading } = useQuery({
    queryKey: ["report-movements", debounced, page],
    queryFn: () => getInventoryMovements(debounced, page),
    placeholderData: keepPreviousData,
    enabled: !selected,
  });

  if (selected) {
    return <ProductMovements product={selected} onBack={() => setSelected(null)} />;
  }

  return (
    <>
      <ReportHeader
        title="Movimientos de inventario"
        icon="fa-clock-rotate-left"
        description="Selecciona un producto para consultar su historial de entradas, salidas y devoluciones."
      />

      <div className="mb-4 max-w-xs">
        <div className="relative">
          <Search className="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
          <Input
            type="search"
            placeholder="Nombre o SKU…"
            className="pl-8"
            value={search}
            onChange={(e) => { setSearch(e.target.value); setPage(1); }}
          />
        </div>
      </div>

      {isLoading || !data ? (
        <Skeleton className="h-96" />
      ) : (
        <>
          <Card>
            <CardContent className="p-0">
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>SKU</TableHead>
                    <TableHead>Producto</TableHead>
                    <TableHead>Categoría</TableHead>
                    <TableHead>Proveedor</TableHead>
                    <TableHead>Estado stock</TableHead>
                    <TableHead className="text-right">Stock actual</TableHead>
                    <TableHead className="w-20 text-right">Acciones</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {data.products.length === 0 ? (
                    <TableRow>
                      <TableCell colSpan={7} className="py-8 text-center text-sm text-muted-foreground">
                        Ningún producto coincide con la búsqueda.
                      </TableCell>
                    </TableRow>
                  ) : data.products.map((p) => (
                    <TableRow key={p.product_id}>
                      <TableCell><strong>{p.sku}</strong></TableCell>
                      <TableCell className="font-medium">{p.name}</TableCell>
                      <TableCell>{p.category_name}</TableCell>
                      <TableCell>
                        {p.supplier_name ? (
                          <span>
                            <i className="fas fa-truck-fast mr-1.5 text-xs opacity-60" aria-hidden />
                            {p.supplier_name}
                          </span>
                        ) : (
                          <span className="text-muted-foreground">—</span>
                        )}
                      </TableCell>
                      <TableCell>
                        <StatusBadge tone={toTone(p.stock_badge_class)}>{p.stock_label}</StatusBadge>
                      </TableCell>
                      <TableCell className="text-right">
                        <strong>{nf.format(p.stock_current)}</strong>{" "}
                        <span className="text-xs text-muted-foreground">unid.</span>
                      </TableCell>
                      <TableCell>
                        <div className="flex justify-end">
                          <ActionBar>
                            <ActionBtn icon="fa-clock-rotate-left" label="Ver movimientos" tone="view" onClick={() => setSelected(p)} />
                          </ActionBar>
                        </div>
                      </TableCell>
                    </TableRow>
                  ))}
                </TableBody>
              </Table>
            </CardContent>
          </Card>
          <PaginationControls
            currentPage={data.pagination.currentPage}
            lastPage={data.pagination.lastPage}
            total={data.pagination.total}
            onPageChange={setPage}
          />
        </>
      )}
    </>
  );
}
