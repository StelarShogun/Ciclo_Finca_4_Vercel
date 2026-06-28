import { api } from "@/lib/api/client";

export type ParentOption = { category_id: number; name: string };

export type HierarchyRow = {
  category_id: number;
  name: string;
  parent_name: string | null;
  is_parent: boolean;
};

export type CategoryTree = { parents: ParentOption[]; hierarchy: HierarchyRow[] };

export async function getCategories(): Promise<CategoryTree> {
  const { data } = await api.get("/api/v1/admin/categories");
  return data.data as CategoryTree;
}

export async function createParentCategory(name: string, description?: string) {
  const { data } = await api.post("/api/v1/admin/categories/parent", {
    name,
    description: description || null,
  });
  return data;
}

export async function createSubcategory(
  name: string,
  parentCategoryId: number,
  description?: string,
) {
  const { data } = await api.post("/api/v1/admin/categories/subcategory", {
    name,
    parent_category_id: parentCategoryId,
    description: description || null,
  });
  return data;
}
