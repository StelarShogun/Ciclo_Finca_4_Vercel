// ----------------------------------------------------------------
// SHARED UTILITIES
// ----------------------------------------------------------------

/** Returns the CSRF token from a meta tag or hidden input fallback. */
function getCsrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    if (meta) return meta.content;
    const input = document.querySelector('input[name="_token"]');
    return input ? input.value : '';
}

/** Renders an error or success message with an icon inside the given element. */
function showMsg(msgId, type, text) {
    const el = document.getElementById(msgId);
    if (!el) return;
    el.className = 'field-msg ' + type;
    el.innerHTML = (type === 'error')
        ? '<i class="fas fa-exclamation-circle"></i><span>' + text + '</span>'
        : '<i class="fas fa-check-circle"></i><span>' + text + '</span>';
}

/** Clears any validation message from the given element. */
function clearMsg(msgId) {
    const el = document.getElementById(msgId);
    if (el) { el.className = 'field-msg'; el.innerHTML = ''; }
}

/** Applies or removes an error/success CSS class on a form input. */
function setInputState(input, state) {
    input.classList.remove('input-error', 'input-ok');
    if (state) input.classList.add(state);
}

// ----------------------------------------------------------------
// CART COUNTER (navbar)
// ----------------------------------------------------------------

/** Updates the cart badge count in the navbar; hides it when count is zero. */
function updateCartCount(count) {
    const cartCountEl = document.getElementById('cart-count');
    const cartLinkEl  = document.getElementById('cart-link');
    if (cartCountEl) {
        cartCountEl.textContent   = count;
        cartCountEl.style.display = count > 0 ? 'flex' : 'none';
    }
    if (cartLinkEl) {
        cartLinkEl.setAttribute('data-cart-count', count);
    }
}

// ----------------------------------------------------------------
// ADD TO CART
// ----------------------------------------------------------------

/**
 * Sends an authenticated POST request to add a product to the cart.
 * On success, refreshes the navbar badge and shows a toast notification.
 */
function addToCart(productId, quantity) {
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
                closeModal('add-to-cart-modal');
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
                Swal.fire({ icon: 'error', title: 'Error', text: data.message || 'No se pudo agregar el producto al carrito' });
            }
        })
        .catch(function (err) {
            console.error('Error adding to cart:', err);
            Swal.fire({ icon: 'error', title: 'Error', text: 'Ocurrió un error al agregar el producto al carrito' });
        });
}

// ----------------------------------------------------------------
// ADD-TO-CART MODAL (catalog & home)
// ----------------------------------------------------------------

/** Holds the product ID currently staged in the quantity-selection modal. */
var currentProductId = null;

/**
 * Populates and opens the add-to-cart modal with the target product's
 * name, price, stock, and thumbnail pulled from the nearest card element.
 */
function openAddToCartModal(btn) {
    currentProductId = btn.dataset.productId;
    var productName  = btn.dataset.productName;
    var productPrice = parseFloat(btn.dataset.productPrice);
    var productStock = parseInt(btn.dataset.productStock, 10);

    var nameEl  = document.getElementById('preview-name');
    var priceEl = document.getElementById('preview-price');
    var stockEl = document.getElementById('preview-stock');
    var qtyEl   = document.getElementById('cart-quantity');

    if (nameEl)  nameEl.textContent  = productName;
    if (priceEl) priceEl.textContent = '₡' + productPrice.toLocaleString('es-CR');
    if (stockEl) stockEl.textContent = 'Stock disponible: ' + productStock;
    if (qtyEl)   { qtyEl.max = productStock; qtyEl.value = 1; }

    // Reuse the product thumbnail from the nearest card in the DOM.
    var productCard  = btn.closest('.product-card');
    var productImage = productCard ? productCard.querySelector('.product-image img') : null;
    var previewImg   = document.getElementById('preview-image');
    if (previewImg && productImage) previewImg.src = productImage.src;

    openModal('add-to-cart-modal');
}

// ----------------------------------------------------------------
// MODAL HELPERS
// ----------------------------------------------------------------

/** Activates a modal overlay by ID. */
function openModal(id) {
    var modal = document.getElementById(id);
    if (modal) modal.classList.add('active');
}

/** Deactivates a modal overlay by ID. */
function closeModal(id) {
    var modal = document.getElementById(id);
    if (modal) modal.classList.remove('active');
}

// ----------------------------------------------------------------
// TOGGLE PASSWORD VISIBILITY
// ----------------------------------------------------------------

/**
 * Toggles a password input between plain text and masked.
 * Used via inline onclick="togglePass('inputId', 'iconId')".
 */
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

/**
 * Toggles a password input using the button element reference.
 * Used via inline onclick="togglePassword('inputId', this)".
 */
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

// ----------------------------------------------------------------
// CART PAGE (/cart)
// ----------------------------------------------------------------

/**
 * Sends a PUT request to update a cart line's quantity.
 * On success, recalculates the line subtotal and refreshes the order
 * total in the summary panel without a full page reload.
 */
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

                // Derive the line subtotal from the unit price already in the DOM.
                var cartItem = document.querySelector('.cart-item[data-product-id="' + productId + '"]');
                if (cartItem) {
                    var unitPriceEl    = cartItem.querySelector('.item-price');
                    var unitPriceText  = unitPriceEl ? unitPriceEl.textContent : '';
                    // Strip formatting chars from "₡1.234 c/u" to get the raw integer.
                    var unitPrice      = parseInt(unitPriceText.replace(/[^\d]/g, ''), 10) || 0;
                    var lineSubtotalEl = cartItem.querySelector('.subtotal-amount');
                    if (lineSubtotalEl) {
                        lineSubtotalEl.textContent = '₡' + (unitPrice * quantity).toLocaleString('es-CR');
                    }
                }
            } else {
                Swal.fire('Error', data.message || 'No se pudo actualizar el carrito', 'error');
            }
        })
        .catch(function () {
            Swal.fire('Error', 'Ocurrió un error al actualizar el carrito', 'error');
        });
}

/**
 * Replaces the cart card's inner HTML with an empty-state message,
 * preserving the "continue shopping" link without a full page reload.
 */
function showCartEmptyState() {
    var card = document.querySelector('.cart-page-card');
    if (!card) return;
    var catalogUrl = (card.querySelector('.cart-header a[href]') || {}).href || '/catalog';
    card.innerHTML =
        '<div class="cart-header">' +
        '<h1 class="cart-title"><i class="fas fa-shopping-cart"></i> Carrito de Compras</h1>' +
        '<a href="' + catalogUrl + '" class="btn btn-outline-secondary btn-sm">' +
        '<i class="fas fa-arrow-left"></i> Continuar Comprando</a>' +
        '</div>' +
        '<div class="cart-empty"><div class="empty-state">' +
        '<i class="fas fa-shopping-cart"></i>' +
        '<h2>Tu carrito está vacío</h2>' +
        '<p>Agrega productos desde nuestro catálogo</p>' +
        '<a href="' + catalogUrl + '" class="btn btn-primary btn-lg">' +
        '<i class="fas fa-th"></i> Ver Catálogo</a>' +
        '</div></div>';
}

// ----------------------------------------------------------------
// USER DROPDOWN MENU (header)
// ----------------------------------------------------------------

/** Sets the user menu open/closed state and syncs ARIA attributes. */
function setUserMenuOpen(open) {
    var wrap    = document.getElementById('user-menu');
    var panel   = document.getElementById('user-dropdown');
    var trigger = document.getElementById('user-menu-trigger');
    if (!wrap) return;
    wrap.classList.toggle('open', open);
    if (panel)   panel.setAttribute('aria-hidden', String(!open));
    if (trigger) trigger.setAttribute('aria-expanded', String(open));
}

/** Closes the user profile dropdown. */
function closeUserDropdown() {
    setUserMenuOpen(false);
}

/** Toggles the user profile dropdown open or closed. */
function toggleUserDropdown() {
    var wrap   = document.getElementById('user-menu');
    var isOpen = wrap ? wrap.classList.contains('open') : false;
    setUserMenuOpen(!isOpen);
}

// ----------------------------------------------------------------
// LOGIN MODAL
// ----------------------------------------------------------------

/** Closes the login modal and removes the overlay's active state. */
function closeLoginModal() {
    closeModal('login-modal');
    var overlay = document.getElementById('login-modal-overlay');
    if (overlay) overlay.classList.remove('active');
}

// ----------------------------------------------------------------
// PROFILE PAGE
// ----------------------------------------------------------------

/** Stores original field values so edits can be reverted on cancel. */
var profileOriginalValues = {};
var profileEditableFields = ['name', 'first_surname', 'second_surname', 'gmail'];

/** Snapshots current field values into profileOriginalValues. */
function profileSaveOriginals() {
    profileEditableFields.forEach(function (id) {
        var el = document.getElementById(id);
        if (el) profileOriginalValues[id] = el.value;
    });
}

/** Unlocks profile fields for editing and swaps the button to "Save". */
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

/** Discards unsaved edits, restores original values, and re-locks fields. */
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

/** Shows a confirmation dialog before submitting the profile form. */
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

/**
 * Submits profile data via AJAX.
 * On success, updates displayed name, initials, and email across the hero
 * section and the header dropdown without reloading the page.
 * On 422, surfaces the first validation error via the profile alert banner.
 */
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
                // Lock fields and update the snapshot with the new values.
                profileEditableFields.forEach(function (id) {
                    var el = document.getElementById(id);
                    if (el) { profileOriginalValues[id] = el.value; el.setAttribute('readonly', true); }
                });

                var name  = (document.getElementById('name')          || {}).value || '';
                var fs    = (document.getElementById('first_surname')  || {}).value || '';
                var ss    = (document.getElementById('second_surname') || {}).value || '';
                var gmail = (document.getElementById('gmail')          || {}).value || '';

                // Reflect updated name/email in the profile hero section.
                var heroName  = document.getElementById('heroName');
                var initials  = document.getElementById('avatarInitials');
                var heroEmail = document.querySelector('.profile-email');
                if (heroName)  heroName.textContent  = [name, fs, ss].filter(Boolean).join(' ');
                if (initials)  initials.textContent  = (name.charAt(0) + fs.charAt(0)).toUpperCase();
                if (heroEmail) heroEmail.textContent = gmail;

                // Reflect updated name/email in the header dropdown.
                var headerInitials  = document.querySelector('.user-avatar-bubble');
                var headerShortName = document.querySelector('.user-trigger-name');
                var headerFullName  = document.querySelector('.user-dropdown-fullname');
                var headerEmail     = document.querySelector('.user-dropdown-email');
                if (headerInitials)  headerInitials.textContent  = (name.charAt(0) + fs.charAt(0)).toUpperCase();
                if (headerShortName) headerShortName.textContent = name;
                if (headerFullName)  headerFullName.textContent  = [name, fs].filter(Boolean).join(' ');
                if (headerEmail)     headerEmail.textContent     = gmail;

                // Reset the edit button back to its default state.
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

/**
 * Calculates a 1–4 point password strength score based on length,
 * uppercase letters, digits, and special characters, then updates
 * the strength bar and label accordingly.
 */
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
        { w: '100%', c: '#2e7d32', t: 'Fuerte'  }
    ];
    var lvl = levels[Math.max(score - 1, 0)];
    if (fill)  { fill.style.width = lvl.w; fill.style.background = lvl.c; }
    if (label) { label.textContent = lvl.t; label.style.color = lvl.c; }
}

/** Shows the password change form and hides the Google-account CTA. */
function showPasswordForm() {
    var form = document.getElementById('formPassword');
    var cta  = document.getElementById('googlePassCta');
    if (form) form.classList.remove('hidden');
    if (cta)  cta.classList.add('hidden');
}

/** Hides the password change form and restores the Google-account CTA. */
function hidePasswordForm() {
    var form = document.getElementById('formPassword');
    var cta  = document.getElementById('googlePassCta');
    if (form) form.classList.add('hidden');
    if (cta)  cta.classList.remove('hidden');
}

/** Displays a dismissible alert banner on the profile page with an auto-hide timer. */
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

/** Hides the profile alert banner. */
function closeProfileAlert() {
    var alertEl = document.getElementById('profileAlert');
    if (alertEl) alertEl.classList.add('hidden');
}

/**
 * Submits the password change form via AJAX.
 * Handles the special case where a Google-only account is gaining a local
 * password for the first time: injects the "current password" field into
 * the DOM and updates labels to reflect the account type change.
 */
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

            // If the account transitioned from Google-only to local, update
            // the form to require "current password" on future changes.
            if (res.provider_changed) {
                var cta       = document.getElementById('googlePassCta');
                var cancelBtn = form.querySelector('.btn-secondary');
                if (cta)       cta.classList.add('hidden');
                if (cancelBtn) cancelBtn.remove();

                // Update the profile hero badge to reflect the local account type.
                var heroBadge = document.querySelector('.profile-badge');
                if (heroBadge) {
                    heroBadge.className = 'profile-badge profile-badge--local';
                    heroBadge.innerHTML = '<i class="fas fa-envelope"></i> Cuenta local';
                }

                // Inject the "current password" field if not already present.
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

// ----------------------------------------------------------------
// PAGE: REGISTRO
// ----------------------------------------------------------------
(function initRegistro() {
    const formRegistro = document.getElementById('formRegistroCliente');
    if (!formRegistro) return;

    // Rejects any character that is not a letter, accent, or space.
    const invalidChars = /[^A-Za-záéíóúÁÉÍÓÚüÜñÑ\s]/;

    // Attach live validation to name and surname fields.
    [
        { id: 'name',           msgId: 'msg-name',           label: 'El nombre',          required: true  },
        { id: 'first_surname',  msgId: 'msg-first-surname',  label: 'El apellido',         required: true  },
        { id: 'second_surname', msgId: 'msg-second-surname', label: 'El segundo apellido', required: false },
    ].forEach(function (field) {
        const input = document.getElementById(field.id);
        if (!input) return;

        input.addEventListener('input', function () {
            // Strip invalid characters in real time.
            if (invalidChars.test(this.value)) {
                this.value = this.value.replace(/[^A-Za-záéíóúÁÉÍÓÚüÜñÑ\s]/g, '');
                showMsg(field.msgId, 'error', 'Solo se permiten letras y espacios, sin números ni símbolos.');
                setInputState(this, 'input-error');
                return;
            }
            const val = this.value.trim();
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

        // Show required error on blur if the field was left empty.
        input.addEventListener('blur', function () {
            if (field.required && this.value.trim() === '') {
                showMsg(field.msgId, 'error', field.label + ' es obligatorio.');
                setInputState(this, 'input-error');
            }
        });
    });

    // Only @gmail.com addresses are accepted.
    const gmailInput = document.getElementById('gmail');
    if (gmailInput) {
        gmailInput.addEventListener('input', function () {
            const val = this.value.trim().toLowerCase();
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

    /** Checks that both password fields are non-empty and match. */
    function checkPassMatch() {
        const p       = document.getElementById('password').value;
        const pc      = document.getElementById('password_confirmation').value;
        const pcInput = document.getElementById('password_confirmation');
        if (pc.length === 0) { clearMsg('msg-pass-confirm'); setInputState(pcInput, null); return; }
        if (p !== pc) {
            showMsg('msg-pass-confirm', 'error', 'Las contraseñas no coinciden.');
            setInputState(pcInput, 'input-error');
        } else {
            showMsg('msg-pass-confirm', 'success', 'Las contraseñas coinciden.');
            setInputState(pcInput, 'input-ok');
        }
    }

    const passInput = document.getElementById('password');
    if (passInput) {
        passInput.addEventListener('input', function () {
            const v = this.value;
            if (v.length === 0)    { clearMsg('msg-pass'); setInputState(this, null); }
            else if (v.length < 8) { showMsg('msg-pass', 'error', 'Mínimo 8 caracteres (' + v.length + '/8).'); setInputState(this, 'input-error'); }
            else                   { showMsg('msg-pass', 'success', 'Longitud correcta.'); setInputState(this, 'input-ok'); }
            checkPassMatch();
        });
    }

    const passConfirmInput = document.getElementById('password_confirmation');
    if (passConfirmInput) {
        passConfirmInput.addEventListener('input', checkPassMatch);
    }

    // Final validation gate on submit; shows the loading state if all checks pass.
    formRegistro.addEventListener('submit', function (e) {
        let valid = true;

        const nameVal = document.getElementById('name');
        if (nameVal && nameVal.value.trim() === '') {
            showMsg('msg-name', 'error', 'El nombre es obligatorio.');
            setInputState(nameVal, 'input-error');
            valid = false;
        }

        const surnameVal = document.getElementById('first_surname');
        if (surnameVal && surnameVal.value.trim() === '') {
            showMsg('msg-first-surname', 'error', 'El apellido es obligatorio.');
            setInputState(surnameVal, 'input-error');
            valid = false;
        }

        const gv = document.getElementById('gmail');
        if (gv) {
            const gvVal = gv.value.trim().toLowerCase();
            if (gvVal === '') {
                showMsg('msg-gmail', 'error', 'El correo Gmail es obligatorio.');
                setInputState(gv, 'input-error');
                valid = false;
            } else if (!gvVal.endsWith('@gmail.com')) {
                showMsg('msg-gmail', 'error', 'Solo se aceptan correos @gmail.com.');
                setInputState(gv, 'input-error');
                valid = false;
            }
        }

        const pv  = passInput ? passInput.value : '';
        const pcv = passConfirmInput ? passConfirmInput.value : '';

        if (pv.length === 0) {
            showMsg('msg-pass', 'error', 'La contraseña es obligatoria.');
            setInputState(passInput, 'input-error');
            valid = false;
        } else if (pv.length < 8) {
            showMsg('msg-pass', 'error', 'Mínimo 8 caracteres.');
            setInputState(passInput, 'input-error');
            valid = false;
        }
        if (pcv.length === 0) {
            showMsg('msg-pass-confirm', 'error', 'Debes confirmar la contraseña.');
            setInputState(passConfirmInput, 'input-error');
            valid = false;
        } else if (pv !== pcv) {
            showMsg('msg-pass-confirm', 'error', 'Las contraseñas no coinciden.');
            setInputState(passConfirmInput, 'input-error');
            valid = false;
        }

        if (!valid) { e.preventDefault(); return; }

        // Show the loading spinner and disable the button while the form submits.
        document.getElementById('btnRegistrarTexto').style.display   = 'none';
        document.getElementById('btnRegistrarCargando').style.display = 'inline';
        document.getElementById('btnRegistrar').disabled              = true;
    });
})();

// ----------------------------------------------------------------
// PAGE: VERIFICACIÓN DE EMAIL
// ----------------------------------------------------------------
(function initVerificacion() {
    const codeInput     = document.getElementById('verification_code');
    const formVerificar = document.getElementById('formVerificar');
    if (!codeInput || !formVerificar) return;

    // Allow only digits and cap at 6 characters.
    codeInput.addEventListener('input', function () {
        this.value = this.value.replace(/\D/g, '').slice(0, 6);
    });

    formVerificar.addEventListener('submit', function (e) {
        const code = codeInput.value.trim();
        const err  = document.getElementById('code-error');

        if (code.length !== 6) {
            err.style.display           = 'block';
            codeInput.style.borderColor = '#e74c3c';
            e.preventDefault();
            return;
        }

        err.style.display           = 'none';
        codeInput.style.borderColor = '#dadce0';

        // Show the loading state while the form submits.
        document.getElementById('btnVerificarTexto').style.display   = 'none';
        document.getElementById('btnVerificarCargando').style.display = 'inline';
        document.getElementById('btnVerificar').disabled              = true;
    });
})();

// ----------------------------------------------------------------
// PAGE: RECUPERACIÓN DE CONTRASEÑA
// ----------------------------------------------------------------
(function initRecovery() {
    const formRecovery = document.getElementById('formRecovery');
    if (!formRecovery) return;

    // Toggle visibility de las contraseñas
    const togglePassBtn    = document.getElementById('toggle-recovery-password');
    const toggleConfirmBtn = document.getElementById('toggle-recovery-confirm');
    if (togglePassBtn) {
        togglePassBtn.addEventListener('click', function () {
            togglePass('recovery-password', 'eye-recovery-password');
        });
    }
    if (toggleConfirmBtn) {
        toggleConfirmBtn.addEventListener('click', function () {
            togglePass('recovery-password-confirm', 'eye-recovery-confirm');
        });
    }

    // Validación del correo (solo @gmail.com)
    const recEmailInput = document.getElementById('recovery-email');
    if (recEmailInput) {
        recEmailInput.addEventListener('input', function () {
            const val = this.value.trim().toLowerCase();
            if (val === '') {
                clearMsg('msg-recovery-email');
                setInputState(this, null);
                return;
            }
            if (!val.endsWith('@gmail.com')) {
                showMsg('msg-recovery-email', 'error', 'Solo se aceptan correos @gmail.com.');
                setInputState(this, 'input-error');
            } else {
                showMsg('msg-recovery-email', 'success', 'Correo válido.');
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

    // Indicador de longitud de la contraseña
    const recPassInput = document.getElementById('recovery-password');
    if (recPassInput) {
        recPassInput.addEventListener('input', function () {
            const v = this.value;
            if (v.length === 0) {
                clearMsg('msg-recovery-password');
                setInputState(this, null);
            } else if (v.length < 8) {
                showMsg('msg-recovery-password', 'error', `Mínimo 8 caracteres (${v.length}/8).`);
                setInputState(this, 'input-error');
            } else {
                showMsg('msg-recovery-password', 'success', 'Longitud correcta.');
                setInputState(this, 'input-ok');
            }
            checkRecoveryMatch();
        });
    }

    // Función que compara las dos contraseñas
    function checkRecoveryMatch() {
        const passEl    = document.getElementById('recovery-password');
        const confirmEl = document.getElementById('recovery-password-confirm');
        if (!passEl || !confirmEl) return;
        const p  = passEl.value;
        const pc = confirmEl.value;
        if (pc.length === 0) {
            clearMsg('msg-recovery-confirm');
            setInputState(confirmEl, null);
            return;
        }
        if (p !== pc) {
            showMsg('msg-recovery-confirm', 'error', 'Las contraseñas no coinciden.');
            setInputState(confirmEl, 'input-error');
        } else {
            showMsg('msg-recovery-confirm', 'success', 'Las contraseñas coinciden.');
            setInputState(confirmEl, 'input-ok');
        }
    }

    const recConfirmInput = document.getElementById('recovery-password-confirm');
    if (recConfirmInput) {
        recConfirmInput.addEventListener('input', checkRecoveryMatch);
    }

    // Validación final al enviar el formulario
    formRecovery.addEventListener('submit', function (e) {
        let valid = true;

        const emailVal = recEmailInput ? recEmailInput.value.trim().toLowerCase() : '';
        if (!emailVal) {
            showMsg('msg-recovery-email', 'error', 'El correo Gmail es obligatorio.');
            if (recEmailInput) setInputState(recEmailInput, 'input-error');
            valid = false;
        } else if (!emailVal.endsWith('@gmail.com')) {
            showMsg('msg-recovery-email', 'error', 'Solo se aceptan correos @gmail.com.');
            if (recEmailInput) setInputState(recEmailInput, 'input-error');
            valid = false;
        }

        const passVal = recPassInput ? recPassInput.value : '';
        if (passVal.length === 0) {
            showMsg('msg-recovery-password', 'error', 'La contraseña es obligatoria.');
            if (recPassInput) setInputState(recPassInput, 'input-error');
            valid = false;
        } else if (passVal.length < 8) {
            showMsg('msg-recovery-password', 'error', 'Mínimo 8 caracteres.');
            if (recPassInput) setInputState(recPassInput, 'input-error');
            valid = false;
        }

        const confVal = recConfirmInput ? recConfirmInput.value : '';
        if (confVal.length === 0) {
            showMsg('msg-recovery-confirm', 'error', 'Debes confirmar la contraseña.');
            if (recConfirmInput) setInputState(recConfirmInput, 'input-error');
            valid = false;
        } else if (passVal !== confVal) {
            showMsg('msg-recovery-confirm', 'error', 'Las contraseñas no coinciden.');
            if (recConfirmInput) setInputState(recConfirmInput, 'input-error');
            valid = false;
        }

        if (!valid) {
            e.preventDefault();
            return;
        }

        // Muestra el indicador de carga
        const btn         = document.getElementById('btnRecovery');
        const btnTexto    = document.getElementById('btnRecoveryTexto');
        const btnCargando = document.getElementById('btnRecoveryCargando');
        if (btn)         btn.disabled = true;
        if (btnTexto)    btnTexto.style.display = 'none';
        if (btnCargando) btnCargando.style.display = 'inline';
    });
})();

// ----------------------------------------------------------------
// GENERAL INITIALIZATION (DOMContentLoaded)
// ----------------------------------------------------------------
document.addEventListener('DOMContentLoaded', function () {

    // Seed the navbar cart badge from the server-rendered data attribute.
    var cartLinkEl  = document.getElementById('cart-link');
    var cartGuestEl = document.getElementById('cart-guest');
    var cartRef     = cartLinkEl || cartGuestEl;
    if (cartRef) {
        updateCartCount(parseInt(cartRef.getAttribute('data-cart-count') || '0', 10));
    }

    // Inform unauthenticated users when they attempt to open the cart.
    if (cartGuestEl) {
        cartGuestEl.addEventListener('click', function () {
            Swal.fire({ icon: 'info', title: 'Inicia sesión', text: 'Debes iniciar sesión para ver tu carrito.', confirmButtonText: 'Entendido' });
        });
    }

    // Clone the trigger to remove any stale event listeners before re-attaching.
    var userMenuTrigger = document.getElementById('user-menu-trigger');
    if (userMenuTrigger) {
        userMenuTrigger.replaceWith(userMenuTrigger.cloneNode(true));
        userMenuTrigger = document.getElementById('user-menu-trigger');
        userMenuTrigger.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            toggleUserDropdown();
        });
    }

    // Close the user dropdown when clicking outside the menu container.
    document.addEventListener('click', function (e) {
        var wrap = document.getElementById('user-menu');
        if (wrap && !wrap.contains(e.target)) setUserMenuOpen(false);
    });

    // Login modal wiring.
    var loginModalTrigger = document.getElementById('login-modal-trigger');
    if (loginModalTrigger) {
        loginModalTrigger.addEventListener('click', function () { window.location.href = '/login'; });
    }
    var closeLoginModalBtn = document.getElementById('close-login-modal');
    if (closeLoginModalBtn) closeLoginModalBtn.addEventListener('click', closeLoginModal);
    var loginModalOverlay = document.getElementById('login-modal-overlay');
    if (loginModalOverlay) loginModalOverlay.addEventListener('click', closeLoginModal);

    // Password visibility toggle on the login page.
    var togglePasswordBtn = document.getElementById('toggle-password');
    if (togglePasswordBtn) {
        togglePasswordBtn.addEventListener('click', function () {
            togglePass('login-password', 'eye-icon');
        });
    }

    // AJAX login form: handles CSRF expiry (419) and redirects on success.
    var publicLoginForm = document.getElementById('public-login-form');
    if (publicLoginForm) {
        publicLoginForm.addEventListener('submit', function (e) {
            e.preventDefault();

            var csrfToken = getCsrfToken();
            if (!csrfToken) { window.location.href = '/login?session_expired=1'; return; }

            var formData  = new FormData(this);
            var submitBtn = document.getElementById('login-submit-btn');

            // Show a loading state while the request is in-flight.
            if (submitBtn) {
                submitBtn.disabled = true;
                var icon       = submitBtn.querySelector('i');
                var normalSpan = submitBtn.querySelector('span');
                if (icon)       icon.className         = 'fas fa-spinner fa-spin';
                if (normalSpan) normalSpan.textContent = 'Iniciando sesión...';
            }

            fetch('/login', {
                method: 'POST',
                body: formData,
                credentials: 'same-origin',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'X-Requested-With': 'XMLHttpRequest' }
            })
                .then(function (response) {
                    // Expired CSRF token: redirect to refresh the session.
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
                        Swal.fire({
                            icon: 'success', title: '¡Bienvenido!',
                            text: data.message || 'Inicio de sesión exitoso',
                            timer: 1500, showConfirmButton: false
                        }).then(function () { window.location.href = data.redirect || '/'; });
                    } else {
                        // Offer a shortcut to registration on failed login.
                        Swal.fire({
                            icon: 'error', title: 'Error',
                            html: (data.message || 'Error al iniciar sesión') +
                                '<hr style="margin:12px 0">' +
                                '<p style="font-size:0.9rem;margin:0">¿Tienes una cuenta registrada? ¿O deseas registrarte?</p>',
                            showCancelButton: true,
                            confirmButtonText: 'Ir a Registro',
                            cancelButtonText: 'Cancelar',
                            confirmButtonColor: '#2d7a2d',
                            cancelButtonColor: '#6c757d'
                        }).then(function (result) {
                            if (result.isConfirmed) window.location.href = '/register';
                        });
                        if (submitBtn) {
                            submitBtn.disabled = false;
                            var icon       = submitBtn.querySelector('i');
                            var normalSpan = submitBtn.querySelector('span');
                            if (icon)       icon.className         = 'fas fa-sign-in-alt';
                            if (normalSpan) normalSpan.textContent = 'Iniciar Sesión';
                        }
                    }
                })
                .catch(function (err) {
                    if (err === 'csrf' || err === 'parse') return;
                    console.error('Login error:', err);
                    Swal.fire({ icon: 'error', title: 'Error', text: 'Ocurrió un error al iniciar sesión' });
                    if (submitBtn) submitBtn.disabled = false;
                });
        });
    }

    // Delegated: open the quantity modal when an add-to-cart button is clicked,
    // falling back to a direct add if no modal is present.
    document.addEventListener('click', function (e) {
        var addBtn = e.target.closest('.add-to-cart-btn');
        if (addBtn) {
            var modal = document.getElementById('add-to-cart-modal');
            modal ? openAddToCartModal(addBtn) : addToCart(addBtn.dataset.productId, 1);
            return;
        }
        // Redirect guests to the login page.
        if (e.target.closest('.guest-add-btn')) { window.location.href = '/login'; return; }
        // Dismiss any modal by clicking its backdrop.
        if (e.target.classList.contains('modal') && e.target.classList.contains('active')) {
            e.target.classList.remove('active');
        }
    });

    // Confirm button inside the add-to-cart modal.
    var confirmAddBtn = document.getElementById('confirm-add-to-cart');
    if (confirmAddBtn) {
        confirmAddBtn.addEventListener('click', function () {
            var qtyEl    = document.getElementById('cart-quantity');
            var quantity = parseInt(qtyEl ? qtyEl.value : '1', 10);
            if (quantity < 1) { Swal.fire('Error', 'La cantidad debe ser mayor a 0', 'error'); return; }
            addToCart(currentProductId, quantity);
        });
    }
    var cancelAddBtn = document.getElementById('cancel-add-to-cart');
    if (cancelAddBtn) cancelAddBtn.addEventListener('click', function () { closeModal('add-to-cart-modal'); });
    var closeAddBtn = document.getElementById('close-add-to-cart-modal');
    if (closeAddBtn) closeAddBtn.addEventListener('click', function () { closeModal('add-to-cart-modal'); });

    // Delegated: confirm before removing a single cart item.
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
            icon: 'warning', showCancelButton: true,
            confirmButtonText: 'Sí, eliminar', cancelButtonText: 'Cancelar'
        }).then(function (result) {
            if (!result.isConfirmed) return;
            fetch('/cart/remove/' + itemId, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': getCsrfToken(), 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            })
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    if (!data.success) { Swal.fire({ icon: 'error', title: 'Error', text: data.message || 'No se pudo eliminar' }); return; }
                    cartItem.remove();
                    Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Producto eliminado del carrito', showConfirmButton: false, timer: 2500, timerProgressBar: true });
                    var totalFormatted = (data.cart_total != null) ? ('₡' + Number(data.cart_total).toLocaleString('es-CR')) : '₡0';
                    var subtotalEl = document.getElementById('cart-subtotal');
                    var totalEl    = document.getElementById('cart-total-amount');
                    if (subtotalEl) subtotalEl.textContent = totalFormatted;
                    if (totalEl)    totalEl.textContent    = totalFormatted;
                    updateCartCount(data.cart_count || 0);
                    // Show empty state if no items remain.
                    if (document.querySelectorAll('.cart-item').length === 0) showCartEmptyState();
                })
                .catch(function (err) {
                    console.error('Error removing cart item:', err);
                    Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo eliminar el producto' });
                });
        });
    });

    // Delegated: confirm before clearing all cart items at once.
    document.addEventListener('click', function (e) {
        if (!e.target.closest('#btn-clear-cart')) return;
        Swal.fire({
            title: '¿Vaciar carrito?', text: 'Se eliminarán todos los productos del carrito.',
            icon: 'warning', showCancelButton: true,
            confirmButtonText: 'Sí, vaciar', cancelButtonText: 'Cancelar'
        }).then(function (result) {
            if (!result.isConfirmed) return;
            fetch('/cart/clear', {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': getCsrfToken(), 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            })
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    if (data.success) {
                        updateCartCount(0);
                        showCartEmptyState();
                        Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Carrito vaciado correctamente', showConfirmButton: false, timer: 2500, timerProgressBar: true });
                    } else {
                        Swal.fire({ icon: 'error', title: 'Error', text: data.message || 'No se pudo vaciar el carrito' });
                    }
                })
                .catch(function () { Swal.fire({ icon: 'error', title: 'Error', text: 'Ocurrió un error al vaciar el carrito' }); });
        });
    });

    // Clamp manual quantity input to [1, stock] on change and sync with the server.
    document.querySelectorAll('.quantity-input').forEach(function (input) {
        input.addEventListener('change', function () {
            var productId = this.dataset.productId;
            var quantity  = parseInt(this.value, 10);
            var max       = parseInt(this.max, 10);
            if (quantity < 1)        { this.value = 1;   updateCartQuantity(productId, 1); }
            else if (quantity > max) { this.value = max; Swal.fire('Aviso', 'La cantidad no puede exceder el stock disponible', 'warning'); updateCartQuantity(productId, max); }
            else                     { updateCartQuantity(productId, quantity); }
        });
    });

    // Increment / decrement stepper buttons on the cart page.
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

    // Product detail page: standalone quantity stepper (not tied to the cart page).
    var productQtyInput = document.getElementById('product-quantity');
    var productQty      = 1;
    if (productQtyInput) {
        var maxQty      = parseInt(productQtyInput.max, 10) || 999;
        var decreaseBtn = document.getElementById('decrease-qty');
        var increaseBtn = document.getElementById('increase-qty');
        if (decreaseBtn) decreaseBtn.addEventListener('click', function () { if (productQty > 1) { productQty--; productQtyInput.value = productQty; } });
        if (increaseBtn) increaseBtn.addEventListener('click', function () { if (productQty < maxQty) { productQty++; productQtyInput.value = productQty; } });
        // Keep the local productQty variable in sync with direct text input.
        productQtyInput.addEventListener('change', function () {
            var value = parseInt(this.value, 10);
            if (value < 1)           { this.value = 1;      productQty = 1; }
            else if (value > maxQty) { this.value = maxQty; productQty = maxQty; }
            else                     { productQty = value; }
        });
        // Wire the detail-page add-to-cart button to the local quantity variable.
        var detailAddBtn = document.querySelector('.product-detail-actions .add-to-cart-btn');
        if (detailAddBtn) detailAddBtn.addEventListener('click', function () { addToCart(this.dataset.productId, productQty); });
    }

    // Checkout: confirm intent, POST the order, disable the button while in-flight.
    var proceedBtn = document.getElementById('proceed-checkout');
    if (proceedBtn) {
        proceedBtn.addEventListener('click', function () {
            Swal.fire({
                title: '¿Confirmar compra?', text: 'Se enviará tu pedido para retiro en tienda.',
                icon: 'question', showCancelButton: true,
                confirmButtonText: 'Sí, confirmar', cancelButtonText: 'Cancelar'
            }).then(function (result) {
                if (!result.isConfirmed) return;
                proceedBtn.disabled  = true;
                proceedBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Procesando...';
                fetch('/cart/checkout', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': getCsrfToken(), 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                })
                    .then(function (res) { return res.json(); })
                    .then(function (data) {
                        if (!data.success) {
                            Swal.fire({ icon: 'error', title: 'Error', text: data.message || 'No se pudo procesar el pedido' });
                            proceedBtn.disabled  = false;
                            proceedBtn.innerHTML = '<i class="fas fa-check"></i> Confirmar Compra';
                            return;
                        }
                        updateCartCount(0);
                        showCartEmptyState();
                        Swal.fire({
                            icon: 'success',
                            text: 'Su pedido fue enviado con éxito. Tiene un lapso de 3 días para retirarlo en nuestro local. El pago se realiza de forma presencial mediante SINPE, efectivo o tarjeta.',
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

    // Close dropdowns and modals on Escape key press.
    document.addEventListener('keydown', function (e) {
        if (e.key !== 'Escape') return;
        closeUserDropdown();
        closeLoginModal();
        document.querySelectorAll('.modal.active').forEach(function (modal) { modal.classList.remove('active'); });
    });

    // Catalog price filter: disable submit when min > max.
    (function initCatalogPriceFilter() {
        var form      = document.getElementById('filter-form');
        if (!form) return;
        var minInput  = document.getElementById('min_price');
        var maxInput  = document.getElementById('max_price');
        var submitBtn = document.getElementById('filter-submit-btn');

        function checkPriceRange() {
            if (!minInput || !maxInput || !submitBtn) return;
            var min     = parseFloat(minInput.value);
            var max     = parseFloat(maxInput.value);
            var invalid = minInput.value.trim() !== '' && maxInput.value.trim() !== '' && !isNaN(min) && !isNaN(max) && min > max;
            submitBtn.disabled = invalid;
            invalid ? submitBtn.setAttribute('title', 'El precio mínimo debe ser menor o igual al precio máximo.') : submitBtn.removeAttribute('title');
        }

        if (minInput) { minInput.addEventListener('input', checkPriceRange); minInput.addEventListener('change', checkPriceRange); }
        if (maxInput) { maxInput.addEventListener('input', checkPriceRange); maxInput.addEventListener('change', checkPriceRange); }
        checkPriceRange();
    })();

    // Catalog pagination: handles disabled links and the "go to page" input.
    (function initCatalogPagination() {
        var wrapper = document.querySelector('.pagination-wrapper .pagination');
        if (!wrapper) return;
        var goInput = wrapper.querySelector('#goToPageInput');
        var goBtn   = wrapper.querySelector('#goToPageBtn');

        // Prevent navigation on aria-disabled pagination links.
        wrapper.querySelectorAll('.button[aria-label]').forEach(function (a) {
            if (a.getAttribute('aria-disabled') === 'true') {
                a.addEventListener('click', function (e) { e.preventDefault(); });
                a.classList.add('is-disabled');
            }
        });

        /** Navigates to the requested page number, clamped to [1, lastPage]. */
        function goToPage() {
            var totalSpan = wrapper.querySelector('.button.button-primary');
            if (!totalSpan) return;
            var parts    = totalSpan.textContent.trim().split('/');
            var lastPage = Math.max(1, parseInt((parts[1] || '1').trim(), 10));
            var target   = parseInt((goInput && goInput.value) ? goInput.value.trim() : '1', 10);
            if (isNaN(target) || target < 1) target = 1;
            if (target > lastPage) target = lastPage;
            var url = new URL(window.location.href);
            url.searchParams.set('page', String(target));
            window.location.assign(url.toString());
        }

        if (goBtn)   goBtn.addEventListener('click', goToPage);
        if (goInput) goInput.addEventListener('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); goToPage(); } });
    })();

    // Profile page: snapshot original values and wire the password strength meter.
    if (document.getElementById('formPerfil')) {
        profileSaveOriginals();

        var passInputProfile = document.getElementById('new_password');
        if (passInputProfile) {
            passInputProfile.addEventListener('input', function () { updateStrength(this.value); });
        }

        // Surface server-side flash messages as profile alerts.
        var flash = window.__profileFlash || {};
        if (flash.profile_updated)  showProfileAlert('Cambios guardados correctamente.', 'success');
        if (flash.password_updated) showProfileAlert('Contraseña actualizada correctamente.', 'success');
        if (flash.password_defined) showProfileAlert('Contraseña definida. Ahora puedes iniciar sesión con correo y contraseña.', 'success');
    }

    // Password change form: confirm before sending; distinguish Google-only vs local accounts.
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

}); // end DOMContentLoaded

// ----------------------------------------------------------------
// GLOBAL EXPORTS (called from onclick attributes in Blade templates)
// ----------------------------------------------------------------
window.addToCart         = addToCart;
window.updateCartCount   = updateCartCount;
window.togglePass        = togglePass;
window.togglePassword    = togglePassword;
window.enableEdit        = enableEdit;
window.cancelEdit        = cancelEdit;
window.submitProfile     = submitProfile;
window.showPasswordForm  = showPasswordForm;
window.hidePasswordForm  = hidePasswordForm;
window.showProfileAlert  = showProfileAlert;
window.closeProfileAlert = closeProfileAlert;