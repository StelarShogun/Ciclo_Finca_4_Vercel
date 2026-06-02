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
