import { escapeHtml, fireSwal } from '../shared/swal.js';

export async function initStockModal() {
    // Swal is lazy-loaded inside fireSwal() on first dialog.
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
        return fireSwal({
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
    let currentStock = 0;

    // ── Bootstrap ─────────────────────────────────────────────────────────
    // This module is lazy-loaded after DOMContentLoaded has already fired,
    // so the DOM is available immediately — no event listener needed.
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

    qtyInput?.addEventListener('input', updateStockPreview);
    reasonInput?.addEventListener('input', updateReasonCharCount);

    function updateReasonCharCount() {
        const reasonCountEl = document.getElementById('stock-modal-reason-count');
        if (!reasonCountEl || !reasonInput) return;
        const len = (reasonInput.value || '').length;
        reasonCountEl.textContent = `${len} / 500`;
    }

    function updateStockPreview() {
        const previewEl = document.getElementById('stock-modal-preview');
        if (!previewEl || !qtyInput) return;

        const qty = parseInt(qtyInput.value, 10);
        if (!qtyInput.value || Number.isNaN(qty) || qty < 1) {
            previewEl.hidden = true;
            previewEl.textContent = '';
            previewEl.className = 'stock-form-hint';
            return;
        }

        const result = currentAction === 'add'
            ? currentStock + qty
            : Math.max(0, currentStock - qty);

        previewEl.hidden = false;
        previewEl.className = `stock-form-hint stock-form-hint--${currentAction === 'add' ? 'add' : 'remove'}`;
        previewEl.textContent = `Stock resultante: ${result} unidades`;
    }

    // ── Open modal ────────────────────────────────────────────────────────
    function openModal(action, triggerEl) {
        currentAction    = action;
        currentProductId = triggerEl.dataset.productId;

        const name  = triggerEl.dataset.productName  || 'Producto';
        const stockRaw = triggerEl.dataset.productStock;
        const stockNum = stockRaw !== undefined && stockRaw !== '' ? parseInt(stockRaw, 10) : NaN;
        currentStock = Number.isNaN(stockNum) ? 0 : stockNum;
        const stockLabel = stockRaw !== undefined && stockRaw !== '' ? String(stockRaw) : '—';

        const modalBox = document.getElementById('stock-modal-box');
        if (modalBox) {
            modalBox.classList.remove('is-add', 'is-remove');
            modalBox.classList.add(action === 'add' ? 'is-add' : 'is-remove');
        }

        // Populate info strip
        productIdInput.value  = currentProductId;
        productNameEl.textContent  = name;
        productStockEl.textContent = stockLabel;

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
            if (!Number.isNaN(stockNum) && stockNum > 0) {
                qtyInput.max = String(stockNum);
            } else {
                qtyInput.removeAttribute('max');
            }
        }

        if (action === 'add') {
            qtyInput.removeAttribute('max');
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
        const previewEl = document.getElementById('stock-modal-preview');
        if (previewEl) {
            previewEl.hidden = true;
            previewEl.textContent = '';
        }
        updateReasonCharCount();
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
        } else if (currentAction === 'remove' && qty > currentStock) {
            qtyInput.classList.add('is-invalid');
            qtyError.textContent = `No podés retirar más de ${currentStock} unidad(es).`;
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
        const { isConfirmed } = await fireSwal({
            ...swalBase,
            customClass: {
                ...swalBase.customClass,
                confirmButton: confirmBtnClass,
            },
            title: isAdd ? '¿Agregar stock al inventario?' : '¿Retirar stock del inventario?',
            html: isAdd
                ? `<p>Se agregarán <strong>${escapeHtml(qty)}</strong> unidad(es) a <strong>${escapeHtml(productName)}</strong>.</p>`
                : `<p>Se retirarán <strong>${escapeHtml(qty)}</strong> unidad(es) de <strong>${escapeHtml(productName)}</strong>.</p>`,
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
}
