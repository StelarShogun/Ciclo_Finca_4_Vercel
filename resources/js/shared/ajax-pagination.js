/**
 * CF4 — paginación sin recarga completa: sustituye #cf4-list-fragment vía fetch + DOMParser.
 */
const DEFAULT_FRAGMENT_ID = 'cf4-list-fragment';

let popstateBound = false;

function getFragmentId(root) {
    return root?.dataset?.cf4FragmentId || DEFAULT_FRAGMENT_ID;
}

function scrollToFragment(fragmentId) {
    const el = document.getElementById(fragmentId);
    if (!el) return;
    const scrollRoot = el.closest('[data-cf4-ajax-scroll]') || el;
    scrollRoot.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

/**
 * Keeps filter/toolbar forms outside the swapped fragment aligned with the current URL.
 */
function syncPaginationQueryFromUrl(url, root) {
    const parsed = new URL(url, window.location.origin);
    const page = parsed.searchParams.get('page') || '1';
    const perPage = parsed.searchParams.get('per_page');
    const fragment = document.getElementById(getFragmentId(root));

    const catalogPage = document.getElementById('catalog-list-page');
    if (catalogPage) {
        catalogPage.value = page;
    }

    const isInsideFragment = (node) => fragment && fragment.contains(node);

    if (perPage) {
        document.querySelectorAll('input[name="per_page"]').forEach((input) => {
            if (!isInsideFragment(input)) {
                input.value = perPage;
            }
        });
        document.querySelectorAll('select[name="per_page"]').forEach((select) => {
            if (!isInsideFragment(select)) {
                select.value = perPage;
            }
        });
    }

    document.querySelectorAll('input[type="hidden"][name="page"]').forEach((input) => {
        if (isInsideFragment(input)) {
            return;
        }
        if (input.closest('.cf4-pagination-toolbar')) {
            return;
        }
        input.value = page;
    });
}

async function loadListFragment(url, root, options = {}) {
    const { pushState = true } = options;
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
        scrollToFragment(fragmentId);
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

function buildUrlFromPerPageForm(form) {
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

function bindAjaxPaginationRoot(root) {
    if (!root || root.dataset.cf4AjaxBound === '1') {
        return;
    }
    root.dataset.cf4AjaxBound = '1';

    root.addEventListener(
        'click',
        (e) => {
            const link = e.target.closest(
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
        const goBtn = e.target.closest('.pagination-go-button');
        if (!goBtn || !root.contains(goBtn)) {
            return;
        }
        const toolbar = goBtn.closest('.pagination');
        const goInput = toolbar?.querySelector('.pagination-go-input');
        if (!toolbar || !goInput) {
            return;
        }
        e.preventDefault();
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
        void loadListFragment(url.toString(), root);
    });

    root.addEventListener('keydown', (e) => {
        if (e.key !== 'Enter') {
            return;
        }
        const goInput = e.target.closest('.pagination-go-input');
        if (!goInput || !root.contains(goInput)) {
            return;
        }
        e.preventDefault();
        goInput.closest('.pagination')?.querySelector('.pagination-go-button')?.click();
    });

    root.addEventListener('change', (e) => {
        const sel = e.target.closest('.admin-pagination-per-page-select');
        if (!sel || !root.contains(sel)) {
            return;
        }
        const form = sel.closest('form.admin-pagination-per-page-form');
        if (!form) {
            return;
        }
        e.preventDefault();
        void loadListFragment(buildUrlFromPerPageForm(form), root);
    });

    root.addEventListener('submit', (e) => {
        const form = e.target.closest('form.admin-pagination-per-page-form');
        if (!form || !root.contains(form)) {
            return;
        }
        e.preventDefault();
        void loadListFragment(buildUrlFromPerPageForm(form), root);
    });
}

function initAllAjaxPagination() {
    document.querySelectorAll('[data-cf4-ajax-pagination]').forEach((root) => {
        bindAjaxPaginationRoot(root);
    });

    if (!popstateBound) {
        popstateBound = true;
        window.addEventListener('popstate', () => {
            const root = document.querySelector('[data-cf4-ajax-pagination]');
            if (!root) {
                return;
            }
            void loadListFragment(window.location.href, root, { pushState: false });
        });
    }
}

document.addEventListener('DOMContentLoaded', initAllAjaxPagination);
document.addEventListener('cf4:ajax-pagination:loaded', initAllAjaxPagination);

export { initAllAjaxPagination, loadListFragment };
