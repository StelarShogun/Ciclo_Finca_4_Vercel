/**
 * CF4 — paginación sin recarga completa: sustituye #cf4-list-fragment vía fetch + DOMParser.
 */
import type { AjaxPaginationOptions } from '@/shared/legacy-dom';
import { asInput, asSelect, eventTargetEl } from '@/shared/legacy-dom';

const DEFAULT_FRAGMENT_ID = 'cf4-list-fragment';

let popstateBound = false;

function getFragmentId(root: HTMLElement): string {
    return root.dataset.cf4FragmentId || DEFAULT_FRAGMENT_ID;
}

function scrollToFragment(fragmentId: string): void {
    const el = document.getElementById(fragmentId);
    if (!el) return;
    const scrollRoot = el.closest('[data-cf4-ajax-scroll]') || el;
    scrollRoot.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function syncPaginationQueryFromUrl(url: string, root: HTMLElement): void {
    const parsed = new URL(url, window.location.origin);
    const page = parsed.searchParams.get('page') || '1';
    const perPage = parsed.searchParams.get('per_page');
    const fragment = document.getElementById(getFragmentId(root));

    const catalogPage = asInput(document.getElementById('catalog-list-page'));
    if (catalogPage) {
        catalogPage.value = page;
    }

    const isInsideFragment = (node: Element): boolean =>
        Boolean(fragment && fragment.contains(node));

    if (perPage) {
        document.querySelectorAll('input[name="per_page"]').forEach((node) => {
            const input = asInput(node);
            if (input && !isInsideFragment(input)) {
                input.value = perPage;
            }
        });
        document.querySelectorAll('select[name="per_page"]').forEach((node) => {
            const select = asSelect(node);
            if (select && !isInsideFragment(select)) {
                select.value = perPage;
            }
        });
    }

    document.querySelectorAll('input[type="hidden"][name="page"]').forEach((node) => {
        const input = asInput(node);
        if (!input || isInsideFragment(input)) {
            return;
        }
        if (input.closest('.cf4-pagination-toolbar')) {
            return;
        }
        input.value = page;
    });
}

export async function loadListFragment(
    url: string,
    root: HTMLElement,
    options: AjaxPaginationOptions = {},
): Promise<void> {
    const { pushState = true, scroll = true } = options;
    const fragmentId = getFragmentId(root);
    const current = document.getElementById(fragmentId);
    if (!current) {
        window.location.assign(url);
        return;
    }

    root.classList.add('is-loading');
    try {
        const res = await fetch(url, {
            credentials: 'same-origin',
            headers: {
                Accept: 'text/html',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        if (!res.ok) {
            window.location.assign(url);
            return;
        }

        const html = await res.text();
        const doc = new DOMParser().parseFromString(html, 'text/html');
        const next = doc.getElementById(fragmentId);

        if (!next) {
            window.location.assign(url);
            return;
        }

        current.innerHTML = next.innerHTML;

        syncPaginationQueryFromUrl(url, root);

        if (pushState) {
            window.history.pushState({ cf4AjaxPagination: true }, '', url);
        }
        if (scroll) {
            scrollToFragment(fragmentId);
        }
        document.dispatchEvent(
            new CustomEvent('cf4:ajax-pagination:loaded', {
                detail: { url, fragmentId, root, pushState },
            }),
        );
    } catch {
        window.location.assign(url);
    } finally {
        root.classList.remove('is-loading');
    }
}

function buildUrlFromPerPageForm(form: HTMLFormElement): string {
    const url = new URL(form.getAttribute('action') || window.location.href, window.location.origin);
    const data = new FormData(form);
    data.forEach((value, key) => {
        if (value === '' || value === null) {
            url.searchParams.delete(key);
        } else {
            url.searchParams.set(key, String(value));
        }
    });
    return url.toString();
}

function bindAjaxPaginationRoot(root: HTMLElement): void {
    if (root.dataset.cf4AjaxBound === '1') {
        return;
    }
    root.dataset.cf4AjaxBound = '1';

    root.addEventListener(
        'click',
        (e) => {
            const target = eventTargetEl(e);
            const link = target?.closest(
                'a.button[data-page], .admin-pagination-nav a.button[href], .pagination a.button[href]',
            );
            if (!link || !root.contains(link)) {
                return;
            }
            if (link.getAttribute('aria-disabled') === 'true') {
                e.preventDefault();
                return;
            }
            const href = link.getAttribute('href');
            if (!href || href === '#') {
                return;
            }
            if (e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) {
                return;
            }
            e.preventDefault();
            void loadListFragment(href, root);
        },
        true,
    );

    root.addEventListener('click', (e) => {
        const target = eventTargetEl(e);
        const goBtn = target?.closest('.pagination-go-button');
        if (!goBtn || !root.contains(goBtn)) {
            return;
        }
        const toolbar = goBtn.closest('.pagination');
        const goInput = asInput(toolbar?.querySelector('.pagination-go-input') ?? null);
        if (!toolbar || !goInput) {
            return;
        }
        e.preventDefault();
        const lastPage = Math.max(
            1,
            parseInt(String(toolbar.getAttribute('data-last-page') || '1'), 10) || 1,
        );
        let targetPage = parseInt(String(goInput.value || '1').trim(), 10);
        if (Number.isNaN(targetPage)) {
            targetPage = 1;
        }
        targetPage = Math.max(1, Math.min(lastPage, targetPage));
        const url = new URL(window.location.href);
        url.searchParams.set('page', String(targetPage));
        void loadListFragment(url.toString(), root);
    });

    root.addEventListener('keydown', (e) => {
        if (e.key !== 'Enter') {
            return;
        }
        const target = eventTargetEl(e);
        const goInput = target?.closest('.pagination-go-input');
        if (!goInput || !root.contains(goInput)) {
            return;
        }
        e.preventDefault();
        goInput.closest('.pagination')?.querySelector<HTMLElement>('.pagination-go-button')?.click();
    });

    root.addEventListener('change', (e) => {
        const target = eventTargetEl(e);
        const sel = target?.closest('.admin-pagination-per-page-select');
        if (!sel || !root.contains(sel)) {
            return;
        }
        const form = sel.closest('form.admin-pagination-per-page-form');
        if (!(form instanceof HTMLFormElement)) {
            return;
        }
        e.preventDefault();
        void loadListFragment(buildUrlFromPerPageForm(form), root);
    });

    root.addEventListener('submit', (e) => {
        const target = eventTargetEl(e);
        const form = target?.closest('form.admin-pagination-per-page-form');
        if (!(form instanceof HTMLFormElement) || !root.contains(form)) {
            return;
        }
        e.preventDefault();
        void loadListFragment(buildUrlFromPerPageForm(form), root);
    });
}

export function initAllAjaxPagination(): void {
    document.querySelectorAll<HTMLElement>('[data-cf4-ajax-pagination]').forEach((root) => {
        bindAjaxPaginationRoot(root);
    });

    if (!popstateBound) {
        popstateBound = true;
        window.addEventListener('popstate', () => {
            const root = document.querySelector<HTMLElement>('[data-cf4-ajax-pagination]');
            if (!root) {
                return;
            }
            void loadListFragment(window.location.href, root, { pushState: false });
        });
    }
}

document.addEventListener('DOMContentLoaded', initAllAjaxPagination);
document.addEventListener('cf4:ajax-pagination:loaded', initAllAjaxPagination);
