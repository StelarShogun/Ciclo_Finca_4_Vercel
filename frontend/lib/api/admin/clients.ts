import { api } from "@/lib/api/client";

export type AdminClient = {
  user_id: number;
  name: string;
  first_surname: string;
  second_surname: string | null;
  gmail: string;
  created_at: string | null;
  updated_at: string | null;
  active: boolean;
};

export type ClientsPagination = {
  currentPage: number;
  lastPage: number;
  perPage: number;
  total: number;
};

export type ClientsIndex = {
  clients: AdminClient[];
  pagination: ClientsPagination;
  filters: Record<string, string>;
  sort: string;
  dir: string;
};

export type ClientOrderRow = {
  sale_id: number;
  invoice_number: string;
  sale_date: string;
  total: string;
};

export type ClientHistory = {
  clientId: number;
  displayName: string;
  gmail: string;
  orders: ClientOrderRow[];
};

export type ClientsListParams = {
  page?: number;
  search?: string;
  status?: string;
  sort?: string;
  dir?: string;
};

export async function getClients(params: ClientsListParams): Promise<ClientsIndex> {
  const clean = Object.fromEntries(
    Object.entries(params).filter(([, v]) => v !== "" && v != null),
  );
  const { data } = await api.get("/api/v1/admin/clients", { params: clean });
  return data.data as ClientsIndex;
}

export async function getClientHistory(id: number): Promise<ClientHistory> {
  const { data } = await api.get(`/api/v1/admin/clients/${id}`);
  return data.data as ClientHistory;
}

export async function banClient(id: number) {
  const { data } = await api.post(`/api/v1/admin/clients/${id}/ban`);
  return data;
}

export async function unbanClient(id: number) {
  const { data } = await api.post(`/api/v1/admin/clients/${id}/unban`);
  return data;
}
