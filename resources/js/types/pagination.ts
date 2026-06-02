export type ClientListPaginationLink = {
  url: string | null;
  label: string;
  active: boolean;
  page?: number | null;
};

export type ClientListPagination = {
  currentPage: number;
  lastPage: number;
  total: number;
  from?: number | null;
  to?: number | null;
  links: ClientListPaginationLink[];
};

/** Paginator payload for Inertia list pages (catalog, cart, reviews, etc.). */
export type InertiaListPagination = {
  currentPage: number;
  lastPage: number;
  perPage?: number;
  total: number;
  from?: number | null;
  to?: number | null;
  links: Array<{
    url?: string | null;
    label: string;
    active: boolean;
    page?: number | null;
  }>;
};
