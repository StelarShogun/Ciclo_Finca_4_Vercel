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
    const emptyLabel = isFilter ? 'Todas las subcategorías' : 'Sin subcategoría';

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

function bindDependentCategorySelectors({ parentSelect, subSelect, hiddenCategoryInput }) {
    if (!parentSelect || !subSelect || !hiddenCategoryInput) return;
    parentSelect.addEventListener('change', () => {
        fillSubcategoryOptions(subSelect, parentSelect.value);
        syncFinalCategory(parentSelect, subSelect, hiddenCategoryInput);
    });
    subSelect.addEventListener('change', () => {
        syncFinalCategory(parentSelect, subSelect, hiddenCategoryInput);
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

            if (newProductForm && typeof newProductForm.reportValidity === 'function' && !newProductForm.reportValidity()) {
                return;
            }

            if (!newFinalCategory || !String(newFinalCategory.value || '').trim()) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        title: 'Categoría',
                        text: 'Selecciona una categoría. La subcategoría es opcional: puedes dejar «Sin subcategoría» para guardar el producto en la categoría padre.',
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

            setButtonLoading(saveNewProductBtn, true);
            const formData = new FormData(newProductForm);
            formData.set('is_featured', qs('#new-featured')?.checked ? '1' : '0');

            smartFetch(newProductForm.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'Accept': 'application/json'
                }
            })
            .then(response => {
                return response.json();
            })
            .then(data => {
                if (!data) return;
                setButtonLoading(saveNewProductBtn, false);
                if (data.success) {
                    newProductModal.classList.remove('active');
                    Swal.fire({
                        title: 'Éxito',
                        text: data.message,
                        icon: 'success',
                        confirmButtonText: 'Entendido'
                    }).then(() => {
                        location.reload();
                    });
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
                    text: 'Ocurrió un error inesperado. Por favor, revisa los logs.',
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

    editBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            const productId = btn.dataset.productId;
            fetch(`/products/${productId}`, {
                credentials: 'same-origin',
                headers: jsonHeaders(),
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Error al cargar el producto');
                }
                return response.json();
            })
            .then(data => {
                if(data.success){
                    const product = data.data;
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
                    }
                    qs('#edit-provider').value = product.supplier_id || '';
                    editBrandCombobox?.setValue(product.brand_id || '');
                    qs('#edit-price-buy').value = product.purchase_price || '';
                    qs('#edit-price-sell').value = product.sale_price || '';
                    qs('#edit-stock').value = product.stock_current || '';
                    qs('#edit-stock-min').value = product.stock_minimum || '';
                    qs('#edit-status').value = product.status || 'active';
                    const editFeatured = qs('#edit-featured');
                    if (editFeatured) {
                        editFeatured.checked = Boolean(product.is_featured);
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
                    text: 'Error al cargar el producto. Inténtalo de nuevo.',
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
        hiddenCategoryInput: newFinalCategory
    });

    bindDependentCategorySelectors({
        parentSelect: editParentCategory,
        subSelect: editSubcategory,
        hiddenCategoryInput: editFinalCategory
    });

    const newBrandCombobox  = initBrandCombobox('new-brand-search',  'new-brand',  'new-brand-dropdown',  'new-brand-combobox');
    const editBrandCombobox = initBrandCombobox('edit-brand-search', 'edit-brand', 'edit-brand-dropdown', 'edit-brand-combobox');
    if (cancelEditBtn) {
        cancelEditBtn.addEventListener('click', () => {
            editModal.classList.remove('active');
        });
    }

    if (saveEditBtn) {
        saveEditBtn.addEventListener('click', () => {
            syncFinalCategory(editParentCategory, editSubcategory, editFinalCategory);

            if (!qs('#edit-brand')?.value) {
                const cb = qs('#edit-brand-combobox');
                if (cb) { cb.classList.add('error'); cb.querySelector('input')?.focus(); }
                Swal.fire({ title: 'Marca requerida', text: 'Selecciona una marca antes de guardar el producto.', icon: 'warning', confirmButtonText: 'Entendido' });
                return;
            }
            qs('#edit-brand-combobox')?.classList.remove('error');

            setButtonLoading(saveEditBtn, true);
            const formData = new FormData(editProductForm);
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
                return response.json();
            })
            .then(data => {
                if (!data) return;
                setButtonLoading(saveEditBtn, false);
                if (data.success) {
                    editModal.classList.remove('active');
                    Swal.fire({
                        title: 'Éxito',
                        text: data.message,
                        icon: 'success',
                        confirmButtonText: 'Entendido'
                    }).then(() => {
                        location.reload();
                    });
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
                    text: 'Ocurrió un error inesperado. Por favor, revisa los logs.',
                    icon: 'error',
                    confirmButtonText: 'Entendido'
                });
            });
        });
    }

    // Modal: View product details
    const viewProductModal = qs('#view-product-modal');
    const viewDetailsBtns = qsa('.view-details-btn');
    const closeViewProductModalBtn = qs('#close-view-product-modal');
    const cancelViewProductBtn = qs('#cancel-view-product');
    const viewProductBody = qs('#view-product-body');

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
                if (!response.ok) {
                    throw new Error('Error al cargar el producto');
                }
                return response.json();
            })
            .then(data => {
                setActionButtonLoading(btn, false);
                setModalLoading(viewProductModal, false);
                if(data.success){
                    const product = data.data;
                    viewProductBody.innerHTML = `
                        <div class="product-details-grid">
                            <div class="product-details-item">
                                <label><i class="fas fa-image icon"></i> Imagen:</label>
                                ${product.image ? `<img src="/assets/images/products/${product.image}" alt="${product.name}" style="max-width: 100%; height: auto; border-radius: var(--border-radius); margin-top: 10px;">` : '<p>No hay imagen</p>'}
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
                    text: 'Error al cargar el producto. Inténtalo de nuevo.',
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
    const exportModal = document.getElementById('export-modal');
    const exportBtn = document.getElementById('export-btn');
    if (exportBtn && exportModal) {
        exportBtn.addEventListener('click', () => exportModal.classList.add('active'));
        document.getElementById('close-export-modal')?.addEventListener('click', () => {
            exportModal.classList.remove('active');
        });
        exportModal.querySelector('.modal-backdrop')?.addEventListener('click', () => {
            exportModal.classList.remove('active');
        });
    }

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
        if (parentFilter && subcategoryFilter) {
            const selectedFromData = subcategoryFilter.dataset.selected || '';
            fillSubcategoryOptions(subcategoryFilter, parentFilter.value, selectedFromData);
            parentFilter.addEventListener('change', () => {
                fillSubcategoryOptions(subcategoryFilter, parentFilter.value);
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