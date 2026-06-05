/**
 * Full-page pagination: jump-to-page for x-pagination toolbars outside AJAX fragments.
 */
import { asInput } from '@/shared/legacy-dom';

function bindClientPaginationToolbar(toolbar: HTMLElement): void {
    if (toolbar.dataset.cf4ClientPaginationBound === '1') {
        return;
    }
    if (toolbar.closest('[data-cf4-ajax-pagination]')) {
        return;
    }
    toolbar.dataset.cf4ClientPaginationBound = '1';

    const goBtn = toolbar.querySelector('.pagination-go-button');
    const goInput = asInput(toolbar.querySelector('.pagination-go-input'));
    if (!goBtn || !goInput) {
        return;
    }

    const navigate = () => {
        const lastPage = Math.max(
            1,
            parseInt(String(toolbar.getAttribute('data-last-page') || '1'), 10) || 1,
        );
        let target = parseInt(String(goInput.value || '1').trim(), 10);
        if (Number.isNaN(target)) {
            target = 1;
        }
        target = Math.max(1, Math.min(lastPage, target));
        const url = new URL(window.location.href);
        url.searchParams.set('page', String(target));
        window.location.assign(url.toString());
    };

    goBtn.addEventListener('click', (e) => {
        e.preventDefault();
        navigate();
    });

    goInput.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            navigate();
        }
    });
}

export function initClientPagination(): void {
    document.querySelectorAll<HTMLElement>('.cf4-pagination-toolbar').forEach(bindClientPaginationToolbar);
}

document.addEventListener('DOMContentLoaded', initClientPagination);
