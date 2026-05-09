// Selector shortcuts
const qs = (s, r = document) => r.querySelector(s);
const qsa = (s, r = document) => Array.from(r.querySelectorAll(s));

// Retrieve CSRF token from meta tag or hidden input
function getCSRFToken() {
    const metaToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const inputToken = document.querySelector('input[name="_token"]')?.value;
    return metaToken || inputToken || '';
}

/** Texto 422 desde respuesta Laravel (modal validación). */
function jsonValidationMessage(data) {
    if (!data) return '';
    if (data.errors) {
        const flat = Object.values(data.errors).flat().filter(Boolean);
        if (flat.length) return flat.join('\n');
    }
    return typeof data.message === 'string' ? data.message : '';
}

/** Breadcrumb categoría para modal detalle (requiere category.parent en JSON). */
function categoryPath(category) {
    if (!category) return '-';
    const parentName = category.parent?.name;
    const currentName = category.name || '';
    return parentName ? `${parentName} > ${currentName}` : (currentName || '-');
}

function jsonHeaders() {
    return {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    };
}

function escapeHtml(raw) {
    return String(raw)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function escapeHtmlAttr(raw) {
    // For attributes; same escaping as HTML content.
    return escapeHtml(raw);
}

async function safeParseJsonResponse(response) {
    const contentType = response.headers.get('content-type') || '';

    // If we were redirected (often to login) or content isn't JSON, treat as session/HTML issue.
    if (response.redirected || !contentType.includes('application/json')) {
        const text = await response.text().catch(() => '');
        const looksLikeHtml = /<html|<body|<!doctype/i.test(text);
        const msg = looksLikeHtml
            ? 'Tu sesión parece expirada. Recargá la página o volvé a iniciar sesión.'
            : 'La respuesta del servidor no es JSON. Recargá la página e intentá de nuevo.';
        throw Object.assign(new Error(msg), {
            status: response.status,
            redirected: response.redirected,
        });
    }

    return await response.json();
}

async function readJsonOrThrow(response, fallbackMessage) {
    if (response.ok) {
        return await safeParseJsonResponse(response);
    }

    try {
        const data = await safeParseJsonResponse(response);
        const msg = data?.message || fallbackMessage || 'Ocurrió un error.';
        throw Object.assign(new Error(msg), { status: response.status, data });
    } catch (err) {
        // safeParseJsonResponse may throw on HTML/redirect; preserve its message.
        if (err instanceof Error) throw err;
        throw Object.assign(new Error(fallbackMessage || 'Ocurrió un error.'), { status: response.status });
    }
}

function renderVariantsListHtml({ baseProductId, variants }) {
    const list = Array.isArray(variants) ? variants : [];
    if (!list.length) {
        return '<span class="text-muted">Sin variantes registradas.</span>';
    }

    const items = list
        .map((v) => {
            const variantId = String(v.product_id ?? '');
            const name = String(v.name ?? '');
            const status = String(v.status ?? '');
            const price = v.sale_price !== undefined ? `₡${v.sale_price}` : '—';
            const stock = v.stock_current !== undefined ? String(v.stock_current) : '—';
            const skuLabel = v.sku !== undefined ? String(v.sku) : '';

            return `
                <div class="variant-row" style="display:flex;align-items:center;justify-content:space-between;gap:10px;padding:8px 0;border-bottom:1px solid #f3f4f6;">
                    <div style="min-width:0;">
                        <div style="font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${escapeHtml(name)}</div>
                        <div class="text-muted" style="font-size:12px;">SKU ${escapeHtml(skuLabel)} · ${escapeHtml(price)} · Stock ${escapeHtml(stock)} · ${escapeHtml(status)}</div>
                    </div>
                    <div style="display:flex;gap:6px;flex-shrink:0;">
                        <button type="button"
                                class="btn btn-secondary js-edit-variant"
                                data-base-product-id="${String(baseProductId)}"
                                data-variant-product-id="${variantId}">
                            <i class="fas fa-pen"></i> Editar
                        </button>
                        <button type="button"
                                class="btn btn-secondary js-delete-variant"
                                data-base-product-id="${String(baseProductId)}"
                                data-variant-product-id="${variantId}"
                                data-variant-name="${escapeHtmlAttr(name)}">
                            <i class="fas fa-trash"></i> Eliminar
                        </button>
                    </div>
                </div>
            `;
        })
        .join('');

    return `<div class="variant-list">${items}</div>`;
}

/** Sincroniza botones estrella destacado (vista tabla y cuadrícula) tras toggle o recarga de datos. */
function syncFeaturedStarButtons(productId, isFeatured) {
    const id = String(productId);
    const on = Boolean(isFeatured);
    qsa('.featured-star-btn').forEach((b) => {
        if (String(b.dataset.productId) !== id) {
            return;
        }
        b.dataset.featured = on ? '1' : '0';
        b.classList.toggle('is-featured', on);
        b.setAttribute('aria-pressed', on ? 'true' : 'false');
        b.setAttribute('aria-label', on ? 'Quitar de destacados en tienda' : 'Marcar como destacado en tienda');
        const icon = b.querySelector('.featured-star-icon');
        if (icon) {
            icon.classList.toggle('fas', on);
            icon.classList.toggle('far', !on);
        }
    });
}

/**
 * Rellena el select de subcategorías según el padre.
 * En el filtro (#subcategory-filter), si no hay padre, lista todas las subcategorías (para poder filtrar solo por sub).
 */
function fillSubcategoryOptions(subSelect, parentId, selectedId = '') {
    if (!subSelect) return;
    const tree = window.inventoryCategoryTree || {};
    const isFilter = subSelect.id === 'subcategory-filter';
    const emptyLabel = isFilter ? 'Todos los tipos' : 'Solo categoría padre (sin tipo)';

    subSelect.innerHTML = '';
    const opt0 = document.createElement('option');
    opt0.value = '';
    opt0.textContent = emptyLabel;
    subSelect.appendChild(opt0);

    let subs = [];
    const hasParent = parentId !== '' && parentId !== null && parentId !== undefined;

    if (!hasParent && isFilter) {
        Object.keys(tree).forEach((pid) => {
            (tree[pid] || []).forEach((sub) => {
                subs.push(sub);
            });
        });
    } else if (hasParent) {
        const key = String(parentId);
        const num = Number(parentId);
        subs = tree[key] || tree[parentId] || (Number.isFinite(num) ? tree[num] : []) || [];
        if (!subs.length && typeof tree === 'object' && tree !== null) {
            for (const k of Object.keys(tree)) {
                if (String(k) === key || Number(k) === num) {
                    subs = tree[k] || [];
                    break;
                }
            }
        }
    }

    subs.forEach((sub) => {
        const opt = document.createElement('option');
        opt.value = String(sub.category_id);
        opt.textContent = sub.name;
        if (selectedId !== '' && selectedId !== undefined && String(selectedId) === String(sub.category_id)) {
            opt.selected = true;
        }
        subSelect.appendChild(opt);
    });
}

/** category_id enviado al backend: subcategoría si hay, si no la categoría raíz elegida. */
function syncFinalCategory(parentSelect, subSelect, hiddenCategoryInput) {
    if (!hiddenCategoryInput) return;
    const parentId = parentSelect?.value || '';
    const subId = subSelect?.value || '';
    if (subId) {
        hiddenCategoryInput.value = subId;
    } else if (parentId) {
        hiddenCategoryInput.value = parentId;
    } else {
        hiddenCategoryInput.value = '';
    }
}

/** CF4-84 — selectores por atributo (JSON: attributes; alias dimensions) */
function refreshClassificationFields(containerSelector, categoryId, preselectedIds) {
    const container = qs(containerSelector);
    if (!container) return;
    container.innerHTML = '';
    if (!categoryId) {
        return;
    }
    fetch(`/classifications/catalog/${categoryId}/options`, {
        credentials: 'same-origin',
        headers: {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        },
    })
        .then((r) => {
            if (!r.ok) throw new Error('options');
            return r.json();
        })
        .then((data) => {
            const attrs = data.attributes || data.dimensions || [];
            if (!attrs.length) {
                const p = document.createElement('p');
                p.className = 'form-text text-muted';
                p.style.fontSize = '0.9rem';
                p.textContent =
                    'No hay atributos para este tipo. Elegí una subcategoría o cargá atributos y valores en «Opciones por tipo».';
                container.appendChild(p);
                return;
            }
            const preset = Array.isArray(preselectedIds) ? preselectedIds.map((x) => Number(x)) : [];
            attrs.forEach((attr) => {
                const wrap = document.createElement('div');
                wrap.className = 'form-group';
                const label = document.createElement('label');
                label.setAttribute('for', `cf-attr-${attr.id}`);
                label.textContent = attr.label;
                const select = document.createElement('select');
                select.id = `cf-attr-${attr.id}`;
                select.name = 'classification_value_ids[]';
                const opt0 = document.createElement('option');
                opt0.value = '';
                opt0.textContent = '— Ninguno —';
                select.appendChild(opt0);
                (attr.values || []).forEach((v) => {
                    const opt = document.createElement('option');
                    opt.value = String(v.id);
                    opt.textContent = v.value;
                    if (preset.includes(Number(v.id))) {
                        opt.selected = true;
                    }
                    select.appendChild(opt);
                });
                wrap.appendChild(label);
                wrap.appendChild(select);
                container.appendChild(wrap);
            });
        })
        .catch(() => {
            const p = document.createElement('p');
            p.className = 'form-text text-muted';
            p.style.color = '#b91c1c';
            p.textContent = 'No se pudieron cargar atributos y valores. Probá de nuevo.';
            container.appendChild(p);
        });
}

function syncParentCategoryHiddenInput(parentSelect, parentHiddenInput) {
    if (!parentHiddenInput || !parentSelect) {
        return;
    }
    parentHiddenInput.value = parentSelect.value || '';
}

function bindDependentCategorySelectors({ parentSelect, subSelect, hiddenCategoryInput, parentCategoryHiddenInput }) {
    if (!parentSelect || !subSelect || !hiddenCategoryInput) return;
    parentSelect.addEventListener('change', () => {
        fillSubcategoryOptions(subSelect, parentSelect.value);
        syncFinalCategory(parentSelect, subSelect, hiddenCategoryInput);
        syncParentCategoryHiddenInput(parentSelect, parentCategoryHiddenInput);
    });
    subSelect.addEventListener('change', () => {
        syncFinalCategory(parentSelect, subSelect, hiddenCategoryInput);
        syncParentCategoryHiddenInput(parentSelect, parentCategoryHiddenInput);
    });
}

/**
 * Custom combobox for brand selection.
 * Renders a filterable dropdown from window.inventoryBrands.
 */
function initBrandCombobox(searchInputId, hiddenInputId, dropdownId, wrapperId) {
    const searchInput = qs('#' + searchInputId);
    const hiddenInput = qs('#' + hiddenInputId);
    const dropdown   = qs('#' + dropdownId);
    const wrapper    = qs('#' + wrapperId);
    const chevron    = wrapper?.querySelector('.brand-combobox-chevron');
    if (!searchInput || !hiddenInput || !dropdown || !wrapper) return null;

    let isOpen = false;
    const brands = window.inventoryBrands || [];

    function renderOptions(query) {
        const q = (query || '').toLowerCase().trim();
        const filtered = q ? brands.filter(b => b.name.toLowerCase().includes(q)) : brands;
        dropdown.innerHTML = '';
        if (!filtered.length) {
            const noResult = document.createElement('div');
            noResult.className = 'brand-combobox-no-result';
            noResult.textContent = 'Sin resultados';
            dropdown.appendChild(noResult);
            return;
        }
        filtered.forEach(brand => {
            const item = document.createElement('div');
            item.className = 'brand-combobox-option';
            if (String(brand.id) === String(hiddenInput.value)) item.classList.add('selected');
            item.textContent = brand.name;
            item.addEventListener('mousedown', (e) => {
                e.preventDefault();
                selectBrand(brand.id, brand.name);
            });
            dropdown.appendChild(item);
        });
    }

    function selectBrand(id, name) {
        hiddenInput.value = id;
        searchInput.value = name;
        wrapper.classList.remove('error');
        close();
    }

    function open() {
        renderOptions(searchInput.value);
        dropdown.classList.add('open');
        wrapper.classList.add('open');
        isOpen = true;
        if (chevron) chevron.classList.add('rotated');
    }

    function close() {
        dropdown.classList.remove('open');
        wrapper.classList.remove('open');
        isOpen = false;
        if (chevron) chevron.classList.remove('rotated');
    }

    function reset() {
        hiddenInput.value = '';
        searchInput.value = '';
        close();
    }

    function setValue(id) {
        const brand = brands.find(b => String(b.id) === String(id));
        if (brand) selectBrand(brand.id, brand.name);
        else reset();
    }

    searchInput.addEventListener('focus', () => open());
    searchInput.addEventListener('input', () => {
        hiddenInput.value = '';
        if (!isOpen) open();
        else renderOptions(searchInput.value);
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
            else { searchInput.focus(); open(); }
        });
    }

    return { selectBrand, setValue, open, close, reset };
}

/**
 * Async combobox for product search (variants selector).
 * Fetches from /admin/products/search?q=... and allows keyboard selection.
 */
function initProductSearchCombobox({ searchInputId, hiddenInputId, dropdownId, wrapperId, onSelected }) {
    const searchInput = qs('#' + searchInputId);
    const hiddenInput = qs('#' + hiddenInputId);
    const dropdown = qs('#' + dropdownId);
    const wrapper = qs('#' + wrapperId);
    const chevron = wrapper?.querySelector('.brand-combobox-chevron');
    if (!searchInput || !hiddenInput || !dropdown || !wrapper) return null;

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
        if (chevron) chevron.classList.add('rotated');
    }

    function close() {
        dropdown.classList.remove('open');
        wrapper.classList.remove('open');
        isOpen = false;
        activeIndex = -1;
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

// Request a fresh CSRF token from the server
async function renewCSRFToken() {
    try {
        const response = await fetch('/csrf-token', {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        
        if (response.ok) {
            const data = await response.json();
            const metaTag = document.querySelector('meta[name="csrf-token"]');
            if (metaTag && data.csrf_token) {
                metaTag.setAttribute('content', data.csrf_token);
                return data.csrf_token;
            }
        }
    } catch (error) {
        console.warn('No se pudo renovar el token CSRF:', error);
    }
    return null;
}

// Automatically retry requests when 419 (CSRF mismatch) occurs
async function handleCSRFError(originalRequest) {
    console.log('Token CSRF expirado, renovando...');
    
    const newToken = await renewCSRFToken();
    
    if (newToken) {
        if (originalRequest.headers) {
            originalRequest.headers['X-CSRF-TOKEN'] = newToken;
        }
        return fetch(originalRequest.url, originalRequest);
    } else {
        throw new Error('No se pudo renovar el token CSRF');
    }
}

// Wrapper for fetch that handles CSRF errors transparently
async function smartFetch(url, options = {}) {
    if (!options.headers) {
        options.headers = {};
    }
    if (!options.headers['X-CSRF-TOKEN']) {
        options.headers['X-CSRF-TOKEN'] = getCSRFToken();
    }
    
    try {
        const response = await fetch(url, options);
        
        if (response.status === 419) {
            console.log('Error CSRF detectado, reintentando automáticamente...');
            showSubtleNotification('Renovando sesión...', 'info');
            
            const retryOptions = {
                ...options,
                headers: {
                    ...options.headers,
                    'X-CSRF-TOKEN': await renewCSRFToken()
                }
            };
            
            const retryResponse = await fetch(url, retryOptions);
            showSubtleNotification('Sesión renovada', 'success');
            return retryResponse;
        }
        
        return response;
    } catch (error) {
        console.error('Error en smartFetch:', error);
        throw error;
    }
}

// Display a temporary toast notification
function showSubtleNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `subtle-notification ${type}`;
    notification.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check' : type === 'error' ? 'times' : 'info'}-circle"></i>
        <span>${message}</span>
    `;
    
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: ${type === 'success' ? '#10b981' : type === 'error' ? '#ef4444' : '#3b82f6'};
        color: white;
        padding: 12px 16px;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        z-index: 10000;
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 14px;
        font-weight: 500;
        transform: translateX(100%);
        transition: transform 0.3s ease;
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.transform = 'translateX(0)';
    }, 100);
    
    setTimeout(() => {
        notification.style.transform = 'translateX(100%)';
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 300);
    }, 3000);
}

// Show loading state on a button
function setButtonLoading(button, isLoading, originalText = null) {
    if (isLoading) {
        button.disabled = true;
        button.dataset.originalText = button.innerHTML;
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        button.classList.add('loading');
    } else {
        button.disabled = false;
        button.innerHTML = originalText || button.dataset.originalText || button.innerHTML;
        button.classList.remove('loading');
    }
}

// Show loading state on an action button (e.g., in a table)
function setActionButtonLoading(button, isLoading, action = '') {
    if (isLoading) {
        button.disabled = true;
        button.dataset.originalContent = button.innerHTML;
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        button.classList.add('action-loading');
        button.style.opacity = '0.7';
    } else {
        button.disabled = false;
        button.innerHTML = button.dataset.originalContent || button.innerHTML;
        button.classList.remove('action-loading');
        button.style.opacity = '1';
    }
}

// Create a full‑screen progress overlay for long operations
function showLongOperationIndicator(message = 'Procesando...') {
    const indicator = document.createElement('div');
    indicator.className = 'long-operation-indicator';
    indicator.innerHTML = `
        <i class="fas fa-spinner fa-spin"></i>
        <span>${message}</span>
    `;
    document.body.appendChild(indicator);
    return indicator;
}

function hideLongOperationIndicator(indicator) {
    if (indicator && indicator.parentNode) {
        indicator.parentNode.removeChild(indicator);
    }
}

// Simple progress bar for operations like file upload
function showProgressBar() {
    const progressBar = document.createElement('div');
    progressBar.className = 'progress-indicator';
    document.body.appendChild(progressBar);
    return progressBar;
}

function hideProgressBar(progressBar) {
    if (progressBar && progressBar.parentNode) {
        progressBar.parentNode.removeChild(progressBar);
    }
}

// Temporary success feedback on a button
function showSuccessFeedback(button, message = '¡Completado!') {
    const originalContent = button.innerHTML;
    button.innerHTML = `<i class="fas fa-check"></i> ${message}`;
    button.classList.add('action-success');
    
    setTimeout(() => {
        button.innerHTML = originalContent;
        button.classList.remove('action-success');
    }, 2000);
}

// Temporary error feedback on a button
function showErrorFeedback(button, message = 'Error') {
    const originalContent = button.innerHTML;
    button.innerHTML = `<i class="fas fa-times"></i> ${message}`;
    button.classList.add('action-error');
    
    setTimeout(() => {
        button.innerHTML = originalContent;
        button.classList.remove('action-error');
    }, 2000);
}

// Disable a modal during async operations
function setModalLoading(modal, isLoading) {
    if (isLoading) {
        modal.classList.add('loading');
        modal.style.pointerEvents = 'none';
    } else {
        modal.classList.remove('loading');
        modal.style.pointerEvents = 'auto';
    }
}

// Smooth scroll to top
function smoothScrollTop() {
    try {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    } catch {
        window.scrollTo(0, 0);
    }
}

// Sidebar toggle with state persistence
(function initSidebarToggle() {
    const btn = qs('#sidebarToggle');
    if (!btn) return;

    const BODY_COLLAPSED = 'sidebar-collapsed';
    const KEY = 'cp_sidebar_collapsed';

    const saved = localStorage.getItem(KEY);
    if (saved === '1') {
        document.body.classList.add(BODY_COLLAPSED);
        btn.classList.add('is-collapsed');
    }

    const setCollapsed = (collapsed) => {
        document.body.classList.toggle(BODY_COLLAPSED, collapsed);
        btn.classList.toggle('is-collapsed', collapsed);
        localStorage.setItem(KEY, collapsed ? '1' : '0');
    };

    btn.addEventListener('click', () => {
        const collapsed = !document.body.classList.contains(BODY_COLLAPSED);
        setCollapsed(collapsed);
        btn.setAttribute('aria-pressed', collapsed ? 'true' : 'false');
    });
})();

// View switcher for inventory list with persistence
(function initViewSwitcher() {
    const viewButtons = qsa('.view-btn');
    const tableView = qs('.table-view');
    const gridView = qs('.grid-view');
    const KEY = 'cp_inventory_view';

    const savedView = localStorage.getItem(KEY);
    if (savedView) {
        setView(savedView);
    }

    viewButtons.forEach(button => {
        button.addEventListener('click', () => {
            const view = button.dataset.view;
            setView(view);
            localStorage.setItem(KEY, view);
        });
    });

    function setView(view) {
        if (view === 'table') {
            tableView.classList.add('active');
            gridView.classList.remove('active');
            qs('.view-btn[data-view="table"]').classList.add('active');
            qs('.view-btn[data-view="grid"]').classList.remove('active');
        } else {
            tableView.classList.remove('active');
            gridView.classList.add('active');
            qs('.view-btn[data-view="table"]').classList.remove('active');
            qs('.view-btn[data-view="grid"]').classList.add('active');
        }
    }
})();

// Modals init and handlers
(function initModals() {
    // Modal: New product
    const newProductModal = qs('#new-product-modal');
    const openNewProductModalBtn = qs('#open-new-product-modal');
    const closeNewProductModalBtn = qs('#close-new-product-modal');
    const cancelNewProductBtn = qs('#cancel-new-product');
    const saveNewProductBtn = qs('#save-new-product');
    const newProductForm = qs('#new-product-form');
    const newParentCategory = qs('#new-parent-category');
    const newSubcategory = qs('#new-subcategory');
    const newFinalCategory = qs('#new-category');
    const newParentCategoryHidden = qs('#new-parent-category-id');

    // --- Gallery input validation (webkitdirectory may pick non-image files) ---
    const VALID_IMAGE_TYPES = ['image/jpeg','image/png','image/gif','image/svg+xml','image/webp','image/avif'];

    const MAX_IMAGE_SIZE_BYTES = 10 * 1024 * 1024; // 10 MB por imagen (igual al límite del servidor)

    function validateGalleryInput(inputEl, hintEl) {
        if (!inputEl || !inputEl.files || inputEl.files.length === 0) return true;
        const images = Array.from(inputEl.files).filter(f => VALID_IMAGE_TYPES.includes(f.type));
        if (images.length === 0) {
            Swal.fire({
                title: 'Sin imágenes válidas',
                text: 'La carpeta seleccionada no contiene imágenes (jpeg, png, webp, gif, svg, avif). Seleccioná una carpeta con imágenes.',
                icon: 'warning',
                confirmButtonText: 'Entendido',
            });
            inputEl.value = '';
            if (hintEl) hintEl.textContent = 'Ningún archivo seleccionado';
            return false;
        }
        const oversized = images.filter(f => f.size > MAX_IMAGE_SIZE_BYTES);
        if (oversized.length > 0) {
            Swal.fire({
                title: 'Error',
                text: 'Ha excedido la capacidad de imágenes que puedes cargar. Cada imagen no puede superar 10 MB.',
                icon: 'error',
                confirmButtonText: 'Entendido',
            });
            inputEl.value = '';
            if (hintEl) hintEl.textContent = 'Ningún archivo seleccionado';
            return false;
        }
        if (hintEl) hintEl.textContent = images.length + ' imagen' + (images.length > 1 ? 'es' : '') + ' seleccionada' + (images.length > 1 ? 's' : '');
        return true;
    }

    const newImagesInput  = qs('#new-images');
    const editImagesInput = qs('#edit-images');

    newImagesInput?.addEventListener('change', function () {
        const hint = this.closest('.form-group')?.querySelector('small');
        validateGalleryInput(this, hint);
    });

    editImagesInput?.addEventListener('change', function () {
        const hint = this.closest('.form-group')?.querySelector('small');
        validateGalleryInput(this, hint);
    });

    if (openNewProductModalBtn) {
        openNewProductModalBtn.addEventListener('click', () => {
            if (newProductForm) {
                newProductForm.reset();
            }
            if (newSubcategory) {
                fillSubcategoryOptions(newSubcategory, '');
            }
            newBrandCombobox?.reset();
            newProductModal.classList.add('active');
            syncFinalCategory(newParentCategory, newSubcategory, newFinalCategory);
            syncParentCategoryHiddenInput(newParentCategory, newParentCategoryHidden);
            refreshClassificationFields('#new-classification-fields', newFinalCategory?.value || '', null);
        });
    }

    if (closeNewProductModalBtn) {
        closeNewProductModalBtn.addEventListener('click', () => {
            newProductModal.classList.remove('active');
        });
    }

    if (cancelNewProductBtn) {
        cancelNewProductBtn.addEventListener('click', () => {
            newProductModal.classList.remove('active');
        });
    }

    if (saveNewProductBtn) {
        saveNewProductBtn.addEventListener('click', () => {
            syncFinalCategory(newParentCategory, newSubcategory, newFinalCategory);
            syncParentCategoryHiddenInput(newParentCategory, newParentCategoryHidden);

            if (newProductForm && typeof newProductForm.reportValidity === 'function' && !newProductForm.reportValidity()) {
                return;
            }

            if (!newFinalCategory || !String(newFinalCategory.value || '').trim()) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        title: 'Categoría',
                        text: 'Elegí la categoría padre. El tipo concreto es opcional, pero si dejás el producto solo en categoría padre (sin tipo) no podrás usar color, talla, etc.',
                        icon: 'info',
                        confirmButtonText: 'Entendido',
                    });
                }
                return;
            }

            if (!qs('#new-brand')?.value) {
                const cb = qs('#new-brand-combobox');
                if (cb) { cb.classList.add('error'); cb.querySelector('input')?.focus(); }
                Swal.fire({ title: 'Marca requerida', text: 'Selecciona una marca antes de guardar el producto.', icon: 'warning', confirmButtonText: 'Entendido' });
                return;
            }
            qs('#new-brand-combobox')?.classList.remove('error');

            // Validate gallery: if a folder was selected, ensure it has at least one image
            if (newImagesInput?.files?.length > 0) {
                const hint = newImagesInput.closest('.form-group')?.querySelector('small');
                if (!validateGalleryInput(newImagesInput, hint)) return;
            }

            setButtonLoading(saveNewProductBtn, true);
            const formData = new FormData(newProductForm);
            // Rebuild images[] with only valid image files (webkitdirectory may include non-images)
            formData.delete('images[]');
            if (newImagesInput?.files?.length > 0) {
                Array.from(newImagesInput.files)
                    .filter(f => VALID_IMAGE_TYPES.includes(f.type))
                    .forEach(f => formData.append('images[]', f));
            }
            formData.set('is_featured', qs('#new-featured')?.checked ? '1' : '0');

            smartFetch(newProductForm.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'Accept': 'application/json'
                }
            })
            .then(response => {
                if (response.status === 413) {
                    throw Object.assign(new Error('PAYLOAD_TOO_LARGE'), { isSizeError: true });
                }
                return response.json();
            })
            .then(data => {
                if (!data) return;
                setButtonLoading(saveNewProductBtn, false);
                if (data.success) {
                    newProductModal.classList.remove('active');
                    Swal.fire({
                        title: 'Producto creado',
                        text: data.message,
                        icon: 'success',
                        timer: 3500,
                        timerProgressBar: true,
                        showConfirmButton: false,
                    }).then(() => { location.reload(); });
                } else if (data.errors) {
                    // Remove previous error messages
                    qsa('.error-message', newProductForm).forEach(el => el.remove());

                    for (const field in data.errors) {
                        const input = qs(`[name="${field}"]`, newProductForm);
                        if (input) {
                            const error = document.createElement('div');
                            error.classList.add('error-message');
                            error.style.color = 'red';
                            error.style.fontSize = '12px';
                            error.textContent = data.errors[field][0];
                            input.parentNode.appendChild(error);
                        }
                    }
                    
                    if (data.csrf_token) {
                        const metaTag = document.querySelector('meta[name="csrf-token"]');
                        if (metaTag) {
                            metaTag.setAttribute('content', data.csrf_token);
                        }
                    }
                    
                    Swal.fire({
                        title: 'Error de validación',
                        text: jsonValidationMessage(data) || data.message || 'Revisa los campos del formulario.',
                        icon: 'error',
                        confirmButtonText: 'Entendido'
                    });
                } else {
                    Swal.fire({
                        title: 'Error',
                        text: data.message || 'Ocurrió un error al crear el producto.',
                        icon: 'error',
                        confirmButtonText: 'Entendido'
                    });
                }
            })
            .catch(error => {
                setButtonLoading(saveNewProductBtn, false);
                console.error('Error:', error);
                Swal.fire({
                    title: 'Error',
                    text: error.isSizeError
                        ? 'Ha excedido la capacidad de imágenes que puedes cargar.'
                        : 'Ocurrió un error inesperado. Por favor, revisa los logs.',
                    icon: 'error',
                    confirmButtonText: 'Entendido'
                });
            });
        });
    }

    // Modal: Edit product
    const editModal = qs('#edit-modal');
    const editBtns = qsa('.edit-btn');
    const closeEditModalBtn = qs('#modal-close');
    const cancelEditBtn = qs('#cancel-edit');
    const saveEditBtn = qs('#save-edit');
    const editProductForm = qs('#edit-product-form');
    const editParentCategory = qs('#edit-parent-category');
    const editSubcategory = qs('#edit-subcategory');
    const editFinalCategory = qs('#edit-category');
    const editParentCategoryHidden = qs('#edit-parent-category-id');

    // CF4-74 — Variants selector (modern UX) inside edit modal
    const editVariantAddBtn = qs('#edit-variant-add-btn');
    const editVariantHidden = qs('#edit-variant-product-id');
    const editVariantSearch = qs('#edit-variant-search');
    const editVariantsList = qs('#edit-variants-list');
    let currentEditProductId = null;
    let currentEditVariants = [];

    const editVariantCombobox = initProductSearchCombobox({
        searchInputId: 'edit-variant-search',
        hiddenInputId: 'edit-variant-product-id',
        dropdownId: 'edit-variant-dropdown',
        wrapperId: 'edit-variant-combobox',
        onSelected: (p) => {
            if (editVariantAddBtn) editVariantAddBtn.disabled = !p?.product_id;
        },
    });

    editBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            const productId = btn.dataset.productId;
            fetch(`/products/${productId}`, {
                credentials: 'same-origin',
                headers: jsonHeaders(),
            })
            .then(response => {
                return readJsonOrThrow(response, 'Error al cargar el producto');
            })
            .then(data => {
                if(data.success){
                    const product = data.data;
                    currentEditProductId = String(productId || '');
                    currentEditVariants = Array.isArray(product.variants) ? product.variants : [];
                    editVariantCombobox?.setBaseContext({
                        baseProductId: currentEditProductId,
                        currentVariants: currentEditVariants,
                    });
                    editVariantCombobox?.reset();
                    if (editVariantAddBtn) editVariantAddBtn.disabled = true;

                    editProductForm.action = `/products/${productId}`;
                    qs('#edit-name').value = product.name || '';
                    qs('#edit-description').value = product.description || '';
                    const currentCategoryId = String(product.category_id || '');
                    const tree = window.inventoryCategoryTree || {};
                    let detectedParentId = '';
                    let detectedSubcategoryId = '';

                    Object.keys(tree).forEach((parentId) => {
                        if (detectedParentId) return;
                        const match = (tree[parentId] || []).find((sub) => String(sub.category_id) === currentCategoryId);
                        if (match) {
                            detectedParentId = String(parentId);
                            detectedSubcategoryId = currentCategoryId;
                        }
                    });

                    if (!detectedParentId) {
                        detectedParentId = currentCategoryId;
                    }

                    if (editParentCategory) {
                        editParentCategory.value = detectedParentId;
                        fillSubcategoryOptions(editSubcategory, detectedParentId, detectedSubcategoryId);
                        syncFinalCategory(editParentCategory, editSubcategory, editFinalCategory);
                        syncParentCategoryHiddenInput(editParentCategory, editParentCategoryHidden);
                    }
                    qs('#edit-provider').value = product.supplier_id || '';
                    editBrandCombobox?.setValue(product.brand_id || '');
                    qs('#edit-price-buy').value = product.purchase_price || '';
                    qs('#edit-price-sell').value = product.sale_price || '';
                    qs('#edit-stock').value = product.stock_current || '';
                    qs('#edit-stock-min').value = product.stock_minimum || '';
                    qs('#edit-status').value = product.status || 'active';
                    editClassificationPreset = product.classification_value_ids || [];
                    refreshClassificationFields(
                        '#edit-classification-fields',
                        editFinalCategory?.value || '',
                        editClassificationPreset
                    );
                    const editFeatured = qs('#edit-featured');
                    if (editFeatured) {
                        editFeatured.checked = Boolean(product.is_featured);
                    }

                    const variantsList = qs('#edit-variants-list');
                    if (variantsList) {
                        variantsList.innerHTML = renderVariantsListHtml({
                            baseProductId: productId,
                            variants: product.variants || [],
                        });
                    }
                    editModal.classList.add('active');
                } else {
                    Swal.fire({
                        title: 'Error',
                        text: data.message || 'Error al cargar el producto',
                        icon: 'error',
                        confirmButtonText: 'Entendido'
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    title: 'Error',
                    text: error?.message || 'Error al cargar el producto. Inténtalo de nuevo.',
                    icon: 'error',
                    confirmButtonText: 'Entendido'
                });
            });
        });
    });

    if (closeEditModalBtn) {
        closeEditModalBtn.addEventListener('click', () => {
            editModal.classList.remove('active');
        });
    }

    bindDependentCategorySelectors({
        parentSelect: newParentCategory,
        subSelect: newSubcategory,
        hiddenCategoryInput: newFinalCategory,
        parentCategoryHiddenInput: newParentCategoryHidden,
    });

    bindDependentCategorySelectors({
        parentSelect: editParentCategory,
        subSelect: editSubcategory,
        hiddenCategoryInput: editFinalCategory,
        parentCategoryHiddenInput: editParentCategoryHidden,
    });

    /** CF4-84 — al cambiar categoría en edición se limpian selecciones previas */
    let editClassificationPreset = [];

    newParentCategory?.addEventListener('change', () => {
        setTimeout(() => {
            refreshClassificationFields('#new-classification-fields', newFinalCategory?.value || '', null);
        }, 0);
    });
    newSubcategory?.addEventListener('change', () => {
        setTimeout(() => {
            refreshClassificationFields('#new-classification-fields', newFinalCategory?.value || '', null);
        }, 0);
    });

    editParentCategory?.addEventListener('change', () => {
        editClassificationPreset = [];
        setTimeout(() => {
            refreshClassificationFields('#edit-classification-fields', editFinalCategory?.value || '', []);
        }, 0);
    });
    editSubcategory?.addEventListener('change', () => {
        editClassificationPreset = [];
        setTimeout(() => {
            refreshClassificationFields('#edit-classification-fields', editFinalCategory?.value || '', []);
        }, 0);
    });

    const newBrandCombobox  = initBrandCombobox('new-brand-search',  'new-brand',  'new-brand-dropdown',  'new-brand-combobox');
    const editBrandCombobox = initBrandCombobox('edit-brand-search', 'edit-brand', 'edit-brand-dropdown', 'edit-brand-combobox');
    if (cancelEditBtn) {
        cancelEditBtn.addEventListener('click', () => {
            editModal.classList.remove('active');
        });
    }

    async function addVariantLink({ baseId, variantId }) {
        const response = await smartFetch(`/products/${baseId}/variants`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                ...jsonHeaders(),
            },
            body: JSON.stringify({ variant_product_id: Number(variantId) }),
        });

        let data = {};
        try {
            data = await response.json();
        } catch {
            data = {};
        }

        if (!response.ok || !data?.success) {
            const msg = data?.message || 'No se pudo agregar la variante.';
            throw Object.assign(new Error(msg), { status: response.status });
        }

        return data.variant;
    }

    function ensureVariantsPlaceholder() {
        if (!editVariantsList) return;
        const rows = editVariantsList.querySelectorAll('.variant-row');
        if (rows.length === 0) {
            editVariantsList.innerHTML = '<span class="text-muted">Sin variantes registradas.</span>';
        }
    }

    if (editVariantAddBtn) {
        editVariantAddBtn.addEventListener('click', async () => {
            const baseId = currentEditProductId;
            const variantId = editVariantHidden?.value;
            if (!baseId || !variantId) return;

            setActionButtonLoading(editVariantAddBtn, true, 'Agregando...');
            try {
                const variant = await addVariantLink({ baseId, variantId });

                currentEditVariants = [...(currentEditVariants || []), variant].filter(Boolean);
                editVariantCombobox?.setBaseContext({ baseProductId: baseId, currentVariants: currentEditVariants });

                if (editVariantsList) {
                    const placeholder = editVariantsList.querySelector('.text-muted');
                    if (placeholder && placeholder.textContent?.includes('Sin variantes')) {
                        editVariantsList.innerHTML = '';
                    }

                    const wrap = document.createElement('div');
                    wrap.innerHTML = renderVariantsListHtml({ baseProductId: baseId, variants: [variant] });
                    const newRow = wrap.querySelector('.variant-row');
                    if (newRow) {
                        const listContainer = editVariantsList.querySelector('.variant-list');
                        if (listContainer) {
                            listContainer.appendChild(newRow);
                        } else {
                            // If the list wasn't wrapped yet, render a full list to keep structure consistent
                            editVariantsList.innerHTML = renderVariantsListHtml({ baseProductId: baseId, variants: currentEditVariants });
                        }
                    } else {
                        editVariantsList.innerHTML = renderVariantsListHtml({ baseProductId: baseId, variants: currentEditVariants });
                    }
                }

                editVariantCombobox?.reset();
                Swal.fire('Agregada', 'Variante agregada correctamente.', 'success');
            } catch (err) {
                const msg = err?.message || 'No se pudo agregar la variante.';
                Swal.fire('No se pudo agregar', msg, 'error');
            } finally {
                setActionButtonLoading(editVariantAddBtn, false);
                editVariantAddBtn.disabled = true;
                editVariantSearch?.focus();
            }
        });
    }

    if (saveEditBtn) {
        saveEditBtn.addEventListener('click', () => {
            syncFinalCategory(editParentCategory, editSubcategory, editFinalCategory);
            syncParentCategoryHiddenInput(editParentCategory, editParentCategoryHidden);

            if (!qs('#edit-brand')?.value) {
                const cb = qs('#edit-brand-combobox');
                if (cb) { cb.classList.add('error'); cb.querySelector('input')?.focus(); }
                Swal.fire({ title: 'Marca requerida', text: 'Selecciona una marca antes de guardar el producto.', icon: 'warning', confirmButtonText: 'Entendido' });
                return;
            }
            qs('#edit-brand-combobox')?.classList.remove('error');

            // Validate gallery: if a folder was selected, ensure it has at least one image
            if (editImagesInput?.files?.length > 0) {
                const hint = editImagesInput.closest('.form-group')?.querySelector('small');
                if (!validateGalleryInput(editImagesInput, hint)) return;
            }

            setButtonLoading(saveEditBtn, true);
            const formData = new FormData(editProductForm);
            // Rebuild images[] with only valid image files
            formData.delete('images[]');
            if (editImagesInput?.files?.length > 0) {
                Array.from(editImagesInput.files)
                    .filter(f => VALID_IMAGE_TYPES.includes(f.type))
                    .forEach(f => formData.append('images[]', f));
            }
            formData.append('_method', 'PUT');
            formData.set('is_featured', qs('#edit-featured')?.checked ? '1' : '0');

            smartFetch(editProductForm.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'Accept': 'application/json'
                }
            })
            .then(response => {
                if (response.status === 413) {
                    throw Object.assign(new Error('PAYLOAD_TOO_LARGE'), { isSizeError: true });
                }
                return response.json();
            })
            .then(data => {
                if (!data) return;
                setButtonLoading(saveEditBtn, false);
                if (data.success) {
                    editModal.classList.remove('active');
                    Swal.fire({
                        title: 'Producto actualizado',
                        text: data.message,
                        icon: 'success',
                        timer: 3500,
                        timerProgressBar: true,
                        showConfirmButton: false,
                    }).then(() => { location.reload(); });
                } else if (data.errors) {
                    qsa('.error-message', editProductForm).forEach(el => el.remove());

                    for (const field in data.errors) {
                        const input = qs(`[name="${field}"]`, editProductForm);
                        if (input) {
                            const error = document.createElement('div');
                            error.classList.add('error-message');
                            error.style.color = 'red';
                            error.style.fontSize = '12px';
                            error.textContent = data.errors[field][0];
                            input.parentNode.appendChild(error);
                        }
                    }
                    
                    if (data.csrf_token) {
                        const metaTag = document.querySelector('meta[name="csrf-token"]');
                        if (metaTag) {
                            metaTag.setAttribute('content', data.csrf_token);
                        }
                    }
                    
                    Swal.fire({
                        title: 'Error de validación',
                        text: jsonValidationMessage(data) || data.message || 'Revisa los campos del formulario.',
                        icon: 'error',
                        confirmButtonText: 'Entendido'
                    });
                } else {
                    Swal.fire({
                        title: 'Error',
                        text: data.message || 'Ocurrió un error al actualizar el producto.',
                        icon: 'error',
                        confirmButtonText: 'Entendido'
                    });
                }
            })
            .catch(error => {
                setButtonLoading(saveEditBtn, false);
                console.error('Error:', error);
                Swal.fire({
                    title: 'Error',
                    text: error.isSizeError
                        ? 'Ha excedido la capacidad de imágenes que puedes cargar.'
                        : 'Ocurrió un error inesperado. Por favor, revisa los logs.',
                    icon: 'error',
                    confirmButtonText: 'Entendido'
                });
            });
        });
    }

    document.body.addEventListener('click', (e) => {
        const btn = e.target.closest('.js-delete-variant');
        if (!btn) return;
        e.preventDefault();

        const baseId = btn.dataset.baseProductId;
        const variantId = btn.dataset.variantProductId;
        const variantName = btn.dataset.variantName || `#${variantId}`;
        if (!baseId || !variantId) return;

        Swal.fire({
            titleText: `¿Eliminar la variante "${variantName}"?`,
            text: 'Esta acción solo elimina la variante seleccionada. El producto base permanecerá activo.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar',
        }).then((result) => {
            if (!result.isConfirmed) return;

            setActionButtonLoading(btn, true, 'Eliminando...');
            smartFetch(`/products/${baseId}/variants/${variantId}`, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    ...jsonHeaders(),
                },
            })
                .then(async (response) => {
                    let data = {};
                    try {
                        data = await response.json();
                    } catch {
                        data = {};
                    }

                    setActionButtonLoading(btn, false);

                    if (response.ok && data.success) {
                        const row = btn.closest('.variant-row');
                        if (row) row.remove();
                        if (currentEditProductId && String(baseId) === String(currentEditProductId)) {
                            currentEditVariants = (currentEditVariants || []).filter((v) => String(v?.product_id) !== String(variantId));
                            editVariantCombobox?.setBaseContext({
                                baseProductId: currentEditProductId,
                                currentVariants: currentEditVariants,
                            });
                        }
                        ensureVariantsPlaceholder();
                        Swal.fire('Eliminada', data.message || 'Variante eliminada correctamente.', 'success');
                        return;
                    }

                    const msg = data.message || 'No se pudo eliminar la variante.';
                    Swal.fire('No se puede eliminar', msg, response.status === 409 ? 'info' : 'error');
                })
                .catch(() => {
                    setActionButtonLoading(btn, false);
                    Swal.fire('Error', 'Error de conexión al eliminar la variante.', 'error');
                });
        });
    });

    // CF4-72 — Editar variante (precio, stock, SKU)
    const variantEditModal = qs('#variant-edit-modal');
    const variantEditBackdrop = qs('#variant-edit-modal-backdrop');
    const variantEditCloseBtn = qs('#variant-edit-modal-close');
    const variantEditCancelBtn = qs('#variant-edit-cancel-btn');
    const variantEditSaveBtn = qs('#variant-edit-save-btn');

    function findVariantInEditList(variantProductId) {
        return (currentEditVariants || []).find((v) => String(v?.product_id) === String(variantProductId));
    }

    function closeVariantEditModal() {
        if (!variantEditModal) return;
        variantEditModal.classList.remove('active');
        variantEditModal.setAttribute('aria-hidden', 'true');
    }

    function openVariantEditModal(baseId, variantProductId) {
        const v = findVariantInEditList(variantProductId);
        if (!v || !variantEditModal) {
            Swal.fire('Error', 'No se encontró la variante en la lista.', 'error');
            return;
        }
        const baseInput = qs('#variant-edit-base-id');
        const variantInput = qs('#variant-edit-variant-id');
        if (baseInput) baseInput.value = String(baseId);
        if (variantInput) variantInput.value = String(variantProductId);

        const titleEl = qs('#variant-edit-variant-title');
        if (titleEl) titleEl.textContent = v.name || `Variante #${variantProductId}`;

        const skuLocked = Boolean(v.sku_locked);
        const lockedFlag = qs('#variant-edit-sku-locked');
        if (lockedFlag) lockedFlag.value = skuLocked ? '1' : '0';

        const skuInput = qs('#variant-edit-sku-input');
        const hintDefault = qs('#variant-edit-sku-hint-default');
        const lockedMsg = qs('#variant-edit-sku-locked-msg');

        if (skuInput) {
            skuInput.disabled = skuLocked;
            const custom = v.sku_custom != null && String(v.sku_custom).trim() !== '' ? String(v.sku_custom) : '';
            skuInput.value = custom;
        }
        if (hintDefault) {
            hintDefault.style.display = skuLocked ? 'none' : 'block';
            hintDefault.textContent = skuLocked
                ? ''
                : `Si lo dejás vacío se usará el código automático (${v.sku ? String(v.sku) : ''}).`;
        }
        if (lockedMsg) {
            lockedMsg.style.display = skuLocked ? 'block' : 'none';
        }

        const priceEl = qs('#variant-edit-sale-price');
        const stockEl = qs('#variant-edit-stock');
        if (priceEl) priceEl.value = v.sale_price != null ? String(v.sale_price) : '';
        if (stockEl) stockEl.value = v.stock_current != null ? String(v.stock_current) : '';

        variantEditModal.classList.add('active');
        variantEditModal.setAttribute('aria-hidden', 'false');
    }

    [variantEditBackdrop, variantEditCloseBtn, variantEditCancelBtn].forEach((el) => {
        el?.addEventListener('click', () => closeVariantEditModal());
    });

    document.body.addEventListener('click', (e) => {
        const editBtn = e.target.closest('.js-edit-variant');
        if (!editBtn) return;
        e.preventDefault();
        const baseId = editBtn.dataset.baseProductId;
        const variantId = editBtn.dataset.variantProductId;
        if (!baseId || !variantId) return;
        openVariantEditModal(baseId, variantId);
    });

    variantEditSaveBtn?.addEventListener('click', () => {
        const baseId = qs('#variant-edit-base-id')?.value;
        const variantId = qs('#variant-edit-variant-id')?.value;
        const locked = qs('#variant-edit-sku-locked')?.value === '1';
        if (!baseId || !variantId) return;

        const salePrice = qs('#variant-edit-sale-price')?.value;
        const stockRaw = qs('#variant-edit-stock')?.value;

        const payload = {
            sale_price: salePrice,
            stock_current: Number.parseInt(String(stockRaw), 10),
        };
        if (!locked) {
            payload.sku = qs('#variant-edit-sku-input')?.value?.trim() ?? '';
        }

        setButtonLoading(variantEditSaveBtn, true, 'Guardando...');

        smartFetch(`/products/${baseId}/variants/${variantId}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                ...jsonHeaders(),
            },
            body: JSON.stringify(payload),
        })
            .then(async (response) => {
                let data = {};
                try {
                    data = await response.json();
                } catch {
                    data = {};
                }
                setButtonLoading(variantEditSaveBtn, false);

                if (response.ok && data.success) {
                    closeVariantEditModal();
                    const updated = data.variant;
                    if (updated && currentEditProductId && String(baseId) === String(currentEditProductId)) {
                        currentEditVariants = (currentEditVariants || []).map((row) =>
                            String(row?.product_id) === String(variantId) ? { ...row, ...updated } : row
                        );
                        const variantsList = qs('#edit-variants-list');
                        if (variantsList) {
                            variantsList.innerHTML = renderVariantsListHtml({
                                baseProductId: baseId,
                                variants: currentEditVariants,
                            });
                        }
                        editVariantCombobox?.setBaseContext({
                            baseProductId: currentEditProductId,
                            currentVariants: currentEditVariants,
                        });
                    }
                    Swal.fire('Listo', data.message || 'Variante actualizada correctamente.', 'success');
                    return;
                }

                const msg = data.message || 'No se pudo guardar la variante.';
                if (data.errors && typeof data.errors === 'object') {
                    const first = Object.values(data.errors).flat()[0];
                    Swal.fire('Revisá los datos', first || msg, 'warning');
                } else {
                    Swal.fire('Error', msg, 'error');
                }
            })
            .catch(() => {
                setButtonLoading(variantEditSaveBtn, false);
                Swal.fire('Error', 'Error de conexión al guardar la variante.', 'error');
            });
    });

    // Modal: View product details
    const viewProductModal = qs('#view-product-modal');
    const viewDetailsBtns = qsa('.view-details-btn');
    const closeViewProductModalBtn = qs('#close-view-product-modal');
    const cancelViewProductBtn = qs('#cancel-view-product');
    const viewProductBody = qs('#view-product-body');

    function initAdminViewCarousel() {
        var track = document.getElementById('admin-carousel-track');
        if (!track) return;
        var slides = track.querySelectorAll('.carousel-slide');
        var total  = slides.length;
        if (total <= 1) return;
        var prevBtn  = document.getElementById('admin-carousel-prev');
        var nextBtn  = document.getElementById('admin-carousel-next');
        var dotsWrap = document.getElementById('admin-carousel-dots');
        var dots     = dotsWrap ? Array.from(dotsWrap.querySelectorAll('.carousel-dot')) : [];
        var current  = 0;

        function goTo(index) {
            current = Math.max(0, Math.min(total - 1, index));
            track.style.transform = 'translateX(-' + (current * 100) + '%)';
            dots.forEach(function (d, i) { d.classList.toggle('active', i === current); });
            if (prevBtn) prevBtn.disabled = current === 0;
            if (nextBtn) nextBtn.disabled = current === total - 1;
        }

        if (prevBtn) prevBtn.addEventListener('click', function () { goTo(current - 1); });
        if (nextBtn) nextBtn.addEventListener('click', function () { goTo(current + 1); });
        dots.forEach(function (d, i) { d.addEventListener('click', function () { goTo(i); }); });

        var startX = null;
        track.addEventListener('touchstart', function (e) { startX = e.touches[0].clientX; }, { passive: true });
        track.addEventListener('touchend', function (e) {
            if (startX === null) return;
            var diff = startX - e.changedTouches[0].clientX;
            if (Math.abs(diff) > 40) goTo(diff > 0 ? current + 1 : current - 1);
            startX = null;
        }, { passive: true });

        // Keyboard arrow navigation (active while modal is open)
        function onKeyDown(e) {
            if (e.key === 'ArrowLeft')  goTo(current - 1);
            if (e.key === 'ArrowRight') goTo(current + 1);
        }
        document.addEventListener('keydown', onKeyDown);

        goTo(0);
    }

    viewDetailsBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            setActionButtonLoading(btn, true, 'Ver detalles');
            setModalLoading(viewProductModal, true);
            const productId = btn.dataset.productId;
            smartFetch(`/products/${productId}`, {
                credentials: 'same-origin',
                headers: jsonHeaders(),
            })
            .then(response => {
                return readJsonOrThrow(response, 'Error al cargar el producto');
            })
            .then(data => {
                setActionButtonLoading(btn, false);
                setModalLoading(viewProductModal, false);
                if(data.success){
                    const product = data.data;
                    // Build image carousel slides from MediaLibrary URLs, fallback to legacy field
                    const allImages = [];
                    if (product.media_main) allImages.push(product.media_main);
                    if (Array.isArray(product.media_gallery)) allImages.push(...product.media_gallery);
                    if (!allImages.length && product.image) allImages.push('/assets/images/products/' + product.image);

                    let imageHtml;
                    if (!allImages.length) {
                        imageHtml = '<p>No hay imagen</p>';
                    } else if (allImages.length === 1) {
                        imageHtml = `<img src="${allImages[0]}" alt="${product.name}" style="max-width:100%;height:auto;border-radius:var(--border-radius);margin-top:10px;">`;
                    } else {
                        const slides = allImages.map(url =>
                            `<div class="carousel-slide"><img src="${url}" alt="${product.name}"></div>`
                        ).join('');
                        const dots = allImages.map((_, i) =>
                            `<button class="carousel-dot${i === 0 ? ' active' : ''}" aria-label="Imagen ${i + 1}"></button>`
                        ).join('');
                        imageHtml = `
                            <div class="admin-product-carousel" style="margin-top:10px;">
                                <div class="carousel-viewport">
                                    <div class="carousel-track" id="admin-carousel-track">${slides}</div>
                                </div>
                                <button class="carousel-btn carousel-btn--prev" id="admin-carousel-prev" disabled aria-label="Anterior">&#8249;</button>
                                <button class="carousel-btn carousel-btn--next" id="admin-carousel-next" aria-label="Siguiente">&#8250;</button>
                                <div class="carousel-dots" id="admin-carousel-dots">${dots}</div>
                            </div>`;
                    }

                    viewProductBody.innerHTML = `
                        <div class="product-details-grid">
                            <div class="product-details-item">
                                <label><i class="fas fa-image icon"></i> Imagen:</label>
                                ${imageHtml}
                            </div>
                            <div class="product-details-item">
                                <label><i class="fas fa-tag icon"></i> Nombre:</label>
                                <p>${product.name}</p>
                            </div>
                            <div class="product-details-item">
                                <label><i class="fas fa-align-left icon"></i> Descripción:</label>
                                <p>${product.description || '-'}</p>
                            </div>
                            <div class="product-details-item">
                                <label><i class="fas fa-boxes icon"></i> Categoría:</label>
                                <p>${categoryPath(product.category)}</p>
                            </div>
                            <div class="product-details-item">
                                <label><i class="fas fa-truck icon"></i> Proveedor:</label>
                                <p>${product.supplier?.name || '-'}</p>
                            </div>
                            <div class="product-details-item">
                                <label><i class="fas fa-dollar-sign icon"></i> Precio de Compra:</label>
                                <p>₡${product.purchase_price}</p>
                            </div>
                            <div class="product-details-item">
                                <label><i class="fas fa-money-bill-wave icon"></i> Precio de Venta:</label>
                                <p>₡${product.sale_price}</p>
                            </div>
                            <div class="product-details-item">
                                <label><i class="fas fa-warehouse icon"></i> Stock Actual:</label>
                                <p>${product.stock_current}</p>
                            </div>
                            <div class="product-details-item">
                                <label><i class="fas fa-minus-circle icon"></i> Stock Mínimo:</label>
                                <p>${product.stock_minimum}</p>
                            </div>
                            <div class="product-details-item">
                                <label><i class="fas fa-info-circle icon"></i> Estado:</label>
                                <p>${product.status}</p>
                            </div>
                            <div class="product-details-item">
                                <label><i class="fas fa-star icon"></i> Destacado en tienda:</label>
                                <p>${product.is_featured ? 'Sí (inicio y catálogo)' : 'No'}</p>
                            </div>
                        </div>
                    `;
                    initAdminViewCarousel();
                    viewProductModal.classList.add('active');
                } else {
                    Swal.fire({
                        title: 'Error',
                        text: data.message || 'Error al cargar el producto',
                        icon: 'error',
                        confirmButtonText: 'Entendido'
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    title: 'Error',
                    text: error?.message || 'Error al cargar el producto. Inténtalo de nuevo.',
                    icon: 'error',
                    confirmButtonText: 'Entendido'
                });
            });
        });
    });

    if (closeViewProductModalBtn) {
        closeViewProductModalBtn.addEventListener('click', () => {
            viewProductModal.classList.remove('active');
        });
    }

    if (cancelViewProductBtn) {
        cancelViewProductBtn.addEventListener('click', () => {
            viewProductModal.classList.remove('active');
        });
    }

    // Modal: Import products
    const importModal = qs('#import-modal');
    const openImportModalBtn = qs('#import-btn');
    const closeImportModalBtn = qs('#close-import-modal');
    const cancelImportBtn = qs('#cancel-import');
    const confirmImportBtn = qs('#confirm-import');
    const importForm = qs('#import-form');

    if (openImportModalBtn) {
        openImportModalBtn.addEventListener('click', () => {
            importModal.classList.add('active');
        });
    }

    if (closeImportModalBtn) {
        closeImportModalBtn.addEventListener('click', () => {
            importModal.classList.remove('active');
        });
    }

    if (cancelImportBtn) {
        cancelImportBtn.addEventListener('click', () => {
            importModal.classList.remove('active');
        });
    }

    // Detect file format by extension
    function detectFileFormat(file) {
        const extension = file.name.split('.').pop().toLowerCase();
        const fileName = file.name.toLowerCase();
        
        if (extension === 'xml' || fileName.endsWith('.xml')) {
            return { format: 'xml', name: 'XML', icon: 'fa-file-code', color: '#f59e0b' };
        } else if (extension === 'csv' || extension === 'txt' || fileName.endsWith('.csv') || fileName.endsWith('.txt')) {
            return { format: 'csv', name: 'CSV', icon: 'fa-file-csv', color: '#3b82f6' };
        } else if (extension === 'json' || fileName.endsWith('.json')) {
            return { format: 'json', name: 'JSON', icon: 'fa-file-alt', color: '#8b5cf6' };
        }
        
        return null;
    }

    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
    }

    const fileInput = qs('#import_file');
    const fileInfo = qs('#file-info');
    const fileName = qs('#file-name');
    const fileFormat = qs('#file-format');
    const fileSize = qs('#file-size');
    const fileIcon = qs('#file-icon');
    const formatDetected = qs('#format-detected');
    const detectedFormatText = qs('#detected-format-text');
    const formatHelpText = qs('.format-help-text');
    const fileUploadLabel = qs('.file-upload-label');
    const removeFileBtn = qs('#remove-file');

    if (fileInput) {
        if (fileUploadLabel) {
            fileUploadLabel.addEventListener('click', () => {
                fileInput.click();
            });
        }

        fileInput.addEventListener('change', (e) => {
            const file = e.target.files[0];
            if (file) {
                const detected = detectFileFormat(file);
                
                if (detected) {
                    fileName.textContent = file.name;
                    fileFormat.textContent = detected.name;
                    fileSize.textContent = formatFileSize(file.size);
                    fileIcon.className = `fas ${detected.icon}`;
                    fileIcon.style.color = detected.color;
                    
                    detectedFormatText.textContent = detected.name;
                    formatHelpText.textContent = `El sistema detectó automáticamente que tu archivo es ${detected.name}. No necesitas seleccionar el formato manualmente.`;
                    
                    fileInfo.classList.remove('hidden');
                    formatDetected.classList.remove('hidden');
                    fileUploadLabel.style.display = 'none';
                    
                    confirmImportBtn.disabled = false;
                } else {
                    Swal.fire({
                        title: 'Formato no soportado',
                        text: 'Por favor selecciona un archivo XML, CSV o JSON.',
                        icon: 'error',
                        confirmButtonText: 'Entendido'
                    });
                    fileInput.value = '';
                }
            }
        });

        if (fileUploadLabel) {
            fileUploadLabel.addEventListener('dragover', (e) => {
                e.preventDefault();
                fileUploadLabel.style.borderColor = '#10b981';
                fileUploadLabel.style.backgroundColor = '#f0fdf4';
            });

            fileUploadLabel.addEventListener('dragleave', (e) => {
                e.preventDefault();
                fileUploadLabel.style.borderColor = '#d1d5db';
                fileUploadLabel.style.backgroundColor = '#f9fafb';
            });

            fileUploadLabel.addEventListener('drop', (e) => {
                e.preventDefault();
                fileUploadLabel.style.borderColor = '#d1d5db';
                fileUploadLabel.style.backgroundColor = '#f9fafb';
                
                const files = e.dataTransfer.files;
                if (files.length > 0) {
                    fileInput.files = files;
                    fileInput.dispatchEvent(new Event('change'));
                }
            });
        }

        if (removeFileBtn) {
            removeFileBtn.addEventListener('click', () => {
                fileInput.value = '';
                fileInfo.classList.add('hidden');
                formatDetected.classList.add('hidden');
                fileUploadLabel.style.display = 'flex';
                confirmImportBtn.disabled = true;
            });
        }
    }

    if (confirmImportBtn) {
        confirmImportBtn.addEventListener('click', () => {
            if (!fileInput.files.length) {
                Swal.fire({
                    title: 'Error',
                    text: 'Por favor selecciona un archivo para importar.',
                    icon: 'error',
                    confirmButtonText: 'Entendido'
                });
                return;
            }
            
            const file = fileInput.files[0];
            const detected = detectFileFormat(file);
            const formatName = detected ? detected.name : 'desconocido';
            
            Swal.fire({
                title: '¿Importar productos?',
                html: `Se importarán los productos desde el archivo <strong>${file.name}</strong> en formato <strong>${formatName}</strong>.`,
                icon: 'info',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Sí, importar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    const progressBar = showProgressBar();
                    const longOperationIndicator = showLongOperationIndicator('Importando productos...');
                    
                    setButtonLoading(confirmImportBtn, true, 'Importando...');
                    
                    importForm.submit();
                    
                    // Fallback: remove indicators after 10 seconds
                    setTimeout(() => {
                        hideProgressBar(progressBar);
                        hideLongOperationIndicator(longOperationIndicator);
                        setButtonLoading(confirmImportBtn, false);
                    }, 10000);
                }
            });
        });
    }

    // Close modals when clicking on backdrop
    qsa('.modal-backdrop').forEach(backdrop => {
        backdrop.addEventListener('click', () => {
            backdrop.closest('.edit-modal').classList.remove('active');
        });
    });
})();

(function initInventoryFeaturedToggle() {
    const root = qs('.products-section');
    if (!root) {
        return;
    }

    root.addEventListener('click', (e) => {
        const btn = e.target.closest('.featured-star-btn');
        if (!btn || !root.contains(btn)) {
            return;
        }
        e.preventDefault();
        e.stopPropagation();

        if (btn.getAttribute('aria-busy') === 'true') {
            return;
        }

        const productId = btn.dataset.productId;
        if (!productId) {
            return;
        }

        btn.setAttribute('aria-busy', 'true');
        btn.classList.add('featured-star-btn--busy');

        smartFetch(`/products/${productId}/toggle-featured`, {
            method: 'POST',
            headers: {
                ...jsonHeaders(),
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({}),
        })
            .then(async (response) => {
                let data = {};
                try {
                    data = await response.json();
                } catch {
                    data = {};
                }
                btn.removeAttribute('aria-busy');
                btn.classList.remove('featured-star-btn--busy');

                if (response.ok && data.success) {
                    syncFeaturedStarButtons(productId, data.is_featured);
                    showSubtleNotification(data.message || 'Destacado actualizado', 'success');
                } else if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        title: 'Error',
                        text: data.message || 'No se pudo actualizar el destacado.',
                        icon: 'error',
                        confirmButtonText: 'Entendido',
                    });
                }
            })
            .catch(() => {
                btn.removeAttribute('aria-busy');
                btn.classList.remove('featured-star-btn--busy');
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        title: 'Error',
                        text: 'No se pudo actualizar el destacado.',
                        icon: 'error',
                        confirmButtonText: 'Entendido',
                    });
                }
            });
    });
})();

// Product deletion with confirmation and feedback
(function initProductDeletion() {
    const deleteButtons = qsa('[data-action="delete"]');

    deleteButtons.forEach(button => {
        button.addEventListener('click', function() {
            const productId = this.dataset.productId;
            const productName = this.dataset.productName;

            Swal.fire({
                title: `¿Estás seguro de que deseas desactivar el producto "${productName}"?`,
                text: "El producto existirá en la base de datos, pero no contará para el stock del inventario.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Sí, desactivar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    setActionButtonLoading(button, true, 'Eliminando...');
                    const url = `/products/${productId}`;
                    const method = 'DELETE';
                            
                    smartFetch(url, {
                        method: method,
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json'
                        }
                    })
                    .then(response => {
                        if (response.ok) {
                            return response.json();
                        } else {
                            throw new Error('Error en la solicitud');
                        }
                    })
                    .then(data => {
                        setActionButtonLoading(button, false);
                        if (data.success) {
                            showSuccessFeedback(button, '¡Desactivado!');
                            Swal.fire(
                                '¡Desactivado!',
                                'El producto ha sido desactivado correctamente.',
                                'success'
                            ).then(() => {
                                location.reload();
                            });
                        } else {
                            showErrorFeedback(button, 'Error');
                            Swal.fire(
                                'Error',
                                data.message || `Hubo un problema al desactivar el producto.`,
                                'error'
                            );
                        }
                    })
                    .catch(error => {
                        setActionButtonLoading(button, false);
                        showErrorFeedback(button, 'Error');
                        console.error('Error:', error);
                        Swal.fire(
                            'Error',
                            'Hubo un problema de conexión o el servidor no respondió correctamente.',
                            'error'
                        );
                    });
                }
            });
        });
    });
})();



// Pagination controls with disabled state handling and smooth scrolling
(function initPagination() {
    const wrapper = qs('.pagination');
    if (!wrapper) return;

    const goInput = qs('#goToPageInput', wrapper);
    const goBtn = qs('#goToPageBtn', wrapper);

    // Prevent navigation on disabled prev/next buttons
    qsa('.pagination .button[aria-label]', wrapper).forEach((a) => {
        const disabled = a.getAttribute('aria-disabled') === 'true';
        if (disabled) {
            a.addEventListener('click', (e) => e.preventDefault());
            a.classList.add('is-disabled');
        }
    });

    // Optional: smooth scroll when clicking page links
    qsa('.pagination .button[aria-label]', wrapper).forEach((a) => {
        a.addEventListener('click', (e) => {
            const dp = a.dataset.page;
            if (!dp || a.getAttribute('aria-disabled') === 'true') return;
            smoothScrollTop();
        });
    });

    // Navigate to a specific page
    function goToPage() {
        const totalSpan = qs('.pagination .button.button-primary', wrapper);
        if (!totalSpan) return;

        const parts = totalSpan.textContent.trim().split('/');
        const lastPage = Math.max(1, parseInt((parts[1] || '1').trim(), 10));
        let target = parseInt((goInput?.value || '1').trim(), 10);

        if (isNaN(target)) target = 1;
        if (target < 1) target = 1;
        if (target > lastPage) target = lastPage;

        const url = new URL(window.location.href);
        url.searchParams.set('page', String(target));
        smoothScrollTop();
        window.location.assign(url.toString());
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
})();

// Initial loading spinner and filter form behavior
document.addEventListener('DOMContentLoaded', () => {
    const productSection = document.querySelector('.products-section');
    const loadingSpinner = document.querySelector('.loading-spinner-overlay');
    const filterForm = document.querySelector('.filter-form');

    if (productSection && loadingSpinner) {
        loadingSpinner.style.display = 'flex';
        window.addEventListener('load', () => {
            loadingSpinner.style.display = 'none';
        });
    }

    if (filterForm) {
        const parentFilter = qs('#parent-category-filter');
        const subcategoryFilter = qs('#subcategory-filter');
        const classificationToggleBtn = qs('#toggle-classification-filters');
        const classificationPanel = qs('#classification-filters-panel');
        const classificationContainer = qs('#classification-filters-container');

        const getSelectedClassificationMap = () => {
            const selected = {};
            qsa('select[name^="classifications["]', filterForm).forEach((select) => {
                const match = select.name.match(/^classifications\[(.+)\]$/);
                if (!match) return;
                selected[match[1]] = String(select.value || '');
            });
            return selected;
        };

        const renderClassificationFilters = (filters, selected = {}) => {
            if (!classificationContainer) return;
            const list = Array.isArray(filters) ? filters : [];
            classificationContainer.innerHTML = '';

            if (!list.length) {
                const empty = document.createElement('p');
                empty.className = 'form-text text-muted';
                empty.textContent = 'No hay clasificaciones disponibles para los filtros base actuales.';
                classificationContainer.appendChild(empty);
                return;
            }

            list.forEach((filter) => {
                const slug = String(filter?.slug || '').trim();
                if (!slug) return;
                const label = String(filter?.label || slug);
                const options = Array.isArray(filter?.options) ? filter.options : [];

                const wrap = document.createElement('div');
                wrap.className = 'filter-group';

                const fieldLabel = document.createElement('label');
                fieldLabel.setAttribute('for', `classification-filter-${slug}`);
                fieldLabel.textContent = label;

                const select = document.createElement('select');
                select.id = `classification-filter-${slug}`;
                select.name = `classifications[${slug}]`;

                const opt0 = document.createElement('option');
                opt0.value = '';
                opt0.textContent = 'Todos';
                select.appendChild(opt0);

                options.forEach((option) => {
                    const opt = document.createElement('option');
                    opt.value = String(option?.value ?? '');
                    opt.textContent = String(option?.label ?? option?.value ?? '');
                    if (String(selected[slug] || '') === opt.value) {
                        opt.selected = true;
                    }
                    select.appendChild(opt);
                });

                wrap.appendChild(fieldLabel);
                wrap.appendChild(select);
                classificationContainer.appendChild(wrap);
            });
        };

        const openClassificationPanel = () => {
            if (!classificationPanel || !classificationToggleBtn) return;
            classificationPanel.hidden = false;
            classificationPanel.classList.add('is-open');
            classificationToggleBtn.setAttribute('aria-expanded', 'true');
        };

        const closeClassificationPanel = () => {
            if (!classificationPanel || !classificationToggleBtn) return;
            classificationPanel.classList.remove('is-open');
            classificationPanel.hidden = true;
            classificationToggleBtn.setAttribute('aria-expanded', 'false');
        };

        const loadClassificationFiltersOnDemand = async () => {
            if (!classificationContainer) return;
            if (classificationContainer.dataset.loaded === '1') return;

            const endpoint = classificationContainer.dataset.endpoint;
            if (!endpoint) return;

            const params = new URLSearchParams();
            const formData = new FormData(filterForm);
            formData.forEach((value, key) => {
                if (typeof value !== 'string' || value.trim() === '') return;
                if (key.startsWith('classifications[')) return;
                params.append(key, value);
            });

            classificationContainer.innerHTML = '<p class="form-text text-muted">Cargando clasificaciones…</p>';
            const url = params.toString() ? `${endpoint}?${params.toString()}` : endpoint;

            try {
                const response = await fetch(url, {
                    method: 'GET',
                    credentials: 'same-origin',
                    headers: jsonHeaders(),
                });
                const data = await readJsonOrThrow(response, 'No se pudieron cargar las clasificaciones.');
                renderClassificationFilters(data?.filters || [], getSelectedClassificationMap());
                classificationContainer.dataset.loaded = '1';
            } catch (_err) {
                classificationContainer.innerHTML = '<p class="form-text text-muted" style="color:#b91c1c;">No se pudieron cargar los filtros de clasificación.</p>';
            }
        };

        if (parentFilter && subcategoryFilter) {
            const selectedFromData = subcategoryFilter.dataset.selected || '';
            fillSubcategoryOptions(subcategoryFilter, parentFilter.value, selectedFromData);
            parentFilter.addEventListener('change', () => {
                fillSubcategoryOptions(subcategoryFilter, parentFilter.value);
            });
        }

        if (classificationToggleBtn && classificationPanel) {
            classificationToggleBtn.addEventListener('click', async () => {
                const isOpen = classificationToggleBtn.getAttribute('aria-expanded') === 'true';
                if (isOpen) {
                    closeClassificationPanel();
                    return;
                }
                openClassificationPanel();
                await loadClassificationFiltersOnDemand();
            });
        }

        filterForm.addEventListener('submit', () => {
            if (loadingSpinner) {
                loadingSpinner.style.display = 'flex';
            }
        });

        const clearFiltersBtn = qs('#clear-filters');
        if (clearFiltersBtn) {
            clearFiltersBtn.addEventListener('click', () => {
                qsa('.filter-form select').forEach(select => select.value = '');
                qsa('.filter-form input[type="text"]').forEach(input => input.value = '');
                filterForm.submit();
            });
        }
    }
});

(function () {
    'use strict';

    function cf4SwalStockBase() {
        return {
            buttonsStyling: false,
            reverseButtons: true,
            focusCancel: true,
            allowOutsideClick: false,
            customClass: {
                popup: 'cf4-swal-popup',
                confirmButton: 'cf4-swal-btn cf4-swal-btn-primary',
                cancelButton: 'cf4-swal-btn cf4-swal-btn-muted',
                actions: 'cf4-swal-actions',
                title: 'cf4-swal-title',
                htmlContainer: 'cf4-swal-html',
            },
        };
    }

    function cf4SwalStockToast(icon, title, text) {
        if (typeof Swal === 'undefined') return Promise.resolve();
        return Swal.fire({
            toast: true,
            position: 'top-end',
            icon,
            title,
            text: text || undefined,
            showConfirmButton: false,
            timer: icon === 'success' ? 3600 : 5200,
            timerProgressBar: true,
            showCloseButton: true,
        });
    }

    // ── DOM references (resolved after DOMContentLoaded) ──────────────────
    let modal, backdrop, modalTitle, modalTitleIcon;
    let productIdInput, productNameEl, productStockEl;
    let qtyInput, reasonInput;
    let qtyError, reasonError, alertBanner, alertMsg;
    let confirmBtn, confirmBtnText, confirmBtnSpinner;
    let cancelBtn, closeBtn;

    // Current state
    let currentAction = 'add'; // 'add' | 'remove'
    let currentProductId = null;

    // ── Bootstrap ─────────────────────────────────────────────────────────
    document.addEventListener('DOMContentLoaded', () => {
        modal              = document.getElementById('stock-adjust-modal');
        if (!modal) return; // Modal not present on this page

        backdrop           = modal.querySelector('.stock-modal-backdrop');
        modalTitle         = document.getElementById('stock-modal-title');
        modalTitleIcon     = document.getElementById('stock-modal-title-icon');
        productIdInput     = document.getElementById('stock-modal-product-id');
        productNameEl      = document.getElementById('stock-modal-product-name');
        productStockEl     = document.getElementById('stock-modal-product-stock');
        qtyInput           = document.getElementById('stock-modal-qty');
        reasonInput        = document.getElementById('stock-modal-reason');
        qtyError           = document.getElementById('stock-modal-qty-error');
        reasonError        = document.getElementById('stock-modal-reason-error');
        alertBanner        = document.getElementById('stock-modal-alert');
        alertMsg           = document.getElementById('stock-modal-alert-msg');
        confirmBtn         = document.getElementById('stock-modal-confirm-btn');
        confirmBtnText     = document.getElementById('stock-modal-confirm-text');
        confirmBtnSpinner  = document.getElementById('stock-modal-confirm-spinner');
        cancelBtn          = document.getElementById('stock-modal-cancel-btn');
        closeBtn           = document.getElementById('stock-modal-close-btn');

        // ── Event delegation: open modal from table rows and cards ────────
        document.body.addEventListener('click', (e) => {
            // "Add" button in table/card actions
            const addBtn = e.target.closest('[data-stock-action="add"]');
            if (addBtn) {
                e.preventDefault();
                openModal('add', addBtn);
                return;
            }

            // "Remove" button in table/card actions
            const removeBtn = e.target.closest('[data-stock-action="remove"]');
            if (removeBtn) {
                e.preventDefault();
                openModal('remove', removeBtn);
                return;
            }

        });

        // ── Close triggers ────────────────────────────────────────────────
        closeBtn.addEventListener('click', closeModal);
        cancelBtn.addEventListener('click', closeModal);
        backdrop.addEventListener('click', closeModal);

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && modal.classList.contains('is-open')) {
                closeModal();
            }
        });

        // ── Confirm ───────────────────────────────────────────────────────
        confirmBtn.addEventListener('click', submitAdjustment);
    });

    // ── Open modal ────────────────────────────────────────────────────────
    function openModal(action, triggerEl) {
        currentAction    = action;
        currentProductId = triggerEl.dataset.productId;

        const name  = triggerEl.dataset.productName  || 'Producto';
        const stock = triggerEl.dataset.productStock !== undefined
            ? triggerEl.dataset.productStock
            : '—';

        // Populate info strip
        productIdInput.value  = currentProductId;
        productNameEl.textContent  = name;
        productStockEl.textContent = stock;

        // Title & icon
        if (action === 'add') {
            modalTitleIcon.className = 'fas fa-plus-circle modal-icon-add';
            modalTitle.textContent   = 'Agregar Stock';
            confirmBtn.className     = 'stock-btn stock-btn-confirm-add';
            confirmBtnText.textContent = 'Confirmar adición';
        } else {
            modalTitleIcon.className = 'fas fa-minus-circle modal-icon-remove';
            modalTitle.textContent   = 'Retirar Stock';
            confirmBtn.className     = 'stock-btn stock-btn-confirm-remove';
            confirmBtnText.textContent = 'Confirmar retiro';
        }

        // Reset form state
        resetForm();

        modal.classList.add('is-open');
        qtyInput.focus();
    }

    // ── Close modal ───────────────────────────────────────────────────────
    function closeModal() {
        modal.classList.remove('is-open');
        resetForm();
        currentProductId = null;
    }

    // ── Reset ─────────────────────────────────────────────────────────────
    function resetForm() {
        qtyInput.value         = '';
        reasonInput.value      = '';
        qtyInput.classList.remove('is-invalid');
        reasonInput.classList.remove('is-invalid');
        qtyError.classList.remove('visible');
        qtyError.textContent   = '';
        reasonError.classList.remove('visible');
        reasonError.textContent = '';
        hideAlert();
        setLoading(false);
    }

    // ── Client-side validation ─────────────────────────────────────────────
    function validate() {
        let valid = true;

        const qty = parseFloat(qtyInput.value);
        if (!qtyInput.value || isNaN(qty) || qty < 1 || !Number.isInteger(qty)) {
            qtyInput.classList.add('is-invalid');
            qtyError.textContent = 'Ingresa una cantidad entera mayor a 0.';
            qtyError.classList.add('visible');
            valid = false;
        } else {
            qtyInput.classList.remove('is-invalid');
            qtyError.classList.remove('visible');
        }

        const reason = (reasonInput.value || '').trim();
        if (!reason) {
            reasonInput.classList.add('is-invalid');
            reasonError.textContent = 'El motivo es obligatorio.';
            reasonError.classList.add('visible');
            valid = false;
        } else if (reason.length < 3) {
            reasonInput.classList.add('is-invalid');
            reasonError.textContent = 'El motivo debe tener al menos 3 caracteres.';
            reasonError.classList.add('visible');
            valid = false;
        } else if (reason.length > 500) {
            reasonInput.classList.add('is-invalid');
            reasonError.textContent = 'El motivo no puede superar los 500 caracteres.';
            reasonError.classList.add('visible');
            valid = false;
        } else {
            reasonInput.classList.remove('is-invalid');
            reasonError.classList.remove('visible');
        }

        return valid;
    }

    // ── Submit ────────────────────────────────────────────────────────────
    async function submitAdjustment() {
        hideAlert();
        if (!validate()) return;

        // ── SweetAlert2 confirmation ──────────────────────────────────────
        const qty         = parseInt(qtyInput.value, 10);
        const productName = productNameEl.textContent || 'este producto';
        const isAdd       = currentAction === 'add';

        const confirmBtnClass = isAdd
            ? 'cf4-swal-btn cf4-swal-btn-primary'
            : 'cf4-swal-btn cf4-swal-btn-danger';

        const swalBase = cf4SwalStockBase();
        const { isConfirmed } = await Swal.fire({
            ...swalBase,
            customClass: {
                ...swalBase.customClass,
                confirmButton: confirmBtnClass,
            },
            title: isAdd ? '¿Agregar stock al inventario?' : '¿Retirar stock del inventario?',
            html: isAdd
                ? `<p>Se agregarán <strong>${qty}</strong> unidad(es) a <strong>${productName}</strong>.</p>`
                : `<p>Se retirarán <strong>${qty}</strong> unidad(es) de <strong>${productName}</strong>.</p>`,
            icon: isAdd ? 'question' : 'warning',
            showCancelButton: true,
            confirmButtonText: isAdd ? 'Sí, agregar' : 'Sí, retirar',
            cancelButtonText: 'Volver',
        });

        if (!isConfirmed) return;
        // ─────────────────────────────────────────────────────────────────

        const endpoint = currentAction === 'add'
            ? `/inventory/add-manual/${currentProductId}`
            : `/inventory/remove-manual/${currentProductId}`;

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

        setLoading(true);

        try {
            const res = await fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept':       'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({
                    quantity: parseInt(qtyInput.value, 10),
                    reason:   (reasonInput.value || '').trim(),
                }),
            });

            const data = await res.json();

            if (res.ok && data.success) {
                closeModal();
                await cf4SwalStockToast('success', 'Stock actualizado', data.message || 'Los cambios ya están registrados.');
                window.location.reload();
            } else {
                // Server-side validation errors
                const firstError = data.message
                    || (data.errors ? Object.values(data.errors).flat()[0] : null)
                    || 'No se pudo actualizar el stock.';

                // Highlight specific fields if the server returned field errors
                if (data.errors) {
                    if (data.errors.quantity) {
                        qtyInput.classList.add('is-invalid');
                        qtyError.textContent = data.errors.quantity[0];
                        qtyError.classList.add('visible');
                    }
                    if (data.errors.reason) {
                        reasonInput.classList.add('is-invalid');
                        reasonError.textContent = data.errors.reason[0];
                        reasonError.classList.add('visible');
                    }
                }

                showAlert('error', firstError);
                setLoading(false);
            }
        } catch (err) {
            console.error('stock-adjust fetch error', err);
            showAlert('error', 'Error de conexión. Verifica tu red e inténtalo de nuevo.');
            setLoading(false);
        }
    }

    // ── Helpers ───────────────────────────────────────────────────────────
    function setLoading(loading) {
        confirmBtn.disabled          = loading;
        cancelBtn.disabled           = loading;
        closeBtn.disabled            = loading;
        confirmBtnSpinner.style.display = loading ? 'inline-block' : 'none';
    }

    function showAlert(type, message) {
        alertBanner.className = `stock-modal-alert visible alert-${type}`;
        alertMsg.textContent  = message;
    }

    function hideAlert() {
        alertBanner.className = 'stock-modal-alert';
        alertMsg.textContent  = '';
    }
})();