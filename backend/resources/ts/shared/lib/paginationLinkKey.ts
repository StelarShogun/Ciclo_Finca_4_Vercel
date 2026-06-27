type PaginationLinkLike = {
  label: string;
  page?: number | null;
  url?: string | null;
};

export function paginationLinkKey(
  link: PaginationLinkLike,
  kind: 'prev' | 'next' | 'ellipsis' | 'active' | 'page',
  labelText: string,
) {
  if (kind === 'prev') {
    return 'pagination-prev';
  }

  if (kind === 'next') {
    return 'pagination-next';
  }

  if (link.page != null) {
    return `pagination-${kind}-page-${link.page}`;
  }

  return `pagination-${kind}-${labelText}-${link.url ?? 'none'}`;
}
