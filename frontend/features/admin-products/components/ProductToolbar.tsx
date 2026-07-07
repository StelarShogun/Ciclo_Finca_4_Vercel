"use client";

import { Search } from "lucide-react";

import { ViewToggle, type ViewMode } from "@/components/admin/view-toggle";
import { Input } from "@/components/ui/input";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { ALL_PRODUCTS_FILTER } from "../hooks/useAdminProductsPage";

type ProductToolbarProps = {
  search: string;
  status: string;
  stockStatus: string;
  view: ViewMode;
  onSearchChange: (value: string) => void;
  onStatusChange: (value: string) => void;
  onStockStatusChange: (value: string) => void;
  onViewChange: (value: ViewMode) => void;
};

export function ProductToolbar({
  search,
  status,
  stockStatus,
  view,
  onSearchChange,
  onStatusChange,
  onStockStatusChange,
  onViewChange,
}: ProductToolbarProps) {
  return (
    <div className="mb-4 flex flex-wrap items-center gap-3">
      <div className="relative w-full max-w-xs">
        <Search className="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
        <Input
          placeholder="Buscar por nombre o descripción…"
          className="pl-8"
          value={search}
          onChange={(event) => onSearchChange(event.target.value)}
        />
      </div>
      <Select value={status} onValueChange={onStatusChange}>
        <SelectTrigger className="w-44" size="sm">
          <SelectValue placeholder="Estado" />
        </SelectTrigger>
        <SelectContent>
          <SelectItem value={ALL_PRODUCTS_FILTER}>Todos los estados</SelectItem>
          <SelectItem value="active">Activo</SelectItem>
          <SelectItem value="inactive">Inactivo</SelectItem>
          <SelectItem value="out_of_stock">Sin stock</SelectItem>
          <SelectItem value="discontinued">Descontinuado</SelectItem>
        </SelectContent>
      </Select>
      <Select value={stockStatus} onValueChange={onStockStatusChange}>
        <SelectTrigger className="w-44" size="sm">
          <SelectValue placeholder="Stock" />
        </SelectTrigger>
        <SelectContent>
          <SelectItem value={ALL_PRODUCTS_FILTER}>Todo el stock</SelectItem>
          <SelectItem value="in-stock">En stock</SelectItem>
          <SelectItem value="low">Stock bajo</SelectItem>
          <SelectItem value="out">Sin stock</SelectItem>
        </SelectContent>
      </Select>
      <div className="ml-auto">
        <ViewToggle view={view} onChange={onViewChange} />
      </div>
    </div>
  );
}
