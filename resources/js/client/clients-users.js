import {
    buildCf4CheckoutSuccessText,
    getCf4PaymentMethodShortLabel,
} from './checkout-copy.js';
import './auth-welcome-toast.js';
import { initHeaderCatalogSearch } from './header-catalog-search.js';

// ============================================================
// GLOBAL UTILITIES
// ============================================================

/** Returns the CSRF token from the meta tag or a hidden form input. */
function getCsrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    if (meta) return meta.content;
    const input = document.querySelector('input[name="_token"]');
    return input ? input.value : '';
}

function isClientStockShortMessage(msg) {
    return msg === 'Producto agotado' || msg === 'Stock insuficiente';
}

function getCheckoutPaymentMethodFallback() {
    var selected = document.querySelector('input[name="checkout_payment_method"]:checked');
    return selected ? selected.value : 'cash';
}

// ============================================================
// CART COUNTER (navbar)
// ============================================================

function updateCartCount(count) {
    const cartCountEl = document.getElementById('cart-count');
    const cartLinkEl  = document.getElementById('cart-link');

    if (cartCountEl) {
        cartCountEl.textContent = count;
        cartCountEl.style.display = count > 0 ? 'flex' : 'none';
    }
    if (cartLinkEl) {
        cartLinkEl.setAttribute('data-cart-count', count);
    }
}

// ============================================================
// INVOICE BADGE (navbar) — keeps pending count in sync
// ============================================================

/** Update navbar invoice badge count. */
function updateInvoiceCount(count) {
    const invoiceLink = document.getElementById('invoices-link');
    if (!invoiceLink) return;

    let badge = document.getElementById('invoice-count');

    if (count > 0) {
        if (!badge) {
            badge = document.createElement('span');
            badge.id = 'invoice-count';
            badge.className = 'cf4-invoice-count';
            invoiceLink.appendChild(badge);
        }
        badge.textContent = count;
        badge.style.display = 'flex';
    } else if (badge) {
        badge.style.display = 'none';
    }
}

// Start polling the invoice heartbeat endpoint to keep the badge live on all pages.
(function startInvoiceHeartbeat() {
    const metaUrl   = document.querySelector('meta[name="cf4-invoice-heartbeat-url"]');
    const metaCount = document.querySelector('meta[name="cf4-invoice-initial-count"]');
    if (!metaUrl) return; // not authenticated

    let lastCount = parseInt(metaCount ? metaCount.getAttribute('content') : '0', 10);

    // Seed the badge immediately from the server-rendered count.
    updateInvoiceCount(lastCount);

    const url = metaUrl.getAttribute('content');

    setInterval(async function () {
        try {
            const res = await fetch(url, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            });
            if (!res.ok) return;
            const data = await res.json();
            if (data.count !== lastCount) {
                lastCount = data.count;
                updateInvoiceCount(lastCount);
                // If the invoices page is open, also reload its content.
                if (window.location.pathname.startsWith('/invoices')) {
                    location.reload();
                }
            }
        } catch (_) {}
    }, 15000);
})();

// ============================================================
// ADD TO CART
// ============================================================

function addToCart(productId, quantity, triggerBtn) {
    quantity = quantity || 1;

    fetch('/cart/add', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': getCsrfToken()
        },
        body: JSON.stringify({ product_id: productId, quantity: quantity })
    })
        .then(function (res) { return res.json(); })
        .then(function (data) {
            if (data.success) {
                updateCartCount(data.cart_count);
                Swal.fire({
                    icon: 'success',
                    title: '¡Agregado!',
                    text: data.message || 'Producto agregado al carrito',
                    timer: 2000,
                    showConfirmButton: false,
                    toast: true,
                    position: 'top-end'
                });
            } else {
                var msg = data.message || 'No se pudo agregar el producto al carrito';
                var stockShort = isClientStockShortMessage(msg);
                Swal.fire({
                    icon: stockShort ? 'warning' : 'error',
                    title: stockShort ? msg : 'Error',
                    text: stockShort ? '' : msg
                });
            }
        })
        .catch(function (err) {
            console.error('Error adding to cart:', err);
            Swal.fire({ icon: 'error', title: 'Error', text: 'Ocurrió un error al agregar el producto al carrito' });
        });
}

// ============================================================
// MODAL HELPERS
// ============================================================

function openModal(id) {
    var modal = document.getElementById(id);
    if (modal) modal.classList.add('active');
}

function closeModal(id) {
    var modal = document.getElementById(id);
    if (modal) modal.classList.remove('active');
}

// ============================================================
// TOGGLE PASSWORD VISIBILITY
// ============================================================

/** Toggles a password field between text and password types (by IDs). */
function togglePass(inputId, iconId) {
    var input = document.getElementById(inputId);
    var icon  = document.getElementById(iconId);
    if (!input) return;

    if (input.type === 'password') {
        input.type = 'text';
        if (icon) { icon.classList.remove('fa-eye'); icon.classList.add('fa-eye-slash'); }
    } else {
        input.type = 'password';
        if (icon) { icon.classList.remove('fa-eye-slash'); icon.classList.add('fa-eye'); }
    }
}

/** Toggles a password field using a button element reference. */
function togglePassword(inputId, btn) {
    var input = document.getElementById(inputId);
    var icon  = btn ? btn.querySelector('i') : null;
    if (!input) return;
    if (input.type === 'password') {
        input.type = 'text';
        if (icon) icon.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
        input.type = 'password';
        if (icon) icon.classList.replace('fa-eye-slash', 'fa-eye');
    }
}

// ============================================================
// FIELD MESSAGE UTILITIES (register & recovery forms)
// ============================================================

/** Shows a validation message below an input field. */
function showMsg(msgId, type, text) {
    var el = document.getElementById(msgId);
    if (!el) return;
    el.className = 'field-msg ' + type;
    el.innerHTML = (type === 'error')
        ? '<i class="fas fa-exclamation-circle"></i><span>' + text + '</span>'
        : '<i class="fas fa-check-circle"></i><span>' + text + '</span>';
}

/** Clears a field-level message. */
function clearMsg(msgId) {
    var el = document.getElementById(msgId);
    if (el) { el.className = 'field-msg'; el.innerHTML = ''; }
}

/** Adds or removes input-error / input-ok CSS class from an input. */
function setInputState(input, state) {
    if (!input) return;
    input.classList.remove('input-error', 'input-ok');
    if (state) input.classList.add(state);
}

// ============================================================
// CART PAGE (/cart)
// ============================================================

function updateCartQuantity(productId, quantity) {
    fetch('/cart/update', {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': getCsrfToken(),
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        },
        body: JSON.stringify({ product_id: productId, quantity: quantity })
    })
        .then(function (res) { return res.json().catch(function () { return {}; }); })
        .then(function (data) {
            if (data.success) {
                var totalFormatted = (data.cart_total != null)
                    ? ('₡' + Number(data.cart_total).toLocaleString('es-CR'))
                    : '₡0';

                var subtotalEl = document.getElementById('cart-subtotal');
                var totalEl    = document.getElementById('cart-total-amount');
                if (subtotalEl) subtotalEl.textContent = totalFormatted;
                if (totalEl)    totalEl.textContent    = totalFormatted;

                updateCartCount(data.cart_count || 0);

                var cartItem = document.querySelector('.cart-item[data-product-id="' + productId + '"]');
                if (cartItem) {
                    var unitPriceEl    = cartItem.querySelector('.item-price');
                    var unitPriceText  = unitPriceEl ? unitPriceEl.textContent : '';
                    var unitPrice      = parseInt(unitPriceText.replace(/[^\d]/g, ''), 10) || 0;
                    var newSubtotal    = unitPrice * quantity;
                    var lineSubtotalEl = cartItem.querySelector('.subtotal-amount');
                    if (lineSubtotalEl) {
                        lineSubtotalEl.textContent = '₡' + newSubtotal.toLocaleString('es-CR');
                    }
                }
            } else {
                var umsg = data.message || 'No se pudo actualizar el carrito';
                var uShort = isClientStockShortMessage(umsg);
                Swal.fire(uShort ? umsg : 'Error', uShort ? '' : umsg, uShort ? 'warning' : 'error');
            }
        })
        .catch(function () {
            Swal.fire('Error', 'Ocurrió un error al actualizar el carrito', 'error');
        });
}

function showCartEmptyState() {
    var card = document.querySelector('.cart-page-card');
    if (!card) return;
    var catalogLink = card.querySelector('a.btn-ghost-cart[href], a[href*="/catalog"]');
    var rawHref = (catalogLink && catalogLink.getAttribute('href')) || '/catalog';
    var catalogBase = rawHref.split('#')[0];
    var spotlightHref = catalogBase + '#catalog-spotlight-heading';
    var homeUrl = '/';
    card.innerHTML =
        '<div class="cart-toolbar">' +
        '<div class="cart-toolbar-text">' +
        '<span class="cart-toolbar-label">Resumen rápido</span>' +
        '</div>' +
        '<div class="cart-toolbar-actions">' +
        '<a href="' + catalogBase + '" class="btn btn-ghost-cart">' +
        '<i class="fas fa-bicycle" aria-hidden="true"></i> Seguir comprando</a>' +
        '</div></div>' +
        '<div class="cart-empty">' +
        '<div class="cart-empty-inner">' +
        '<div class="cart-empty-icon" aria-hidden="true"><i class="fas fa-cart-shopping"></i></div>' +
        '<h2 class="cart-empty-title">Tu carrito está vacío</h2>' +
        '<p class="cart-empty-text">Explorá el catálogo y agregá productos para armar tu solicitud.</p>' +
        '<div class="cart-empty-actions">' +
        '<a href="' + catalogBase + '" class="btn btn-primary btn-lg">' +
        '<i class="fas fa-bicycle" aria-hidden="true"></i> Ir al catálogo</a>' +
        '<a href="' + spotlightHref + '" class="btn btn-ghost-cart btn-lg">' +
        '<i class="fas fa-star" aria-hidden="true"></i> Ver destacados</a>' +
        '</div>' +
        '<p class="cart-empty-home-link">' +
        '<a href="' + homeUrl + '" class="cart-empty-home-anchor">Volver al inicio</a></p>' +
        '</div></div>';
}

// ============================================================
// USER MENU (profile dropdown)
// ============================================================

/** Positions the account dropdown on narrow viewports (fixed + --cf4-user-dropdown-top). */
function syncMobileUserDropdownPosition() {
    var header = document.querySelector('.cliente-header');
    var trigger = document.getElementById('user-menu-trigger');
    if (!header || !trigger) return;
    if (window.innerWidth > 768) {
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
    var wrap   = document.getElementById('user-menu');
    var isOpen = wrap ? wrap.classList.contains('open') : false;
    setUserMenuOpen(!isOpen);
}

window.cf4CloseUserDropdown = closeUserDropdown;
window.cf4ToggleUserDropdown = toggleUserDropdown;
window.cf4SyncMobileUserDropdownPosition = syncMobileUserDropdownPosition;

/** Sets mobile header menu open/closed state. */
function setHeaderMenuOpen(open) {
    var header = document.querySelector('.cliente-header');
    var toggle = document.getElementById('header-menu-toggle');
    var icon   = toggle ? toggle.querySelector('i') : null;
    if (!header) return;
    header.classList.toggle('menu-open', open);
    if (!open) {
        closeUserDropdown();
    }
    if (toggle) {
        toggle.setAttribute('aria-expanded', String(open));
        toggle.setAttribute('aria-label', open ? 'Cerrar menú de navegación' : 'Abrir menú de navegación');
    }
    if (icon) {
        icon.classList.toggle('fa-bars',  !open);
        icon.classList.toggle('fa-times',  open);
    }
    if (open) {
        requestAnimationFrame(function () {
            syncMobileUserDropdownPosition();
        });
    }
}

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
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: err.message || 'No se pudo actualizar favorito.'
            });
        });
}

// ============================================================
// LOGIN MODAL
// ============================================================

function closeLoginModal() {
    closeModal('login-modal');
    var overlay = document.getElementById('login-modal-overlay');
    if (overlay) overlay.classList.remove('active');
}

// ============================================================
// PROFILE PAGE
// ============================================================

var profileOriginalValues = {};
var profileEditableFields = ['name', 'first_surname', 'second_surname', 'gmail'];

/** Stores current field values to allow cancellation of edits. */
function profileSaveOriginals() {
    profileEditableFields.forEach(function (id) {
        var el = document.getElementById(id);
        if (el) profileOriginalValues[id] = el.value;
    });
}

/** Enables profile fields for editing and changes the button to save mode. */
function enableEdit() {
    profileEditableFields.forEach(function (id) {
        var el = document.getElementById(id);
        if (el) el.removeAttribute('readonly');
    });
    var btn = document.getElementById('btnEditarPerfil');
    if (btn) {
        btn.innerHTML = '<i class="fas fa-save"></i> Guardar Cambios';
        btn.className = 'btn btn-sm btn-primary';
        btn.setAttribute('onclick', 'submitProfile()');
    }
    var actions = document.getElementById('accionesEdicion');
    if (actions) actions.classList.remove('hidden');
    var nameField = document.getElementById('name');
    if (nameField) nameField.focus();
}

/** Cancels editing and restores original field values. */
function cancelEdit() {
    profileEditableFields.forEach(function (id) {
        var el = document.getElementById(id);
        if (el) {
            el.setAttribute('readonly', true);
            el.value = profileOriginalValues[id];
        }
    });
    var btn = document.getElementById('btnEditarPerfil');
    if (btn) {
        btn.innerHTML = '<i class="fas fa-pencil-alt"></i> Editar Perfil';
        btn.className = 'btn btn-sm btn-outline-primary';
        btn.setAttribute('onclick', 'enableEdit()');
    }
    var actions = document.getElementById('accionesEdicion');
    if (actions) actions.classList.add('hidden');
}

/** Shows confirmation dialog before submitting the profile form. */
function submitProfile() {
    var form = document.getElementById('formPerfil');
    if (!form) return;
    Swal.fire({
        title: '¿Guardar cambios?',
        text: 'Se actualizarán tus datos personales.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: '<i class="fas fa-save"></i> Sí, guardar',
        cancelButtonText: '<i class="fas fa-times"></i> Cancelar',
        reverseButtons: true
    }).then(function (result) {
        if (!result.isConfirmed) return;
        sendProfile(form);
    });
}

/** Sends profile data via AJAX and updates UI on success. */
function sendProfile(form) {
    fetch(form.action, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': getCsrfToken(),
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        },
        body: new FormData(form)
    })
        .then(function (r) {
            if (r.status === 422) {
                return r.json().then(function (data) {
                    var errors   = data.errors || {};
                    var firstMsg = Object.values(errors)[0];
                    showProfileAlert(
                        Array.isArray(firstMsg) ? firstMsg[0] : (firstMsg || 'Error de validación.'),
                        'danger'
                    );
                    return Promise.reject('validation');
                });
            }
            return r.json();
        })
        .then(function (res) {
            if (res.success) {
                profileEditableFields.forEach(function (id) {
                    var el = document.getElementById(id);
                    if (el) { profileOriginalValues[id] = el.value; el.setAttribute('readonly', true); }
                });

                var name  = (document.getElementById('name')           || {}).value || '';
                var fs    = (document.getElementById('first_surname')   || {}).value || '';
                var ss    = (document.getElementById('second_surname')  || {}).value || '';
                var gmail = (document.getElementById('gmail')           || {}).value || '';

                var heroName  = document.getElementById('heroName');
                var initials  = document.getElementById('avatarInitials');
                var heroEmail = document.querySelector('.profile-email');
                if (heroName)  heroName.textContent  = [name, fs, ss].filter(Boolean).join(' ');
                if (initials)  initials.textContent  = (name.charAt(0) + fs.charAt(0)).toUpperCase();
                if (heroEmail) heroEmail.textContent = gmail;

                var headerInitials  = document.querySelector('.user-avatar-bubble');
                var headerShortName = document.querySelector('.user-trigger-name');
                var headerFullName  = document.querySelector('.user-dropdown-fullname');
                var headerEmail     = document.querySelector('.user-dropdown-email');
                if (headerInitials)  headerInitials.textContent  = (name.charAt(0) + fs.charAt(0)).toUpperCase();
                if (headerShortName) headerShortName.textContent = name;
                if (headerFullName)  headerFullName.textContent  = [name, fs].filter(Boolean).join(' ');
                if (headerEmail)     headerEmail.textContent     = gmail;

                var btn = document.getElementById('btnEditarPerfil');
                if (btn) {
                    btn.innerHTML = '<i class="fas fa-pencil-alt"></i> Editar Perfil';
                    btn.className = 'btn btn-sm btn-outline-primary';
                    btn.setAttribute('onclick', 'enableEdit()');
                }
                var actions = document.getElementById('accionesEdicion');
                if (actions) actions.classList.add('hidden');

                showProfileAlert(res.message || 'Cambios guardados correctamente', 'success');
            } else {
                showProfileAlert(res.message || 'Error al guardar los cambios.', 'danger');
            }
        })
        .catch(function (err) {
            if (err === 'validation') return;
            showProfileAlert('Error de conexión. Intenta de nuevo.', 'danger');
        });
}

/** Evaluates password strength and updates the visual meter. */
function updateStrength(val) {
    var wrapper = document.getElementById('passStrength');
    var fill    = document.getElementById('strengthFill');
    var label   = document.getElementById('strengthLabel');
    if (!wrapper) return;
    if (!val) { wrapper.classList.add('hidden'); return; }
    wrapper.classList.remove('hidden');

    var score = 0;
    if (val.length >= 8)          score++;
    if (/[A-Z]/.test(val))        score++;
    if (/[0-9]/.test(val))        score++;
    if (/[^A-Za-z0-9]/.test(val)) score++;

    var levels = [
        { w: '25%',  c: '#d32f2f', t: 'Débil'  },
        { w: '50%',  c: '#f57c00', t: 'Regular' },
        { w: '75%',  c: '#fbc02d', t: 'Buena'   },
        { w: '100%', c: '#235347', t: 'Fuerte'  }
    ];
    var lvl = levels[Math.max(score - 1, 0)];
    if (fill)  { fill.style.width = lvl.w; fill.style.background = lvl.c; }
    if (label) { label.textContent = lvl.t; label.style.color = lvl.c; }
}

/** Shows the password change form (hides the Google-only CTA). */
function showPasswordForm() {
    var form = document.getElementById('formPassword');
    var cta  = document.getElementById('googlePassCta');
    if (form) form.classList.remove('hidden');
    if (cta)  cta.classList.add('hidden');
}

function hidePasswordForm() {
    var form = document.getElementById('formPassword');
    var cta  = document.getElementById('googlePassCta');
    if (form) form.classList.add('hidden');
    if (cta)  cta.classList.remove('hidden');
}

/** Displays a dismissible alert on the profile page. */
function showProfileAlert(msg, tipo) {
    var alertEl = document.getElementById('profileAlert');
    var textEl  = document.getElementById('profileAlertText');
    var iconEl  = document.getElementById('profileAlertIcon');
    if (!alertEl) return;
    textEl.textContent = msg;
    alertEl.className  = 'alert ' + (tipo === 'danger' ? 'alert-danger' : 'alert-success');
    iconEl.className   = tipo === 'danger' ? 'fas fa-exclamation-circle' : 'fas fa-check-circle';
    alertEl.classList.remove('hidden');
    alertEl.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    clearTimeout(alertEl.profileTimeout);
    alertEl.profileTimeout = setTimeout(closeProfileAlert, 5000);
}

function closeProfileAlert() {
    var alertEl = document.getElementById('profileAlert');
    if (alertEl) alertEl.classList.add('hidden');
}

/** Sends password change request; handles Google-only account transition. */
function sendPassword(form) {
    var submitBtn = form.querySelector('button[type="submit"]');
    if (submitBtn) {
        submitBtn.disabled  = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
    }

    fetch(form.action, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': getCsrfToken(),
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        },
        body: new FormData(form)
    })
        .then(function (r) {
            if (r.status === 422) {
                return r.json().then(function (data) {
                    var errors   = data.errors || {};
                    var firstMsg = Object.values(errors)[0];
                    showProfileAlert(
                        Array.isArray(firstMsg) ? firstMsg[0] : (firstMsg || 'Error de validación.'),
                        'danger'
                    );
                    return Promise.reject('validation');
                });
            }
            return r.json();
        })
        .then(function (res) {
            if (!res.success) {
                showProfileAlert(res.message || 'Error al actualizar la contraseña.', 'danger');
                return;
            }

            form.reset();
            updateStrength('');

            if (res.provider_changed) {
                var cta       = document.getElementById('googlePassCta');
                var cancelBtn = form.querySelector('.btn-secondary');
                if (cta)       cta.classList.add('hidden');
                if (cancelBtn) cancelBtn.remove();

                var heroBadge = document.querySelector('.profile-badge');
                if (heroBadge) {
                    heroBadge.className = 'profile-badge profile-badge--local';
                    heroBadge.innerHTML = '<i class="fas fa-envelope"></i> Cuenta local';
                }

                var fieldsDiv = form.querySelector('.profile-fields');
                if (fieldsDiv && !document.getElementById('currentPassGroup')) {
                    var currentGroup       = document.createElement('div');
                    currentGroup.id        = 'currentPassGroup';
                    currentGroup.className = 'form-group profile-field-full';
                    currentGroup.innerHTML =
                        '<label for="current_password">Contraseña Actual</label>' +
                        '<div class="profile-input-pass">' +
                        '<input type="password" id="current_password" name="current_password"' +
                        ' class="form-control" placeholder="Tu contraseña actual"' +
                        ' autocomplete="current-password">' +
                        '<button type="button" class="profile-toggle-pass"' +
                        ' onclick="togglePassword(\'current_password\', this)">' +
                        '<i class="fas fa-eye"></i>' +
                        '</button>' +
                        '</div>';
                    fieldsDiv.insertBefore(currentGroup, fieldsDiv.firstChild);
                }

                var title   = document.getElementById('passwordCardTitle');
                var saveBtn = document.getElementById('btnSavePassword');
                if (title)   title.textContent = 'Cambiar Contraseña';
                if (saveBtn) saveBtn.innerHTML  = '<i class="fas fa-save"></i> Actualizar Contraseña';

                form.classList.remove('hidden');
            }

            showProfileAlert(res.message || 'Contraseña actualizada correctamente.', 'success');
        })
        .catch(function (err) {
            if (err === 'validation') return;
            showProfileAlert('Error de conexión. Intenta de nuevo.', 'danger');
        })
        .finally(function () {
            if (submitBtn) {
                submitBtn.disabled  = false;
                var isNowLocal      = !!document.getElementById('currentPassGroup');
                submitBtn.innerHTML = '<i class="fas fa-save"></i> ' +
                    (isNowLocal ? 'Actualizar Contraseña' : 'Guardar Contraseña');
            }
        });
}

// ============================================================
// REGISTER FORM (register.blade.php)
// ============================================================

(function initRegistro() {
    var formRegistro = document.getElementById('formRegistroCliente');
    if (!formRegistro) return;

    var invalidChars = /[^A-Za-záéíóúÁÉÍÓÚüÜñÑ\s]/;

    // Live validation for name and surname fields (letters only, min length).
    [
        { id: 'name',           msgId: 'msg-name',           label: 'El nombre',          required: true  },
        { id: 'first_surname',  msgId: 'msg-first-surname',  label: 'El apellido',         required: true  },
        { id: 'second_surname', msgId: 'msg-second-surname', label: 'El segundo apellido', required: false },
    ].forEach(function (field) {
        var input = document.getElementById(field.id);
        if (!input) return;

        input.addEventListener('input', function () {
            if (invalidChars.test(this.value)) {
                this.value = this.value.replace(/[^A-Za-záéíóúÁÉÍÓÚüÜñÑ\s]/g, '');
                showMsg(field.msgId, 'error', 'Solo se permiten letras y espacios, sin números ni símbolos.');
                setInputState(this, 'input-error');
                return;
            }
            var val = this.value.trim();
            if (val === '' && field.required) {
                showMsg(field.msgId, 'error', field.label + ' es obligatorio.');
                setInputState(this, 'input-error');
            } else if (val !== '' && val.length < 2) {
                showMsg(field.msgId, 'error', field.label + ' debe tener al menos 2 caracteres.');
                setInputState(this, 'input-error');
            } else if (val !== '') {
                showMsg(field.msgId, 'success', 'Campo válido.');
                setInputState(this, 'input-ok');
            } else {
                clearMsg(field.msgId);
                setInputState(this, null);
            }
        });

        input.addEventListener('blur', function () {
            if (field.required && this.value.trim() === '') {
                showMsg(field.msgId, 'error', field.label + ' es obligatorio.');
                setInputState(this, 'input-error');
            }
        });
    });

    // Gmail validation: only @gmail.com addresses allowed.
    var gmailInput = document.getElementById('gmail');
    if (gmailInput) {
        gmailInput.addEventListener('input', function () {
            var val = this.value.trim().toLowerCase();
            if (val === '') { clearMsg('msg-gmail'); setInputState(this, null); return; }
            if (!val.endsWith('@gmail.com')) {
                showMsg('msg-gmail', 'error', 'Solo se aceptan correos @gmail.com.');
                setInputState(this, 'input-error');
            } else {
                showMsg('msg-gmail', 'success', 'Correo válido.');
                setInputState(this, 'input-ok');
            }
        });
        gmailInput.addEventListener('blur', function () {
            if (this.value.trim() === '') {
                showMsg('msg-gmail', 'error', 'El correo Gmail es obligatorio.');
                setInputState(this, 'input-error');
            }
        });
    }

    // Password length + confirmation match.
    function checkPassMatch() {
        var passwordEl = document.getElementById('password');
        var pcInput    = document.getElementById('password_confirmation');
        if (!passwordEl || !pcInput) return;
        var p  = passwordEl.value;
        var pc = pcInput.value;
        if (pc.length === 0) { clearMsg('msg-pass-confirm'); setInputState(pcInput, null); return; }
        if (p !== pc) {
            showMsg('msg-pass-confirm', 'error', 'Las contraseñas no coinciden.');
            setInputState(pcInput, 'input-error');
        } else {
            showMsg('msg-pass-confirm', 'success', 'Las contraseñas coinciden.');
            setInputState(pcInput, 'input-ok');
        }
    }

    var passwordInput = document.getElementById('password');
    if (passwordInput) {
        passwordInput.addEventListener('input', function () {
            var v = this.value;
            if (v.length === 0)    { clearMsg('msg-pass'); setInputState(this, null); }
            else if (v.length < 8) { showMsg('msg-pass', 'error', 'Mínimo 8 caracteres (' + v.length + '/8).'); setInputState(this, 'input-error'); }
            else                   { showMsg('msg-pass', 'success', 'Longitud correcta.'); setInputState(this, 'input-ok'); }
            checkPassMatch();
        });
    }

    var passConfirmInput = document.getElementById('password_confirmation');
    if (passConfirmInput) passConfirmInput.addEventListener('input', checkPassMatch);

    // Final validation before submit; shows loading state.
    formRegistro.addEventListener('submit', function (e) {
        var valid = true;

        var nameEl = document.getElementById('name');
        if (nameEl && nameEl.value.trim() === '') {
            showMsg('msg-name', 'error', 'El nombre es obligatorio.');
            setInputState(nameEl, 'input-error');
            valid = false;
        }
        var fsEl = document.getElementById('first_surname');
        if (fsEl && fsEl.value.trim() === '') {
            showMsg('msg-first-surname', 'error', 'El apellido es obligatorio.');
            setInputState(fsEl, 'input-error');
            valid = false;
        }

        var gv = gmailInput ? gmailInput.value.trim().toLowerCase() : '';
        if (gv === '') {
            showMsg('msg-gmail', 'error', 'El correo Gmail es obligatorio.');
            setInputState(gmailInput, 'input-error');
            valid = false;
        } else if (!gv.endsWith('@gmail.com')) {
            showMsg('msg-gmail', 'error', 'Solo se aceptan correos @gmail.com.');
            setInputState(gmailInput, 'input-error');
            valid = false;
        }

        var pv   = passwordInput ? passwordInput.value : '';
        var pcEl = document.getElementById('password_confirmation');
        var pcv  = pcEl ? pcEl.value : '';

        if (pv.length === 0) {
            showMsg('msg-pass', 'error', 'La contraseña es obligatoria.');
            setInputState(passwordInput, 'input-error');
            valid = false;
        } else if (pv.length < 8) {
            showMsg('msg-pass', 'error', 'Mínimo 8 caracteres.');
            setInputState(passwordInput, 'input-error');
            valid = false;
        }
        if (pcv.length === 0) {
            showMsg('msg-pass-confirm', 'error', 'Debes confirmar la contraseña.');
            setInputState(pcEl, 'input-error');
            valid = false;
        } else if (pv !== pcv) {
            showMsg('msg-pass-confirm', 'error', 'Las contraseñas no coinciden.');
            setInputState(pcEl, 'input-error');
            valid = false;
        }

        if (!valid) { e.preventDefault(); return; }

        var btnTexto    = document.getElementById('btnRegistrarTexto');
        var btnCargando = document.getElementById('btnRegistrarCargando');
        var btn         = document.getElementById('btnRegistrar');
        if (btnTexto)    btnTexto.style.display    = 'none';
        if (btnCargando) btnCargando.style.display = 'inline';
        if (btn)         btn.disabled              = true;
    });
})();

// ============================================================
// VERIFICATION EMAIL PAGE
// ============================================================

(function initVerificacion() {
    var codeInput     = document.getElementById('verification_code');
    var formVerificar = document.getElementById('formVerificar');
    if (!codeInput || !formVerificar) return;

    // Restrict input to 6 digits.
    codeInput.addEventListener('input', function () {
        this.value = this.value.replace(/\D/g, '').slice(0, 6);
    });

    formVerificar.addEventListener('submit', function (e) {
        var code = codeInput.value.trim();
        var err  = document.getElementById('code-error');

        if (code.length !== 6) {
            if (err) err.style.display    = 'block';
            codeInput.style.borderColor   = '#e74c3c';
            e.preventDefault();
            return;
        }

        if (err) err.style.display  = 'none';
        codeInput.style.borderColor = '#dadce0';

        var btnTexto    = document.getElementById('btnVerificarTexto');
        var btnCargando = document.getElementById('btnVerificarCargando');
        var btn         = document.getElementById('btnVerificar');
        if (btnTexto)    btnTexto.style.display    = 'none';
        if (btnCargando) btnCargando.style.display = 'inline';
        if (btn)         btn.disabled              = true;
    });
})();

// ============================================================
// RECOVERY PASSWORD PAGE
// ============================================================

(function initRecovery() {
    var formRecovery = document.getElementById('formRecovery');
    if (!formRecovery) return;

    // Password toggle buttons.
    var togglePassBtn    = document.getElementById('toggle-recovery-password');
    var toggleConfirmBtn = document.getElementById('toggle-recovery-confirm');
    if (togglePassBtn)    togglePassBtn.addEventListener('click',    function () { togglePass('recovery-password',         'eye-recovery-password'); });
    if (toggleConfirmBtn) toggleConfirmBtn.addEventListener('click', function () { togglePass('recovery-password-confirm', 'eye-recovery-confirm'); });

    // Gmail validation for recovery email.
    var recEmailInput = document.getElementById('recovery-email');
    if (recEmailInput) {
        recEmailInput.addEventListener('input', function () {
            var val = this.value.trim().toLowerCase();
            if (val === '') { clearMsg('msg-recovery-email'); setInputState(this, null); return; }
            if (!val.endsWith('@gmail.com')) {
                showMsg('msg-recovery-email', 'error', 'Solo se aceptan correos @gmail.com.');
                setInputState(this, 'input-error');
            } else {
                clearMsg('msg-recovery-email');
                setInputState(this, 'input-ok');
            }
        });
        recEmailInput.addEventListener('blur', function () {
            if (this.value.trim() === '') {
                showMsg('msg-recovery-email', 'error', 'El correo Gmail es obligatorio.');
                setInputState(this, 'input-error');
            }
        });
    }

    // Password length indicator.
    var recPassInput = document.getElementById('recovery-password');
    if (recPassInput) {
        recPassInput.addEventListener('input', function () {
            var v = this.value;
            if (v.length === 0)    { clearMsg('msg-recovery-password'); setInputState(this, null); }
            else if (v.length < 8) { showMsg('msg-recovery-password', 'error', 'Mínimo 8 caracteres (' + v.length + '/8).'); setInputState(this, 'input-error'); }
            else                   { showMsg('msg-recovery-password', 'success', 'Longitud correcta.'); setInputState(this, 'input-ok'); }
            checkRecoveryMatch();
        });
    }

    // Password confirmation match.
    function checkRecoveryMatch() {
        var passEl    = document.getElementById('recovery-password');
        var confirmEl = document.getElementById('recovery-password-confirm');
        if (!passEl || !confirmEl) return;
        var p  = passEl.value;
        var pc = confirmEl.value;
        if (pc.length === 0) { clearMsg('msg-recovery-confirm'); setInputState(confirmEl, null); return; }
        if (p !== pc) {
            showMsg('msg-recovery-confirm', 'error', 'Las contraseñas no coinciden.');
            setInputState(confirmEl, 'input-error');
        } else {
            showMsg('msg-recovery-confirm', 'success', 'Las contraseñas coinciden.');
            setInputState(confirmEl, 'input-ok');
        }
    }

    var recConfirmInput = document.getElementById('recovery-password-confirm');
    if (recConfirmInput) recConfirmInput.addEventListener('input', checkRecoveryMatch);

    // Final validation before submitting recovery form.
    formRecovery.addEventListener('submit', function (e) {
        var valid = true;

        var emailVal = recEmailInput ? recEmailInput.value.trim().toLowerCase() : '';
        if (!emailVal) {
            showMsg('msg-recovery-email', 'error', 'El correo Gmail es obligatorio.');
            setInputState(recEmailInput, 'input-error');
            valid = false;
        } else if (!emailVal.endsWith('@gmail.com')) {
            showMsg('msg-recovery-email', 'error', 'Solo se aceptan correos @gmail.com.');
            setInputState(recEmailInput, 'input-error');
            valid = false;
        }

        var passVal = recPassInput ? recPassInput.value : '';
        if (passVal.length === 0) {
            showMsg('msg-recovery-password', 'error', 'La contraseña es obligatoria.');
            setInputState(recPassInput, 'input-error');
            valid = false;
        } else if (passVal.length < 8) {
            showMsg('msg-recovery-password', 'error', 'Mínimo 8 caracteres.');
            setInputState(recPassInput, 'input-error');
            valid = false;
        }

        var confVal = recConfirmInput ? recConfirmInput.value : '';
        if (confVal.length === 0) {
            showMsg('msg-recovery-confirm', 'error', 'Debes confirmar la contraseña.');
            setInputState(recConfirmInput, 'input-error');
            valid = false;
        } else if (passVal !== confVal) {
            showMsg('msg-recovery-confirm', 'error', 'Las contraseñas no coinciden.');
            setInputState(recConfirmInput, 'input-error');
            valid = false;
        }

        if (!valid) { e.preventDefault(); return; }

        var btn         = document.getElementById('btnRecovery');
        var btnTexto    = document.getElementById('btnRecoveryTexto');
        var btnCargando = document.getElementById('btnRecoveryCargando');
        if (btn)         btn.disabled              = true;
        if (btnTexto)    btnTexto.style.display    = 'none';
        if (btnCargando) btnCargando.style.display = 'inline';
    });
})();

// ============================================================
// GENERAL INITIALIZATION (DOMContentLoaded)
// ============================================================

document.addEventListener('DOMContentLoaded', function () {
    favoritesCache = getInitialFavoritesFromMeta();

    initHeaderCatalogSearch();

    // — Initialise cart counter —
    var cartLinkEl  = document.getElementById('cart-link');
    var cartGuestEl = document.getElementById('cart-guest');
    var cartRef     = cartLinkEl || cartGuestEl;
    if (cartRef) {
        var initialCount = parseInt(cartRef.getAttribute('data-cart-count') || '0', 10);
        updateCartCount(initialCount);
    }

    // — Guest cart: prompt to log in —
    if (cartGuestEl) {
        cartGuestEl.addEventListener('click', function () {
            Swal.fire({
                icon: 'info',
                title: 'Inicia sesión',
                text: 'Debes iniciar sesión para ver tu carrito.',
                confirmButtonText: 'Entendido'
            });
        });
    }

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

    // — Mobile header menu toggle —
    var headerMenuToggle = document.getElementById('header-menu-toggle');
    if (headerMenuToggle) {
        headerMenuToggle.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            var header = document.querySelector('.cliente-header');
            if (!header) return;
            setHeaderMenuOpen(!header.classList.contains('menu-open'));
        });
    }

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

    // — Close mobile menu when selecting a nav link —
    document.querySelectorAll('.header-menu-panel .nav-link').forEach(function (link) {
        link.addEventListener('click', function () { setHeaderMenuOpen(false); });
    });

    // — Close mobile header menu on outside click —
    document.addEventListener('click', function (e) {
        var header = document.querySelector('.cliente-header');
        if (!header || !header.classList.contains('menu-open')) return;
        if (header.contains(e.target)) return;
        setHeaderMenuOpen(false);
    });

    // — Keep desktop navbar always collapsed on resize; re-sync account dropdown on mobile —
    window.addEventListener('resize', function () {
        if (window.innerWidth > 768) {
            setHeaderMenuOpen(false);
        }
        syncMobileUserDropdownPosition();
    });

    // — Login modal —
    var loginModalTrigger = document.getElementById('login-modal-trigger');
    if (loginModalTrigger) {
        loginModalTrigger.addEventListener('click', function () {
            window.location.href = '/login';
        });
    }

    var closeLoginModalBtn = document.getElementById('close-login-modal');
    if (closeLoginModalBtn) closeLoginModalBtn.addEventListener('click', closeLoginModal);

    var loginModalOverlay = document.getElementById('login-modal-overlay');
    if (loginModalOverlay) loginModalOverlay.addEventListener('click', closeLoginModal);

    // — Toggle password (login page) —
    var togglePasswordBtn = document.getElementById('toggle-password');
    if (togglePasswordBtn) {
        togglePasswordBtn.addEventListener('click', function () {
            togglePass('login-password', 'eye-icon');
        });
    }

    // — Login form submission via AJAX —
    var publicLoginForm = document.getElementById('public-login-form');
    if (publicLoginForm) {
        publicLoginForm.addEventListener('submit', function (e) {
            e.preventDefault();

            var csrfToken = getCsrfToken();
            if (!csrfToken) {
                window.location.href = '/login?session_expired=1';
                return;
            }

            var formData    = new FormData(this);
            var submitBtn   = document.getElementById('login-submit-btn');
            var loadingSpan = document.getElementById('login-loading');
            var submitSpan  = submitBtn ? submitBtn.querySelector('span:not(.btn-loading)') : null;

            if (submitBtn)   submitBtn.disabled = true;
            if (submitSpan)  submitSpan.classList.add('hidden');
            if (loadingSpan) loadingSpan.classList.remove('hidden');

            fetch('/login', {
                method: 'POST',
                body: formData,
                credentials: 'same-origin',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
                .then(function (response) {
                    // 419 means expired session/CSRF token.
                    if (response.status === 419) {
                        window.location.href = '/login?session_expired=1';
                        return Promise.reject('csrf');
                    }
                    return response.json().catch(function () {
                        window.location.href = '/login?session_expired=1';
                        return Promise.reject('parse');
                    });
                })
                .then(function (data) {
                    if (data.success) {
                        if (typeof window.cf4AuthWelcomeToast === 'function') {
                            window.cf4AuthWelcomeToast({
                                kind: 'welcome',
                                authIcon: 'user',
                                displayName: data.display_name || '',
                                thenUrl: data.redirect || '/',
                            });
                        } else {
                            Swal.fire({
                                icon: 'success',
                                title: '¡Bienvenido!',
                                text: data.message || 'Inicio de sesión exitoso',
                                timer: 4000,
                                showConfirmButton: false,
                            }).then(function () {
                                window.location.href = data.redirect || '/';
                            });
                        }
                    } else if (data.redirect) {
                        // Correo no verificado: ofrecer ir a verificar.
                        if (submitBtn)   submitBtn.disabled = false;
                        if (submitSpan)  submitSpan.classList.remove('hidden');
                        if (loadingSpan) loadingSpan.classList.add('hidden');
                        Swal.fire({
                            icon: 'warning',
                            title: 'Correo no verificado',
                            text: data.message || 'Debes verificar tu correo antes de iniciar sesión.',
                            showCancelButton: true,
                            confirmButtonText: 'Verificar Correo',
                            cancelButtonText: 'Cancelar',
                            confirmButtonColor: '#2d7a2d',
                            cancelButtonColor: '#6c757d'
                        }).then(function (result) {
                            if (!result.isConfirmed) return;
                            // El servidor ya envió el código al detectar el correo no verificado.
                            window.location.href = data.redirect;
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            html: (data.message || 'Error al iniciar sesión') +
                                '<hr style="margin:12px 0">' +
                                '<p style="font-size:0.9rem;margin:0">¿Tienes una cuenta registrada? ¿O deseas registrarte?</p>',
                            showCancelButton: true,
                            confirmButtonText: 'Ir a Registro',
                            cancelButtonText: 'Cancelar',
                            confirmButtonColor: '#2d7a2d',
                            cancelButtonColor: '#6c757d',
                        }).then(function (result) {
                            if (result.isConfirmed) { window.location.href = '/register'; }
                        });
                        if (submitBtn)   submitBtn.disabled = false;
                        if (submitSpan)  submitSpan.classList.remove('hidden');
                        if (loadingSpan) loadingSpan.classList.add('hidden');
                    }
                })
                .catch(function (err) {
                    if (err === 'csrf' || err === 'parse') return;
                    console.error('Login error:', err);
                    Swal.fire({ icon: 'error', title: 'Error', text: 'Ocurrió un error al iniciar sesión' });
                    if (submitBtn)   submitBtn.disabled = false;
                    if (submitSpan)  submitSpan.classList.remove('hidden');
                    if (loadingSpan) loadingSpan.classList.add('hidden');
                });
        });
    }

    // — Profile page: store originals and wire password strength meter —
    if (document.getElementById('formPerfil')) {
        profileSaveOriginals();

        var passInputProfile = document.getElementById('new_password');
        if (passInputProfile) {
            passInputProfile.addEventListener('input', function () { updateStrength(this.value); });
        }

        var flash = window.__profileFlash || {};
        if (flash.profile_updated)  showProfileAlert('Cambios guardados correctamente.', 'success');
        if (flash.password_updated) showProfileAlert('Contraseña actualizada correctamente.', 'success');
        if (flash.password_defined) showProfileAlert('Contraseña definida. Ahora puedes iniciar sesión con correo y contraseña.', 'success');
    }

    // — Password change form: confirm and handle Google-only accounts —
    var formPassword = document.getElementById('formPassword');
    if (formPassword) {
        formPassword.addEventListener('submit', function (e) {
            e.preventDefault();
            var isGoogleOnly = !!document.getElementById('googlePassCta');
            var confirmMsg   = isGoogleOnly
                ? 'Se definirá una contraseña para tu cuenta. Podrás iniciar sesión con correo y contraseña.'
                : 'Se actualizará la contraseña de tu cuenta.';
            var confirmBtn   = isGoogleOnly
                ? '<i class="fas fa-key"></i> Sí, definir'
                : '<i class="fas fa-save"></i> Sí, actualizar';
            Swal.fire({
                title: '¿Confirmar cambio de contraseña?',
                text: confirmMsg, icon: 'warning',
                showCancelButton: true,
                confirmButtonText: confirmBtn,
                cancelButtonText: '<i class="fas fa-times"></i> Cancelar',
                reverseButtons: true
            }).then(function (result) {
                if (!result.isConfirmed) return;
                sendPassword(formPassword);
            });
        });
    }

    // — Favorites drawer remove button (delegated) —
    // El resto de manejadores de carrito se delega a clients-page.js cuando esa
    // entrada de Vite también se carga (catalog/cart/product/home). Si solo se
    // carga clients-users.js (login/register/recovery/profile) no hay UI de
    // carrito, por lo que omitimos los listeners para evitar doble ejecución.
    document.addEventListener('click', function (e) {
        var favoriteRemoveBtn = e.target.closest('[data-favorite-remove-btn]');
        if (favoriteRemoveBtn) {
            e.preventDefault();
            toggleFavoriteFromDrawer(favoriteRemoveBtn.getAttribute('data-product-id'));
        }
    });

    // — Cart UI listeners (only when clients-page.js is NOT loaded on this page) —
    // Pages that render cart UI (cart, catalog, product, home) load clients-page.js,
    // which already binds these handlers. Avoid double-binding here.
    if (!window.__cf4ClientPageJsLoaded) {
    document.addEventListener('click', function (e) {
        var addBtn = e.target.closest('.add-to-cart-btn');
        if (addBtn) {
            if (addBtn.dataset.purchasable === '0' || parseInt(addBtn.dataset.productStock, 10) < 1) {
                Swal.fire({ icon: 'warning', title: 'Producto agotado', text: 'Este producto no tiene unidades disponibles.' });
                return;
            }
            addToCart(addBtn.dataset.productId, 1, addBtn);
            return;
        }

        var guestBtn = e.target.closest('.guest-add-btn');
        if (guestBtn) {
            if (guestBtn.dataset.purchasable === '0' || parseInt(guestBtn.dataset.productStock, 10) < 1) {
                Swal.fire({ icon: 'warning', title: 'Producto agotado', text: 'Este producto no tiene unidades disponibles.' });
                return;
            }
            window.location.href = '/login';
            return;
        }

        if (e.target.classList.contains('modal') && e.target.classList.contains('active')) {
            e.target.classList.remove('active');
        }
    });

    // — Remove single cart item (delegated) —
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.cart-remove-item');
        if (!btn) return;

        var cartItem = btn.closest('.cart-item');
        var itemId   = btn.dataset.productId;
        var itemName = btn.dataset.productName || 'este producto';
        if (!cartItem || !itemId) return;

        Swal.fire({
            title: '¿Eliminar producto?',
            text: '¿Deseas eliminar "' + itemName + '" del carrito?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then(function (result) {
            if (!result.isConfirmed) return;

            fetch('/cart/remove/' + itemId, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': getCsrfToken(),
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    if (!data.success) {
                        Swal.fire({ icon: 'error', title: 'Error', text: data.message || 'No se pudo eliminar' });
                        return;
                    }

                    cartItem.remove();

                    Swal.fire({
                        toast: true, position: 'top-end', icon: 'success',
                        title: 'Producto eliminado del carrito',
                        showConfirmButton: false, timer: 2500, timerProgressBar: true
                    });

                    var totalFormatted = (data.cart_total != null)
                        ? ('₡' + Number(data.cart_total).toLocaleString('es-CR'))
                        : '₡0';
                    var subtotalEl = document.getElementById('cart-subtotal');
                    var totalEl    = document.getElementById('cart-total-amount');
                    if (subtotalEl) subtotalEl.textContent = totalFormatted;
                    if (totalEl)    totalEl.textContent    = totalFormatted;

                    updateCartCount(data.cart_count || 0);

                    if (document.querySelectorAll('.cart-item').length === 0) {
                        showCartEmptyState();
                    }
                })
                .catch(function (err) {
                    console.error('Error removing cart item:', err);
                    Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo eliminar el producto' });
                });
        });
    });

    // — Vaciar carrito (delegado) —
    // Se usa delegación para que funcione aunque el botón sea regenerado
    // dinámicamente o el click caiga sobre el <i> hijo.
    document.addEventListener('click', function (e) {
        if (!e.target.closest('#btn-clear-cart')) return;

        Swal.fire({
            title: '¿Vaciar carrito?',
            text: 'Se eliminarán todos los productos del carrito.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, vaciar',
            cancelButtonText: 'Cancelar'
        }).then(function (result) {
            if (!result.isConfirmed) return;

            fetch('/cart/clear', {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': getCsrfToken(),
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    if (data.success) {
                        updateCartCount(0);
                        showCartEmptyState();
                        Swal.fire({
                            toast: true, position: 'top-end', icon: 'success',
                            title: 'Carrito vaciado correctamente',
                            showConfirmButton: false, timer: 2500, timerProgressBar: true
                        });
                    } else {
                        Swal.fire({ icon: 'error', title: 'Error', text: data.message || 'No se pudo vaciar el carrito' });
                    }
                })
                .catch(function () {
                    Swal.fire({ icon: 'error', title: 'Error', text: 'Ocurrió un error al vaciar el carrito' });
                });
        });
    });

    // — Quantity input: clamp to [1, stock] on change —
    document.querySelectorAll('.quantity-input').forEach(function (input) {
        input.addEventListener('change', function () {
            var productId = this.dataset.productId;
            var quantity  = parseInt(this.value, 10);
            var max       = parseInt(this.max, 10);
            if (quantity < 1) {
                this.value = 1;
                updateCartQuantity(productId, 1);
            } else if (quantity > max) {
                this.value = max;
                Swal.fire('Aviso', 'La cantidad no puede exceder el stock disponible', 'warning');
                updateCartQuantity(productId, max);
            } else {
                updateCartQuantity(productId, quantity);
            }
        });
    });

    // — +/- quantity buttons (cart page) —
    document.querySelectorAll('.quantity-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var action    = this.dataset.action;
            var productId = this.dataset.productId;
            var input     = document.querySelector('.quantity-input[data-product-id="' + productId + '"]');
            if (!input) return;
            var quantity = parseInt(input.value, 10);
            var max      = parseInt(input.max, 10);
            if (action === 'increase' && quantity < max) quantity++;
            else if (action === 'decrease' && quantity > 1) quantity--;
            input.value = quantity;
            updateCartQuantity(productId, quantity);
        });
    });

    // — Quantity controls on product detail page —
    var productQtyInput = document.getElementById('product-quantity');
    var productQty      = 1;

    if (productQtyInput) {
        var maxQty      = parseInt(productQtyInput.max, 10) || 999;
        var decreaseBtn = document.getElementById('decrease-qty');
        var increaseBtn = document.getElementById('increase-qty');

        if (decreaseBtn) {
            decreaseBtn.addEventListener('click', function () {
                if (productQty > 1) { productQty--; productQtyInput.value = productQty; }
            });
        }
        if (increaseBtn) {
            increaseBtn.addEventListener('click', function () {
                if (productQty < maxQty) { productQty++; productQtyInput.value = productQty; }
            });
        }

        productQtyInput.addEventListener('change', function () {
            var value = parseInt(this.value, 10);
            if (value < 1)           { this.value = 1;      productQty = 1; }
            else if (value > maxQty) { this.value = maxQty; productQty = maxQty; }
            else                     { productQty = value; }
        });

        var detailAddBtn = document.querySelector('.product-detail-actions .add-to-cart-btn');
        if (detailAddBtn) {
            detailAddBtn.addEventListener('click', function () {
                addToCart(this.dataset.productId, productQty, this);
            });
        }
    }

    // — Checkout confirmation —
    var proceedBtn = document.getElementById('proceed-checkout');
    if (proceedBtn) {
        proceedBtn.addEventListener('click', function () {
            var chosenMethodPreview = getCheckoutPaymentMethodFallback();
            Swal.fire({
                title: '¿Confirmar pedido con pago por '
                    + getCf4PaymentMethodShortLabel(chosenMethodPreview) + '?',
                text: 'Se enviará tu pedido para retiro en tienda.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sí, confirmar',
                cancelButtonText: 'Cancelar'
            }).then(function (result) {
                if (!result.isConfirmed) return;

                proceedBtn.disabled  = true;
                proceedBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Procesando...';

                fetch('/cart/checkout', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': getCsrfToken(),
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({ payment_method: getCheckoutPaymentMethodFallback() })
                })
                    .then(function (res) { return res.json(); })
                    .then(function (data) {
                        if (!data.success) {
                            var cmsg = data.message || 'No se pudo procesar el pedido';
                            var cshort = isClientStockShortMessage(cmsg);
                            Swal.fire({
                                icon: cshort ? 'warning' : 'error',
                                title: cshort ? cmsg : 'Error',
                                text: cshort ? '' : cmsg
                            });
                            proceedBtn.disabled  = false;
                            proceedBtn.innerHTML = '<i class="fas fa-check"></i> Confirmar Compra';
                            return;
                        }
                        updateCartCount(0);
                        showCartEmptyState();
                        var paidWith = (data && data.payment_method)
                            ? data.payment_method
                            : getCheckoutPaymentMethodFallback();
                        Swal.fire({
                            icon: 'success',
                            title: '¡Pedido confirmado!',
                            text: buildCf4CheckoutSuccessText(paidWith),
                            confirmButtonText: 'Entendido'
                        });
                    })
                    .catch(function (err) {
                        console.error('Checkout error:', err);
                        Swal.fire({ icon: 'error', title: 'Error', text: 'Ocurrió un error al procesar el pedido' });
                        proceedBtn.disabled  = false;
                        proceedBtn.innerHTML = '<i class="fas fa-check"></i> Confirmar Compra';
                    });
            });
        });
    }
    } // end if (!window.__cf4ClientPageJsLoaded)

    // — ESC closes all modals and dropdowns —
    document.addEventListener('keydown', function (e) {
        if (e.key !== 'Escape') return;
        setHeaderMenuOpen(false);
        setFavoritesDrawerOpen(false);
        closeUserDropdown();
        closeLoginModal();
        document.querySelectorAll('.modal.active').forEach(function (modal) {
            modal.classList.remove('active');
        });
    });

    // — Catalog price-range filter validation —
    (function initCatalogPriceFilter() {
        var form      = document.getElementById('filter-form');
        if (!form) return;
        var minInput  = document.getElementById('min_price');
        var maxInput  = document.getElementById('max_price');
        var submitBtn = document.getElementById('filter-submit-btn');

        function checkPriceRange() {
            if (!minInput || !maxInput || !submitBtn) return;
            var min       = parseFloat(minInput.value);
            var max       = parseFloat(maxInput.value);
            var minFilled = minInput.value.trim() !== '';
            var maxFilled = maxInput.value.trim() !== '';
            var negMin    = minFilled && !isNaN(min) && min < 0;
            var negMax    = maxFilled && !isNaN(max) && max < 0;
            var invalid   = negMin || negMax || (minFilled && maxFilled && !isNaN(min) && !isNaN(max) && min > max);
            submitBtn.disabled = invalid;
            if (invalid) {
                submitBtn.setAttribute(
                    'title',
                    negMin || negMax
                        ? 'Los precios no pueden ser negativos.'
                        : 'El precio mínimo debe ser menor o igual al precio máximo.'
                );
            } else {
                submitBtn.removeAttribute('title');
            }
        }

        if (minInput) minInput.addEventListener('input',  checkPriceRange);
        if (minInput) minInput.addEventListener('change', checkPriceRange);
        if (maxInput) maxInput.addEventListener('input',  checkPriceRange);
        if (maxInput) maxInput.addEventListener('change', checkPriceRange);
        checkPriceRange();
    })();

    // Catálogo: paginación vía ajax-pagination.js (clients-page.js). Legacy #goToPageBtn solo si queda Blade de Dev.

}); // end DOMContentLoaded

// ============================================================
// GLOBAL EXPORTS (for use from inline scripts / Blade onclicks)
// ============================================================
window.addToCart        = addToCart;
window.updateCartCount  = updateCartCount;
window.togglePass       = togglePass;
window.togglePassword   = togglePassword;
window.showMsg          = showMsg;
window.clearMsg         = clearMsg;
window.setInputState    = setInputState;
window.enableEdit       = enableEdit;
window.cancelEdit       = cancelEdit;
window.submitProfile    = submitProfile;
window.showPasswordForm = showPasswordForm;
window.hidePasswordForm = hidePasswordForm;
window.showProfileAlert  = showProfileAlert;
window.closeProfileAlert = closeProfileAlert;