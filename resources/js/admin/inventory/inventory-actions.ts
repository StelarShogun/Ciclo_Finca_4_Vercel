// @ts-nocheck
import { qs, qsa, smartFetch, jsonHeaders, syncFeaturedStarButtons, showSubtleNotification, setActionButtonLoading, showSuccessFeedback, showErrorFeedback } from './inventory-shared';
import { cf4Confirm, cf4Toast, cf4Error } from '../shared/swal';

const STATUS_LABELS = {
    active: 'Activo',
    inactive: 'Inactivo',
    out_of_stock: 'Agotado',
    discontinued: 'Descontinuado',
};

const STATUS_BADGE_CLASS = {
    active: 'success',
    inactive: 'warning',
    out_of_stock: 'secondary',
    discontinued: 'secondary',
};

export async function initInventoryActions() {
    const root = qs('.products-section');
    if (!root) {
        return;
    }

    root.addEventListener('click', (e) => {
        const featuredBtn = e.target.closest('.featured-star-btn');
        if (featuredBtn && root.contains(featuredBtn)) {
            e.preventDefault();
            e.stopPropagation();
            handleFeaturedToggle(featuredBtn);
            return;
        }

        const statusBtn = e.target.closest('[data-action="deactivate"], [data-action="activate"]');
        if (statusBtn && root.contains(statusBtn)) {
            e.preventDefault();
            e.stopPropagation();
            const action = statusBtn.dataset.action;
            if (action === 'deactivate') {
                void handleDeactivate(statusBtn);
            } else if (action === 'activate') {
                void handleActivate(statusBtn);
            }
        }
    });
}

function handleFeaturedToggle(btn) {
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
            } else {
                void cf4Error(data.message || 'No se pudo actualizar el destacado.', 'Error');
            }
        })
        .catch(() => {
            btn.removeAttribute('aria-busy');
            btn.classList.remove('featured-star-btn--busy');
            void cf4Error('No se pudo actualizar el destacado.', 'Error');
        });
}

function updateStatusBadge(container, status) {
    const badge = container.querySelector('.status-badge');
    if (!badge) {
        return;
    }
    const label = STATUS_LABELS[status] || status;
    const tone = STATUS_BADGE_CLASS[status] || 'secondary';
    badge.className = `status-badge ${tone}`;
    badge.textContent = label;
}

function buildStatusActionButton(productId, productName, status) {
    const isInactive = status === 'inactive';
    const btn = document.createElement('button');
    btn.type = 'button';
    btn.className = isInactive ? 'action-btn activate' : 'action-btn delete';
    btn.dataset.action = isInactive ? 'activate' : 'deactivate';
    btn.dataset.productId = String(productId);
    btn.dataset.productName = productName;
    btn.title = isInactive ? 'Reactivar producto' : 'Desactivar producto';
    const icon = document.createElement('i');
    icon.className = isInactive ? 'fas fa-check-circle' : 'fas fa-ban';
    btn.appendChild(icon);
    return btn;
}

function replaceStatusActionButton(oldBtn, productId, productName, status) {
    const parent = oldBtn.parentElement;
    if (!parent) {
        return;
    }
    const next = buildStatusActionButton(productId, productName, status);
    parent.replaceChild(next, oldBtn);
}

function syncProductStatusInDom(productId, productName, status) {
    const id = String(productId);
    qsa(`tbody tr`).forEach((tr) => {
        if (!tr.querySelector(`[data-product-id="${id}"]`)) {
            return;
        }
        updateStatusBadge(tr, status);
        const btn = tr.querySelector('[data-action="deactivate"], [data-action="activate"]');
        if (btn) {
            replaceStatusActionButton(btn, productId, productName, status);
        }
    });
    qsa('.product-card').forEach((card) => {
        if (!card.querySelector(`[data-product-id="${id}"]`)) {
            return;
        }
        updateStatusBadge(card, status);
        const btn = card.querySelector('[data-action="deactivate"], [data-action="activate"]');
        if (btn) {
            replaceStatusActionButton(btn, productId, productName, status);
        }
    });
}

async function handleDeactivate(button) {
    const productId = button.dataset.productId;
    const productName = button.dataset.productName;

    const result = await cf4Confirm({
        title: `¿Desactivar el producto "${productName}"?`,
        text: 'El producto existirá en la base de datos, pero no contará para el stock del inventario.',
        icon: 'warning',
        confirmButtonText: 'Sí, desactivar',
        cancelButtonText: 'Cancelar',
        danger: true,
    });

    if (!result.isConfirmed) {
        return;
    }

    setActionButtonLoading(button, true, 'Desactivando...');

    try {
        const response = await smartFetch(`/products/${productId}`, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
            },
        });

        const data = await response.json().catch(() => ({}));
        setActionButtonLoading(button, false);

        if (response.ok && data.success) {
            showSuccessFeedback(button, '¡Desactivado!');
            syncProductStatusInDom(productId, productName, 'inactive');
            void cf4Toast({
                icon: 'success',
                title: '¡Desactivado!',
                text: data.already_inactive
                    ? 'El producto ya estaba inactivo.'
                    : 'El producto ha sido desactivado correctamente.',
                timer: 2500,
            });
        } else {
            showErrorFeedback(button, 'Error');
            void cf4Error(data.message || 'Hubo un problema al desactivar el producto.', 'Error');
        }
    } catch (error) {
        setActionButtonLoading(button, false);
        showErrorFeedback(button, 'Error');
        console.error('Error:', error);
        void cf4Error('Hubo un problema de conexión o el servidor no respondió correctamente.', 'Error');
    }
}

async function handleActivate(button) {
    const productId = button.dataset.productId;
    const productName = button.dataset.productName;

    const result = await cf4Confirm({
        title: `¿Reactivar el producto "${productName}"?`,
        text: 'El producto volverá a estar activo en el inventario y catálogo.',
        icon: 'question',
        confirmButtonText: 'Sí, reactivar',
        cancelButtonText: 'Cancelar',
    });

    if (!result.isConfirmed) {
        return;
    }

    setActionButtonLoading(button, true, 'Reactivando...');

    try {
        const response = await smartFetch(`/products/${productId}/activate`, {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                ...jsonHeaders(),
            },
        });

        const data = await response.json().catch(() => ({}));
        setActionButtonLoading(button, false);

        if (response.ok && data.success) {
            showSuccessFeedback(button, '¡Activo!');
            syncProductStatusInDom(productId, productName, 'active');
            void cf4Toast({
                icon: 'success',
                title: '¡Reactivado!',
                text: data.already_active
                    ? 'El producto ya estaba activo.'
                    : 'El producto ha sido reactivado correctamente.',
                timer: 2500,
            });
        } else {
            showErrorFeedback(button, 'Error');
            void cf4Error(data.message || 'Hubo un problema al reactivar el producto.', 'Error');
        }
    } catch (error) {
        setActionButtonLoading(button, false);
        showErrorFeedback(button, 'Error');
        console.error('Error:', error);
        void cf4Error('Hubo un problema de conexión o el servidor no respondió correctamente.', 'Error');
    }
}
