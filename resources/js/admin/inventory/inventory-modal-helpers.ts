// @ts-nocheck
/**
 * Helpers that only the inventory modal bundle needs. Kept in a separate
 * module so the initial chunk (filters/actions/chrome) does not pull in
 * the combobox + dropdown portal helpers.
 */
import { initStaticSearchCombobox, setComboboxFieldError } from '../shared/static-search-combobox';
import { createDropdownPortal } from '../shared/combobox-dropdown-portal';
import { qs, escapeHtml, readJsonOrThrow } from './inventory-shared';

export function applyServerFieldErrors(form, errors) {
    if (!form || !errors) return;
    Object.keys(errors).forEach((field) => {
        const msg = errors[field]?.[0];
        if (!msg) return;
        const input = qs(`[name="${field}"]`, form);
        if (!input) return;
        const combobox = input.closest('.form-group')?.querySelector('.brand-combobox');
        if (combobox) {
            setComboboxFieldError(combobox, msg);
            return;
        }
        const group = input.closest('.form-group');
        if (!group) return;
        let err = group.querySelector('.js-server-field-error');
        if (!err) {
            err = document.createElement('p');
            err.className = 'field-error js-server-field-error';
            group.appendChild(err);
        }
        err.textContent = msg;
    });
}

export function initBrandCombobox(searchInputId, hiddenInputId, dropdownId, wrapperId) {
    return initStaticSearchCombobox({
        searchInputId,
        hiddenInputId,
        dropdownId,
        wrapperId,
        options: window.inventoryBrands || [],
        getId: (b) => b.id,
        getLabel: (b) => b.name,
        placeholder: 'Escribe para buscar una marca...',
    });
}

/**
 * Async combobox for product search (variants selector). Lives in the modal
 * helpers bundle because it is only used inside variant editing flows.
 */
export function initProductSearchCombobox({ searchInputId, hiddenInputId, dropdownId, wrapperId, onSelected }) {
    const searchInput = qs('#' + searchInputId);
    const hiddenInput = qs('#' + hiddenInputId);
    const dropdown = qs('#' + dropdownId);
    const wrapper = qs('#' + wrapperId);
    const chevron = wrapper?.querySelector('.brand-combobox-chevron');
    if (!searchInput || !hiddenInput || !dropdown || !wrapper) return null;

    const dropdownPortal = createDropdownPortal(wrapper, dropdown);
    let isOpen = false;
    let activeIndex = -1;
    let lastResults = [];
    let debounceTimer = null;
    let abortController = null;
    let currentBaseProductId = null;
    let excludedVariantIds = new Set();

    function setBaseContext({ baseProductId, currentVariants }) {
        currentBaseProductId = baseProductId ? String(baseProductId) : null;
        const ids = (Array.isArray(currentVariants) ? currentVariants : [])
            .map((v) => String(v?.product_id ?? ''))
            .filter(Boolean);
        excludedVariantIds = new Set(ids);
        if (currentBaseProductId) excludedVariantIds.add(String(currentBaseProductId));
    }

    function open() {
        dropdown.classList.add('open');
        wrapper.classList.add('open');
        isOpen = true;
        dropdownPortal.mount();
        if (chevron) chevron.classList.add('rotated');
    }

    function close() {
        dropdown.classList.remove('open');
        wrapper.classList.remove('open');
        isOpen = false;
        activeIndex = -1;
        dropdownPortal.unmount();
        if (chevron) chevron.classList.remove('rotated');
    }

    function reset() {
        hiddenInput.value = '';
        searchInput.value = '';
        lastResults = [];
        activeIndex = -1;
        dropdown.innerHTML = '';
        close();
        onSelected?.(null);
    }

    function renderResults(results, { query }) {
        dropdown.innerHTML = '';
        lastResults = results;
        activeIndex = results.length ? 0 : -1;

        if (!results.length) {
            const noResult = document.createElement('div');
            noResult.className = 'brand-combobox-no-result';
            noResult.textContent = query && query.trim().length >= 2 ? 'Sin resultados' : 'Escribí al menos 2 caracteres';
            dropdown.appendChild(noResult);
            return;
        }

        results.forEach((p, idx) => {
            const row = document.createElement('div');
            row.className = 'brand-combobox-option';
            if (idx === activeIndex) row.classList.add('selected');

            const name = String(p?.name ?? '');
            const sku = String(p?.sku ?? '');
            const unitPrice = p?.unit_price !== undefined ? `₡${p.unit_price}` : '';
            row.innerHTML = `
                <div style="display:flex;flex-direction:column;gap:2px;">
                    <div style="font-weight:600;line-height:1.15;">${escapeHtml(name)}</div>
                    <div style="font-size:12px;color:#6b7280;">${escapeHtml(sku)}${unitPrice ? ` · ${escapeHtml(unitPrice)}` : ''}</div>
                </div>
            `;

            row.addEventListener('mousedown', (e) => {
                e.preventDefault();
                selectProduct(p);
            });

            dropdown.appendChild(row);
        });
    }

    function setActiveIndex(nextIndex) {
        const options = Array.from(dropdown.querySelectorAll('.brand-combobox-option'));
        if (!options.length) return;
        const safe = Math.max(0, Math.min(options.length - 1, nextIndex));
        activeIndex = safe;
        options.forEach((el, i) => el.classList.toggle('selected', i === safe));
        options[safe]?.scrollIntoView({ block: 'nearest' });
    }

    function selectProduct(product) {
        if (!product || !product.product_id) return;
        hiddenInput.value = String(product.product_id);
        searchInput.value = String(product.name || '');
        wrapper.classList.remove('error');
        close();
        onSelected?.(product);
    }

    function showLoading() {
        dropdown.innerHTML = '';
        const msg = document.createElement('div');
        msg.className = 'brand-combobox-no-result';
        msg.textContent = 'Buscando…';
        dropdown.appendChild(msg);
    }

    async function fetchProducts(query) {
        const q = String(query || '').trim();
        if (q.length < 2) {
            renderResults([], { query: q });
            return;
        }

        abortController?.abort();
        abortController = new AbortController();

        showLoading();

        const url = new URL('/admin/products/search', window.location.origin);
        url.searchParams.set('q', q);

        const response = await fetch(url.toString(), {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            signal: abortController.signal,
        });

        const data = await readJsonOrThrow(response, 'No se pudo buscar productos.');
        const products = Array.isArray(data?.products) ? data.products : [];
        const filtered = products.filter((p) => {
            const id = String(p?.product_id ?? '');
            if (!id) return false;
            return !excludedVariantIds.has(id);
        });

        renderResults(filtered, { query: q });
    }

    function scheduleFetch() {
        const q = searchInput.value;
        if (!isOpen) open();
        hiddenInput.value = '';
        onSelected?.(null);
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => {
            fetchProducts(q).catch((err) => {
                if (err?.name === 'AbortError') return;
                dropdown.innerHTML = '';
                const noResult = document.createElement('div');
                noResult.className = 'brand-combobox-no-result';
                noResult.textContent = 'No se pudo buscar. Probá de nuevo.';
                dropdown.appendChild(noResult);
            });
        }, 220);
    }

    searchInput.addEventListener('focus', () => open());
    searchInput.addEventListener('input', scheduleFetch);
    searchInput.addEventListener('keydown', (e) => {
        if (!isOpen && (e.key === 'ArrowDown' || e.key === 'Enter')) {
            open();
            scheduleFetch();
            return;
        }
        if (!isOpen) return;

        if (e.key === 'Escape') {
            e.preventDefault();
            close();
            return;
        }
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            setActiveIndex(activeIndex + 1);
            return;
        }
        if (e.key === 'ArrowUp') {
            e.preventDefault();
            setActiveIndex(activeIndex - 1);
            return;
        }
        if (e.key === 'Enter') {
            const pick = lastResults[activeIndex];
            if (pick) {
                e.preventDefault();
                selectProduct(pick);
            }
        }
    });
    searchInput.addEventListener('blur', () => {
        setTimeout(() => {
            if (!hiddenInput.value) searchInput.value = '';
            close();
        }, 150);
    });
    if (chevron) {
        chevron.addEventListener('mousedown', (e) => {
            e.preventDefault();
            if (isOpen) close();
            else { searchInput.focus(); open(); scheduleFetch(); }
        });
    }

    return { reset, open, close, setBaseContext, selectProduct };
}
