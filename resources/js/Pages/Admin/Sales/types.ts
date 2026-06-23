export type SaleRow = {
  sale_id: number;
  invoice_number: string;
  customer: string;
  customer_email: string | null;
  sale_date_label: string;
  status: string;
  status_label: string;
  payment_method: string;
  payment_label: string;
  total: number;
};

export type SalesKpis = {
  dailySales: number;
  dailySalesTrend: number;
  dailyTransactions: number;
  dailyTransactionsTrend: number;
  refunds: number;
  refundsTrend: number;
};

export type SalesFilters = {
  status: string;
  date_range: string;
  payment_method: string;
  search: string;
  date_from: string;
  date_to: string;
};

export type SaleProductOption = {
  product_id: number;
  name: string;
  sku: string;
  unit_price: number;
  stock: number | null;
};

export type SaleDetailItem = {
  id: number;
  product_id: number;
  quantity: number;
  unit_price: number;
  total: number;
  product: { product_id: number; name: string; sku: string } | null;
};

export type SaleDetail = {
  sale_id: number;
  invoice_number: string | null;
  status: string;
  payment_method: string;
  payment_reference: string | null;
  subtotal: number;
  discount: number;
  total: number;
  notes: string | null;
  order_source: string | null;
  sale_date_label: string | null;
  order_placed_at_label: string | null;
  confirmed_at_label: string | null;
  returned_at: string | null;
  returned_by: { name: string } | null;
  buyer: { name: string | null; email: string | null } | null;
  client: {
    user_id: number;
    name: string;
    first_surname: string | null;
    second_surname: string | null;
    gmail: string | null;
  } | null;
  sale_items: SaleDetailItem[];
};
