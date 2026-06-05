/**
 * Mirrors App\Support\AdminPerPage::ALLOWED (keep in sync with PHP).
 */
export const ADMIN_PER_PAGE_OPTIONS = [10, 25, 50] as const;

export function normalizeAdminPerPage(value: unknown): number {
    const n = parseInt(String(value ?? '10'), 10);
    return (ADMIN_PER_PAGE_OPTIONS as readonly number[]).includes(n) ? n : 10;
}

/** Reads data-last-page from the rendered pagination toolbar inside a wrapper. */
export function readPaginationLastPage(container: ParentNode | null | undefined): number {
    const toolbar =
        container?.querySelector?.('.cf4-pagination-toolbar[data-last-page]')
        || container?.querySelector?.('.pagination[data-last-page]');

    return Math.max(1, parseInt(String(toolbar?.getAttribute('data-last-page') || '1'), 10) || 1);
}
