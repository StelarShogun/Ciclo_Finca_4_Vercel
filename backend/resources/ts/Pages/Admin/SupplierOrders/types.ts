export type SupplierOrderRow = {
  num_order: number;
  po_short: string;
  po_full: string;
  supplier_id: number | null;
  supplier_name: string | null;
  date_label: string;
  edd_label: string;
  edd_class: string;
  delivered_label: string | null;
  state: string;
  state_label: string;
  initial_total: number;
  received_total: number;
  shorts_total: number;
  has_received_data: boolean;
  has_shorts: boolean;
};

export type SupplierOption = {
  supplier_id: number;
  name: string;
  primary_contact: string | null;
  email: string | null;
  phone: string | null;
};

export type SupplierOrdersFilters = {
  state: string;
  date_range: string;
  date_from: string;
  date_to: string;
  search: string;
};

export type SupplierOrderDetail = {
  num_order: number;
  po_number: string | null;
  supplier: { supplier_id: number; name: string; primary_contact: string | null; email: string | null; phone: string | null } | null;
  products: Array<{ id: number; name: string; quantity: number; received_quantity: number | null; unit_price: number; total: number }>;
  date: string | null;
  estimated_delivery_date: string | null;
  received_at: string | null;
  closed_with_shorts: boolean;
  state: string;
  total: number;
  timeline: Array<{ state: string; changed_at: string; user_name: string; reason: string | null }>;
};

export type SupplierDetail = {
  supplier_id: number;
  name: string;
  primary_contact: string | null;
  phone: string | null;
  email: string | null;
  address: string | null;
  delivery_time: number;
  rating: number;
  status: string;
  products_count: number;
};

export type ProductSearchItem = {
  product_id: number;
  name: string;
  sku: string;
  unit_price: number;
};
