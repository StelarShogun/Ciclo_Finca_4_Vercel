import { createDropdownPortal } from '../shared/combobox-dropdown-portal.js';

function getCSRFToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
}

function esc(text) {
    const d = document.createElement('div');
    d.textContent = String(text ?? '');
    return d.innerHTML;
}

function formatColones(value) {
    const n = Math.round(Number(value) || 0);
    return `₡${n.toLocaleString('es-CR')}`;
}

async function fetchSaleProducts(q) {
    const params = new URLSearchParams();
    params.set('q', q);
    params.set('supplier_id', '0');
    params.set('context', 'sale');
    const res = await fetch(`/admin/products/search?${params.toString()}`, {
        headers: { Accept: 'application/json', 'X-CSRF-TOKEN': getCSRFToken() },
        credentials: 'same-origin',
    });
    const text = await res.text();
    let data = null;
    try {
        data = text ? JSON.parse(text) : null;
    } catch {
        data = null;
    }
    if (!res.ok) {
        throw new Error(data?.message || `No se pudo buscar productos (HTTP ${res.status}).`);
    }
    return data?.products ?? [];
}

/**
 * @param {HTMLElement} row
 * @param {{ onSelected?: () => void }} options
 */
export function initSaleProductCombobox(row, { onSelected } = {}) {
    const wrapper = row.querySelector('.sale-product-combobox');
    const searchInput = row.querySelector('.sale-product-search');
    const hiddenInput = row.querySelector('.sale-product-id');
    const dropdown = row.querySelector('.sale-product-dropdown');

    if (!wrapper || !searchInput || !hiddenInput || !dropdown) {
        return { reset() {}, destroy() {} };
    }

    const portal = createDropdownPortal(wrapper, dropdown);
    let debounceTimer = null;
    let fetchSeq = 0;
    let activeIndex = -1;
    let lastResults = [];

    function open() {
        wrapper.classList.add('open');
        dropdown.classList.add('open');
        searchInput.setAttribute('aria-expanded', 'true');
        portal.mount();
    }

    function close() {
        wrapper.classList.remove('open');
        dropdown.classList.remove('open');
        searchInput.setAttribute('aria-expanded', 'false');
        activeIndex = -1;
        portal.unmount();
    }

    function applyProduct(product) {
        const price = Number(product.unit_price) || 0;
        hiddenInput.value = String(product.product_id);
        const stock = product.stock != null ? ` · Stock: ${product.stock}` : '';
        searchInput.value = `${product.name} — ${formatColones(price)}${stock}`;
        wrapper.classList.remove('error');

        const priceInput = row.querySelector('input[name*="[precio_unitario]"]');
        if (priceInput) {
            priceInput.value = price > 0 ? price.toFixed(2) : '';
        }

        close();
        if (typeof onSelected === 'function') {
            onSelected(product);
        }
    }

    function highlightActive() {
        dropdown.querySelectorAll('.product-combobox-option').forEach((el, i) => {
            el.classList.toggle('selected', i === activeIndex);
        });
        const active = dropdown.querySelectorAll('.product-combobox-option')[activeIndex];
        active?.scrollIntoView({ block: 'nearest' });
    }

    function renderList(list, { hint } = {}) {
        lastResults = list;
        activeIndex = -1;

        if (hint) {
            dropdown.innerHTML = `<div class="product-combobox-hint">${esc(hint)}</div>`;
            open();
            return;
        }

        if (!list.length) {
            dropdown.innerHTML = `<div class="product-combobox-no-result">Sin resultados</div>`;
            open();
            return;
        }

        dropdown.innerHTML = list
            .map(
                (p, i) => `
            <div class="product-combobox-option" data-idx="${i}" role="option">
                <div class="product-combobox-option-info">
                    <div class="product-combobox-option-name">${esc(p.name)}</div>
                    <div class="product-combobox-option-meta">
                        <code>${esc(p.sku)}</code>
                        <span>${esc(formatColones(p.unit_price))}</span>
                        ${p.stock != null ? `<span>Stock: ${esc(p.stock)}</span>` : ''}
                    </div>
                </div>
            </div>`
            )
            .join('');

        dropdown.querySelectorAll('.product-combobox-option').forEach((option) => {
            option.addEventListener('mousedown', (e) => {
                e.preventDefault();
                const idx = Number(option.getAttribute('data-idx'));
                const p = list[idx];
                if (p) {
                    applyProduct(p);
                }
            });
        });

        open();
    }

    async function runSearch(q) {
        const seq = ++fetchSeq;
        const trimmed = q.trim();

        if (trimmed.length === 1) {
            renderList([], { hint: 'Escribí al menos 2 caracteres (nombre o SKU)…' });
            return;
        }

        try {
            const products = await fetchSaleProducts(trimmed);
            if (seq !== fetchSeq) {
                return;
            }
            renderList(products);
        } catch {
            if (seq !== fetchSeq) {
                return;
            }
            renderList([], { hint: 'No se pudo cargar la búsqueda. Intentá de nuevo.' });
        }
    }

    function scheduleSearch() {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => {
            void runSearch(searchInput.value);
        }, 220);
    }

    function reset() {
        hiddenInput.value = '';
        searchInput.value = '';
        wrapper.classList.remove('error');
        close();
        dropdown.innerHTML = '';
    }

    function destroy() {
        clearTimeout(debounceTimer);
        document.removeEventListener('click', onDocClick);
        searchInput.removeEventListener('focus', onFocus);
        searchInput.removeEventListener('input', onInput);
        searchInput.removeEventListener('keydown', onKeydown);
        searchInput.removeEventListener('blur', onBlur);
        close();
    }

    function onDocClick(e) {
        if (!wrapper.contains(e.target) && !dropdown.contains(e.target)) {
            close();
        }
    }

    function onFocus() {
        void runSearch(searchInput.value);
    }

    function onInput() {
        hiddenInput.value = '';
        const priceInput = row.querySelector('input[name*="[precio_unitario]"]');
        if (priceInput) {
            priceInput.value = '';
        }
        scheduleSearch();
    }

    function onBlur() {
        window.setTimeout(() => {
            const match = lastResults.find((p) => String(p.product_id) === hiddenInput.value);
            if (!match && searchInput.value.trim() && !hiddenInput.value) {
                searchInput.value = '';
            }
        }, 150);
    }

    function onKeydown(e) {
        if (!dropdown.classList.contains('open') || !lastResults.length) {
            if (e.key === 'ArrowDown') {
                void runSearch(searchInput.value);
            }
            return;
        }

        if (e.key === 'ArrowDown') {
            e.preventDefault();
            activeIndex = Math.min(activeIndex + 1, lastResults.length - 1);
            highlightActive();
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            activeIndex = Math.max(activeIndex - 1, 0);
            highlightActive();
        } else if (e.key === 'Enter' && activeIndex >= 0) {
            e.preventDefault();
            const p = lastResults[activeIndex];
            if (p) {
                applyProduct(p);
            }
        } else if (e.key === 'Escape') {
            close();
        }
    }

    document.addEventListener('click', onDocClick);
    searchInput.addEventListener('focus', onFocus);
    searchInput.addEventListener('input', onInput);
    searchInput.addEventListener('keydown', onKeydown);
    searchInput.addEventListener('blur', onBlur);

    return { reset, destroy, setError: (on) => wrapper.classList.toggle('error', Boolean(on)) };
}

export function productRowComboboxMarkup(index) {
    return `
        <div class="form-group form-group--product-combobox">
            <label for="sale-product-search-${index}">Producto</label>
            <div class="product-combobox sale-product-combobox" id="sale-product-combobox-${index}">
                <input
                    type="text"
                    id="sale-product-search-${index}"
                    class="product-combobox-input sale-product-search"
                    placeholder="Buscar por nombre o SKU…"
                    autocomplete="off"
                    role="combobox"
                    aria-expanded="false"
                    aria-controls="sale-product-dropdown-${index}"
                >
                <input type="hidden" name="items[${index}][product_id]" class="sale-product-id" value="" required>
                <i class="fas fa-chevron-down product-combobox-chevron" aria-hidden="true"></i>
                <div
                    id="sale-product-dropdown-${index}"
                    class="product-combobox-dropdown sale-product-dropdown"
                    role="listbox"
                ></div>
            </div>
        </div>`;
}
