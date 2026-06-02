/**
 * Favoritos en tarjetas de catálogo / home / producto (botón [data-product-favorite-btn]).
 * Vive en módulo propio y se inicializa desde clients-users.js (header en todas las páginas)
 * para que no dependa de que clients-page.js cargue o falle en otras ramas.
 */
import { cf4Error } from './swal.js';

function showToast(payload) {
    if (typeof window !== 'undefined' && typeof window.cf4ShowToast === 'function') {
        window.cf4ShowToast(payload);
        return true;
    }
    return false;
}

function getCsrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    if (meta) return meta.content;
    const input = document.querySelector('input[name="_token"]');
    return input ? input.value : '';
}

function setFavoriteButtonState(btn, isFavorite) {
    if (!btn) return;
    btn.classList.toggle('is-active', !!isFavorite);
    btn.setAttribute('aria-pressed', isFavorite ? 'true' : 'false');
    btn.setAttribute('aria-label', isFavorite ? 'Quitar de favoritos' : 'Agregar a favoritos');
    var icon = btn.querySelector('i');
    if (icon) {
        icon.classList.toggle('fas', !!isFavorite);
        icon.classList.toggle('far', !isFavorite);
        icon.classList.add('fa-heart');
    }
    var detailLabel = btn.querySelector('.product-detail-favorite__label');
    if (detailLabel) {
        detailLabel.textContent = isFavorite ? 'En favoritos' : 'Agregar a favoritos';
    }
}

function notifyFavoriteChange(productId, isFavorite) {
    var payload = {
        product_id: String(productId || ''),
        is_favorite: !!isFavorite
    };
    window.dispatchEvent(new CustomEvent('cf4:favorites:changed', { detail: payload }));
}

export function toggleFavoriteProduct(btn) {
    var cfg = window.catalogFavoriteConfig || {};
    var productId = btn ? btn.getAttribute('data-product-id') : null;
    if (!productId) return;

    if (!cfg.toggleUrl) {
        window.location.href = cfg.loginUrl || '/login';
        return;
    }

    btn.disabled = true;

    fetch(cfg.toggleUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': getCsrfToken(),
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        },
        body: JSON.stringify({ product_id: productId })
    })
        .then(function (res) {
            return res.text().then(function (raw) {
                var payload = {};
                try {
                    payload = raw ? JSON.parse(raw) : {};
                } catch (e) {
                    payload = { success: false, message: raw || 'Respuesta inválida del servidor.' };
                }
                if (!res.ok) {
                    payload.success = false;
                }
                return payload;
            });
        })
        .then(function (data) {
            if (!data || data.success !== true) {
                throw new Error((data && data.message) ? data.message : 'No se pudo actualizar favorito');
            }
            var isFavorite = !!data.is_favorite;
            setFavoriteButtonState(btn, isFavorite);
            notifyFavoriteChange(productId, isFavorite);
        })
        .catch(function (err) {
            console.error('Error toggling favorite:', err);
            const msg = err && err.message ? err.message : 'No se pudo actualizar tu favorito.';
            if (!showToast({ variant: 'error', title: 'Error', message: msg })) {
                void cf4Error(msg, 'Error');
            }
        })
        .finally(function () {
            btn.disabled = false;
        });
}

var __cf4FavoriteDelegationBound = false;

/** Un solo listener global para corazones del catálogo (y otras vistas con el mismo data-attribute). */
export function initCatalogFavoriteClickDelegation() {
    if (__cf4FavoriteDelegationBound) return;
    __cf4FavoriteDelegationBound = true;

    document.addEventListener('click', function (e) {
        var favoriteBtn = e.target.closest('[data-product-favorite-btn]');
        if (!favoriteBtn) return;
        e.preventDefault();
        e.stopPropagation();
        toggleFavoriteProduct(favoriteBtn);
    });
}
