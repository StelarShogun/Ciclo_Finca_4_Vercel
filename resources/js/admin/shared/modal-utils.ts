// @ts-nocheck
/**
 * Shared admin modal helpers (close siblings, loading overlay, backdrop/Escape).
 */

const DEFAULT_MODAL_SELECTOR = '.edit-modal, .modal-overlay';

function qsa(selector, root = document) {
    return Array.from(root.querySelectorAll(selector));
}

/**
 * Close all modals matching selector except the one with given id.
 */
export function closeOtherModals(modalSelector = DEFAULT_MODAL_SELECTOR, exceptId = null) {
    qsa(modalSelector).forEach((modal) => {
        if (exceptId && modal.id === exceptId) {
            return;
        }
        modal.classList.remove('active', 'loading');
        modal.style.pointerEvents = '';
        modal.setAttribute('aria-hidden', 'true');
    });
}

/**
 * Toggle loading state on a modal. By default only dims .modal-body so backdrop/close stay usable.
 */
export function setModalLoading(modal, isLoading, { blockShell = false } = {}) {
    if (!modal) return;
    if (isLoading) {
        modal.classList.add('loading');
        if (blockShell) {
            modal.style.pointerEvents = 'none';
        }
    } else {
        modal.classList.remove('loading');
        modal.style.pointerEvents = '';
    }
}

export const MODAL_LOADING_SPINNER_HTML = `
    <div class="loading-spinner" role="status">
        <i class="fas fa-spinner fa-spin fa-2x" aria-hidden="true"></i>
        <p>Cargando…</p>
    </div>`;

/**
 * One-shot backdrop click: closes modal when clicking the backdrop element itself.
 */
export function bindModalBackdropClose(root = document, modalSelector = DEFAULT_MODAL_SELECTOR) {
    if (root.dataset.cf4ModalBackdropBound === '1') {
        return;
    }
    root.dataset.cf4ModalBackdropBound = '1';

    root.addEventListener('click', (e) => {
        const backdrop = e.target.closest('.modal-backdrop');
        if (backdrop && e.target === backdrop) {
            backdrop.closest(modalSelector.split(',')[0].trim())?.classList.remove('active');
            const overlay = backdrop.closest('.modal-overlay');
            if (overlay && e.target === overlay) {
                overlay.classList.remove('active');
            }
            const editModal = backdrop.closest('.edit-modal');
            if (editModal) {
                editModal.classList.remove('active', 'loading');
                editModal.style.pointerEvents = '';
                editModal.setAttribute('aria-hidden', 'true');
            }
            return;
        }

        if (e.target.classList.contains('modal-overlay') && e.target === e.currentTarget) {
            e.target.classList.remove('active');
            e.target.setAttribute('aria-hidden', 'true');
        }
    });
}

/**
 * Returns the topmost visible admin modal (highest in DOM among active).
 */
export function getTopmostActiveModal(modalSelector = DEFAULT_MODAL_SELECTOR) {
    const active = qsa(modalSelector).filter((el) => el.classList.contains('active'));
    return active.length ? active[active.length - 1] : null;
}

/**
 * Escape closes only the topmost active modal.
 */
export function bindModalEscapeClose(modalSelector = DEFAULT_MODAL_SELECTOR) {
    if (document.documentElement.dataset.cf4ModalEscapeBound === '1') {
        return;
    }
    document.documentElement.dataset.cf4ModalEscapeBound = '1';

    document.addEventListener('keydown', (e) => {
        if (e.key !== 'Escape') return;
        const top = getTopmostActiveModal(modalSelector);
        if (!top) return;
        top.classList.remove('active', 'loading');
        top.style.pointerEvents = '';
        top.setAttribute('aria-hidden', 'true');
    });
}

/**
 * Open modal, show spinner in body, run async loader, render HTML or error inline.
 */
export async function openModalWithSpinner({
    modal,
    bodyEl,
    exceptId,
    modalSelector = DEFAULT_MODAL_SELECTOR,
    loadFn,
    onErrorHtml = '<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> Error de conexión.</div>',
}) {
    if (!modal || !bodyEl) return null;

    closeOtherModals(modalSelector, exceptId ?? modal.id);
    bodyEl.innerHTML = MODAL_LOADING_SPINNER_HTML;
    modal.classList.add('active');
    modal.setAttribute('aria-hidden', 'false');
    setModalLoading(modal, true);

    try {
        const html = await loadFn();
        setModalLoading(modal, false);
        if (html != null) {
            bodyEl.innerHTML = html;
        }
        return html;
    } catch {
        setModalLoading(modal, false);
        bodyEl.innerHTML = onErrorHtml;
        return null;
    }
}
