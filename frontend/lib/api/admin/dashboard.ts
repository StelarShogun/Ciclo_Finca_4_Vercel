import { api } from "@/lib/api/client";

export type RecentSale = {
  id: number;
  invoice: string;
  client: string;
  total: number;
  dateShort: string;
  dateFull: string;
  statusClass: string;
  statusShort: string;
  statusTitle: string;
};

export type LowStockRow = {
  id: number;
  name: string;
  sku: string;
  category: string;
  stock: number;
};

export type SalesByDay = { date: string; total: number };
export type CategoryRow = { label: string; total: number };
export type TopProduct = { name: string; units: number; revenue: number };

export type DashboardData = {
  totalProducts: number;
  totalSuppliers: number;
  totalCategories: number;
  todaySales: number;
  lowStockProducts: number;
  salesTrend: number;
  monthlySales: number;
  monthlyTrend: number;
  recentSales: RecentSale[];
  lowStockList: LowStockRow[];
  salesByDay: SalesByDay[];
  salesRange: string;
  salesFrom: string | null;
  salesTo: string | null;
  productsByCategory: CategoryRow[];
  topProducts: TopProduct[];
  error: string | null;
};

export type SalesRange = "last7" | "last15" | "last30" | "month" | "custom";

export const SALES_RANGES: { value: SalesRange; label: string }[] = [
  { value: "last7", label: "Últimos 7 días" },
  { value: "last15", label: "Últimos 15 días" },
  { value: "last30", label: "Últimos 30 días" },
  { value: "month", label: "Este mes" },
  { value: "custom", label: "Personalizado" },
];

export async function getDashboard(params: {
  range?: string;
  from?: string;
  to?: string;
}): Promise<DashboardData> {
  const { data } = await api.get("/api/v1/admin/dashboard", { params });
  return data.data as DashboardData;
}
