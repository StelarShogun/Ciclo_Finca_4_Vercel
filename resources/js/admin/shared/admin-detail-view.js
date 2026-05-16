/**
 * Helpers for read-only “view details” modals (inventory, sales, orders, suppliers).
 */

export function escapeHtml(text) {
    return String(text ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

export function detailLoading(message = 'Cargando detalles…') {
    return `
        <div class="cf-detail-loading" role="status">
            <i class="fas fa-spinner fa-spin fa-2x" aria-hidden="true"></i>
            <p>${escapeHtml(message)}</p>
        </div>`;
}

export function detailError(message = 'No se pudieron cargar los detalles.') {
    return `<div class="alert alert-danger" role="alert"><i class="fas fa-exclamation-circle"></i> ${escapeHtml(message)}</div>`;
}

export function detailView(innerHtml) {
    return `<div class="cf-detail-view">${innerHtml}</div>`;
}

export function detailSection(title, iconClass, bodyHtml) {
    const icon = iconClass ? `<i class="fas ${iconClass}" aria-hidden="true"></i>` : '';
    return `
        <section class="cf-detail-section">
            <h4 class="cf-detail-section__title">${icon}<span>${escapeHtml(title)}</span></h4>
            <div class="cf-detail-section__body">${bodyHtml}</div>
        </section>`;
}

export function detailItem(label, valueHtml, iconClass = '') {
    const icon = iconClass ? `<i class="fas ${iconClass} icon" aria-hidden="true"></i>` : '';
    return `
        <div class="cf-detail-item">
            <span class="cf-detail-item__label">${icon}${escapeHtml(label)}</span>
            <div class="cf-detail-item__value">${valueHtml}</div>
        </div>`;
}

export function detailGrid(itemsHtml) {
    return `<div class="cf-detail-grid">${itemsHtml}</div>`;
}
