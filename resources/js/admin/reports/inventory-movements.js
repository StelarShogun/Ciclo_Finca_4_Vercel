document.addEventListener('DOMContentLoaded', () => {
    const root = document.getElementById('inventory-movements-root');
    if (!root) return;

    // Read endpoint configuration from data attributes.
    const MOVEMENTS_URL = root.dataset.movementsUrl;
    const PAGE_URL      = root.dataset.pageUrl;

    // Store the current filter and pagination state.
    const state = {
        type:     root.dataset.initialType   || '',
        origin:   root.dataset.initialOrigin || '',
        dateFrom: root.dataset.initialFrom   || '',
        dateTo:   root.dataset.initialTo     || '',
        page:     1,
        lastPage: 1,
    };

    // Cache frequently used DOM elements.
    const elError    = document.getElementById('inventory-movements-error');
    const elLoading  = document.getElementById('inventory-movements-loading');
    const elContent  = document.getElementById('inventory-movements-content');
    const elEmpty    = document.getElementById('inv-empty-state');

    const elTotal    = document.getElementById('inv-metric-total');
    const elEntradas = document.getElementById('inv-metric-entradas');
    const elSalidas  = document.getElementById('inv-metric-salidas');

    const elTableBody  = document.getElementById('inv-table-body');
    const elTableCount = document.getElementById('inv-table-count');

    const elPagination        = document.getElementById('inv-pagination');
    const elPaginationControls = document.getElementById('inv-pagination-controls');
    const elPageInfo          = document.getElementById('inv-page-info');

    // Cache date input fields.
    const elFromD = document.getElementById('inv-from-d');
    const elFromM = document.getElementById('inv-from-m');
    const elFromY = document.getElementById('inv-from-y');
    const elToD   = document.getElementById('inv-to-d');
    const elToM   = document.getElementById('inv-to-m');
    const elToY   = document.getElementById('inv-to-y');

    // Toggle element visibility.
    function show(el)  { el.hidden = false; }
    function hide(el)  { el.hidden = true; }
    function setError(msg) {
        if (msg) { elError.textContent = msg; show(elError); }
        else { hide(elError); }
    }

    // Build the current page URL for browser history updates.
    function buildPageUrl() {
        const params = new URLSearchParams();
        if (state.type)     params.set('type', state.type);
        if (state.origin)   params.set('origin', state.origin);
        if (state.dateFrom) params.set('date_from', state.dateFrom);
        if (state.dateTo)   params.set('date_to', state.dateTo);
        if (state.page > 1) params.set('page', state.page);
        const qs = params.toString();
        return PAGE_URL + (qs ? '?' + qs : '');
    }

    // Build the API URL based on the current state.
    function buildApiUrl() {
        const params = new URLSearchParams();
        if (state.type)     params.set('type', state.type);
        if (state.origin)   params.set('origin', state.origin);
        if (state.dateFrom) params.set('date_from', state.dateFrom);
        if (state.dateTo)   params.set('date_to', state.dateTo);
        params.set('page', state.page);
        params.set('per_page', 10);
        return MOVEMENTS_URL + '?' + params.toString();
    }

    // Convert separate day, month, and year inputs into an ISO date.
    function parseDateParts(d, m, y) {
        const dv = d.value.padStart(2, '0');
        const mv = m.value.padStart(2, '0');
        const yv = y.value;
        if (dv && mv && yv.length === 4) return `${yv}-${mv}-${dv}`;
        return '';
    }

    // Validate individual date fields before parsing them.
    // Returns an error message string or null.
    function validateDateFields(dEl, mEl, yEl, label) {
        const dRaw = dEl.value.trim();
        const mRaw = mEl.value.trim();
        const yRaw = yEl.value.trim();

        // Treat an empty date as omitted and valid.
        if (!dRaw && !mRaw && !yRaw) return null;

        // Reject partially completed dates.
        if (!dRaw || !mRaw || !yRaw) {
            return `La fecha de ${label} está incompleta. Ingresá día, mes y año.`;
        }

        const d = parseInt(dRaw, 10);
        const m = parseInt(mRaw, 10);
        const y = parseInt(yRaw, 10);

        // Restrict the year to a valid four-digit range.
        const currentYear = new Date().getFullYear();
        if (isNaN(y) || yRaw.length !== 4 || y < 2000 || y > currentYear) {
            return `El año de la fecha de ${label} debe estar entre 2000 y ${currentYear}.`;
        }

        // Validate the calendar month range.
        if (isNaN(m) || m < 1 || m > 12) {
            return `El mes de la fecha de ${label} debe estar entre 01 y 12.`;
        }

        // Validate the base day range before checking the exact month.
        if (isNaN(d) || d < 1 || d > 31) {
            return `El día de la fecha de ${label} debe estar entre 01 y 31.`;
        }

        // Resolve the last valid day for the given month and year.
        const maxDay = new Date(y, m, 0).getDate();
        if (d > maxDay) {
            return `El mes ${String(m).padStart(2,'0')}/${y} solo tiene ${maxDay} días.`;
        }

        return null;
    }

    // Populate split date inputs from an ISO date.
    function fillDateParts(isoDate, d, m, y) {
        if (!isoDate) return;
        const [yv, mv, dv] = isoDate.split('-');
        d.value = dv || '';
        m.value = mv || '';
        y.value = yv || '';
    }

    // Map movement types to badge classes.
    function typeBadgeClass(type) {
        const map = {
            entrada:    'inv-type-badge--entrada',
            salida:     'inv-type-badge--salida',
            devolucion: 'inv-type-badge--devolucion',
            ajuste:     'inv-type-badge--ajuste',
        };
        return map[type] || 'inv-type-badge--default';
    }

    // Translate backend type labels to Spanish display values.
    const TYPE_LABEL_ES = {
        'entrada':          'Entrada',
        'salida':           'Salida',
        'devolucion':       'Devolución',
        'ajuste':           'Ajuste',
        'damage':           'Daño',
        'damaged':          'Daño',
        'manual adjustment':'Ajuste manual',
        'manual_adjustment':'Ajuste manual',
        'adjustment':       'Ajuste',
        'refund':           'Entrada manual (reembolso)',
        'return':           'Devolución de venta',
        'in':               'Entrada',
        'out':              'Salida',
    };

    // Normalize translated type labels for the UI.
    function translateTypeLabel(label) {
        if (!label) return label;
        const key = String(label).toLowerCase().trim();
        return TYPE_LABEL_ES[key] ?? label;
    }

    // Validate the parsed ISO date range.
    function validateDateRange(from, to) {
        const today = new Date();
        today.setHours(23, 59, 59, 999); // Use the end of the current day as the upper bound.

        if (from) {
            const dFrom = new Date(from);
            if (dFrom > today) return 'La fecha de inicio no puede ser mayor a la fecha actual.';
        }
        if (to) {
            const dTo = new Date(to);
            if (dTo > today) return 'La fecha de fin no puede ser mayor a la fecha actual.';
        }
        if (from && to) {
            const dFrom = new Date(from);
            const dTo   = new Date(to);
            if (dFrom > dTo) return 'La fecha de inicio debe ser anterior a la fecha de fin.';
        }
        return null;
    }

    // Format numeric values for the local locale.
    function fmt(n) {
        return Number(n).toLocaleString('es-CR');
    }

    // Render summary metrics.
    function renderMetrics(data) {
        elTotal.textContent    = fmt(data.meta.total);
        elEntradas.textContent = '+' + fmt(data.summary?.total_entradas ?? 0);
        elSalidas.textContent  = '-' + fmt(data.summary?.total_salidas  ?? 0);
        elTableCount.textContent = fmt(data.meta.total);
    }

    // Render a single movement row.
    function renderRow(m) {
        const tr = document.createElement('tr');
        const isNegative = m.type === 'salida';
        tr.innerHTML = `
            <td>
                <div class="inv-date-main">${formatDate(m.created_at)}</div>
                <div class="inv-date-time">${formatTime(m.created_at)}</div>
            </td>
            <td>
                <span class="inv-type-badge ${typeBadgeClass(m.type)}">${escHtml(translateTypeLabel(m.type_label))}</span>
            </td>
            <td>
                <span>${escHtml(m.origin_label)}</span>
                ${m.reference_id ? `<div class="inv-ref-id">#${escHtml(String(m.reference_id))}</div>` : ''}
            </td>
            <td class="text-end">
                <span class="${isNegative ? 'inv-qty-negative' : 'inv-qty-positive'}">
                    ${isNegative ? '-' : '+'}${fmt(m.quantity)}
                </span>
            </td>
            <td class="text-end" style="color:var(--color-text-muted,#6b7280)">${fmt(m.stock_before)}</td>
            <td class="text-end" style="font-weight:600">${fmt(m.stock_after)}</td>
            <td class="inv-admin-cell">${m.admin ? escHtml(m.admin.name) : '<span style="color:var(--color-text-muted)">Sistema</span>'}</td>
            <td class="inv-notes-cell">${m.notes ? escHtml(m.notes) : '<span style="color:var(--color-text-muted)">—</span>'}</td>
        `;
        return tr;
    }

    // Render the movements table body.
    function renderTable(movements) {
        elTableBody.innerHTML = '';
        movements.forEach(m => elTableBody.appendChild(renderRow(m)));
    }

    // Render pagination controls from the API metadata.
    function renderPagination(meta) {
        state.lastPage = meta.last_page;

        if (meta.last_page <= 1) { hide(elPagination); return; }

        const current = meta.current_page;
        const last    = Math.max(1, meta.last_page);
        const hasPrev = current > 1;
        const hasNext = current < last;

        const SVG_PREV = `<svg width="16" height="16" viewBox="0 0 24 24" aria-hidden="true"><path d="M15 18l-6-6 6-6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>`;
        const SVG_NEXT = `<svg width="16" height="16" viewBox="0 0 24 24" aria-hidden="true"><path d="M9 6l6 6-6 6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>`;

        // Mirror the shared compact pagination markup.
        elPaginationControls.innerHTML = `
            <div class="pagination is-compact" role="navigation" aria-label="Paginación movimientos">
                <div class="results-info" aria-live="polite">
                    Mostrando ${fmt(meta.from ?? ((current - 1) * 10 + 1))} a ${fmt(meta.to ?? Math.min(current * 10, meta.total))} de ${fmt(meta.total)} resultados
                </div>
                <a class="button" aria-label="Previous" href="#"
                   ${!hasPrev ? 'aria-disabled="true" tabindex="-1"' : ''}
                   data-page="${current - 1}">
                    ${SVG_PREV}
                </a>
                <span class="button button-primary" aria-current="page">
                    ${current} / ${last}
                </span>
                <a class="button" aria-label="Next" href="#"
                   ${!hasNext ? 'aria-disabled="true" tabindex="-1"' : ''}
                   data-page="${current + 1}">
                    ${SVG_NEXT}
                </a>
                <label class="sr-only" for="inv-go-page-input">Ir a página</label>
                <input id="inv-go-page-input" type="number" min="1" max="${last}" step="1"
                       value="${current}" inputmode="numeric" />
                <button class="go-button" id="inv-go-page-btn" type="button">Ir</button>
            </div>
        `;

        // Clear the external helper text because the component already renders it.
        if (elPageInfo) elPageInfo.textContent = '';

        elPaginationControls.querySelector('[aria-label="Previous"]')
            ?.addEventListener('click', e => {
                e.preventDefault();
                if (hasPrev) { state.page = current - 1; loadMovements(); }
            });

        elPaginationControls.querySelector('[aria-label="Next"]')
            ?.addEventListener('click', e => {
                e.preventDefault();
                if (hasNext) { state.page = current + 1; loadMovements(); }
            });

        elPaginationControls.querySelector('#inv-go-page-btn')
            ?.addEventListener('click', () => {
                const val = parseInt(elPaginationControls.querySelector('#inv-go-page-input').value, 10);
                if (val >= 1 && val <= last) { state.page = val; loadMovements(); }
            });

        elPaginationControls.querySelector('#inv-go-page-input')
            ?.addEventListener('keydown', e => {
                if (e.key === 'Enter') {
                    const val = parseInt(e.target.value, 10);
                    if (val >= 1 && val <= last) { state.page = val; loadMovements(); }
                }
            });

        show(elPagination);
    }

    // Format an ISO timestamp as a local date.
    function formatDate(iso) {
        const d = new Date(iso);
        return d.toLocaleDateString('es-CR', { day: '2-digit', month: '2-digit', year: 'numeric' });
    }

    // Format an ISO timestamp as a local time.
    function formatTime(iso) {
        const d = new Date(iso);
        return d.toLocaleTimeString('es-CR', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
    }

    // Escape HTML-sensitive characters before rendering.
    function escHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    // Fetch and render movement data.
    async function loadMovements() {
        setError(null);
        hide(elContent);
        show(elLoading);

        try {
            const res  = await fetch(buildApiUrl(), {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            });
            if (!res.ok) throw new Error(`Error ${res.status}`);
            const data = await res.json();

            hide(elLoading);
            show(elContent);

            const movements = data.data ?? [];

            if (movements.length === 0) {
                show(elEmpty);
                hide(elPagination);
                elTotal.textContent      = '0';
                elEntradas.textContent   = '+0';
                elSalidas.textContent    = '-0';
                elTableCount.textContent = '0';
                elTableBody.innerHTML    = '';
            } else {
                hide(elEmpty);
                renderMetrics(data);
                renderTable(movements);
                renderPagination(data.meta);
            }

            history.replaceState(null, '', buildPageUrl());

        } catch (err) {
            hide(elLoading);
            show(elContent);
            setError('No se pudieron cargar los movimientos. Intentá de nuevo.');
            console.error('[inventory-movements]', err);
        }
    }

    // Sync active button state for a filter group.
    function syncToggle(filterAttr, value) {
        document.querySelectorAll(`.inv-mov-btn[data-filter="${filterAttr}"]`).forEach(btn => {
            const isActive = btn.dataset.value === value;
            btn.classList.toggle('active', isActive);
            btn.setAttribute('aria-pressed', isActive);
        });
    }

    document.querySelectorAll('.inv-mov-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const filter = btn.dataset.filter;
            const value  = btn.dataset.value;
            state[filter] = value;
            state.page    = 1;
            syncToggle(filter, value);
            loadMovements();
        });
    });

    document.getElementById('inv-apply-dates')?.addEventListener('click', () => {
        // Validate each date field before building ISO values.
        const fieldErrorFrom = validateDateFields(elFromD, elFromM, elFromY, 'inicio');
        if (fieldErrorFrom) { setError(fieldErrorFrom); return; }

        const fieldErrorTo = validateDateFields(elToD, elToM, elToY, 'fin');
        if (fieldErrorTo) { setError(fieldErrorTo); return; }

        // Build ISO date strings after field validation succeeds.
        const from = parseDateParts(elFromD, elFromM, elFromY);
        const to   = parseDateParts(elToD,   elToM,   elToY);

        // Validate the final date range.
        const rangeError = validateDateRange(from, to);
        if (rangeError) { setError(rangeError); return; }

        setError(null);
        state.dateFrom = from;
        state.dateTo   = to;
        state.page     = 1;
        loadMovements();
    });

    document.getElementById('inv-clear-filters')?.addEventListener('click', () => {
        state.type     = '';
        state.origin   = '';
        state.dateFrom = '';
        state.dateTo   = '';
        state.page     = 1;

        [elFromD, elFromM, elFromY, elToD, elToM, elToY].forEach(el => { el.value = ''; });
        syncToggle('type',   '');
        syncToggle('origin', '');
        loadMovements();
    });

    // Move focus to the next input when the current one is complete.
    function wireAutoAdvance(inputs) {
        inputs.forEach((input, i) => {
            input.addEventListener('input', () => {
                if (input.value.length >= parseInt(input.maxLength, 10) && i < inputs.length - 1) {
                    inputs[i + 1].focus();
                }
            });
        });
    }
    wireAutoAdvance([elFromD, elFromM, elFromY]);
    wireAutoAdvance([elToD, elToM, elToY]);

    // Initialize UI state from the server-provided defaults.
    syncToggle('type',   state.type);
    syncToggle('origin', state.origin);
    fillDateParts(state.dateFrom, elFromD, elFromM, elFromY);
    fillDateParts(state.dateTo,   elToD,   elToM,   elToY);

    loadMovements();
});