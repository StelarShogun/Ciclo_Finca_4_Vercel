import { api } from "@/lib/api/client";

export type AuditLog = {
  id: number;
  created_at: string;
  user: string;
  action_type: string;
  action_label: string;
  module: string;
  module_label: string;
  description: string;
};

export type AuditOption = { value: string; label: string };

export type AuditPagination = {
  currentPage: number;
  lastPage: number;
  perPage: number;
  total: number;
};

export type AuditIndex = {
  logs: AuditLog[];
  pagination: AuditPagination;
  actionTypeOptions: AuditOption[];
  moduleOptions: AuditOption[];
  filters: Record<string, string>;
};

export type AuditListParams = {
  page?: number;
  user?: string;
  action_type?: string;
  module?: string;
  from?: string;
  to?: string;
  dir?: string;
};

export async function getAuditLogs(params: AuditListParams): Promise<AuditIndex> {
  const clean = Object.fromEntries(
    Object.entries({ dir: "desc", ...params }).filter(([, v]) => v !== "" && v != null),
  );
  const { data } = await api.get("/api/v1/admin/audit-logs", { params: clean });
  return data.data as AuditIndex;
}
