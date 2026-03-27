/* inventory.js — Paginación siempre visible y toggle de sidebar con persistencia */

// ---------- Utilidades ----------
const qs = (s, r = document) => r.querySelector(s);
const qsa = (s, r = document) => Array.from(r.querySelectorAll(s));

/** Texto legible a partir de la respuesta JSON 422 de Laravel (errors + message). */
function jsonValidationMessage(data) {
    if (!data) return '';
    if (data.errors) {
        const flat = Object.values(data.errors).flat().filter(Boolean);
        if (flat.length) return flat.join('\n');
    }
    return typeof data.message === 'string' ? data.message : '';
}

function categoryPath(category) {
    if (!category) return '-';
    const parentName = category.parent?.name;
    const currentName = category.name || '';
    return parentName ? `${parentName} > ${currentName}` : (currentName || '-');
}

// Función para obtener el CSRF token dinámicamente
function getCSRFToken() {
    const metaToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const inputToken = document.querySelector('input[name="_token"]')?.value;
    return metaToken || inputToken || '';
}

// Función para renovar el CSRF token automáticamente
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

// Función para manejar errores CSRF de forma elegante
async function handleCSRFError(originalRequest) {
    console.log('Token CSRF expirado, renovando...');
    
    // Renovar el token
    const newToken = await renewCSRFToken();
    
    if (newToken) {
        // Actualizar el token en la petición original
        if (originalRequest.headers) {
            originalRequest.headers['X-CSRF-TOKEN'] = newToken;
        }
        
        // Reintentar la petición original
        return fetch(originalRequest.url, originalRequest);
    } else {
        throw new Error('No se pudo renovar el token CSRF');
    }
}

// Función wrapper para fetch que maneja automáticamente errores CSRF
async function smartFetch(url, options = {}) {
    // Asegurar que el token CSRF esté presente
    if (!options.headers) {
        options.headers = {};
    }
    if (!options.headers['X-CSRF-TOKEN']) {
        options.headers['X-CSRF-TOKEN'] = getCSRFToken();
    }
    
    try {
        const response = await fetch(url, options);
        
        // Si es error 419 (CSRF token mismatch), manejar automáticamente
        if (response.status === 419) {
            console.log('Error CSRF detectado, reintentando automáticamente...');
            
            // Mostrar notificación sutil de renovación
            showSubtleNotification('Renovando sesión...', 'info');
            
            // Crear una copia de la petición original para reintentar
            const retryOptions = {
                ...options,
                headers: {
                    ...options.headers,
                    'X-CSRF-TOKEN': await renewCSRFToken()
                }
            };
            
            // Reintentar la petición con el nuevo token
            const retryResponse = await fetch(url, retryOptions);
            
            // Mostrar notificación de éxito
            showSubtleNotification('Sesión renovada', 'success');
            
            return retryResponse;
        }
        
        return response;
    } catch (error) {
        console.error('Error en smartFetch:', error);
        throw error;
    }
}

// Función para mostrar notificaciones sutiles
function showSubtleNotification(message, type = 'info') {
    // Crear elemento de notificación
    const notification = document.createElement('div');
    notification.className = `subtle-notification ${type}`;
    notification.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check' : type === 'error' ? 'times' : 'info'}-circle"></i>
        <span>${message}</span>
    `;
    
    // Agregar estilos
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
    
    // Animar entrada
    setTimeout(() => {
        notification.style.transform = 'translateX(0)';
    }, 100);
    
    // Auto-remover después de 3 segundos
    setTimeout(() => {
        notification.style.transform = 'translateX(100%)';
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 300);
    }, 3000);
}

// Función para mostrar estado de carga en botones
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

// Función para mostrar estado de carga en acciones de tabla
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

// Función para mostrar indicador de operación larga
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

// Función para ocultar indicador de operación larga
function hideLongOperationIndicator(indicator) {
    if (indicator && indicator.parentNode) {
        indicator.parentNode.removeChild(indicator);
    }
}

// Función para mostrar barra de progreso
function showProgressBar() {
    const progressBar = document.createElement('div');
    progressBar.className = 'progress-indicator';
    document.body.appendChild(progressBar);
    return progressBar;
}

// Función para ocultar barra de progreso
function hideProgressBar(progressBar) {
    if (progressBar && progressBar.parentNode) {
        progressBar.parentNode.removeChild(progressBar);
    }
}

// Función para mostrar feedback visual de éxito
function showSuccessFeedback(button, message = '¡Completado!') {
    const originalContent = button.innerHTML;
    button.innerHTML = `<i class="fas fa-check"></i> ${message}`;
    button.classList.add('action-success');
    
    setTimeout(() => {
        button.innerHTML = originalContent;
        button.classList.remove('action-success');
    }, 2000);
}

// Función para mostrar feedback visual de error
function showErrorFeedback(button, message = 'Error') {
    const originalContent = button.innerHTML;
    button.innerHTML = `<i class="fas fa-times"></i> ${message}`;
    button.classList.add('action-error');
    
    setTimeout(() => {
        button.innerHTML = originalContent;
        button.classList.remove('action-error');
    }, 2000);
}

// Función para mostrar estado de carga en modales
function setModalLoading(modal, isLoading) {
    if (isLoading) {
        modal.classList.add('loading');
        modal.style.pointerEvents = 'none';
    } else {
        modal.classList.remove('loading');
        modal.style.pointerEvents = 'auto';
    }
}

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

// Configure SweetAlert2 z-index globally to ensure it's above modals
if (typeof Swal !== 'undefined') {
    // Override SweetAlert2 default configuration
    const originalFire = Swal.fire;
    Swal.fire = function(...args) {
        const config = args[0] || {};
        
        // Ensure z-index is always high
        config.customClass = {
            ...config.customClass,
            popup: 'swal-high-z-index'
        };
        
        // Set high z-index in didOpen callback
        const originalDidOpen = config.didOpen;
        config.didOpen = function() {
            // Ensure SweetAlert popup is above modals
            const popup = Swal.getPopup();
            if (popup) {
                popup.style.zIndex = '10000';
            }
            
            // Call original didOpen if it exists
            if (originalDidOpen) {
                originalDidOpen.call(this);
            }
        };
        
        return originalFire.call(this, config);
    };
}

// ---------- Sidebar: toggle minimalista (persistencia) ----------
(function initSidebarToggle() {
    const btn = qs('#sidebarToggle');
    if (!btn) return;

    const BODY_COLLAPSED = 'sidebar-collapsed';
    const KEY = 'cp_sidebar_collapsed';

    // Estado inicial desde localStorage
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
        // Accesibilidad: feedback corto
        btn.setAttribute('aria-pressed', collapsed ? 'true' : 'false');
    });
})();

// ---------- View Switcher ----------
(function initViewSwitcher() {
    const viewButtons = qsa('.view-btn');
    const tableView = qs('.table-view');
    const gridView = qs('.grid-view');
    const KEY = 'cp_inventory_view';

    // Estado inicial desde localStorage
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

// ---------- Category/Subcategory dependent selectors ----------
function getSubcategoriesForParent(parentId) {
    const tree = window.inventoryCategoryTree || {};
    return tree[String(parentId)] || tree[parentId] || [];
}

function fillSubcategoryOptions(selectEl, parentId, selectedSubId = '') {
    if (!selectEl) return;
    const subs = parentId ? getSubcategoriesForParent(parentId) : [];
    selectEl.innerHTML = '<option value="">Sin subcategoría</option>';
    subs.forEach((sub) => {
        const opt = document.createElement('option');
        opt.value = String(sub.category_id);
        opt.textContent = sub.name;
        if (String(selectedSubId) === String(sub.category_id)) {
            opt.selected = true;
        }
        selectEl.appendChild(opt);
    });
}

function syncFinalCategory(parentSelect, subSelect, hiddenCategoryInput) {
    if (!parentSelect || !hiddenCategoryInput) return;
    const parentId = parentSelect.value;
    const subId = subSelect ? subSelect.value : '';
    hiddenCategoryInput.value = subId || parentId || '';
}

function bindDependentCategorySelectors(config) {
    const { parentSelect, subSelect, hiddenCategoryInput } = config;
    if (!parentSelect || !subSelect) return;

    const selectedSub = subSelect.dataset.selected || '';
    fillSubcategoryOptions(subSelect, parentSelect.value, selectedSub);
    syncFinalCategory(parentSelect, subSelect, hiddenCategoryInput);

    parentSelect.addEventListener('change', () => {
        fillSubcategoryOptions(subSelect, parentSelect.value);
        syncFinalCategory(parentSelect, subSelect, hiddenCategoryInput);
    });

    subSelect.addEventListener('change', () => {
        syncFinalCategory(parentSelect, subSelect, hiddenCategoryInput);
    });
}

// ---------- Modals ----------
(function initModals() {
    // Modal de nuevo producto
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
            newProductModal.classList.add('active');
            if (newParentCategory && !newParentCategory.value) {
                fillSubcategoryOptions(newSubcategory, '');
            }
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
            setButtonLoading(saveNewProductBtn, true);
            const formData = new FormData(newProductForm);

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
                if (!data) return; // Si no hay data (por el CSRF), salir
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
                    // Clear previous errors
                    qsa('.error-message', newProductForm).forEach(el => el.remove());

                    // Display new errors
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
                    
                    // Actualizar CSRF token si viene en la respuesta
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

    // Modal de editar producto
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
            fetch(`/products/${productId}`,
            {
                headers: {
                    'Accept': 'application/json'
                }
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
                    qs('#edit-price-buy').value = product.purchase_price || '';
                    qs('#edit-price-sell').value = product.sale_price || '';
                    qs('#edit-stock').value = product.stock_current || '';
                    qs('#edit-stock-min').value = product.stock_minimum || '';
                    qs('#edit-status').value = product.status || 'active';
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

    if (cancelEditBtn) {
        cancelEditBtn.addEventListener('click', () => {
            editModal.classList.remove('active');
        });
    }

    if (saveEditBtn) {
        saveEditBtn.addEventListener('click', () => {
            setButtonLoading(saveEditBtn, true);
            const formData = new FormData(editProductForm);
            formData.append('_method', 'PUT');

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
                if (!data) return; // Si no hay data (por el CSRF), salir
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
                    // Clear previous errors
                    qsa('.error-message', editProductForm).forEach(el => el.remove());

                    // Display new errors
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
                    
                    // Actualizar CSRF token si viene en la respuesta
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

    // Modal de ver detalles del producto
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
                headers: {
                    'Accept': 'application/json'
                }
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

    // Modal de importar productos
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

    // Función para detectar el formato del archivo
    function detectFileFormat(file) {
        const extension = file.name.split('.').pop().toLowerCase();
        const fileName = file.name.toLowerCase();
        
        // Detectar por extensión
        if (extension === 'xml' || fileName.endsWith('.xml')) {
            return { format: 'xml', name: 'XML', icon: 'fa-file-code', color: '#f59e0b' };
        } else if (extension === 'csv' || extension === 'txt' || fileName.endsWith('.csv') || fileName.endsWith('.txt')) {
            return { format: 'csv', name: 'CSV', icon: 'fa-file-csv', color: '#3b82f6' };
        } else if (extension === 'json' || fileName.endsWith('.json')) {
            return { format: 'json', name: 'JSON', icon: 'fa-file-alt', color: '#8b5cf6' };
        }
        
        return null;
    }

    // Función para formatear el tamaño del archivo
    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
    }

    // Manejar selección de archivo
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
        // Click en el label para abrir el selector de archivos
        if (fileUploadLabel) {
            fileUploadLabel.addEventListener('click', () => {
                fileInput.click();
            });
        }

        // Manejar cambio de archivo
        fileInput.addEventListener('change', (e) => {
            const file = e.target.files[0];
            if (file) {
                const detected = detectFileFormat(file);
                
                if (detected) {
                    // Mostrar información del archivo
                    fileName.textContent = file.name;
                    fileFormat.textContent = detected.name;
                    fileSize.textContent = formatFileSize(file.size);
                    fileIcon.className = `fas ${detected.icon}`;
                    fileIcon.style.color = detected.color;
                    
                    // Mostrar formato detectado
                    detectedFormatText.textContent = detected.name;
                    formatHelpText.textContent = `El sistema detectó automáticamente que tu archivo es ${detected.name}. No necesitas seleccionar el formato manualmente.`;
                    
                    // Mostrar elementos
                    fileInfo.classList.remove('hidden');
                    formatDetected.classList.remove('hidden');
                    fileUploadLabel.style.display = 'none';
                    
                    // Habilitar botón de importar
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

        // Manejar drag and drop
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

        // Botón para remover archivo
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
            
            // Confirmar importación
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
                    // Mostrar indicador de progreso
                    const progressBar = showProgressBar();
                    const longOperationIndicator = showLongOperationIndicator('Importando productos...');
                    
                    // Deshabilitar botón y mostrar estado de carga
                    setButtonLoading(confirmImportBtn, true, 'Importando...');
                    
                    // Enviar formulario
                    importForm.submit();
                    
                    // Limpiar indicadores después de un tiempo (fallback)
                    setTimeout(() => {
                        hideProgressBar(progressBar);
                        hideLongOperationIndicator(longOperationIndicator);
                        setButtonLoading(confirmImportBtn, false);
                    }, 10000);
                }
            });
        });
    }

    // Cerrar modales al hacer clic en el backdrop
    qsa('.modal-backdrop').forEach(backdrop => {
        backdrop.addEventListener('click', () => {
            backdrop.closest('.edit-modal').classList.remove('active');
        });
    });
})();


// ---------- Product Deletion with SweetAlert ----------
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



// ---------- Paginación: go-to + deshabilitados + enter ----------
(function initPagination() {
    const wrapper = qs('.pagination');
    if (!wrapper) return;

    const goInput = qs('#goToPageInput', wrapper);
    const goBtn = qs('#goToPageBtn', wrapper);

    // Si enlaces prev/next están "aria-disabled", evita navegación
    qsa('.pagination .button[aria-label]', wrapper).forEach((a) => {
        const disabled = a.getAttribute('aria-disabled') === 'true';
        if (disabled) {
            a.addEventListener('click', (e) => e.preventDefault());
            a.classList.add('is-disabled');
        }
    });

    // Click en Prev/Next por seguridad: si hay data-page, refuerza la navegación
    qsa('.pagination .button[aria-label]', wrapper).forEach((a) => {
        a.addEventListener('click', (e) => {
            const dp = a.dataset.page;
            if (!dp || a.getAttribute('aria-disabled') === 'true') return;
            // Si el href ya viene del paginator, dejamos que actúe;
            // esto es por si usas eventos SPA ajax en el futuro.
            smoothScrollTop();
        });
    });

    // Validación y "Ir"
    function goToPage() {
        const totalSpan = qs('.pagination .button.button-primary', wrapper);
        if (!totalSpan) return;

        // totalSpan innerText: "3 / 5" => extrae el total
        const parts = totalSpan.textContent.trim().split('/');
        const lastPage = Math.max(1, parseInt((parts[1] || '1').trim(), 10));
        let target = parseInt((goInput?.value || '1').trim(), 10);

        if (isNaN(target)) target = 1;
        if (target < 1) target = 1;
        if (target > lastPage) target = lastPage;

        // Construye URL conservando query string (filtros)
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

document.addEventListener('DOMContentLoaded', () => {
    const productSection = document.querySelector('.products-section');
    const loadingSpinner = document.querySelector('.loading-spinner-overlay');
    const filterForm = document.querySelector('.filter-form');

    if (productSection && loadingSpinner) {
        // Show spinner on page load
        loadingSpinner.style.display = 'flex';

        // Hide spinner when content is loaded
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