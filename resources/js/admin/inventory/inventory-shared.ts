// @ts-nocheck
/**
 * Inventory shared utilities used by both the initial bundle
 * (filters/actions/chrome) and the deferred modals bundle.
 *
 * Modal-only helpers (combobox, server field error rendering) live in
 * inventory-modal-helpers.js so the initial chunk stays small.
 */

import {
    buildProductMediaPlaceholderHtml,
    productUsesPlaceholderImage,
} from '../../shared/product-media-placeholder';
import {
    closeOtherModals,
    setModalLoading as setModalLoadingUtil,
} from '../shared/modal-utils';

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

function setEditCurrentProductImage(url) {
    const block = qs('#current-image-preview');
    const removeInput = qs('#edit-remove-main-image');
    if (!block) return;

    if (!url) {
        block.hidden = true;
        block.innerHTML = '';
        return;
    }

    if (removeInput) removeInput.value = '0';
    block.hidden = false;
    const safeUrl = escapeHtml(url);
    block.innerHTML =
        '<div class="cf-product-current-image__media">' +
        '<img src="' + safeUrl + '" alt="Imagen actual del producto" class="cf-product-current-image__thumb">' +
        '</div>' +
        '<div class="cf-product-current-image__actions">' +
        '<p class="cf-product-current-image__label">Imagen principal actual</p>' +
        '<button type="button" class="btn btn-secondary btn-sm js-remove-current-image">' +
        '<i class="fas fa-trash-alt" aria-hidden="true"></i> Eliminar imagen' +
        '</button>' +
        '</div>';
}

function setEditCurrentProductImagePreview(product) {
    const block = qs('#current-image-preview');
    if (!block) return;

    if (!product || productUsesPlaceholderImage(product)) {
        if (product && productUsesPlaceholderImage(product)) {
            const removeInput = qs('#edit-remove-main-image');
            if (removeInput) removeInput.value = '0';
            block.hidden = false;
            block.innerHTML =
                '<div class="cf-product-current-image__media">' +
                buildProductMediaPlaceholderHtml(
                    product.placeholder_icon_class || 'fas fa-box',
                    product.name,
                    'thumb-card'
                ) +
                '</div>' +
                '<div class="cf-product-current-image__actions">' +
                '<p class="cf-product-current-image__label">Sin imagen principal</p>' +
                '</div>';
        } else {
            block.hidden = true;
            block.innerHTML = '';
        }
        return;
    }

    let url = product.media_main || '';
    if (!url && product.image && product.image !== 'default.png') {
        url = '/assets/images/products/' + product.image;
    }
    setEditCurrentProductImage(url || null);
}

function markEditMainImageForRemoval() {
    setEditCurrentProductImage(null);
    const removeInput = qs('#edit-remove-main-image');
    if (removeInput) removeInput.value = '1';
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
function resolveSubcategoriesForParent(parentId, isFilter) {
    const tree = window.inventoryCategoryTree || {};
    const hasParent = parentId !== '' && parentId !== null && parentId !== undefined;
    let subs = [];

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

    return subs;
}

function fillSubcategoryOptions(subSelect, parentId, selectedId = '', subCombobox = null) {
    if (!subSelect && !subCombobox) return;
    const subId = subSelect?.id || subCombobox?.element?.id || '';
    const isFilter = subId === 'subcategory-filter';
    const isModalSub = subId === 'new-subcategory' || subId === 'edit-subcategory';
    const hasParent = parentId !== '' && parentId !== null && parentId !== undefined;

    let firstOptText;
    if (isFilter) {
        firstOptText = 'Todos los tipos';
    } else if (isModalSub) {
        firstOptText = hasParent
            ? 'Sin subcategoría (solo esta categoría)'
            : 'Seleccioná primero una categoría';
    } else {
        firstOptText = 'Sin subcategoría (solo esta categoría)';
    }

    const subs = resolveSubcategoriesForParent(parentId, isFilter);

    if (subCombobox && isModalSub) {
        const hintId = subId === 'new-subcategory' ? 'new-subcategory-hint' : 'edit-subcategory-hint';
        const hintEl = document.getElementById(hintId);
        const defaultHint =
            hintEl?.getAttribute('data-default-hint')?.trim() ||
            'Si no elegís subcategoría, no vas a poder cargar color, talla, etc. Elegí una subcategoría cuando exista.';

        const optionList = [
            { id: '', name: firstOptText },
            ...subs.map((sub) => ({ id: sub.category_id, name: sub.name })),
        ];

        const disabled = !hasParent || subs.length === 0;
        subCombobox.setOptions(optionList, {
            disabled,
            placeholder: hasParent ? 'Escribe para buscar subcategoría…' : firstOptText,
        });

        if (selectedId !== '' && selectedId !== undefined) {
            subCombobox.setValue(selectedId, { silent: true });
        } else {
            subCombobox.reset();
        }

        if (!hasParent) {
            if (hintEl) {
                hintEl.textContent =
                    'Elegí primero una categoría para ver las subcategorías disponibles.';
            }
        } else if (subs.length === 0) {
            if (hintEl) {
                hintEl.textContent =
                    'Esta categoría no tiene subcategorías registradas. Podés guardar el producto clasificado solo en la categoría. Para atributos como color o talla, creá subcategorías en administración de categorías.';
            }
        } else if (hintEl) {
            hintEl.textContent = defaultHint;
        }
        return;
    }

    if (!subSelect || subSelect.tagName !== 'SELECT') return;

    subSelect.innerHTML = '';
    const opt0 = document.createElement('option');
    opt0.value = '';
    opt0.textContent = firstOptText;
    subSelect.appendChild(opt0);

    subs.forEach((sub) => {
        const opt = document.createElement('option');
        opt.value = String(sub.category_id);
        opt.textContent = sub.name;
        if (selectedId !== '' && selectedId !== undefined && String(selectedId) === String(sub.category_id)) {
            opt.selected = true;
        }
        subSelect.appendChild(opt);
    });

    if (isModalSub) {
        const hintId = subId === 'new-subcategory' ? 'new-subcategory-hint' : 'edit-subcategory-hint';
        const hintEl = document.getElementById(hintId);
        const defaultHint =
            hintEl?.getAttribute('data-default-hint')?.trim() ||
            'Si no elegís subcategoría, no vas a poder cargar color, talla, etc. Elegí una subcategoría cuando exista.';

        if (!hasParent) {
            subSelect.disabled = true;
            if (hintEl) {
                hintEl.textContent =
                    'Elegí primero una categoría para ver las subcategorías disponibles.';
            }
        } else if (subs.length === 0) {
            subSelect.disabled = true;
            if (hintEl) {
                hintEl.textContent =
                    'Esta categoría no tiene subcategorías registradas. Podés guardar el producto clasificado solo en la categoría. Para atributos como color o talla, creá subcategorías en administración de categorías.';
            }
        } else {
            subSelect.disabled = false;
            if (hintEl) {
                hintEl.textContent = defaultHint;
            }
        }
    }
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

function syncParentCategoryHiddenInput(parentSelect, parentHiddenInput) {
    if (!parentHiddenInput || !parentSelect) {
        return;
    }
    parentHiddenInput.value = parentSelect.value || '';
}

function bindDependentCategorySelectors({
    parentSelect,
    subSelect,
    hiddenCategoryInput,
    parentCategoryHiddenInput,
    subCombobox = null,
}) {
    if (!parentSelect || !subSelect || !hiddenCategoryInput) return;
    parentSelect.addEventListener('change', () => {
        fillSubcategoryOptions(subSelect, parentSelect.value, '', subCombobox);
        syncFinalCategory(parentSelect, subSelect, hiddenCategoryInput);
        syncParentCategoryHiddenInput(parentSelect, parentCategoryHiddenInput);
    });
    subSelect.addEventListener('change', () => {
        syncFinalCategory(parentSelect, subSelect, hiddenCategoryInput);
        syncParentCategoryHiddenInput(parentSelect, parentCategoryHiddenInput);
    });
    if (subCombobox) {
        subCombobox.onChange(() => {
            syncFinalCategory(parentSelect, subSelect, hiddenCategoryInput);
            syncParentCategoryHiddenInput(parentSelect, parentCategoryHiddenInput);
        });
    }
}

function initCollapsibleFormSections(root = document) {
    root.querySelectorAll('.form-section__toggle').forEach((btn) => {
        if (btn.dataset.cfSectionBound === '1') return;
        btn.dataset.cfSectionBound = '1';
        const section = btn.closest('.form-section');
        btn.addEventListener('click', () => {
            section?.classList.toggle('is-collapsed');
            const expanded = !section?.classList.contains('is-collapsed');
            btn.setAttribute('aria-expanded', expanded ? 'true' : 'false');
        });
    });
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
function setButtonLoading(button, isLoading, loadingText = null) {
    if (isLoading) {
        if (!button.classList.contains('loading')) {
            button.dataset.originalText = button.innerHTML;
        }

        button.disabled = true;
        button.innerHTML = loadingText
            ? `<i class="fas fa-spinner fa-spin"></i> ${escapeHtml(String(loadingText))}`
            : '<i class="fas fa-spinner fa-spin"></i>';
        button.classList.add('loading');
    } else {
        button.disabled = false;
        button.innerHTML = button.dataset.originalText || button.innerHTML;
        delete button.dataset.originalText;
        button.classList.remove('loading');
    }
}

// Show loading state on an action button (e.g., in a table)
function setActionButtonLoading(button, isLoading) {
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

// Create a full-screen progress overlay for long operations
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

/** Disable a modal during async operations (backdrop/close remain usable). */
function setModalLoading(modal, isLoading) {
    setModalLoadingUtil(modal, isLoading, { blockShell: false });
}

/** Close every inventory modal except the one being opened (prevents stacked blank overlays). */
function closeOtherInventoryModals(exceptId = null) {
    closeOtherModals('.edit-modal', exceptId);
}


export {
    bindDependentCategorySelectors,
    buildProductMediaPlaceholderHtml,
    categoryPath,
    escapeHtml,
    escapeHtmlAttr,
    fillSubcategoryOptions,
    getCSRFToken,
    handleCSRFError,
    hideLongOperationIndicator,
    hideProgressBar,
    initCollapsibleFormSections,
    jsonHeaders,
    jsonValidationMessage,
    markEditMainImageForRemoval,
    productUsesPlaceholderImage,
    qs,
    qsa,
    readJsonOrThrow,
    renderVariantsListHtml,
    renewCSRFToken,
    resolveSubcategoriesForParent,
    safeParseJsonResponse,
    setActionButtonLoading,
    setButtonLoading,
    setEditCurrentProductImage,
    setEditCurrentProductImagePreview,
    setModalLoading,
    closeOtherInventoryModals,
    showErrorFeedback,
    showLongOperationIndicator,
    showProgressBar,
    showSubtleNotification,
    showSuccessFeedback,
    smartFetch,
    syncFeaturedStarButtons,
    syncFinalCategory,
    syncParentCategoryHiddenInput,
};
