/**
 * Responsive behavior for tables using .admin-table (CF4-140).
 */

const SCROLL_PARENT_SELECTORS = [
    '.sales-table-container',
    '.table-wrap',
    '.clients-table-wrapper',
    '.products-table.table-view',
    '.inv-table-wrap',
    '.items-table-wrap',
    '.xml-review-table-wrap',
    '.table-section',
    '.table-content',
    '.table-scroll-wrapper',
    '.sale-details .detail-section',
    '.cf4-invoices-card .sales-table-container',
].join(', ');

function headerLabel(th: HTMLTableCellElement): string {
    const aria = th.getAttribute('aria-label');
    if (aria?.trim()) {
        return aria.trim();
    }
    const text = (th.textContent || '').replace(/\s+/g, ' ').trim();
    if (text) {
        return text;
    }
    if (
        th.classList.contains('admin-table__col--actions')
        || th.classList.contains('col-actions')
    ) {
        return 'Acciones';
    }
    return '';
}

function enhanceOneTable(table: HTMLTableElement): void {
    const headers = [...table.querySelectorAll<HTMLTableCellElement>('thead th')].map(headerLabel);

    if (headers.length === 0) {
        return;
    }

    table.querySelectorAll('tbody tr').forEach((row) => {
        row.querySelectorAll('td').forEach((cell, index) => {
            const label = headers[index];
            if (!label) {
                return;
            }
            if (cell.dataset.label !== label) {
                cell.dataset.label = label;
            }
        });
    });

    const parent = table.parentElement;
    if (parent?.matches(SCROLL_PARENT_SELECTORS)) {
        parent.classList.add('admin-table-scroll');
    }
}

export function enhanceAdminTables(root: ParentNode = document): void {
    root.querySelectorAll<HTMLTableElement>('table.admin-table').forEach(enhanceOneTable);
}

let debounceTimer: ReturnType<typeof setTimeout> | null = null;

function scheduleEnhance(): void {
    if (debounceTimer) {
        clearTimeout(debounceTimer);
    }
    debounceTimer = setTimeout(() => {
        debounceTimer = null;
        enhanceAdminTables(document);
    }, 80);
}

export function initAdminTableResponsive(): void {
    const run = () => enhanceAdminTables(document);

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', run, { once: true });
    } else {
        run();
    }

    document.addEventListener('cf4:ajax-pagination:loaded', run);

    const observer = new MutationObserver(scheduleEnhance);
    observer.observe(document.body, { childList: true, subtree: true });
}

initAdminTableResponsive();
