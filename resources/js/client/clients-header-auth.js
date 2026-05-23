import { fireSwal } from './swal.js';
import { getCsrfToken } from './cart-shared.js';
import { setHeaderMenuOpen } from './header-menu.js';

/** Positions the account dropdown on narrow viewports (fixed + --cf4-user-dropdown-top). */
function syncMobileUserDropdownPosition() {
    var header = document.querySelector('.cliente-header');
    var trigger = document.getElementById('user-menu-trigger');
    if (!header || !trigger) return;
    if (window.innerWidth > 900) {
        header.style.removeProperty('--cf4-user-dropdown-top');
        return;
    }
    var wrap = document.getElementById('user-menu');
    if (!wrap || !wrap.classList.contains('open')) {
        header.style.removeProperty('--cf4-user-dropdown-top');
        return;
    }
    var rect = trigger.getBoundingClientRect();
    var gap = 8;
    header.style.setProperty('--cf4-user-dropdown-top', Math.round(rect.bottom + gap) + 'px');
}

/** Sets the user menu open/closed state and updates ARIA attributes. */
function setUserMenuOpen(open) {
    var wrap    = document.getElementById('user-menu');
    var panel   = document.getElementById('user-dropdown');
    var trigger = document.getElementById('user-menu-trigger');
    if (!wrap) return;
    wrap.classList.toggle('open', open);
    if (panel)   panel.setAttribute('aria-hidden', String(!open));
    if (trigger) trigger.setAttribute('aria-expanded', String(open));
    syncMobileUserDropdownPosition();
}

function closeUserDropdown() {
    setUserMenuOpen(false);
}

function toggleUserDropdown() {
    var wrap = document.getElementById('user-menu');
    var isOpen = wrap ? wrap.classList.contains('open') : false;
    setUserMenuOpen(!isOpen);
}

window.cf4CloseUserDropdown = closeUserDropdown;
window.cf4ToggleUserDropdown = toggleUserDropdown;
window.cf4SyncMobileUserDropdownPosition = syncMobileUserDropdownPosition;

function setFavoritesDrawerOpen(open) {
    var drawer = document.getElementById('favorites-drawer');
    var overlay = document.getElementById('favorites-overlay');
    if (!drawer || !overlay) return;
    drawer.classList.toggle('is-open', open);
    drawer.setAttribute('aria-hidden', String(!open));
    overlay.hidden = !open;
}

var favoritesCache = [];
var favoritesPagination = {
    current_page: 1,
    last_page: 1,
    per_page: 10,
    total: 0,
    from: null,
    to: null
};

function getInitialFavoritesFromMeta() {
    var meta = document.querySelector('meta[name="cf4-favorites-initial"]');
    if (!meta) return [];
    try {
        var parsed = JSON.parse(meta.getAttribute('content') || '[]');
        return Array.isArray(parsed) ? parsed : [];
    } catch (e) {
        return [];
    }
}

function syncFavoriteButtonsState(productId, isFavorite) {
    document.querySelectorAll('[data-product-favorite-btn][data-product-id="' + productId + '"]').forEach(function (btn) {
        btn.classList.toggle('is-active', !!isFavorite);
        btn.setAttribute('aria-pressed', isFavorite ? 'true' : 'false');
        btn.setAttribute('aria-label', isFavorite ? 'Quitar de favoritos' : 'Agregar a favoritos');
        var icon = btn.querySelector('i');
        if (icon) {
            icon.classList.toggle('fas', !!isFavorite);
            icon.classList.toggle('far', !isFavorite);
            icon.classList.add('fa-heart');
        }
    });
}

function upsertFavoriteCacheItem(productId) {
    var pid = String(productId);
    var cardBtn = document.querySelector('[data-product-favorite-btn][data-product-id="' + pid + '"]');
    var card = cardBtn ? cardBtn.closest('.product-card') : null;
    if (!card) return;

    var nameLink = card.querySelector('.product-name a');
    var categoryEl = card.querySelector('.product-category');
    var priceEl = card.querySelector('.product-price-value');
    var imageEl = card.querySelector('.product-image img');

    var existingIndex = favoritesCache.findIndex(function (item) {
        return String(item.product_id) === pid;
    });

    var entry = {
        product_id: Number(pid),
        name: nameLink ? nameLink.textContent.trim() : '',
        category: categoryEl ? categoryEl.textContent.trim() : 'Sin categoría',
        price_formatted: priceEl ? priceEl.textContent.trim() : '',
        url: nameLink ? nameLink.getAttribute('href') : '#',
        image_url: imageEl ? imageEl.getAttribute('src') : ''
    };

    if (existingIndex >= 0) {
        favoritesCache[existingIndex] = entry;
    } else {
        favoritesCache.unshift(entry);
    }
}

function renderFavoritesPaginationFooter(meta) {
    var footer = document.getElementById('favorites-drawer-pagination');
    var info = document.getElementById('favorites-pagination-info');
    var prevBtn = document.getElementById('favorites-page-prev');
    var nextBtn = document.getElementById('favorites-page-next');
    if (!footer || !info || !prevBtn || !nextBtn) return;

    var lastPage = Math.max(1, parseInt(String(meta && meta.last_page ? meta.last_page : 1), 10) || 1);
    var currentPage = Math.max(1, parseInt(String(meta && meta.current_page ? meta.current_page : 1), 10) || 1);
    var total = parseInt(String(meta && meta.total ? meta.total : 0), 10) || 0;
    var from = meta && meta.from != null ? meta.from : null;
    var to = meta && meta.to != null ? meta.to : null;

    favoritesPagination = {
        current_page: currentPage,
        last_page: lastPage,
        per_page: parseInt(String(meta && meta.per_page ? meta.per_page : 10), 10) || 10,
        total: total,
        from: from,
        to: to
    };

    if (total === 0 || lastPage <= 1) {
        footer.hidden = true;
        info.textContent = '';
        prevBtn.disabled = true;
        nextBtn.disabled = true;
        return;
    }

    footer.hidden = false;
    info.textContent = 'Mostrando ' + from + '–' + to + ' de ' + total + ' favoritos';
    prevBtn.disabled = currentPage <= 1;
    nextBtn.disabled = currentPage >= lastPage;
}

function renderFavoritesDrawerItems(items) {
    var body = document.getElementById('favorites-drawer-body');
    if (!body) return;

    if (!Array.isArray(items) || items.length === 0) {
        body.innerHTML = ''
            + '<div class="cf4-favorites-empty">'
            + '<i class="far fa-heart"></i>'
            + '<p>Aún no tienes productos guardados.<br>¡Explora el catálogo!</p>'
            + '</div>';
        return;
    }

    body.innerHTML = items.map(function (item) {
        return ''
            + '<article class="cf4-favorite-item" data-favorite-product-id="' + item.product_id + '">'
            + '  <img src="' + item.image_url + '" alt="' + item.name + '">'
            + '  <div class="cf4-favorite-meta">'
            + '    <div class="cf4-favorite-category">' + item.category + '</div>'
            + '    <a class="cf4-favorite-name" href="' + item.url + '">' + item.name + '</a>'
            + '    <div class="cf4-favorite-price">' + item.price_formatted + '</div>'
            + '  </div>'
            + '  <button type="button" class="cf4-favorite-remove" data-favorite-remove-btn data-product-id="' + item.product_id + '" aria-label="Quitar de favoritos">'
            + '    <i class="fas fa-heart"></i>'
            + '  </button>'
            + '</article>';
    }).join('');
}

function buildFavoritesIndexUrl(page) {
    var indexMeta = document.querySelector('meta[name="cf4-favorites-index-url"]');
    if (!indexMeta) return null;

    var url = new URL(indexMeta.getAttribute('content'), window.location.origin);
    var targetPage = Math.max(1, parseInt(String(page || favoritesPagination.current_page || 1), 10) || 1);
    url.searchParams.set('page', String(targetPage));
    url.searchParams.set('per_page', String(favoritesPagination.per_page || 10));

    return url.toString();
}

function loadFavoritesDrawerItems(page) {
    var body = document.getElementById('favorites-drawer-body');
    var indexMeta = document.querySelector('meta[name="cf4-favorites-index-url"]');
    if (!body) return Promise.resolve();

    body.innerHTML = '<p class="cf4-favorites-loading">Cargando favoritos...</p>';

    if (!indexMeta) {
        renderFavoritesDrawerItems(favoritesCache);
        renderFavoritesPaginationFooter(favoritesPagination);
        return Promise.resolve();
    }

    var requestUrl = buildFavoritesIndexUrl(page);
    if (!requestUrl) {
        renderFavoritesDrawerItems(favoritesCache);
        renderFavoritesPaginationFooter(favoritesPagination);
        return Promise.resolve();
    }

    return fetch(requestUrl, {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
        .then(function (res) { return res.json(); })
        .then(function (data) {
            if (!data || data.success !== true) {
                throw new Error((data && data.message) ? data.message : 'No se pudieron cargar favoritos');
            }
            favoritesCache = Array.isArray(data.favorites) ? data.favorites : [];
            renderFavoritesDrawerItems(favoritesCache);
            renderFavoritesPaginationFooter(data.pagination || favoritesPagination);
        })
        .catch(function () {
            renderFavoritesDrawerItems(favoritesCache);
            renderFavoritesPaginationFooter(favoritesPagination);
        });
}

function toggleFavoriteFromDrawer(productId) {
    var toggleMeta = document.querySelector('meta[name="cf4-favorites-toggle-url"]');
    if (!toggleMeta || !productId) return Promise.resolve();

    return fetch(toggleMeta.getAttribute('content'), {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': getCsrfToken(),
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        },
        body: JSON.stringify({ product_id: productId })
    })
        .then(function (res) { return res.json(); })
        .then(function (data) {
            if (!data || data.success !== true) {
                throw new Error((data && data.message) ? data.message : 'No se pudo actualizar favorito');
            }
            var removed = data.is_favorite === false;
            if (removed) {
                favoritesCache = favoritesCache.filter(function (item) {
                    return String(item.product_id) !== String(productId);
                });
            }
            syncFavoriteButtonsState(String(productId), !removed);
            var reloadPage = favoritesPagination.current_page || 1;
            if (removed && favoritesCache.length === 0 && reloadPage > 1) {
                reloadPage -= 1;
            }
            return loadFavoritesDrawerItems(reloadPage);
        })
        .catch(function (err) {
            fireSwal({
                icon: 'error',
                title: 'Error',
                text: err.message || 'No se pudo actualizar favorito.',
            });
        });
}

export function initClientHeaderAuth() {
    if (window.__cf4HeaderAuthBound) {
        return;
    }
    window.__cf4HeaderAuthBound = true;

    // — User menu toggle —
    var userMenuTrigger = document.getElementById('user-menu-trigger');
    if (userMenuTrigger) {
        userMenuTrigger.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            toggleUserDropdown();
        });

    }

    // — Close user dropdown on outside click —
    document.addEventListener('click', function (e) {
        var userMenu = document.getElementById('user-menu');
        if (!userMenu || !userMenu.classList.contains('open')) return;
        if (userMenu.contains(e.target)) return;
        closeUserDropdown();
    });

    var favoritesCloseBtn = document.getElementById('favorites-close-btn');
    var favoritesOverlay = document.getElementById('favorites-overlay');
    var favoritesDrawer = document.getElementById('favorites-drawer');

    if (favoritesDrawer && favoritesOverlay) {
        document.querySelectorAll('.cf4-favorites-open-trigger').forEach(function (favBtn) {
            favBtn.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                setHeaderMenuOpen(false);
                closeUserDropdown();
                setFavoritesDrawerOpen(true);
                loadFavoritesDrawerItems();
            });
        });
    }

    if (favoritesCloseBtn) {
        favoritesCloseBtn.addEventListener('click', function () {
            setFavoritesDrawerOpen(false);
        });
    }

    if (favoritesOverlay) {
        favoritesOverlay.addEventListener('click', function () {
            setFavoritesDrawerOpen(false);
        });
    }

    var favoritesPagePrev = document.getElementById('favorites-page-prev');
    var favoritesPageNext = document.getElementById('favorites-page-next');
    if (favoritesPagePrev) {
        favoritesPagePrev.addEventListener('click', function () {
            var page = (favoritesPagination.current_page || 1) - 1;
            if (page < 1) return;
            loadFavoritesDrawerItems(page);
        });
    }
    if (favoritesPageNext) {
        favoritesPageNext.addEventListener('click', function () {
            var page = (favoritesPagination.current_page || 1) + 1;
            if (page > (favoritesPagination.last_page || 1)) return;
            loadFavoritesDrawerItems(page);
        });
    }

    window.addEventListener('cf4:favorites:changed', function (event) {
        var detail = event && event.detail ? event.detail : {};
        var pid = String(detail.product_id || '');
        var isFav = !!detail.is_favorite;
        if (!pid) return;

        if (isFav) {
            upsertFavoriteCacheItem(pid);
        } else {
            favoritesCache = favoritesCache.filter(function (item) {
                return String(item.product_id) !== pid;
            });
        }

        syncFavoriteButtonsState(pid, isFav);

        if (favoritesDrawer && favoritesDrawer.classList.contains('is-open')) {
            loadFavoritesDrawerItems(favoritesPagination.current_page || 1);
        }
    });

}
