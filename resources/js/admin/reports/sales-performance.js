/**
 * CF4-24 — desempeño de ventas: métricas y comparativa vía JSON sin recargar.
 * Rango personalizado: calendario real, desde 2025-01-01 hasta hoy, máx. 731 días inclusivos (alineado al backend).
 */

const YEAR_MIN = 2025;

/** Mismo límite que SalesPerformanceRangeRequest::MAX_CUSTOM_RANGE_DAYS_INCLUSIVE */
const MAX_CUSTOM_RANGE_DAYS_INCLUSIVE = 731;

function todayParts() {
    const n = new Date();
    return { y: n.getFullYear(), m: n.getMonth() + 1, d: n.getDate() };
}

function pad2(n) {
    return String(n).padStart(2, '0');
}

function ymdToDate(y, m, d) {
    return new Date(y, m - 1, d);
}

/** Fecha de calendario real (p. ej. 31/02 inválido), sin política de año mínimo */
function isCalendarDateStructurallyValid(y, m, d) {
    if (!Number.isInteger(y) || !Number.isInteger(m) || !Number.isInteger(d)) return false;
    if (m < 1 || m > 12 || d < 1 || d > 31) return false;
    const dt = ymdToDate(y, m, d);
    return dt.getFullYear() === y && dt.getMonth() === m - 1 && dt.getDate() === d;
}

function daysInclusiveBetweenYmd(fromStr, toStr) {
    const [ay, am, ad] = fromStr.split('-').map((x) => parseInt(x, 10));
    const [by, bm, bd] = toStr.split('-').map((x) => parseInt(x, 10));
    const t0 = Date.UTC(ay, am - 1, ad);
    const t1 = Date.UTC(by, bm - 1, bd);
    return Math.floor((t1 - t0) / (24 * 60 * 60 * 1000)) + 1;
}

function dateNotAfterToday(y, m, d) {
    const t = todayParts();
    const a = y * 10000 + m * 100 + d;
    const b = t.y * 10000 + t.m * 100 + t.d;
    return a <= b;
}

function parseYmd(s) {
    if (!s || typeof s !== 'string' || !/^\d{4}-\d{2}-\d{2}$/.test(s)) return null;
    const [y, m, d] = s.split('-').map((x) => parseInt(x, 10));
    return { y, m, d };
}

function defaultCustomRangeYmd() {
    const t = todayParts();
    const from = `${t.y}-${pad2(t.m)}-01`;
    const to = `${t.y}-${pad2(t.m)}-${pad2(t.d)}`;
    return { from, to };
}

function formatColones(value) {
    const n = Math.round(Number(value));
    return `₡${n.toLocaleString('es-CR')}`;
}

function formatSignedInt(value) {
    const n = Math.round(Number(value));
    const sign = n > 0 ? '+' : '';
    return `${sign}${n.toLocaleString('es-CR')}`;
}

function formatSignedColones(value) {
    const n = Math.round(Number(value));
    const abs = Math.abs(n).toLocaleString('es-CR');
    if (n > 0) return `+₡${abs}`;
    if (n < 0) return `-₡${abs}`;
    return `₡${abs}`;
}

function syncPageUrl(pageUrl, { preset, from, to }) {
    const u = new URL(pageUrl, window.location.origin);
    u.searchParams.set('preset', preset);
    if (preset === 'custom' && from && to) {
        u.searchParams.set('from', from);
        u.searchParams.set('to', to);
    } else {
        u.searchParams.delete('from');
        u.searchParams.delete('to');
    }
    window.history.replaceState({}, '', `${u.pathname}${u.search}`);
}

function buildMetricsUrl(metricsUrl, { preset, from, to }) {
    const u = new URL(metricsUrl, window.location.origin);
    u.searchParams.set('preset', preset);
    if (preset === 'custom') {
        if (from) u.searchParams.set('from', from);
        if (to) u.searchParams.set('to', to);
    }
    return u.toString();
}

function setTrendClass(el, trend) {
    el.classList.remove('trend-up', 'trend-down', 'trend-flat');
    if (trend === 'up') el.classList.add('trend-up');
    else if (trend === 'down') el.classList.add('trend-down');
    else el.classList.add('trend-flat');
}

function renderComparisonValue(el, { delta, formatter }) {
    if (!el) return;
    el.classList.remove('trend-up', 'trend-down', 'trend-flat');
    if (delta === null || delta === undefined || Number.isNaN(Number(delta))) {
        el.textContent = '—';
        return;
    }
    const n = Number(delta);
    el.textContent = formatter(n);
    const trend = n > 0 ? 'up' : (n < 0 ? 'down' : 'flat');
    setTrendClass(el, trend);
}

function digitsOnly(value, maxLen) {
    const d = String(value).replace(/\D/g, '');
    return maxLen ? d.slice(0, maxLen) : d;
}

function wireNumericInput(el, maxLen) {
    el.addEventListener('beforeinput', (e) => {
        if (e.data && /\D/.test(e.data)) {
            e.preventDefault();
        }
    });
    el.addEventListener('input', () => {
        const pos = el.selectionStart;
        const cleaned = digitsOnly(el.value, maxLen);
        if (el.value !== cleaned) {
            el.value = cleaned;
            try {
                el.setSelectionRange(pos, pos);
            } catch {
                /* noop */
            }
        }
    });
    el.addEventListener('paste', (e) => {
        e.preventDefault();
        const t = e.clipboardData?.getData('text') || '';
        el.value = digitsOnly(t, maxLen);
    });
}

function readParts(els) {
    const d = parseInt(els.d.value, 10);
    const m = parseInt(els.m.value, 10);
    const y = parseInt(els.y.value, 10);
    return { d, m, y };
}

function validateCustomRange(elsFrom, elsTo) {
    const a = readParts(elsFrom);
    const b = readParts(elsTo);

    if ([a.d, a.m, a.y, b.d, b.m, b.y].some((n) => Number.isNaN(n))) {
        return { ok: false, msg: 'Completá día, mes y año en ambas fechas.' };
    }

    if (!isCalendarDateStructurallyValid(a.y, a.m, a.d)) {
        return { ok: false, msg: 'La fecha “Desde” no existe en el calendario (revisá día y mes, p. ej. febrero).' };
    }
    if (!isCalendarDateStructurallyValid(b.y, b.m, b.d)) {
        return { ok: false, msg: 'La fecha “Hasta” no existe en el calendario (revisá día y mes, p. ej. febrero).' };
    }

    if (a.y < YEAR_MIN || b.y < YEAR_MIN) {
        return { ok: false, msg: 'Las fechas deben ser desde el 1 de enero de 2025 en adelante (política del reporte).' };
    }

    if (!dateNotAfterToday(a.y, a.m, a.d) || !dateNotAfterToday(b.y, b.m, b.d)) {
        return { ok: false, msg: 'No podés usar fechas posteriores a hoy.' };
    }

    const fromStr = `${a.y}-${pad2(a.m)}-${pad2(a.d)}`;
    const toStr = `${b.y}-${pad2(b.m)}-${pad2(b.d)}`;

    if (fromStr > toStr) {
        return { ok: false, msg: 'La fecha “Desde” no puede ser mayor que “Hasta”.' };
    }

    const spanDays = daysInclusiveBetweenYmd(fromStr, toStr);
    if (spanDays > MAX_CUSTOM_RANGE_DAYS_INCLUSIVE) {
        return {
            ok: false,
            msg: `El rango personalizado no puede superar ${MAX_CUSTOM_RANGE_DAYS_INCLUSIVE} días (incluyendo inicio y fin). Acortá el periodo o usá filtros predefinidos (mes, año).`,
        };
    }

    return { ok: true, from: fromStr, to: toStr };
}

function fillPartsFromYmd(els, ymd) {
    const p = parseYmd(ymd);
    if (!p) return;
    els.d.value = String(p.d);
    els.m.value = String(p.m);
    els.y.value = String(p.y);
}

function initSalesPerformanceReport() {
    const root = document.getElementById('sales-performance-root');
    if (!root) return;

    const metricsUrl = root.dataset.metricsUrl;
    const pageUrl = root.dataset.pageUrl;
    const loadingEl = document.getElementById('sales-performance-loading');
    const contentEl = document.getElementById('sales-performance-content');
    const resultsSectionEl = document.getElementById('sales-perf-results');
    const errorEl = document.getElementById('sales-performance-error');
    const customRow = document.getElementById('sales-custom-range');
    const applyBtn = document.getElementById('sales-apply-custom');

    const elsFrom = {
        d: document.getElementById('sales-from-d'),
        m: document.getElementById('sales-from-m'),
        y: document.getElementById('sales-from-y'),
    };
    const elsTo = {
        d: document.getElementById('sales-to-d'),
        m: document.getElementById('sales-to-m'),
        y: document.getElementById('sales-to-y'),
    };

    if (
        !metricsUrl ||
        !pageUrl ||
        !loadingEl ||
        !contentEl ||
        !customRow ||
        !applyBtn ||
        !elsFrom.d ||
        !elsFrom.m ||
        !elsFrom.y ||
        !elsTo.d ||
        !elsTo.m ||
        !elsTo.y
    ) {
        return;
    }

    [elsFrom.d, elsTo.d].forEach((el) => wireNumericInput(el, 2));
    [elsFrom.m, elsTo.m].forEach((el) => wireNumericInput(el, 2));
    [elsFrom.y, elsTo.y].forEach((el) => wireNumericInput(el, 4));

    const params = new URLSearchParams(window.location.search);
    let preset = params.get('preset') || root.dataset.initialPreset || 'month';
    const allowed = new Set(['today', 'week', 'month', 'year', 'custom']);
    if (!allowed.has(preset)) preset = 'month';

    let fromVal = params.get('from') || root.dataset.initialFrom || '';
    let toVal = params.get('to') || root.dataset.initialTo || '';

    if (fromVal && toVal) {
        fillPartsFromYmd(elsFrom, fromVal);
        fillPartsFromYmd(elsTo, toVal);
    }

    function updatePresetButtons() {
        root.querySelectorAll('.period-btn[data-preset]').forEach((btn) => {
            btn.classList.toggle('active', btn.getAttribute('data-preset') === preset);
        });
        customRow.hidden = preset !== 'custom';
    }

    function showError(msg) {
        if (!errorEl) return;
        errorEl.textContent = msg;
        errorEl.hidden = false;
    }

    function hideError() {
        if (!errorEl) return;
        errorEl.hidden = true;
        errorEl.textContent = '';
    }

    function setResultsPanelVisible(visible) {
        if (resultsSectionEl) {
            resultsSectionEl.hidden = !visible;
        }
    }

    function clearCustomInputs() {
        [elsFrom.d, elsFrom.m, elsFrom.y, elsTo.d, elsTo.m, elsTo.y].forEach((el) => {
            el.value = '';
        });
    }

    async function loadMetrics() {
        hideError();
        setResultsPanelVisible(true);
        loadingEl.hidden = false;
        contentEl.hidden = true;

        if (preset === 'custom') {
            const v = validateCustomRange(elsFrom, elsTo);
            if (!v.ok) {
                loadingEl.hidden = true;
                contentEl.hidden = true;
                setResultsPanelVisible(false);
                showError(v.msg);
                return;
            }
            fromVal = v.from;
            toVal = v.to;
        }

        const url = buildMetricsUrl(metricsUrl, {
            preset,
            from: preset === 'custom' ? fromVal : '',
            to: preset === 'custom' ? toVal : '',
        });

        try {
            const res = await fetch(url, {
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
            });

            const data = await res.json().catch(() => ({}));

            if (!res.ok) {
                const errMsgs = data.errors && Object.values(data.errors).flat().filter(Boolean);
                const msg =
                    (errMsgs && errMsgs.length ? errMsgs.join(' ') : '') ||
                    data.message ||
                    `No se pudo cargar (${res.status}).`;
                throw new Error(msg);
            }

            if (!data.success) {
                throw new Error('Respuesta inválida.');
            }

            syncPageUrl(pageUrl, { preset, from: fromVal, to: toVal });

            const cur = data.current_metrics || {};
            const prev = data.previous_metrics || {};
            const curLabel = data.current_period?.label || '';
            const prevLabel = data.previous_period?.label || '';

            const rangeCur = document.getElementById('sales-range-current-label');
            const rangePrev = document.getElementById('sales-range-previous-label');
            if (rangeCur) {
                rangeCur.textContent = curLabel || '—';
            }
            if (rangePrev) {
                rangePrev.textContent = prevLabel || '—';
            }

            const count = Number(cur.sales_count ?? 0);
            const revenue = Number(cur.revenue ?? 0);
            const prevCount = Number(prev.sales_count ?? 0);
            const prevRevenue = Number(prev.revenue ?? 0);

            const countEl = document.getElementById('sales-metric-count');
            const revenueEl = document.getElementById('sales-metric-revenue');
            const prevCountEl = document.getElementById('sales-prev-metric-count');
            const prevRevenueEl = document.getElementById('sales-prev-metric-revenue');
            if (countEl) countEl.textContent = count.toLocaleString('es-CR');
            if (revenueEl) revenueEl.textContent = formatColones(revenue);
            if (prevCountEl) prevCountEl.textContent = prevCount.toLocaleString('es-CR');
            if (prevRevenueEl) prevRevenueEl.textContent = formatColones(prevRevenue);

            const countDelta = count - prevCount;
            const revenueDelta = revenue - prevRevenue;

            const empty = count === 0 && revenue === 0;
            const emptyEl = document.getElementById('sales-empty-state');
            if (emptyEl) emptyEl.hidden = !empty;

            renderComparisonValue(document.getElementById('sales-compare-revenue'), {
                delta: revenueDelta,
                formatter: formatSignedColones,
            });

            renderComparisonValue(document.getElementById('sales-compare-count'), {
                delta: countDelta,
                formatter: formatSignedInt,
            });

            contentEl.hidden = false;
        } catch (e) {
            contentEl.hidden = true;
            setResultsPanelVisible(false);
            showError(e instanceof Error ? e.message : 'Error al cargar.');
        } finally {
            loadingEl.hidden = true;
        }
    }

    root.querySelectorAll('.period-btn[data-preset]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const next = btn.getAttribute('data-preset');
            if (!next || next === preset) return;
            const previousPreset = preset;
            preset = next;

            if (previousPreset === 'custom' && preset !== 'custom') {
                clearCustomInputs();
                fromVal = '';
                toVal = '';
            }

            if (preset === 'custom') {
                const def = defaultCustomRangeYmd();
                if (!fromVal || !toVal || previousPreset !== 'custom') {
                    fillPartsFromYmd(elsFrom, def.from);
                    fillPartsFromYmd(elsTo, def.to);
                    fromVal = def.from;
                    toVal = def.to;
                }
                updatePresetButtons();
                void loadMetrics();
                elsFrom.d.focus();
                return;
            }

            updatePresetButtons();
            void loadMetrics();
        });
    });

    applyBtn.addEventListener('click', () => {
        preset = 'custom';
        updatePresetButtons();
        void loadMetrics();
    });

    updatePresetButtons();

    if (preset === 'custom') {
        if (!fromVal || !toVal) {
            const def = defaultCustomRangeYmd();
            fillPartsFromYmd(elsFrom, def.from);
            fillPartsFromYmd(elsTo, def.to);
            fromVal = def.from;
            toVal = def.to;
        }
        void loadMetrics();
    } else {
        void loadMetrics();
    }
}

document.addEventListener('DOMContentLoaded', initSalesPerformanceReport);
