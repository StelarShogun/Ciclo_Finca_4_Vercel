"use client";

import { useEffect, useState } from "react";
import { keepPreviousData, useQuery } from "@tanstack/react-query";
import type { ColumnDef } from "@tanstack/react-table";
import { Search } from "lucide-react";

import { getAuditLogs, type AuditLog } from "@/lib/api/admin/audit";
import { PageHeader } from "@/components/admin/page-header";
import { DataTable } from "@/components/admin/data-table";
import { PaginationControls } from "@/components/admin/pagination-controls";
import { StatusBadge } from "@/components/admin/status-badge";
import { Card, CardContent } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Skeleton } from "@/components/ui/skeleton";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";

const ALL = "all";

const columns: ColumnDef<AuditLog>[] = [
  { accessorKey: "created_at", header: "Fecha" },
  { accessorKey: "user", header: "Usuario", cell: ({ row }) => <span className="text-sm">{row.original.user}</span> },
  {
    accessorKey: "module_label",
    header: "Módulo",
    cell: ({ row }) => <StatusBadge tone="neutral">{row.original.module_label}</StatusBadge>,
  },
  { accessorKey: "action_label", header: "Acción" },
  {
    accessorKey: "description",
    header: "Descripción",
    cell: ({ row }) => <span className="text-sm text-muted-foreground">{row.original.description}</span>,
  },
];

export default function AuditPage() {
  const [page, setPage] = useState(1);
  const [user, setUser] = useState("");
  const [debounced, setDebounced] = useState("");
  const [module, setModule] = useState(ALL);
  const [actionType, setActionType] = useState(ALL);

  useEffect(() => {
    const t = setTimeout(() => setDebounced(user), 350);
    return () => clearTimeout(t);
  }, [user]);

  const filters = {
    user: debounced,
    module: module === ALL ? "" : module,
    action_type: actionType === ALL ? "" : actionType,
  };

  const { data, isLoading, isError } = useQuery({
    queryKey: ["admin-audit", page, filters],
    queryFn: () => getAuditLogs({ page, ...filters }),
    placeholderData: keepPreviousData,
  });

  return (
    <>
      <PageHeader title="Auditoría" description="Bitácora de acciones administrativas." />

      <div className="mb-4 flex flex-wrap items-center gap-3">
        <div className="relative w-full max-w-xs">
          <Search className="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
          <Input
            placeholder="Buscar por usuario…"
            className="pl-8"
            value={user}
            onChange={(e) => {
              setUser(e.target.value);
              setPage(1);
            }}
          />
        </div>
        <Select value={module} onValueChange={(v) => { setModule(v); setPage(1); }}>
          <SelectTrigger className="w-44" size="sm"><SelectValue placeholder="Módulo" /></SelectTrigger>
          <SelectContent>
            <SelectItem value={ALL}>Todos los módulos</SelectItem>
            {(data?.moduleOptions ?? []).map((o) => (
              <SelectItem key={o.value} value={o.value}>{o.label}</SelectItem>
            ))}
          </SelectContent>
        </Select>
        <Select value={actionType} onValueChange={(v) => { setActionType(v); setPage(1); }}>
          <SelectTrigger className="w-52" size="sm"><SelectValue placeholder="Acción" /></SelectTrigger>
          <SelectContent>
            <SelectItem value={ALL}>Todas las acciones</SelectItem>
            {(data?.actionTypeOptions ?? []).map((o) => (
              <SelectItem key={o.value} value={o.value}>{o.label}</SelectItem>
            ))}
          </SelectContent>
        </Select>
      </div>

      {isLoading ? (
        <Skeleton className="h-96" />
      ) : isError || !data ? (
        <Card>
          <CardContent className="py-8 text-center text-sm text-muted-foreground">
            No fue posible cargar la bitácora.
          </CardContent>
        </Card>
      ) : (
        <>
          <DataTable columns={columns} data={data.logs} emptyTitle="Sin registros" />
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
