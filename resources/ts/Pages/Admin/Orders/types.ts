export type OrderRow = {
  sale_id: number;
  invoice_number: string | null;
  reference: string;
  customer: string;
  customer_email: string | null;
  order_placed_label: string;
  ready_label: string;
  confirmed_label: string;
  status: string;
  status_label: string;
  total: number;
};

export type OrdersFilters = {
  status: string;
  date_range: string;
  date_from: string;
  date_to: string;
  search: string;
};
