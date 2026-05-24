import { escapeHtml } from './escape-html.js';

/** Whether a product/API row should show the category icon placeholder instead of an image. */
export function productUsesPlaceholderImage(item) {
    if (item?.uses_placeholder_image != null) {
        return Boolean(item.uses_placeholder_image);
    }
    if (item?.media_main) {
        return false;
    }
    if (Array.isArray(item?.media_gallery) && item.media_gallery.length > 0) {
        return false;
    }
    if (item?.image_url) {
        return false;
    }
    const legacy = item?.image || 'default.png';

    return legacy === '' || legacy === 'default.png';
}

/** Build placeholder markup for client/admin compact surfaces. */
export function buildProductMediaPlaceholderHtml(iconClass, alt, variant = 'card') {
    const safeIcon = escapeHtml(iconClass || 'fas fa-box');
    const safeAlt = escapeHtml(alt || 'Producto');
    const labelHtml = variant === 'detail'
        ? '<span class="product-media-placeholder__label">Sin imagen</span>'
        : '';

    return `<div class="product-media-placeholder product-media-placeholder--${escapeHtml(variant)}" role="img" aria-label="Sin imagen: ${safeAlt}">` +
        `<i class="${safeIcon}" aria-hidden="true"></i>${labelHtml}</div>`;
}
