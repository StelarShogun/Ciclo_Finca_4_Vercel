/**
 * CF4-33 — listado de compras por cliente: tabla JSON; detalle en vista dedicada por cliente.
 */

function formatColones(value) {
    const n = Math.round(Number(value));
    return `₡${n.toLocaleString('es-CR')}`;
}

function esc(text) {
    const d = document.createElement('div');
    d.textContent = String(text);
    return d.innerHTML;
}

const ADMIN_PER_PAGE_OPTIONS = [10, 25, 50, 100];

function normalizeAdminPerPage(value) {
    const n = parseInt(String(value ?? '10'), 10);
    return ADMIN_PER_PAGE_OPTIONS.includes(n) ? n : 10;
}

function syncReportUrl(pageUrl, { period, sort, dir, q, page, perPage }) {
    const u = new URL(pageUrl, window.location.origin);
    u.searchParams.set('period', period);
    u.searchParams.set('sort', sort);
    u.searchParams.set('dir', dir);
    u.searchParams.set('page', String(page || 1));
    u.searchParams.set('per_page', String(perPage || 10));
    const trimmed = String(q || '').trim();
    if (trimmed) {
        u.searchParams.set('q', trimmed);
    } else {
        u.searchParams.delete('q');
    }
    window.history.replaceState({}, '', `${u.pathname}${u.search}`);
}

function updateSortHeaderUI(root, sort, dir) {
    root.querySelectorAll('.nav-sort').forEach((btn) => {
        btn.classList.remove('is-active');
        btn.querySelectorAll('i').forEach((i) => i.remove());
        const key = btn.getAttribute('data-sort');
        if (key === sort) {
            btn.classList.add('is-active');
            const icon = document.createElement('i');
            icon.setAttribute('aria-hidden', 'true');
            icon.className = `fas fa-sort-${dir === 'asc' ? 'up' : 'down'}`;
            btn.appendChild(icon);
        }
    });
}

function updatePeriodUI(root, activePeriod) {
    root.querySelectorAll('.period-btn').forEach((btn) => {
        const p = btn.getAttribute('data-period');
        btn.classList.toggle('active', p === activePeriod);
    });
}

async function fetchTable(tableUrl, { period, sort, dir, q, page, perPage }) {
    const params = new URLSearchParams({
        period,
        sort,
        dir,
        page: String(page || 1),
        per_page: String(perPage || 10),
    });
    const trimmed = q.trim();
    if (trimmed) {
        params.set('q', trimmed);
    }
    const res = await fetch(`${tableUrl}?${params.toString()}`, {
        headers: { Accept: 'application/json' },
        credentials: 'same-origin',
    });
    if (!res.ok) {
        throw new Error(`HTTP ${res.status}`);
    }
    return res.json();
}

function initClientPurchasesReport() {
    const root = document.getElementById('client-purchases-root');
    if (!root) {
        return;
    }

    const tableUrl = root.dataset.tableUrl;
    const pageUrl = root.dataset.pageUrl;
    const showUrlTemplate = root.dataset.showUrlTemplate || '';
    const initialParams = new URLSearchParams(window.location.search);
    let period = root.dataset.period || '30d';
    let sort = root.dataset.sort || 'total_purchased';
    let dir = root.dataset.dir || 'desc';
    let page = Math.max(1, parseInt(initialParams.get('page') || '1', 10) || 1);
    let perPage = normalizeAdminPerPage(initialParams.get('per_page'));

    const searchInput = document.getElementById('client-purchases-search');
    const tbody = document.getElementById('client-purchases-body');
    const emptyMsg = document.getElementById('client-purchases-empty');
    const paginationWrap = document.getElementById('client-purchases-pagination');

    if (!tableUrl || !pageUrl || !searchInput || !tbody) {
        return;
    }

    searchInput.value = root.dataset.initialQ || '';

    let debounceTimer = null;
    const debounceMs = 250;

    function buildClientShowHref(clientId) {
        const base = showUrlTemplate.replace('__CLIENT__', String(clientId));
        const u = new URL(base, window.location.origin);
        u.searchParams.set('back_period', period);
        u.searchParams.set('back_sort', sort);
        u.searchParams.set('back_dir', dir);
        u.searchParams.set('back_page', String(page));
        u.searchParams.set('back_per_page', String(perPage));
        const tq = searchInput.value.trim();
        if (tq) {
            u.searchParams.set('back_q', tq);
        }
        return `${u.pathname}${u.search}`;
    }

    function buildRowsHtml(rows) {
        if (!rows.length) {
            return '<tr><td colspan="6" class="empty-cell">Sin resultados</td></tr>';
        }
        return rows
            .map((row) => {
                const href = esc(buildClientShowHref(row.client_id));
                return `<tr class="is-clickable" data-client-id="${String(row.client_id)}" tabindex="0" role="button">
                <td>${esc(row.display_name)}</td>
                <td>${esc(row.gmail)}</td>
                <td class="num">${esc(formatColones(row.total_purchased))}</td>
                <td class="num">${esc(String(row.orders_count))}</td>
                <td class="num">${esc(formatColones(row.avg_ticket))}</td>
                <td class="col-actions" onclick="event.stopPropagation()">
                    <a class="btn-client-orders-open" href="${href}">
                        <i class="fas fa-list" aria-hidden="true"></i> Ver compras
                    </a>
                </td>
            </tr>`;
            })
            .join('');
    }

    function goToClient(clientId) {
        window.location.href = buildClientShowHref(clientId);
    }

    function wireRowClicks() {
        tbody.querySelectorAll('tr[data-client-id]').forEach((tr) => {
            tr.addEventListener('click', (e) => {
                if (e.target.closest('a.btn-client-orders-open')) {
                    return;
                }
                const id = tr.getAttribute('data-client-id');
                if (id) goToClient(id);
            });
            tr.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' || e.key === ' ') {
                    if (e.target.closest('a.btn-client-orders-open')) {
                        return;
                    }
                    e.preventDefault();
                    const id = tr.getAttribute('data-client-id');
                    if (id) goToClient(id);
                }
            });
        });
    }

    async function loadPage(nextPage) {
        page = Math.max(1, Number(nextPage || 1));
        syncReportUrl(pageUrl, { period, sort, dir, q: searchInput.value, page, perPage });
        void loadFromApi();
    }

    async function loadFromApi() {
        const q = searchInput.value;
        try {
            const data = await fetchTable(tableUrl, { period, sort, dir, q, page, perPage });
            if (!data.success) {
                throw new Error('Invalid response');
            }
            const rows = data.rows || [];
            tbody.innerHTML = buildRowsHtml(rows);
            wireRowClicks();
            if (paginationWrap) {
                paginationWrap.innerHTML = data.pagination_html || '';
                wirePagination(paginationWrap);
            }
            if (emptyMsg) {
                const total = Number(data.pagination?.total || rows.length || 0);
                emptyMsg.hidden = total > 0;
            }
        } catch {
            tbody.innerHTML =
                '<tr><td colspan="6" class="empty-cell">No se pudo cargar el reporte.</td></tr>';
            if (paginationWrap) {
                paginationWrap.innerHTML = '';
            }
            if (emptyMsg) {
                emptyMsg.hidden = true;
            }
        }
    }

    function wirePagination(wrapper) {
        const goInput = wrapper.querySelector('.pagination-go-input');
        const goBtn = wrapper.querySelector('.pagination-go-button');

        wrapper.querySelectorAll('a.button[data-page]').forEach((a) => {
            const disabled = a.getAttribute('aria-disabled') === 'true';
            if (disabled) {
                a.addEventListener('click', (e) => e.preventDefault());
                a.classList.add('is-disabled');
                return;
            }
            a.addEventListener('click', (e) => {
                e.preventDefault();
                const dp = a.getAttribute('data-page');
                if (dp === null || dp === '') return;
                loadPage(dp);
            });
        });

        function goToPage() {
            const lastPage = Math.max(
                1,
                parseInt(String(wrapper.getAttribute('data-last-page') || '1'), 10) || 1,
            );
            let target = parseInt(String(goInput?.value || '1').trim(), 10);
            if (Number.isNaN(target)) target = 1;
            target = Math.max(1, Math.min(lastPage, target));
            loadPage(target);
        }

        if (goBtn) {
            goBtn.addEventListener('click', goToPage);
        }
        if (goInput) {
            goInput.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    goToPage();
                }
            });
        }

        const perSel = wrapper.querySelector('.admin-pagination-per-page-select');
        if (perSel) {
            perSel.addEventListener('change', () => {
                perPage = normalizeAdminPerPage(perSel.value);
                page = 1;
                syncReportUrl(pageUrl, { period, sort, dir, q: searchInput.value, page, perPage });
                void loadFromApi();
            });
        }
    }

    root.querySelectorAll('.period-btn').forEach((btn) => {
        btn.addEventListener('click', () => {
            const next = btn.getAttribute('data-period');
            if (!next || next === period) {
                return;
            }
            period = next;
            page = 1;
            syncReportUrl(pageUrl, { period, sort, dir, q: searchInput.value, page, perPage });
            updatePeriodUI(root, period);
            void loadFromApi();
        });
    });

    root.querySelectorAll('.nav-sort').forEach((btn) => {
        btn.addEventListener('click', () => {
            const nextSort = btn.getAttribute('data-sort');
            if (!nextSort) {
                return;
            }
            if (nextSort === sort) {
                dir = dir === 'asc' ? 'desc' : 'asc';
            } else {
                sort = nextSort;
                dir = 'desc';
            }
            page = 1;
            syncReportUrl(pageUrl, { period, sort, dir, q: searchInput.value, page, perPage });
            updateSortHeaderUI(root, sort, dir);
            void loadFromApi();
        });
    });

    searchInput.addEventListener('input', () => {
        window.clearTimeout(debounceTimer);
        debounceTimer = window.setTimeout(() => {
            page = 1;
            syncReportUrl(pageUrl, { period, sort, dir, q: searchInput.value, page, perPage });
            void loadFromApi();
        }, debounceMs);
    });

    void loadFromApi();
}

document.addEventListener('DOMContentLoaded', initClientPurchasesReport);
