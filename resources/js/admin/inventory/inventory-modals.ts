// @ts-nocheck
import {
    qs,
    qsa,
    getCSRFToken,
    jsonHeaders,
    escapeHtml,
    escapeHtmlAttr,
    safeParseJsonResponse,
    readJsonOrThrow,
    smartFetch,
    jsonValidationMessage,
    renderVariantsListHtml,
    setEditCurrentProductImagePreview,
    markEditMainImageForRemoval,
    syncFeaturedStarButtons,
    fillSubcategoryOptions,
    syncFinalCategory,
    syncParentCategoryHiddenInput,
    bindDependentCategorySelectors,
    initCollapsibleFormSections,
    showSubtleNotification,
    setButtonLoading,
    setActionButtonLoading,
    showSuccessFeedback,
    showErrorFeedback,
    setModalLoading,
    closeOtherInventoryModals,
    categoryPath,
    productUsesPlaceholderImage,
    buildProductMediaPlaceholderHtml,
} from './inventory-shared';
import {
    applyServerFieldErrors,
    initBrandCombobox,
    initProductSearchCombobox,
} from './inventory-modal-helpers';
import {
    refreshClassificationFields,
    collectClassificationValueIds,
    CF_API,
} from './inventory-classification';
import { initStaticSearchCombobox, setComboboxFieldError } from '../shared/static-search-combobox';
import { initFileUploadZone } from '../shared/file-upload-zone';
import { cf4Confirm, cf4Warning, cf4Toast, cf4Error } from '../shared/swal';
import { compressImageFile, compressFileList } from './product-image-compression';

export async function initModals() {
    // SweetAlert2 is lazy-loaded via cf4* helpers on first dialog.
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
    const VALID_IMAGE_TYPES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

    const MAX_IMAGE_SIZE_BYTES = 10 * 1024 * 1024; // 10 MB por imagen (igual al límite del servidor)

    function validateGalleryInput(inputEl, hintEl) {
        if (!inputEl || !inputEl.files || inputEl.files.length === 0) return true;
        const images = Array.from(inputEl.files).filter(f => VALID_IMAGE_TYPES.includes(f.type));
        if (images.length === 0) {
            void cf4Warning(
                'La carpeta seleccionada no contiene imágenes (JPEG, PNG, WebP o GIF). Seleccioná una carpeta con imágenes.',
                'Sin imágenes válidas',
            );
            inputEl.value = '';
            if (hintEl) hintEl.textContent = 'Ningún archivo seleccionado';
            return false;
        }
        const oversized = images.filter(f => f.size > MAX_IMAGE_SIZE_BYTES);
        if (oversized.length > 0) {
            void cf4Error(
                'Ha excedido la capacidad de imágenes que puedes cargar. Cada imagen no puede superar 10 MB.',
                'Error',
            );
            inputEl.value = '';
            if (hintEl) hintEl.textContent = 'Ningún archivo seleccionado';
            return false;
        }
        if (hintEl) hintEl.textContent = images.length + ' imagen' + (images.length > 1 ? 'es' : '') + ' seleccionada' + (images.length > 1 ? 's' : '');
        return true;
    }

    const newImagesInput  = qs('#new-images');
    const editImagesInput = qs('#edit-images');

    function galleryHintForInput(inputEl) {
        return (
            inputEl?.closest('.cf-file-upload-field')?.querySelector('.cf-file-upload__hint')
            || inputEl?.closest('.form-group')?.querySelector('small')
        );
    }

    newImagesInput?.addEventListener('change', function () {
        validateGalleryInput(this, galleryHintForInput(this));
    });

    editImagesInput?.addEventListener('change', function () {
        validateGalleryInput(this, galleryHintForInput(this));
    });

    if (openNewProductModalBtn) {
        openNewProductModalBtn.addEventListener('click', () => {
            closeOtherInventoryModals('new-product-modal');
            if (newProductForm) {
                newProductForm.reset();
            }
            fillSubcategoryOptions(newSubcategory, '', '', newSubcategoryCombobox);
            newBrandCombobox?.reset();
            newParentCategoryCombobox?.reset();
            newProviderCombobox?.reset();
            newImageUpload?.reset();
            newGalleryUpload?.reset();
            newProductModal.classList.add('active');
            newProductModal.setAttribute('aria-hidden', 'false');
            refreshNewClassificationFields();
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
        saveNewProductBtn.addEventListener('click', async () => {
            syncFinalCategory(newParentCategory, newSubcategory, newFinalCategory);
            syncParentCategoryHiddenInput(newParentCategory, newParentCategoryHidden);

            if (newProductForm && typeof newProductForm.reportValidity === 'function' && !newProductForm.reportValidity()) {
                return;
            }

            if (!newFinalCategory || !String(newFinalCategory.value || '').trim()) {
                void cf4Warning(
                    'Elegí una categoría. La subcategoría es opcional; si guardás solo la categoría (sin subcategoría) no podrás usar color, talla, etc.',
                    'Categoría',
                );
                return;
            }

            if (!qs('#new-brand')?.value) {
                const cb = qs('#new-brand-combobox');
                setComboboxFieldError(cb, 'Seleccioná una marca antes de guardar el producto.');
                cb?.querySelector('input')?.focus();
                void cf4Warning('Selecciona una marca antes de guardar el producto.', 'Marca requerida');
                return;
            }
            setComboboxFieldError(qs('#new-brand-combobox'), '');

            // Validate gallery: if a folder was selected, ensure it has at least one image
            if (newImagesInput?.files?.length > 0) {
                if (!validateGalleryInput(newImagesInput, galleryHintForInput(newImagesInput))) return;
            }

            setButtonLoading(saveNewProductBtn, true);

            const formData = new FormData(newProductForm);

            const mainInput = qs('#new-image');
            if (mainInput?.files?.[0]) {
                const metaSize = qs('#new-image-meta .cf-file-upload-meta__size');
                if (metaSize) metaSize.textContent = 'Preparando imagen…';
                const compressedMain = await compressImageFile(mainInput.files[0]);
                formData.set('image', compressedMain, compressedMain.name);
                if (metaSize) metaSize.textContent = `${Math.round(compressedMain.size / 1024)} KB`;
            }

            formData.delete('images[]');
            if (newImagesInput?.files?.length > 0) {
                const galleryMetaName = qs('#new-images-meta .cf-file-upload-meta__name');
                if (galleryMetaName) galleryMetaName.textContent = 'Preparando imágenes…';
                const validGallery = Array.from(newImagesInput.files)
                    .filter((f) => VALID_IMAGE_TYPES.includes(f.type));
                const compressedGallery = await compressFileList(validGallery);
                compressedGallery.forEach((f) => formData.append('images[]', f));
                if (galleryMetaName) {
                    galleryMetaName.textContent = `${compressedGallery.length} imagen(es) lista(s)`;
                }
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
                    void cf4Toast({
                        icon: 'success',
                        title: 'Producto creado',
                        text: data.message,
                        timer: 3500,
                    }).then(() => { location.reload(); });
                } else if (data.errors) {
                    qsa('.error-message', newProductForm).forEach((el) => el.remove());
                    qsa('.js-server-field-error', newProductForm).forEach((el) => el.remove());
                    qsa('.brand-combobox.error', newProductForm).forEach((el) => el.classList.remove('error'));
                    applyServerFieldErrors(newProductForm, data.errors);

                    if (data.csrf_token) {
                        const metaTag = document.querySelector('meta[name="csrf-token"]');
                        if (metaTag) {
                            metaTag.setAttribute('content', data.csrf_token);
                        }
                    }

                    void cf4Error(
                        jsonValidationMessage(data) || data.message || 'Revisa los campos del formulario.',
                        'Error de validación',
                    );
                } else {
                    void cf4Error(data.message || 'Ocurrió un error al crear el producto.', 'Error');
                }
            })
            .catch(error => {
                setButtonLoading(saveNewProductBtn, false);
                console.error('Error:', error);
                void cf4Error(
                    error.isSizeError
                        ? 'Ha excedido la capacidad de imágenes que puedes cargar.'
                        : 'Ocurrió un error inesperado. Por favor, revisa los logs.',
                    'Error',
                );
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
                    closeOtherInventoryModals('edit-modal');
                    editModal?.classList.add('active');
                    editModal?.setAttribute('aria-hidden', 'false');
                    setModalLoading(editModal, true);
            fetch(`/products/${productId}`, {
                credentials: 'same-origin',
                headers: jsonHeaders(),
            })
            .then(response => {
                return readJsonOrThrow(response, 'Error al cargar el producto');
            })
            .then(data => {
                setModalLoading(editModal, false);
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
                    editImageUpload?.reset();
                    editGalleryUpload?.reset();
                    setEditCurrentProductImagePreview(product);
                    const removeMainInput = qs('#edit-remove-main-image');
                    if (removeMainInput) removeMainInput.value = '0';
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

                    editClassificationPreset = product.classification_value_ids || [];

                    if (editParentCategory) {
                        editParentCategoryCombobox?.setValue(detectedParentId, { silent: true });
                        fillSubcategoryOptions(
                            editSubcategory,
                            detectedParentId,
                            detectedSubcategoryId,
                            editSubcategoryCombobox
                        );
                        syncFinalCategory(editParentCategory, editSubcategory, editFinalCategory);
                        syncParentCategoryHiddenInput(editParentCategory, editParentCategoryHidden);
                    }
                    editProviderCombobox?.setValue(product.supplier_id || '', { silent: true });
                    editBrandCombobox?.setValue(product.brand_id || '', { silent: true });
                    qs('#edit-price-buy').value = product.purchase_price || '';
                    qs('#edit-price-sell').value = product.sale_price || '';
                    qs('#edit-stock').value = product.stock_current || '';
                    qs('#edit-stock-min').value = product.stock_minimum || '';
                    qs('#edit-status').value = product.status || 'active';
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
                } else {
                    setModalLoading(editModal, false);
                    const editBody = editModal?.querySelector('.modal-body');
                    if (editBody) {
                        editBody.innerHTML = '<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> '
                            + escapeHtml(data.message || 'Error al cargar el producto') + '</div>';
                    }
                    void cf4Error(data.message || 'Error al cargar el producto', 'Error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                setModalLoading(editModal, false);
                const editBody = editModal?.querySelector('.modal-body');
                if (editBody) {
                    editBody.innerHTML = '<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> '
                        + escapeHtml(error?.message || 'Error al cargar el producto. Inténtalo de nuevo.') + '</div>';
                }
            });
        });
    });

    if (closeEditModalBtn) {
        closeEditModalBtn.addEventListener('click', () => {
            editModal.classList.remove('active');
        });
    }

    /** CF4-84 — al cambiar categoría en edición se limpian selecciones previas */
    let editClassificationPreset = [];

    const newBrandCombobox = initBrandCombobox('new-brand-search', 'new-brand', 'new-brand-dropdown', 'new-brand-combobox');
    const editBrandCombobox = initBrandCombobox('edit-brand-search', 'edit-brand', 'edit-brand-dropdown', 'edit-brand-combobox');

    const newParentCategoryCombobox = initStaticSearchCombobox({
        searchInputId: 'new-parent-category-search',
        hiddenInputId: 'new-parent-category',
        dropdownId: 'new-parent-category-dropdown',
        wrapperId: 'new-parent-category-combobox',
        options: window.inventoryParentCategories || [],
        getId: (c) => c.id,
        getLabel: (c) => c.name,
        placeholder: 'Escribe para buscar una categoría...',
    });
    const editParentCategoryCombobox = initStaticSearchCombobox({
        searchInputId: 'edit-parent-category-search',
        hiddenInputId: 'edit-parent-category',
        dropdownId: 'edit-parent-category-dropdown',
        wrapperId: 'edit-parent-category-combobox',
        options: window.inventoryParentCategories || [],
        getId: (c) => c.id,
        getLabel: (c) => c.name,
        placeholder: 'Escribe para buscar una categoría...',
    });
    const newProviderCombobox = initStaticSearchCombobox({
        searchInputId: 'new-provider-search',
        hiddenInputId: 'new-provider',
        dropdownId: 'new-provider-dropdown',
        wrapperId: 'new-provider-combobox',
        options: window.inventorySuppliers || [],
        getId: (s) => s.id,
        getLabel: (s) => s.name,
        placeholder: 'Escribe para buscar un proveedor...',
    });
    const editProviderCombobox = initStaticSearchCombobox({
        searchInputId: 'edit-provider-search',
        hiddenInputId: 'edit-provider',
        dropdownId: 'edit-provider-dropdown',
        wrapperId: 'edit-provider-combobox',
        options: window.inventorySuppliers || [],
        getId: (s) => s.id,
        getLabel: (s) => s.name,
        placeholder: 'Escribe para buscar un proveedor...',
    });

    const newSubcategoryCombobox = initStaticSearchCombobox({
        searchInputId: 'new-subcategory-search',
        hiddenInputId: 'new-subcategory',
        dropdownId: 'new-subcategory-dropdown',
        wrapperId: 'new-subcategory-combobox',
        options: [{ id: '', name: 'Seleccioná primero una categoría' }],
        getId: (o) => o.id,
        getLabel: (o) => o.name,
        placeholder: 'Seleccioná primero una categoría',
    });
    const editSubcategoryCombobox = initStaticSearchCombobox({
        searchInputId: 'edit-subcategory-search',
        hiddenInputId: 'edit-subcategory',
        dropdownId: 'edit-subcategory-dropdown',
        wrapperId: 'edit-subcategory-combobox',
        options: [{ id: '', name: 'Seleccioná primero una categoría' }],
        getId: (o) => o.id,
        getLabel: (o) => o.name,
        placeholder: 'Seleccioná primero una categoría',
    });

    bindDependentCategorySelectors({
        parentSelect: newParentCategory,
        subSelect: newSubcategory,
        hiddenCategoryInput: newFinalCategory,
        parentCategoryHiddenInput: newParentCategoryHidden,
        subCombobox: newSubcategoryCombobox,
    });

    bindDependentCategorySelectors({
        parentSelect: editParentCategory,
        subSelect: editSubcategory,
        hiddenCategoryInput: editFinalCategory,
        parentCategoryHiddenInput: editParentCategoryHidden,
        subCombobox: editSubcategoryCombobox,
    });

    function refreshNewClassificationFields() {
        syncFinalCategory(newParentCategory, newSubcategory, newFinalCategory);
        syncParentCategoryHiddenInput(newParentCategory, newParentCategoryHidden);
        void refreshClassificationFields('#new-classification-fields', newFinalCategory?.value || '', null);
    }

    function refreshEditClassificationFields() {
        syncFinalCategory(editParentCategory, editSubcategory, editFinalCategory);
        syncParentCategoryHiddenInput(editParentCategory, editParentCategoryHidden);
        void refreshClassificationFields(
            '#edit-classification-fields',
            editFinalCategory?.value || '',
            editClassificationPreset
        );
    }

    function scheduleRefreshNewClassificationFields() {
        queueMicrotask(refreshNewClassificationFields);
    }

    function scheduleRefreshEditClassificationFields() {
        queueMicrotask(refreshEditClassificationFields);
    }

    newParentCategory?.addEventListener('change', scheduleRefreshNewClassificationFields);
    newSubcategory?.addEventListener('change', scheduleRefreshNewClassificationFields);
    newParentCategoryCombobox?.onChange(scheduleRefreshNewClassificationFields);
    newSubcategoryCombobox?.onChange(scheduleRefreshNewClassificationFields);

    editParentCategory?.addEventListener('change', () => {
        editClassificationPreset = [];
        scheduleRefreshEditClassificationFields();
    });
    editSubcategory?.addEventListener('change', () => {
        editClassificationPreset = [];
        scheduleRefreshEditClassificationFields();
    });
    editParentCategoryCombobox?.onChange(() => {
        editClassificationPreset = [];
        scheduleRefreshEditClassificationFields();
    });
    editSubcategoryCombobox?.onChange(() => {
        editClassificationPreset = [];
        scheduleRefreshEditClassificationFields();
    });

    const newImageUpload = initFileUploadZone({
        inputId: 'new-image',
        metaId: 'new-image-meta',
        triggerId: 'new-image-trigger',
        imagePreview: true,
    });
    const newGalleryUpload = initFileUploadZone({
        inputId: 'new-images',
        metaId: 'new-images-meta',
        triggerId: 'new-images-trigger',
    });
    const editImageUpload = initFileUploadZone({
        inputId: 'edit-image',
        metaId: 'edit-image-meta',
        triggerId: 'edit-image-trigger',
        imagePreview: true,
        onChange(file) {
            if (file) {
                const removeInput = qs('#edit-remove-main-image');
                if (removeInput) removeInput.value = '0';
            }
        },
    });

    qs('#current-image-preview')?.addEventListener('click', (e) => {
        if (!e.target.closest('.js-remove-current-image')) return;
        e.preventDefault();
        markEditMainImageForRemoval();
        editImageUpload?.reset();
    });
    const editGalleryUpload = initFileUploadZone({
        inputId: 'edit-images',
        metaId: 'edit-images-meta',
        triggerId: 'edit-images-trigger',
    });

    initCollapsibleFormSections(newProductModal);
    initCollapsibleFormSections(editModal);

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
                void cf4Toast({ icon: 'success', title: 'Agregada', text: 'Variante agregada correctamente.' });
            } catch (err) {
                const msg = err?.message || 'No se pudo agregar la variante.';
                void cf4Error(msg, 'No se pudo agregar');
            } finally {
                setActionButtonLoading(editVariantAddBtn, false);
                editVariantAddBtn.disabled = true;
                editVariantSearch?.focus();
            }
        });
    }

    if (saveEditBtn) {
        saveEditBtn.addEventListener('click', async () => {
            syncFinalCategory(editParentCategory, editSubcategory, editFinalCategory);
            syncParentCategoryHiddenInput(editParentCategory, editParentCategoryHidden);

            if (!qs('#edit-brand')?.value) {
                const cb = qs('#edit-brand-combobox');
                setComboboxFieldError(cb, 'Seleccioná una marca antes de guardar el producto.');
                cb?.querySelector('input')?.focus();
                void cf4Warning('Selecciona una marca antes de guardar el producto.', 'Marca requerida');
                return;
            }
            setComboboxFieldError(qs('#edit-brand-combobox'), '');

            // Validate gallery: if a folder was selected, ensure it has at least one image
            if (editImagesInput?.files?.length > 0) {
                if (!validateGalleryInput(editImagesInput, galleryHintForInput(editImagesInput))) return;
            }

            setButtonLoading(saveEditBtn, true);

            const formData = new FormData(editProductForm);

            const editMainInput = qs('#edit-image');
            if (editMainInput?.files?.[0]) {
                const editMetaSize = qs('#edit-image-meta .cf-file-upload-meta__size');
                if (editMetaSize) editMetaSize.textContent = 'Preparando imagen…';
                const compressedEditMain = await compressImageFile(editMainInput.files[0]);
                formData.set('image', compressedEditMain, compressedEditMain.name);
                if (editMetaSize) editMetaSize.textContent = `${Math.round(compressedEditMain.size / 1024)} KB`;
            }

            formData.delete('images[]');
            if (editImagesInput?.files?.length > 0) {
                const editGalleryMetaName = qs('#edit-images-meta .cf-file-upload-meta__name');
                if (editGalleryMetaName) editGalleryMetaName.textContent = 'Preparando imágenes…';
                const validEditGallery = Array.from(editImagesInput.files)
                    .filter((f) => VALID_IMAGE_TYPES.includes(f.type));
                const compressedEditGallery = await compressFileList(validEditGallery);
                compressedEditGallery.forEach((f) => formData.append('images[]', f));
                if (editGalleryMetaName) {
                    editGalleryMetaName.textContent = `${compressedEditGallery.length} imagen(es) lista(s)`;
                }
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
                    void cf4Toast({
                        icon: 'success',
                        title: 'Producto actualizado',
                        text: data.message || '',
                        timer: 2600,
                    }).then(() => { location.reload(); });
                } else if (data.errors) {
                    qsa('.error-message', editProductForm).forEach((el) => el.remove());
                    qsa('.js-server-field-error', editProductForm).forEach((el) => el.remove());
                    qsa('.brand-combobox.error', editProductForm).forEach((el) => el.classList.remove('error'));
                    applyServerFieldErrors(editProductForm, data.errors);

                    if (data.csrf_token) {
                        const metaTag = document.querySelector('meta[name="csrf-token"]');
                        if (metaTag) {
                            metaTag.setAttribute('content', data.csrf_token);
                        }
                    }
                    
                    void cf4Error(
                        jsonValidationMessage(data) || data.message || 'Revisa los campos del formulario.',
                        'Error de validación',
                    );
                } else {
                    void cf4Error(data.message || 'Ocurrió un error al actualizar el producto.', 'Error');
                }
            })
            .catch(error => {
                setButtonLoading(saveEditBtn, false);
                console.error('Error:', error);
                void cf4Error(
                    error.isSizeError
                        ? 'Ha excedido la capacidad de imágenes que puedes cargar.'
                        : 'Ocurrió un error inesperado. Por favor, revisa los logs.',
                    'Error',
                );
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

        void cf4Confirm({
            title: `¿Eliminar la variante "${variantName}"?`,
            text: 'Esta acción solo elimina la variante seleccionada. El producto base permanecerá activo.',
            icon: 'warning',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar',
            danger: true,
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
                        void cf4Toast({ icon: 'success', title: 'Eliminada', text: data.message || 'Variante eliminada correctamente.' });
                        return;
                    }

                    const msg = data.message || 'No se pudo eliminar la variante.';
                    if (response.status === 409) {
                        void cf4Warning(msg, 'No se puede eliminar');
                    } else {
                        void cf4Error(msg, 'No se puede eliminar');
                    }
                })
                .catch(() => {
                    setActionButtonLoading(btn, false);
                    void cf4Error('Error de conexión al eliminar la variante.', 'Error');
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
        editModal?.classList.remove('is-underlay');
    }

    function openVariantEditModal(baseId, variantProductId) {
        const v = findVariantInEditList(variantProductId);
        if (!v || !variantEditModal) {
            void cf4Error('No se encontró la variante en la lista.', 'Error');
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

        editModal?.classList.add('is-underlay');
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
                    void cf4Toast({ icon: 'success', title: 'Listo', text: data.message || 'Variante actualizada correctamente.' });
                    return;
                }

                const msg = data.message || 'No se pudo guardar la variante.';
                if (data.errors && typeof data.errors === 'object') {
                    const first = Object.values(data.errors).flat()[0];
                    void cf4Warning(first || msg, 'Revisá los datos');
                } else {
                    void cf4Error(msg, 'Error');
                }
            })
            .catch(() => {
                setButtonLoading(variantEditSaveBtn, false);
                void cf4Error('Error de conexión al guardar la variante.', 'Error');
            });
    });

    // Modal: View product details
    const viewProductModal = qs('#view-product-modal');
    const viewDetailsBtns = qsa('.view-details-btn');
    const closeViewProductModalBtn = qs('#close-view-product-modal');
    const cancelViewProductBtn = qs('#cancel-view-product');
    const viewProductBody = qs('#view-product-body');

    function productDetailField(label, iconClass, valueHtml, fullWidth = false) {
        const wide = fullWidth ? ' product-detail-field--full' : '';
        return `<div class="form-group product-detail-field${wide}">
            <label><i class="fas ${iconClass}" aria-hidden="true"></i> ${label}</label>
            <div class="product-details-value">${valueHtml}</div>
        </div>`;
    }

    function productDetailSection(title, sectionKey, bodyHtml) {
        return `<section class="form-section product-details-section" data-section="${sectionKey}">
            <button type="button" class="form-section__toggle" aria-expanded="true">
                <span>${title}</span>
                <i class="fas fa-chevron-down" aria-hidden="true"></i>
            </button>
            <div class="form-section__body">${bodyHtml}</div>
        </section>`;
    }

    function buildClassificationFieldsHtml(product) {
        const values = product.classification_values || product.classificationValues || [];
        if (!Array.isArray(values) || values.length === 0) {
            return '';
        }
        return values.map((cv) => {
            const dimName = cv.dimension?.name || 'Clasificación';
            const val = cv.value ?? '—';
            return productDetailField(escapeHtml(dimName), 'fa-tags', escapeHtml(String(val)));
        }).join('');
    }

    function wrapProductDetailMedia(innerHtml) {
        if (innerHtml.includes('product-details-carousel-outer')) {
            return `<div class="product-details-media">${innerHtml}</div>`;
        }
        return `<div class="product-details-media"><div class="product-details-media-frame">${innerHtml}</div></div>`;
    }

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
            closeOtherInventoryModals('view-product-modal');
            setActionButtonLoading(btn, true, 'Ver detalles');
            if (viewProductBody) {
                viewProductBody.innerHTML = `
                    <div class="loading-spinner" role="status">
                        <i class="fas fa-spinner fa-spin fa-2x" aria-hidden="true"></i>
                        <p>Cargando detalles…</p>
                    </div>`;
            }
            viewProductModal?.classList.add('active');
            viewProductModal?.setAttribute('aria-hidden', 'false');
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
                    const usesPlaceholder = productUsesPlaceholderImage(product);
                    const allImages = [];
                    if (!usesPlaceholder) {
                        if (product.media_main) allImages.push(product.media_main);
                        if (Array.isArray(product.media_gallery)) allImages.push(...product.media_gallery);
                        if (!allImages.length && product.image && product.image !== 'default.png') {
                            allImages.push('/assets/images/products/' + product.image);
                        }
                    }

                    let imageHtml;
                    if (usesPlaceholder) {
                        imageHtml = buildProductMediaPlaceholderHtml(
                            product.placeholder_icon_class || 'fas fa-box',
                            product.name,
                            'detail'
                        );
                    } else if (!allImages.length) {
                        imageHtml = '<p class="product-details-empty">No hay imagen</p>';
                    } else if (allImages.length === 1) {
                        imageHtml = `<img src="${allImages[0]}" alt="${escapeHtmlAttr(product.name)}">`;
                    } else {
                        const slides = allImages.map(url =>
                            `<div class="carousel-slide"><img src="${url}" alt="${escapeHtmlAttr(product.name)}"></div>`
                        ).join('');
                        const dots = allImages.map((_, i) =>
                            `<button class="carousel-dot${i === 0 ? ' active' : ''}" aria-label="Imagen ${i + 1}"></button>`
                        ).join('');
                        imageHtml = `
                            <div class="product-details-carousel-outer">
                                <button type="button" class="carousel-btn carousel-btn--prev" id="admin-carousel-prev" disabled aria-label="Anterior">&#8249;</button>
                                <div class="product-details-media-frame">
                                    <div class="admin-product-carousel">
                                        <div class="carousel-viewport">
                                            <div class="carousel-track" id="admin-carousel-track">${slides}</div>
                                        </div>
                                        <div class="carousel-dots" id="admin-carousel-dots">${dots}</div>
                                    </div>
                                </div>
                                <button type="button" class="carousel-btn carousel-btn--next" id="admin-carousel-next" aria-label="Siguiente">&#8250;</button>
                            </div>`;
                    }

                    const statusLabels = {
                        active: 'Activo',
                        inactive: 'Inactivo',
                        out_of_stock: 'Agotado',
                        discontinued: 'Descontinuado',
                    };
                    const featuredLabel = product.is_featured
                        ? 'Sí (inicio y catálogo)'
                        : 'No';

                    viewProductBody.innerHTML = `
                        <div class="product-details-view">
                            ${productDetailSection('Imágenes', 'images', wrapProductDetailMedia(imageHtml))}
                            ${productDetailSection('Datos básicos', 'basic', `
                                <div class="product-details-fields">
                                    ${productDetailField('Nombre', 'fa-tag', escapeHtml(product.name))}
                                    ${productDetailField('Categoría', 'fa-boxes', escapeHtml(categoryPath(product.category)))}
                                    ${productDetailField('Descripción', 'fa-align-left', escapeHtml(product.description || '-'), true)}
                                    ${productDetailField('Proveedor', 'fa-truck', escapeHtml(product.supplier?.name || '-'))}
                                    ${buildClassificationFieldsHtml(product)}
                                    ${productDetailField('Destacado en tienda', 'fa-star', featuredLabel)}
                                </div>
                            `)}
                            ${productDetailSection('Precios y stock', 'pricing', `
                                <div class="product-details-fields">
                                    ${productDetailField('Precio de Compra', 'fa-dollar-sign', `₡${escapeHtml(product.purchase_price)}`)}
                                    ${productDetailField('Precio de Venta', 'fa-money-bill-wave', `₡${escapeHtml(product.sale_price)}`)}
                                    ${productDetailField('Stock Actual', 'fa-warehouse', escapeHtml(product.stock_current))}
                                    ${productDetailField('Stock Mínimo', 'fa-minus-circle', escapeHtml(product.stock_minimum))}
                                    ${productDetailField('Estado', 'fa-info-circle', escapeHtml(statusLabels[product.status] || product.status))}
                                </div>
                            `)}
                        </div>`;
                    initCollapsibleFormSections(viewProductModal);
                    initAdminViewCarousel();
                } else {
                    if (viewProductBody) {
                        viewProductBody.innerHTML = '<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> '
                            + escapeHtml(data.message || 'Error al cargar el producto') + '</div>';
                    }
                    void cf4Error(data.message || 'Error al cargar el producto', 'Error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                setActionButtonLoading(btn, false);
                setModalLoading(viewProductModal, false);
                if (viewProductBody) {
                    viewProductBody.innerHTML = '<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> '
                        + escapeHtml(error?.message || 'Error al cargar el producto. Inténtalo de nuevo.') + '</div>';
                }
            });
        });
    });

    const viewProductBackdrop = qs('#view-product-modal-backdrop');
    const closeViewProductModal = () => viewProductModal?.classList.remove('active');

    if (closeViewProductModalBtn) {
        closeViewProductModalBtn.addEventListener('click', closeViewProductModal);
    }

    if (cancelViewProductBtn) {
        cancelViewProductBtn.addEventListener('click', closeViewProductModal);
    }

    viewProductBackdrop?.addEventListener('click', closeViewProductModal);

    // Modal: Import products
    const importModal = qs('#import-modal');
    const openImportModalBtn = qs('#open-import-modal') || qs('#import-btn');
    const closeImportModalBtn = qs('#close-import-modal');
    const cancelImportBtn = qs('#cancel-import');
    const confirmImportBtn = qs('#confirm-import');
    const importForm = qs('#import-form');

    // --- Background import: progress pane + polling state ---
    const importQueue = qs('#import-queue');
    const importQueueList = qs('#import-queue-list');
    const importQueueCloseBtn = qs('#import-queue-close');
    const importQueueNewBtn = qs('#import-queue-new');
    const importProgress = qs('#import-progress');
    const progressIcon = qs('#import-progress-icon');
    const progressTitle = qs('#import-progress-title');
    const progressFile = qs('#import-progress-file');
    const progressBar = qs('#import-progress-bar');
    const progressFill = qs('#import-progress-fill');
    const progressMessage = qs('#import-progress-message');
    const progressHint = qs('#import-progress-hint');
    const progressCloseBtn = qs('#import-progress-close');
    const progressDoneBtn = qs('#import-progress-done');
    const statCreated = qs('#import-stat-created');
    const statUpdated = qs('#import-stat-updated');
    const statSkipped = qs('#import-stat-skipped');
    const statErrors = qs('#import-stat-errors');

    const activeUrl = importModal?.dataset.activeUrl || '';
    const progressUrlTpl = importModal?.dataset.progressUrl || '';
    const dismissUrl = importModal?.dataset.dismissUrl || '';
    const csrfToken =
        importForm?.querySelector('input[name="_token"]')?.value ||
        document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ||
        '';

    let pollTimer = null;
    let activeImportId = null;

    function stopPolling() {
        if (pollTimer) {
            clearTimeout(pollTimer);
            pollTimer = null;
        }
    }

    function showImportForm() {
        if (importForm) importForm.hidden = false;
        if (importQueue) importQueue.hidden = true;
        if (importProgress) importProgress.hidden = true;
    }

    function showImportQueuePane() {
        if (importForm) importForm.hidden = true;
        if (importQueue) importQueue.hidden = false;
        if (importProgress) importProgress.hidden = true;
    }

    function showImportProgressPane() {
        if (importForm) importForm.hidden = true;
        if (importQueue) importQueue.hidden = true;
        if (importProgress) importProgress.hidden = false;
    }

    function setStat(el, value) {
        if (el) el.textContent = String(value ?? 0);
    }

    function renderProgress(p) {
        if (!p) return;
        const status = p.status || 'running';
        const total = Number(p.total || 0);
        const processed = Number(p.processed || 0);
        const terminal = status === 'done' || status === 'failed';
        const level = p.level || (status === 'failed' ? 'error' : 'running');

        // Title + icon by state.
        if (status === 'done') {
            progressTitle && (progressTitle.textContent = 'Importación finalizada');
        } else if (status === 'failed') {
            progressTitle && (progressTitle.textContent = 'Importación fallida');
        } else if (status === 'queued') {
            progressTitle && (progressTitle.textContent = 'En cola…');
        } else {
            progressTitle && (progressTitle.textContent = 'Importando productos…');
        }

        if (progressFile) progressFile.textContent = p.filename || '';
        if (progressMessage) progressMessage.textContent = p.message || '';

        setStat(statCreated, p.created);
        setStat(statUpdated, p.updated);
        setStat(statSkipped, p.skipped);
        setStat(statErrors, p.errors);

        // Progress bar.
        const indeterminate = !terminal && total <= 0;
        if (progressBar) {
            progressBar.classList.toggle('is-indeterminate', indeterminate);
        }
        if (progressFill) {
            if (indeterminate) {
                progressFill.style.width = '';
            } else {
                const pct = terminal ? 100 : (total > 0 ? Math.min(100, Math.round((processed / total) * 100)) : 0);
                progressFill.style.width = pct + '%';
                progressBar?.setAttribute('aria-valuenow', String(pct));
            }
            const fillLevel = level === 'error' ? 'error' : level === 'warning' ? 'warning' : 'success';
            progressFill.setAttribute('data-level', fillLevel);
        }

        // Icon state.
        if (progressIcon) {
            const iconState = status === 'done'
                ? (level === 'warning' ? 'warning' : level === 'error' ? 'error' : 'success')
                : status === 'failed' ? 'error' : 'running';
            progressIcon.setAttribute('data-state', iconState);
            const glyph = iconState === 'success'
                ? 'fa-circle-check'
                : iconState === 'warning'
                    ? 'fa-triangle-exclamation'
                    : iconState === 'error'
                        ? 'fa-circle-exclamation'
                        : 'fa-spinner fa-spin';
            progressIcon.innerHTML = `<i class="fas ${glyph}" aria-hidden="true"></i>`;
        }

        // Footer + hint depend on terminal state.
        if (terminal) {
            progressHint && (progressHint.hidden = true);
            progressCloseBtn && (progressCloseBtn.hidden = true);
            progressDoneBtn && (progressDoneBtn.hidden = false);
        } else {
            progressHint && (progressHint.hidden = false);
            progressCloseBtn && (progressCloseBtn.hidden = false);
            progressDoneBtn && (progressDoneBtn.hidden = true);
        }
    }

    function progressStatusLabel(progress) {
        const status = progress?.status || 'queued';
        if (status === 'done') return 'Finalizada';
        if (status === 'failed') return 'Fallida';
        if (status === 'running') return 'En proceso';
        return 'En cola';
    }

    function progressStatusLevel(progress) {
        const status = progress?.status || 'queued';
        if (status === 'done') return progress?.level === 'warning' ? 'warning' : 'success';
        if (status === 'failed' || progress?.level === 'error') return 'error';
        return 'running';
    }

    function progressPercent(progress) {
        const status = progress?.status || 'queued';
        if (status === 'done' || status === 'failed') return 100;

        const total = Number(progress?.total || 0);
        const processed = Number(progress?.processed || 0);

        return total > 0 ? Math.min(100, Math.round((processed / total) * 100)) : null;
    }

    function renderImportQueue(imports) {
        const visibleImports = Array.isArray(imports)
            ? imports.filter((item) => item && item.importId && item.progress)
            : [];

        if (!importQueueList) return false;

        if (visibleImports.length === 0) {
            importQueueList.innerHTML = '';
            return false;
        }

        importQueueList.innerHTML = visibleImports.map((item) => {
            const progress = item.progress || {};
            const percent = progressPercent(progress);
            const level = progressStatusLevel(progress);
            const statusLabel = progressStatusLabel(progress);
            const filename = progress.filename || 'Importación de catálogo';
            const updatedAt = progress.updatedAt
                ? new Date(progress.updatedAt).toLocaleString('es-CR', { dateStyle: 'short', timeStyle: 'short' })
                : '';
            const metrics = [
                `${Number(progress.created || 0)} creados`,
                `${Number(progress.updated || 0)} actualizados`,
                `${Number(progress.skipped || 0)} omitidos`,
            ].join(' · ');
            const progressWidth = percent === null ? 38 : percent;

            return `
                <article class="import-queue__item" data-state="${escapeHtmlAttr(level)}">
                    <div class="import-queue__main">
                        <div class="import-queue__title-row">
                            <strong class="import-queue__filename">${escapeHtml(filename)}</strong>
                            <span class="import-queue__badge" data-state="${escapeHtmlAttr(level)}">${escapeHtml(statusLabel)}</span>
                        </div>
                        <div class="import-queue__meta">
                            <span>${escapeHtml(metrics)}</span>
                            ${updatedAt ? `<span>Actualizado ${escapeHtml(updatedAt)}</span>` : ''}
                        </div>
                        <div class="import-queue__bar ${percent === null ? 'is-indeterminate' : ''}" aria-hidden="true">
                            <span style="width: ${progressWidth}%"></span>
                        </div>
                    </div>
                    <button type="button" class="btn btn-secondary import-queue__view" data-import-id="${escapeHtmlAttr(item.importId)}">
                        <i class="fas fa-eye" aria-hidden="true"></i> Ver
                    </button>
                </article>
            `;
        }).join('');

        return true;
    }

    function openImportProgress(importId, progress = null) {
        activeImportId = importId;
        showImportProgressPane();
        if (progress) {
            renderProgress(progress);
        }
        if (!progress || (progress.status !== 'done' && progress.status !== 'failed')) {
            startPolling(importId);
        } else {
            stopPolling();
        }
    }

    async function pollProgress(id) {
        if (!progressUrlTpl) return;
        try {
            const res = await fetch(progressUrlTpl.replace('__ID__', encodeURIComponent(id)), {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            if (res.status === 404) {
                // El progreso expiró del cache: volvemos al formulario.
                stopPolling();
                activeImportId = null;
                showImportForm();
                return;
            }
            const p = await res.json();
            renderProgress(p);
            if (p.status === 'done' || p.status === 'failed') {
                stopPolling();
                return;
            }
        } catch {
            // Error transitorio de red: reintentamos en el próximo ciclo.
        }
        pollTimer = window.setTimeout(() => pollProgress(id), 1000);
    }

    function startPolling(id) {
        stopPolling();
        activeImportId = id;
        pollTimer = window.setTimeout(() => pollProgress(id), 1000);
    }

    async function reattachActiveImport() {
        if (!activeUrl) {
            showImportForm();
            return;
        }
        try {
            const res = await fetch(activeUrl, {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            const data = await res.json();
            if (data && renderImportQueue(data.imports)) {
                showImportQueuePane();
                return;
            }
            if (data && data.importId && data.progress) {
                openImportProgress(data.importId, data.progress);
                return;
            }
        } catch {
            // Sin importación activa o error: mostramos el formulario.
        }
        activeImportId = null;
        importUpload?.reset();
        resetImportUi();
        showImportForm();
    }

    async function dismissActiveImport() {
        if (!dismissUrl) return;
        try {
            await fetch(dismissUrl, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({ importId: activeImportId }),
            });
        } catch {
            // Best-effort.
        }
    }

    if (openImportModalBtn) {
        openImportModalBtn.addEventListener('click', () => {
            closeOtherInventoryModals('import-modal');
            importModal.classList.add('active');
            importModal.setAttribute('aria-hidden', 'false');
            void reattachActiveImport();
        });
    }

    if (closeImportModalBtn) {
        closeImportModalBtn.addEventListener('click', () => {
            stopPolling();
            importModal.classList.remove('active');
        });
    }

    if (cancelImportBtn) {
        cancelImportBtn.addEventListener('click', () => {
            stopPolling();
            importModal.classList.remove('active');
        });
    }

    if (progressCloseBtn) {
        // Cierra la ventana pero deja la importación corriendo en segundo plano.
        progressCloseBtn.addEventListener('click', () => {
            stopPolling();
            importModal.classList.remove('active');
        });
    }

    if (importQueueCloseBtn) {
        importQueueCloseBtn.addEventListener('click', () => {
            stopPolling();
            importModal.classList.remove('active');
        });
    }

    if (importQueueNewBtn) {
        importQueueNewBtn.addEventListener('click', () => {
            stopPolling();
            activeImportId = null;
            importUpload?.reset();
            resetImportUi();
            showImportForm();
        });
    }

    if (importQueueList) {
        importQueueList.addEventListener('click', (event) => {
            const viewButton = event.target?.closest?.('.import-queue__view');
            if (!viewButton) return;

            const importId = viewButton.dataset.importId;
            if (!importId) return;

            const queueItem = viewButton.closest('.import-queue__item');
            setButtonLoading(viewButton, true, 'Abriendo…');
            stopPolling();
            activeImportId = importId;
            showImportProgressPane();
            progressTitle && (progressTitle.textContent = 'Cargando importación…');
            progressFile && (progressFile.textContent = queueItem?.querySelector('.import-queue__filename')?.textContent || '');
            progressMessage && (progressMessage.textContent = 'Consultando el estado del proceso…');
            void pollProgress(importId).finally(() => setButtonLoading(viewButton, false));
        });
    }

    if (progressDoneBtn) {
        // Estado terminal: olvida la importación activa y refresca el inventario.
        progressDoneBtn.addEventListener('click', async () => {
            stopPolling();
            await dismissActiveImport();
            activeImportId = null;
            window.location.reload();
        });
    }

    // Detect file format by extension
    function detectFileFormat(file) {
        const extension = file.name.split('.').pop().toLowerCase();
        const fileName = file.name.toLowerCase();
        
        if (extension === 'zip' || fileName.endsWith('.zip')) {
            return { format: 'zip', name: 'ZIP (catálogo completo)', icon: 'fa-file-archive', color: '#059669' };
        } else if (extension === 'xml' || fileName.endsWith('.xml')) {
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
    const importSummary = qs('#import-file-summary');

    function resetImportUi() {
        importSummary?.classList.add('hidden');
        if (importSummary) importSummary.innerHTML = '';
        if (confirmImportBtn) confirmImportBtn.disabled = true;
    }

    function handleImportFileSelected(file) {
        if (!file) {
            resetImportUi();
            return;
        }
        const detected = detectFileFormat(file);
        if (!detected) {
            void cf4Error('Usá un archivo ZIP, XML, CSV o JSON.', 'Formato no soportado');
            importUpload?.reset();
            resetImportUi();
            return;
        }
        if (importSummary) {
            importSummary.innerHTML = `<span class="import-summary-name">${escapeHtml(file.name)}</span><span class="import-summary-meta">${detected.name} · ${formatFileSize(file.size)}</span>`;
            importSummary.classList.remove('hidden');
        }
        if (confirmImportBtn) confirmImportBtn.disabled = false;
    }

    const importUpload = initFileUploadZone({
        inputId: 'import_file',
        metaId: 'import_file-meta',
        triggerId: 'import_file-trigger',
        onChange: (file) => handleImportFileSelected(file),
    });

    if (confirmImportBtn) {
        confirmImportBtn.addEventListener('click', () => {
            if (!fileInput.files.length) {
                void cf4Error('Por favor selecciona un archivo para importar.', 'Error');
                return;
            }
            
            const file = fileInput.files[0];
            const detected = detectFileFormat(file);
            const formatName = detected ? detected.name : 'desconocido';
            
            void cf4Confirm({
                title: '¿Importar productos?',
                html: `Se importarán los productos desde el archivo <strong>${escapeHtml(file.name)}</strong> en formato <strong>${escapeHtml(formatName)}</strong>.`,
                icon: 'info',
                confirmButtonText: 'Sí, importar',
                cancelButtonText: 'Cancelar',
            }).then(async (result) => {
                if (!result.isConfirmed) return;

                setButtonLoading(confirmImportBtn, true, 'Enviando…');

                try {
                    const formData = new FormData(importForm);
                    const response = await fetch(importForm.action, {
                        method: 'POST',
                        body: formData,
                        headers: {
                            Accept: 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });

                    let data = {};
                    try {
                        data = await response.json();
                    } catch {
                        data = {};
                    }

                    if (!response.ok) {
                        const message = jsonValidationMessage(data)
                            || data.message
                            || 'No se pudo iniciar la importación.';
                        void cf4Error(message, response.status === 422 ? 'Archivo no válido' : 'Importación fallida');
                        return;
                    }

                    // El job corre en segundo plano: mostramos la barra de progreso reanudable.
                    openImportProgress(data.importId, data.progress || null);
                } catch {
                    void cf4Error(
                        'Error de conexión al iniciar la importación. Verificá tu red e intentá de nuevo.',
                        'Error',
                    );
                } finally {
                    setButtonLoading(confirmImportBtn, false);
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
}
