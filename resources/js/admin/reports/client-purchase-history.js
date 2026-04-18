/**
 * CF4-33 — compras por cliente: tabla JSON, ordenación, búsqueda, detalle en modal.
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

function syncReportUrl(pageUrl, { period, sort, dir, q, page }) {
    const u = new URL(pageUrl, window.location.origin);
    u.searchParams.set('period', period);
    u.searchParams.set('sort', sort);
    u.searchParams.set('dir', dir);
    u.searchParams.set('page', String(page || 1));
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

async function fetchTable(tableUrl, { period, sort, dir, q, page }) {
    const params = new URLSearchParams({ period, sort, dir, page: String(page || 1) });
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

function buildRowsHtml(rows) {
    if (!rows.length) {
        return '<tr><td colspan="5" class="empty-cell">Sin resultados</td></tr>';
    }
    return rows
        .map(
            (row) => `<tr class="is-clickable" data-client-id="${String(row.client_id)}" tabindex="0" role="button">
                <td>${esc(row.display_name)}</td>
                <td>${esc(row.gmail)}</td>
                <td class="num">${esc(formatColones(row.total_purchased))}</td>
                <td class="num">${esc(String(row.orders_count))}</td>
                <td class="num">${esc(formatColones(row.avg_ticket))}</td>
            </tr>`,
        )
        .join('');
}

function initClientPurchasesReport() {
    const root = document.getElementById('client-purchases-root');
    if (!root) {
        return;
    }

    const tableUrl = root.dataset.tableUrl;
    const pageUrl = root.dataset.pageUrl;
    const ordersUrlTemplate = root.dataset.ordersUrlTemplate || '';
    let period = root.dataset.period || '30d';
    let sort = root.dataset.sort || 'total_purchased';
    let dir = root.dataset.dir || 'desc';
    let page = 1;

    const searchInput = document.getElementById('client-purchases-search');
    const tbody = document.getElementById('client-purchases-body');
    const emptyMsg = document.getElementById('client-purchases-empty');
    const paginationWrap = document.getElementById('client-purchases-pagination');
    const dialog = document.getElementById('client-orders-dialog');
    const dialogBody = document.getElementById('client-orders-dialog-body');
    const dialogMeta = document.getElementById('client-orders-dialog-meta');
    const dialogClose = document.getElementById('client-orders-dialog-close');

    if (!tableUrl || !pageUrl || !searchInput || !tbody) {
        return;
    }

    searchInput.value = root.dataset.initialQ || '';

    let debounceTimer = null;
    const debounceMs = 250;

    function ordersUrlForClient(clientId) {
        return ordersUrlTemplate.replace('__CLIENT__', String(clientId));
    }

    async function openOrdersDialog(clientId) {
        if (!dialog || !dialogBody || !dialogMeta) return;
        dialogBody.innerHTML = '<tr><td colspan="3" class="loading-cell">Cargando…</td></tr>';
        dialogMeta.textContent = '';
        dialog.showModal();

        const url = `${ordersUrlForClient(clientId)}?${new URLSearchParams({ period }).toString()}`;
        try {
            const res = await fetch(url, {
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
            });
            const data = await res.json().catch(() => ({}));
            if (!res.ok || !data.success) {
                throw new Error(data.message || 'No se pudo cargar el detalle.');
            }
            const c = data.client || {};
            dialogMeta.textContent = `${c.display_name || ''} · ${c.gmail || ''}`;
            const orders = data.orders || [];
            if (!orders.length) {
                dialogBody.innerHTML =
                    '<tr><td colspan="3" class="empty-cell">Sin órdenes en el periodo.</td></tr>';
                return;
            }
            dialogBody.innerHTML = orders
                .map(
                    (o) => `<tr>
                    <td><code>${esc(o.invoice_number)}</code></td>
                    <td>${esc(o.sale_date)}</td>
                    <td class="num">${esc(formatColones(o.total))}</td>
                </tr>`,
                )
                .join('');
        } catch (e) {
            dialogBody.innerHTML = `<tr><td colspan="3" class="empty-cell">${esc(e instanceof Error ? e.message : 'Error')}</td></tr>`;
        }
    }

    function wireRowClicks() {
        tbody.querySelectorAll('tr[data-client-id]').forEach((tr) => {
            const go = () => {
                const id = tr.getAttribute('data-client-id');
                if (id) void openOrdersDialog(id);
            };
            tr.addEventListener('click', go);
            tr.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    go();
                }
            });
        });
    }

    async function loadPage(nextPage) {
        page = Math.max(1, Number(nextPage || 1));
        syncReportUrl(pageUrl, { period, sort, dir, q: searchInput.value, page });
        void loadFromApi();
    }

    async function loadFromApi() {
        const q = searchInput.value;
        try {
            const data = await fetchTable(tableUrl, { period, sort, dir, q, page });
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
                '<tr><td colspan="5" class="empty-cell">No se pudo cargar el reporte.</td></tr>';
            if (paginationWrap) {
                paginationWrap.innerHTML = '';
            }
            if (emptyMsg) {
                emptyMsg.hidden = true;
            }
        }
    }

    function wirePagination(wrapper) {
        const goInput = wrapper.querySelector('#goToPageInput');
        const goBtn = wrapper.querySelector('#goToPageBtn');

        wrapper.querySelectorAll('.button[aria-label]').forEach((a) => {
            const disabled = a.getAttribute('aria-disabled') === 'true';
            if (disabled) {
                a.addEventListener('click', (e) => e.preventDefault());
                a.classList.add('is-disabled');
                return;
            }
            a.addEventListener('click', (e) => {
                e.preventDefault();
                const dp = a.getAttribute('data-page');
                if (!dp) return;
                loadPage(dp);
            });
        });

        function goToPage() {
            const totalSpan = wrapper.querySelector('.button.button-primary');
            if (!totalSpan) return;
            const parts = totalSpan.textContent.trim().split('/');
            const lastPage = Math.max(1, parseInt((parts[1] || '1').trim(), 10));
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
    }

    root.querySelectorAll('.period-btn').forEach((btn) => {
        btn.addEventListener('click', () => {
            const next = btn.getAttribute('data-period');
            if (!next || next === period) {
                return;
            }
            period = next;
            page = 1;
            syncReportUrl(pageUrl, { period, sort, dir, q: searchInput.value, page });
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
            syncReportUrl(pageUrl, { period, sort, dir, q: searchInput.value, page });
            updateSortHeaderUI(root, sort, dir);
            void loadFromApi();
        });
    });

    searchInput.addEventListener('input', () => {
        window.clearTimeout(debounceTimer);
        debounceTimer = window.setTimeout(() => {
            page = 1;
            syncReportUrl(pageUrl, { period, sort, dir, q: searchInput.value, page });
            void loadFromApi();
        }, debounceMs);
    });

    if (dialogClose && dialog) {
        dialogClose.addEventListener('click', () => dialog.close());
    }
    if (dialog) {
        dialog.addEventListener('click', (e) => {
            if (e.target === dialog) {
                dialog.close();
            }
        });
    }

    void loadFromApi();
}

document.addEventListener('DOMContentLoaded', initClientPurchasesReport);
