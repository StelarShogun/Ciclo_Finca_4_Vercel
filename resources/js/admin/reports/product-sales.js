/**
 * CF4-30 — productos más vendidos: carga tabla vía JSON, filtro con debounce.
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

function buildTableRows(rows, { rank = false } = {}) {
    if (!rows.length) {
        return `<tr><td colspan="${rank ? 5 : 4}" class="empty-cell">Sin datos</td></tr>`;
    }
    return rows
        .map((row, i) => {
            const rk = rank ? `<td class="num">${i + 1}</td>` : '';
            return `<tr>
                ${rk}
                <td>${esc(row.name)}</td>
                <td><code>${esc(row.sku)}</code></td>
                <td class="num">${esc(String(row.units_sold))}</td>
                <td class="num">${esc(formatColones(row.revenue))}</td>
            </tr>`;
        })
        .join('');
}

/** Actualiza la URL sin recargar (compartir / refrescar conserva estado). */
function syncReportUrl(pageUrl, { period, sort, dir, q, page, top10 }) {
    const u = new URL(pageUrl, window.location.origin);
    u.searchParams.set('period', period);
    u.searchParams.set('sort', sort);
    u.searchParams.set('dir', dir);
    u.searchParams.set('page', String(page || 1));
    u.searchParams.set('top10', top10 || 'revenue');
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

async function fetchTable(tableUrl, { period, sort, dir, q, page, top10 }) {
    const params = new URLSearchParams({ period, sort, dir, page: String(page || 1), top10: top10 || 'revenue' });
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

function initProductSalesReport() {
    const root = document.getElementById('product-sales-root');
    if (!root) {
        return;
    }

    const tableUrl = root.dataset.tableUrl;
    const pageUrl = root.dataset.pageUrl;
    let period = root.dataset.period || '30d';
    let sort = root.dataset.sort || 'revenue';
    let dir = root.dataset.dir || 'desc';
    let page = 1;
    let top10 = root.dataset.top10 || 'revenue';
    const searchInput = document.getElementById('product-sales-search');
    const top10Body = document.getElementById('top10-body');
    const top10MetricLabel = document.getElementById('top10-metric-label');
    const fullBody = document.getElementById('full-table-body');
    const emptyMsg = document.getElementById('product-sales-empty');
    const paginationWrap = document.getElementById('full-table-pagination');

    if (!tableUrl || !pageUrl || !searchInput || !top10Body || !fullBody) {
        return;
    }

    searchInput.value = root.dataset.initialQ || '';

    let debounceTimer = null;
    const debounceMs = 250;

    function updateTop10Hint() {
        if (!top10MetricLabel) return;
        top10MetricLabel.textContent = top10 === 'units' ? 'unidades' : 'ingresos';
    }

    async function loadPage(nextPage) {
        page = Math.max(1, Number(nextPage || 1));
        syncReportUrl(pageUrl, { period, sort, dir, q: searchInput.value, page, top10 });
        void loadFromApi();
    }

    async function loadFromApi() {
        const q = searchInput.value;
        try {
            const data = await fetchTable(tableUrl, { period, sort, dir, q, page, top10 });
            if (!data.success) {
                throw new Error('Invalid response');
            }
            top10Body.innerHTML = buildTableRows(data.top10 || [], { rank: true });
            const rows = data.rows || [];
            fullBody.innerHTML = buildTableRows(rows, { rank: false });
            if (paginationWrap) {
                paginationWrap.innerHTML = data.pagination_html || '';
                wirePagination(paginationWrap);
            }
            if (emptyMsg) {
                const total = Number(data.pagination?.total || rows.length || 0);
                emptyMsg.hidden = total > 0;
            }
        } catch {
            top10Body.innerHTML =
                '<tr><td colspan="5" class="empty-cell">No se pudo cargar el reporte.</td></tr>';
            fullBody.innerHTML =
                '<tr><td colspan="4" class="empty-cell">No se pudo cargar el reporte.</td></tr>';
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
            syncReportUrl(pageUrl, { period, sort, dir, q: searchInput.value, page, top10 });
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
            syncReportUrl(pageUrl, { period, sort, dir, q: searchInput.value, page, top10 });
            updateSortHeaderUI(root, sort, dir);
            void loadFromApi();
        });
    });

    searchInput.addEventListener('input', () => {
        window.clearTimeout(debounceTimer);
        debounceTimer = window.setTimeout(() => {
            page = 1;
            syncReportUrl(pageUrl, { period, sort, dir, q: searchInput.value, page, top10 });
            void loadFromApi();
        }, debounceMs);
    });

    root.querySelectorAll('.top10-btn').forEach((btn) => {
        btn.addEventListener('click', () => {
            const next = btn.getAttribute('data-top10');
            if (!next || next === top10) return;
            top10 = next;
            root.querySelectorAll('.top10-btn').forEach((b) => b.classList.toggle('is-active', b === btn));
            updateTop10Hint();
            syncReportUrl(pageUrl, { period, sort, dir, q: searchInput.value, page, top10 });
            void loadFromApi();
        });
    });

    updateTop10Hint();
    loadFromApi();
}

document.addEventListener('DOMContentLoaded', initProductSalesReport);
