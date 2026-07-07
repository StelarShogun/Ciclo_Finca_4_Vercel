"use client";

import { Search } from "lucide-react";

import { ViewToggle, type ViewMode } from "@/components/admin/view-toggle";
import { Input } from "@/components/ui/input";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { ALL_ORDER_FILTER } from "../hooks/useAdminOrdersPage";

type OrderToolbarProps = {
  search: string;
  status: string;
  view: ViewMode;
  onSearchChange: (value: string) => void;
  onStatusChange: (value: string) => void;
  onViewChange: (value: ViewMode) => void;
};

export function OrderToolbar({ search, status, view, onSearchChange, onStatusChange, onViewChange }: OrderToolbarProps) {
  return (
    <div className="mb-4 flex flex-wrap items-center gap-3">
      <div className="relative w-full max-w-xs">
        <Search className="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
        <Input
          placeholder="Buscar por referencia o cliente…"
          className="pl-8"
          value={search}
          onChange={(event) => onSearchChange(event.target.value)}
        />
      </div>
      <Select value={status} onValueChange={onStatusChange}>
        <SelectTrigger className="w-48" size="sm">
          <SelectValue placeholder="Estado" />
        </SelectTrigger>
        <SelectContent>
          <SelectItem value={ALL_ORDER_FILTER}>Todos</SelectItem>
          <SelectItem value="pending">Pendiente</SelectItem>
          <SelectItem value="ready_to_pickup">Listo para recoger</SelectItem>
          <SelectItem value="completed">Confirmado</SelectItem>
          <SelectItem value="cancelled">Rechazado</SelectItem>
        </SelectContent>
      </Select>
      <div className="ml-auto">
        <ViewToggle view={view} onChange={onViewChange} />
      </div>
    </div>
  );
}
