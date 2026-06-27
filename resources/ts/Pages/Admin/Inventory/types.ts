export type InventoryProduct = {
  product_id: number;
  name: string;
  sku: string;
  image_url: string;
  uses_placeholder: boolean;
  placeholder_icon: string;
  category_name: string;
  stock: number;
  stock_minimum: number;
  stock_badge_class: string;
  availability_label: string;
  price: number | string;
  status: string;
  status_label: string;
  status_class: string;
  is_featured: boolean;
};

export type CategoryOption = { category_id: number; name: string };
export type SubByParent = Record<string, Array<{ category_id: number; name: string }>>;
export type BrandOption = { id: number; name: string };
export type SupplierOption = { supplier_id: number; name: string };

export type InventoryFilters = {
  search: string;
  parent_category_id: string;
  subcategory_id: string;
  stock_status: string;
  status: string;
};

/** Atributo de clasificación devuelto por /classifications/catalog/{cat}/options */
export type ClassificationAttribute = {
  id: number;
  label: string;
  slug: string;
  values: Array<{ id: number; value: string }>;
};
